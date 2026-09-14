# Non-Functional Requirements

> These are **budgets to design against**, set before measurement exists. §7 defines when and how they are revised. They are engineering targets, not contractual SLAs — no external commitment is made from this document.

## 1. Interactive Latency

Server-side processing time, excluding network transit, measured at p95 under expected load.

| Class | Examples | p95 | p99 |
|---|---|---|---|
| Read, simple | `GET /me`, `GET /automations` | 200 ms | 500 ms |
| Read, composed | dashboard, calendar range, match list | 400 ms | 900 ms |
| Write, simple | task status, notes, preferences | 300 ms | 700 ms |
| Operation start (`202`) | any AI operation kick-off | 250 ms | 600 ms |
| Operation poll | `GET /operations/{id}` | 150 ms | 400 ms |

**Operation start must stay fast.** It enqueues and returns; it never waits on the AI provider. A `202` slower than its budget means work is being done synchronously that should not be.

Frontend targets: Largest Contentful Paint under 2.5 s and Interaction to Next Paint under 200 ms, on a mid-tier device over 4G.

## 2. Asynchronous Completion Targets

Time from enqueue to terminal state, p95. Every job class in `ADR-002` has a target.

| Job class | Target p95 | Ceiling | Notes |
|---|---|---|---|
| Profile research | 90 s | 5 min | Onboarding's first-value moment; the stage UI must stay honest throughout |
| Profile analysis | 45 s | 3 min | |
| Weekly plan generation | 60 s | 3 min | Scheduled; latency is not user-blocking |
| Content strategy generation | 60 s | 3 min | |
| Content idea generation | 30 s | 2 min | |
| Post draft generation | 30 s | 2 min | User is usually waiting — the tightest generation budget |
| Job discovery | 5 min | 20 min | Per source, per user |
| Job matching | 20 s per job | 2 min | Batch capped per run |
| Publishing | 30 s from scheduled time | 5 min | **Timeliness, not throughput** — see §3 |
| Analytics retrieval | 2 min | 10 min | Only where metrics are available |
| Digest generation | 2 min | 10 min | Must land within the user's configured local hour |
| Email sending | 30 s | 5 min | Handoff to provider |
| Profile refresh | 90 s | 5 min | Same cost profile as research |
| Data export | 5 min | 30 min | DSR clock in `10` is the binding constraint |
| Account erasure | 15 min | Retention window | Deletion markers take effect immediately regardless |

### Queue health

| Metric | Target | Alert |
|---|---|---|
| Queue wait before start | < 30 s p95 | > 5 min sustained 10 min |
| Publishing queue wait | < 10 s p95 | > 60 s — publishing is time-critical |
| Failed-job rate | < 1% of runs | > 5% over 15 min |
| Dead-lettered jobs | 0 sustained | any job dead-lettered |

## 3. Publishing Timeliness

Scheduled publishing is judged on **how close to the intended time it fires**, not on throughput.

- A post publishes within **60 seconds** of its scheduled instant at p95, and within 5 minutes at p99.
- A post never publishes **early**.
- A post publishes **at most once**, enforced by idempotency key (`content/scheduling-and-publishing`).
- Manual-path notifications are delivered on the same budget, since the user's window to act starts then.

## 4. Availability and Durability

| Component | Target | Degradation |
|---|---|---|
| API | 99.5% monthly | — |
| Database | 99.9% | Hard dependency; no read-only mode in MVP |
| Queue workers | 99.5% | Backlog absorbs short outages; publishing timeliness degrades first |
| AI provider | Not controlled | Dependent features fail cleanly with a retry action; manual entry, manual publishing, and the tracker stay usable (`platform/ai-runtime`) |
| Email provider | Not controlled | In-app notifications retained; delivery retried |
| Job source | Not controlled | Discovery reports unavailable; manual analysis unaffected (`jobs/job-discovery`) |

**No single third-party outage may take the product down.** This is the availability consequence of `ADR-006` and is the property most worth protecting.

Backups: daily full, point-in-time recovery to 7 days. Restore is rehearsed before launch and at least quarterly.

## 5. Capacity Assumptions

Sizing basis for MVP 1. Revise when actuals land.

| Assumption | Value |
|---|---|
| Registered accounts, 6 months | 5,000 |
| Weekly active share | 40% (2,000) |
| Plans generated | 1 per active user per week |
| Posts generated | 3 per active user per week |
| Jobs collected per user per day | ≤ 200 pre-filter |
| Jobs matched per user per day | ≤ 20 post-filter (the per-run analysis cap) |
| Peak queue concurrency | 50 workers |
| Database size, 12 months | < 100 GB |

The dominant scaling axis is **AI operations per active user per week**, not request volume. Capacity planning tracks that number.

## 6. AI Cost Ceilings

Cost is the primary scaling risk, so it gets hard limits at three levels.

| Level | Control | Owner |
|---|---|---|
| Per operation | Input and output token ceilings per capability | `platform/ai-runtime` |
| Per account | Plan quotas on metered operations | `billing/plans-and-entitlements`, values in `11` |
| Aggregate | Daily and monthly spend thresholds | Operations |

**Aggregate threshold behavior.** Crossing the daily threshold alerts operators. Crossing the hard ceiling suspends **non-essential automated generation** — scheduled ideas, strategy refresh, discovery, matching — while leaving **user-initiated work** unaffected. A user who clicks "generate" still gets output; the agent stops working in the background until the ceiling resets or is raised.

Cost efficiency targets:

- Research result reuse (cache hit rate on repeated URLs): **> 60%**
- Duplicate job analyses avoided: **> 90%**
- Share of calls on the cheap tier: **> 40%** (transformations must not use the reasoning tier)
- Blended AI cost per active user per month: **under the lowest paid plan's gross margin floor** — the number that decides whether the business model works

## 7. Revision Triggers

Every number here is provisional. Revise when any of these fire, and record the revision in this document with its date:

1. **Two weeks of production data exist.** Replace every estimate with the measured p95 plus headroom. This is the primary trigger.
2. A target is **missed for 3 consecutive days** — either fix the system or correct the target; do not leave it silently violated.
3. A target is met by **more than 3×** for a month — it was set too loosely and is not constraining design.
4. **Capacity assumptions are exceeded by 50%**, which invalidates the sizing basis.
5. **Blended AI cost per active user exceeds the margin floor**, which is a business-model signal, not just an engineering one.
6. A new AI capability ships — it needs its own completion target and token ceiling before launch.

## 8. Measurement

These are unmeasurable unless instrumented. `13-observability-and-runbooks.md` defines the log schema, metrics, and alerts that make each number above observable. A target with no corresponding metric is not a target.
