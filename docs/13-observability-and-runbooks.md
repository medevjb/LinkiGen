# Observability & Runbooks

> Every budget in `09-non-functional-requirements.md` is unmeasurable without instrumentation. A target with no metric is not a target. This document defines the log schema, the metrics, the alerts, and what to do when one fires.

## 1. Structured Log Schema

Every log line is JSON with a common envelope:

```json
{
  "timestamp": "2026-09-14T10:00:00.123Z",
  "level": "info",
  "message": "operation.completed",
  "request_id": "req_01J...",
  "trace_id": "trc_01J...",
  "user_id": "usr_01J...",
  "component": "jobs.matching",
  "event": "operation.completed",
  "duration_ms": 1840,
  "context": { "operation_id": "op_01J...", "job_id": "job_01J..." }
}
```

**The classification rule from `10` §3 is binding here.** Logs identify records by identifier only. No profile content, no post bodies, no employer names, no email addresses beyond what diagnosis requires. A log line naming an employer is a defect, not a convenience.

`trace_id` propagates from the inbound request through every queued job it spawns, so a slow profile import is traceable end to end.

### Required events

| Event | Emitted by |
|---|---|
| `http.request` | Every API request — method, route, status, duration |
| `operation.started` / `.completed` / `.failed` / `.partial` | Long-running operations |
| `job.enqueued` / `.started` / `.completed` / `.failed` / `.retrying` / `.dead_lettered` | Queue workers |
| `ai.call.started` / `.completed` / `.validation_failed` | AI runtime |
| `automation.run` | Every automation, including skips with reason |
| `publish.attempt` | Every publishing attempt, success or failure |
| `quota.check` / `quota.exceeded` / `quota.warning` | Billing |
| `auth.failure` / `auth.throttled` | Identity |
| `retention.sweep` | Privacy |
| `audit.*` | Any action taken as the user, any operator access |

## 2. AI Run Tracing

Every AI call writes an `AIRun` (`docs/06` §7.11). These fields are what make an AI failure diagnosable without re-running the call:

`agent_key` · `agent_version` · `prompt_version` · `schema_version` · `model` · `status` · `validation_error` · `token_usage` · `cost_cents` · `latency_ms` · input and output references

Three views this enables:

- **Quality regression** — filter by `agent_version` to attribute a drop to a specific change.
- **Cost attribution** — sum `cost_cents` by `agent_key` to find what is actually expensive.
- **Validation failures** — `validation_failed` runs carry the offending output, which is the fastest route to a schema or prompt defect.

`AIRun` inputs and outputs are personal data under the retention schedule in `10` §5. Dashboards read metadata; they do not render prompt content.

## 3. Metrics

### Golden signals

| Metric | Dimensions |
|---|---|
| Request rate, error rate, duration | route, status |
| Queue depth, wait time, throughput | queue, job class |
| Job failure rate, retry rate, dead-letter count | job class |
| Operation completion time | operation kind |

### Product-critical

| Metric | Why it matters |
|---|---|
| **Publish timeliness** — delta between scheduled and actual | The one budget with reputational consequence (`09` §3) |
| **Publish failure rate** | Directly user-visible |
| Profile research success / partial / failure rate | First-value moment; the strongest onboarding signal |
| Schema validation failure rate by `agent_key` | Leading indicator of AI quality regression |
| AI spend — hourly, daily, by capability | Primary scaling risk |
| Quota exceptions by entitlement | Product signal: are the limits wrong? |
| Automation suspension count | Users silently losing their agent |
| Email bounce and suppression rate | Action-required notifications not arriving |

### Cost efficiency (`09` §6)

Research cache hit rate, duplicate analyses avoided, cheap-tier call share, blended cost per active user.

## 4. Alerts

| Alert | Condition | Severity |
|---|---|---|
| API error rate | 5xx > 2% over 5 min | **Page** |
| Database unreachable | any | **Page** |
| Publishing queue wait | > 60 s sustained 5 min | **Page** |
| Publish failure rate | > 10% over 15 min | **Page** |
| AI spend hard ceiling | daily ceiling reached | **Page** |
| Job dead-lettered | any | **Page** |
| Queue wait | > 5 min sustained 10 min | Warn |
| Job failure rate | > 5% over 15 min | Warn |
| Schema validation failures | > 1% for any `agent_key` over 1 h | Warn |
| AI spend daily threshold | 80% of ceiling | Warn |
| Profile research failure rate | > 20% over 1 h | Warn |
| Automation suspensions | > 5 accounts in 1 h | Warn |
| Email bounce rate | > 5% over 1 h | Warn |
| Retention sweep | did not run in 48 h | Warn |

**Page** means wake someone. Everything else waits for business hours. The page list is deliberately short — it is user-facing damage or money.

## 5. Runbooks

### 5.1 AI provider outage

**Detect:** `ai.call.failed` rate spikes; operations fail or stall; provider status page.

**Immediate:**
1. Confirm it is the provider, not our credentials or quota — check for `401`/`429` versus `5xx`/timeout.
2. Verify degradation is behaving: dependent features should fail cleanly with a retry action; **manual entry, manual publishing, and the tracker must still work** (`platform/ai-runtime`). If the whole product is down, that is a separate and more serious defect.
3. If the outage is sustained, pause non-essential automated generation to stop burning retries — scheduled ideas, strategy refresh, discovery, matching. **Leave user-initiated work enabled.**
4. Post status if user-visible.

**Recovery:** Resume automations. Failed scheduled runs recover at their next occurrence — do **not** retroactively execute missed runs (`automation/automation-control`). Check for accounts suspended by consecutive failures during the outage and clear those suspensions, since the failures were not theirs.

**Follow-up:** Suspension thresholds should not punish users for a provider outage. If they did, fix the threshold logic.

---

### 5.2 Queue backlog

**Detect:** queue depth climbing; wait time over budget; publishing timeliness degrading.

**Immediate:**
1. Identify the job class responsible — backlog is usually one class, not all.
2. Check for a poison job: high retry count on repeated identifiers.
3. **Protect publishing first.** It is the only time-critical class. If workers are shared, prioritize or isolate the publishing queue.
4. Scale workers if the cause is volume rather than a stuck job.

**Recovery:** Drain the backlog. Verify publishing timeliness recovers first. Identify posts that missed their window and decide — with the timeliness rule in mind — whether to publish late or fall back to manual notification.

**Follow-up:** If caused by volume, the capacity assumptions in `09` §5 need revising. If by a poison job, add a dead-letter path for that class.

---

### 5.3 Publish failure spike

**Detect:** publish failure rate alert; `publish.attempt` failures clustering.

**Immediate:**
1. Group failures by `error_code`. The three cases diverge completely:
   - **Authorization expired or revoked** → **stop retrying**. Mark integrations as needing reconnection, notify affected users, fall back to manual (`content/scheduling-and-publishing`). Blind retry here can trip provider rate limits and worsen it.
   - **Rate limited** → back off per the provider's guidance, not the default schedule.
   - **Transient 5xx** → let bounded retry run; monitor.
2. Confirm no post was published twice — check `IdempotencyKey` collisions. Duplicate publication is the worst outcome here and is user-visible on a public profile.
3. Verify affected users can reach the manual path.

**Recovery:** Retry eligible posts. Posts past their window go to manual with a clear explanation.

**Follow-up:** A duplicate publication is a **sev-1 post-incident review** — idempotency failed.

---

### 5.4 AI cost spike

**Detect:** spend threshold alert; cost per active user trending up.

**Immediate:**
1. Break spend down by `agent_key` from `AIRun.cost_cents`. It is almost always one capability.
2. Check the usual four causes in order:
   - **Cache miss regression** — research or job-analysis reuse rate dropped. Most common cause.
   - **Retry storm** — a failing capability retrying at cost.
   - **Model tier regression** — a transformation capability moved to the reasoning tier.
   - **Abuse** — one account driving disproportionate volume; check against fair-use (`11` §7).
3. At the hard ceiling, suspend non-essential automated generation. **User-initiated work stays enabled** (`09` §6).

**Recovery:** Fix the cause. Restore caching or tiering. Contact the account if abuse.

**Follow-up:** If legitimate usage caused it, the quota matrix in `11` §3 is mispriced — that is a business decision, not an incident fix.

---

### 5.5 Email delivery failure

**Detect:** bounce or suppression rate alert; provider dashboard.

**Immediate:**
1. Distinguish provider-wide failure from per-address bounces.
2. Confirm **in-app notifications are still being created** — action-required information must not be lost when email fails (`notifications/notifications-and-digests`).
3. On provider failure, queue rather than drop.

**Recovery:** Resend failed action-required notifications. Do **not** resend digests — they are time-boxed and stale digests are noise.

**Follow-up:** Rising hard bounces usually mean an address-hygiene problem, not a delivery problem.

## 6. Dashboards

| Dashboard | Contents |
|---|---|
| **Service health** | Golden signals, queue depth, worker health |
| **Publishing** | Timeliness distribution, failure rate by code, integration states |
| **AI quality** | Schema validation by `agent_key` and `agent_version`, evaluation trend |
| **AI cost** | Spend by capability, cache hit rates, cost per active user |
| **Onboarding funnel** | Research success/partial/failure, confirmation rate, time to first value |
| **Automation health** | Runs by outcome, suspensions, approvals pending and expired |

## 7. Retention and Access

Operational logs: 30 days. Metrics: 13 months (year-over-year comparison). Audit log: 24 months. Per `10` §5.

Dashboard access is authenticated. Any operator view of an individual user's data is itself audited (`platform/data-privacy-and-retention`).
