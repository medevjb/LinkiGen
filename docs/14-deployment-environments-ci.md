# Deployment, Environments & CI

> Two deployables (`ADR-001`): the Laravel API with its queue workers and scheduler, and the Next.js client. They release independently, which makes the API contract in `08` the compatibility boundary.

## 1. Environments

| Environment | Purpose | Data | AI provider | External providers |
|---|---|---|---|---|
| **Local** | Development | Synthetic seed | Recorded fixtures | All stubbed |
| **CI** | Automated verification | Ephemeral, per-run | Recorded fixtures — **never live** | All stubbed |
| **Staging** | Pre-release verification | Synthetic only, **never production data** | Live, on a separate key with its own spend cap | Sandbox where available; `PublishingProvider` and `JobSource` absent by default |
| **Production** | Users | Real | Live | Real |

**Staging deliberately runs with no publishing provider and no job source.** That is the MVP's actual configuration (`ADR-006`), so staging verifies the degraded paths are the normal paths — not an afterthought tested once.

Production data is never copied to any other environment. Reproducing a user-specific bug uses a synthetic fixture built from the report, not a database copy (`10` §3).

## 2. Components

| Component | Scaling | Notes |
|---|---|---|
| API | Horizontal, stateless | Sessions in Redis, not in-process |
| Queue workers | Horizontal by queue | **Publishing runs on its own queue and its own workers** — it is the only time-critical class (`09` §3) and must not queue behind a research backlog |
| Scheduler | **Exactly one instance** | Two schedulers means duplicate runs |
| Client | Static/edge | Independent releases |

## 3. Configuration and Secrets

- All configuration by environment variable. No environment-specific code branches.
- Secrets in a managed secret store, injected at runtime. Never in the repository, never in images, never in logs (`10` §7).
- `.env.example` lists every required variable with a non-secret placeholder and a comment. It is the deployment contract and is kept current.
- Rotation: quarterly, and immediately on suspected compromise. Rotation is exercised before launch, not first attempted during an incident.
- Separate least-privilege credentials per environment. A staging key must never reach production data.

## 4. Database Migrations

**Migrations run before the new application version starts**, and must be backward-compatible with the currently running version — otherwise the deploy window is an outage window.

Expand-and-contract for anything destructive:

1. **Expand** — add the new column/table, nullable or defaulted. Deploy. Old code ignores it.
2. **Backfill** — as a queued job for large tables, never inline in a migration.
3. **Migrate reads and writes** — deploy code using the new shape while the old shape still exists.
4. **Contract** — drop the old column in a later release, once rollback to the prior version is no longer needed.

Rules:

- No destructive migration in the same release as the code that stops using the column.
- Every migration is tested against a production-sized dataset for lock duration before it ships.
- Long-running index creation uses a concurrent path.
- Migrations are forward-only. **Rollback is a new migration**, not a `down()` run against production.

## 5. CI Pipeline

| Stage | Gate |
|---|---|
| Lint and static analysis | Blocks merge |
| Unit tests | Blocks merge |
| Integration tests (real DB + queue, stubbed AI) | Blocks merge |
| Contract tests (every `08` endpoint) | Blocks merge |
| `openspec validate --strict` | Blocks merge |
| Non-negotiable cases (`12` §2) | Blocks merge |
| Dependency vulnerability scan — critical | Blocks merge |
| Migration lock-duration check | Blocks merge |
| E2E journeys (`12` §3) | Blocks release |
| AI hard gates for changed capabilities (`12` §5.2) | Blocks release |

**AI evaluation gates release, not merge.** Evaluation is slow and costs provider spend; running it per-commit is wasteful and would push people to skip it. It runs nightly and before release, and any prompt, schema, or model change triggers the full suite for that capability with a version increment (`ADR-003`).

## 6. Release

**Default:** rolling deploy with health checks, API first, then client.

Order matters because of the contract boundary:

- **Additive API change** → API first, then client. The client tolerates fields it does not know.
- **Client needs a new endpoint** → API first, verified in production, client after.
- **Breaking API change** → not permitted within `/api/v1`. Version the endpoint and retire the old one after clients have moved.

Feature flags carry anything user-visible that is not ready, and anything touching publishing or automation defaults. Flags are removed once fully rolled out — a permanent flag is untested configuration.

Release checklist:

- [ ] All CI gates green
- [ ] Migrations reviewed for backward compatibility and lock duration
- [ ] Feature flags set for the intended state
- [ ] Runbooks current if failure modes changed (`13` §5)
- [ ] Rollback plan confirmed for this specific release

## 7. Rollback

| Situation | Action |
|---|---|
| Application defect, no schema change | Redeploy the previous version |
| Defect after an expand-phase migration | Redeploy previous version — the additive schema is compatible by construction |
| Defect after a contract-phase migration | **Cannot roll back the schema.** Fix forward. This is why contract is a separate, later release |
| Bad data written by a defect | Restore affected records from point-in-time backup; never restore the whole database over live data without assessing what else would be lost |

**Queue considerations on rollback:** jobs enqueued by the new version may carry a payload the old version cannot read. Drain or quarantine the queue before rolling back, and check for jobs mid-retry. Publishing jobs need particular care — a rollback that re-enqueues them risks duplicate publication, which idempotency keys must prevent (`content/scheduling-and-publishing`).

## 8. Scheduler and Worker Deployment

- The scheduler must be a **singleton**. Enforce with a lock, not by convention.
- Workers drain gracefully: finish the current job, stop accepting new work, then exit. A worker killed mid-publish relies on the idempotency key to avoid a duplicate on retry.
- Deployment must not silently drop a scheduled run. A missed occurrence recovers at its next occurrence; runs are **not** executed retroactively (`automation/automation-control`).

## 9. Launch Readiness

Beyond the security checklist in `10` §9:

- [ ] Staging verified end to end with no publishing provider and no job source
- [ ] Backup restore rehearsed
- [ ] Alerts firing to a real on-call destination, tested with a deliberate trigger
- [ ] Dashboards (`13` §6) populated with real data
- [ ] AI spend caps configured on the production key
- [ ] Rate limits configured per `08` §8
- [ ] Retention sweeps scheduled and verified
- [ ] Scheduler singleton confirmed in production topology
- [ ] Publishing queue isolated from general workers
