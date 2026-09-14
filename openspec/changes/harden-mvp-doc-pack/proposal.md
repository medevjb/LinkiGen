## Why

The `docs/` pack (`00`–`07`) describes a compelling product but is not buildable as written: the backend framework is still "Laravel or Node", there is no API contract, no non-functional requirements, no billing/entitlement model, no test or AI-evaluation strategy, no deployment or privacy specification, and `openspec/specs/` is empty — so no requirement in the pack has testable acceptance criteria. A scenario sweep of the pack also surfaced undefined behavior that will block implementation the first day it is hit: two users claiming the same LinkedIn URL, quota exhaustion mid-generation, an expired publishing integration, subscription downgrade below current usage, account deletion with in-flight automation runs, and timezone handling for scheduled posts.

This change converts the documentation pack from product intent into an implementation-ready specification, so human or AI developers can build MVP 1 without re-deciding architecture or inventing missing behavior.

## What Changes

### Locked decisions (previously open)

- **BREAKING (to `06-system-architecture-data-model.md`)** — Backend stack is locked to **Laravel** (API, Queues, Scheduler) + **PostgreSQL** + **Redis**, with a **Next.js/TypeScript** frontend. The "Laravel or Node.js" fork is removed. ADR-001 and ADR-002 are written, not deferred.
- **BREAKING (to `02-mvp-prd.md` §4.10)** — LinkedIn publishing for MVP 1 is **manual fallback only** (copy → open LinkedIn → mark published). API publishing is designed behind a `PublishingProvider` interface and ships dark; MVP launch has **no dependency** on LinkedIn partner approval. The "Option A / Option B" ambiguity is removed.
- **BREAKING (to `02-mvp-prd.md` §4.11)** — Job discovery for MVP 1 is **manual job analysis (guaranteed path) + one pluggable `JobSource` adapter**. Automated discovery degrades gracefully to "no source configured" rather than being a launch dependency. LinkedIn job scraping stays prohibited.
- Product naming is resolved: working product name **JB AI Career Agent**, repository/codename **LinkiGen**. (Assumption — see Open Questions.)

### Requirements become testable specs

- 16 new capability specs are written under `openspec/specs/`, derived from the pack and gap-filled from the scenario sweep. Each requirement carries `WHEN/THEN` scenarios including the failure, empty, partial-success, permission, and quota paths that `05-screen-requirements-ux-states.md` calls for but never enumerates.

### The `docs/` pack is rewritten and extended

- `00`–`07` are normalized: locked decisions folded in, contradictions resolved, every "should" that is actually mandatory promoted to a requirement with a spec reference.
- Nine implementation documents are added that the pack currently lacks:
  - `08-api-contract.md` — REST surface, auth, pagination, idempotency, error envelope
  - `09-non-functional-requirements.md` — latency budgets, async-job SLAs, AI cost ceilings, availability, capacity
  - `10-security-privacy-compliance.md` — threat model, GDPR/DSR (export, erasure), retention schedule, AI-prompt data minimization
  - `11-billing-entitlements.md` — Free/Pro/Pro+ quota matrix, metering, overage, downgrade and cancellation behavior
  - `12-testing-and-ai-evaluation.md` — test pyramid, the ten evaluation cases from `07` §14 made concrete, factuality and schema-validity gates
  - `13-observability-and-runbooks.md` — structured logging, AI-run tracing, queue monitoring, on-call runbooks for the top failure modes
  - `14-deployment-environments-ci.md` — environments, migrations, secrets, release and rollback
  - `15-analytics-event-plan.md` — event schema backing every metric in `02` §9
  - `16-error-catalog.md` — stable error codes shared by API, queue, and UI states
- `ADR/` directory seeded with ADR-001 … ADR-006.

## Capabilities

### New Capabilities

- `identity/account-access`: Registration, email verification, sign-in, password reset, session handling, account deletion and data export.
- `career/profile-import`: LinkedIn-URL onboarding, AI web research, CV/manual fallback, research failure and partial-result handling, duplicate-URL claim resolution.
- `career/career-profile`: The canonical `CareerProfile` — viewing, editing, confirming, provenance and confidence, scheduled refresh as *proposed changes* rather than silent overwrite.
- `career/goals-and-analysis`: Career goal capture and the goal-relative profile score, category scores, and prioritized recommendations.
- `planning/weekly-plan`: Automatic weekly plan generation, task lifecycle, time budgeting, de-duplication, regeneration.
- `content/content-strategy`: Content pillars, topic distribution, cadence, and idea generation grounded in real profile evidence.
- `content/post-authoring`: Post draft generation, AI edit actions (shorten/expand/tone/hook), unsupported-claim detection, approval.
- `content/scheduling-and-publishing`: Calendar, scheduling with timezone correctness, the manual publish path, the `PublishingProvider` abstraction, and publish-failure recovery.
- `jobs/job-analysis`: Manual job intake by URL or pasted description, parsing, and the match breakdown with recommendation enum.
- `jobs/job-discovery`: Pluggable `JobSource` adapter, normalization, de-duplication, filtering, ranking, and graceful no-source degradation.
- `jobs/application-tracker`: Application statuses, notes, next actions, source links.
- `automation/automation-control`: Automation levels (Assistant/Copilot/Autopilot), per-automation settings, runs, approval requests, pause, bounded retries, and visible failure.
- `notifications/notifications-and-digests`: In-app and email notifications, digest composition, de-duplication, and delivery-failure handling.
- `billing/plans-and-entitlements`: Plan quotas, metering, enforcement at generation time, upgrade/downgrade/cancellation behavior.
- `platform/ai-runtime`: `CareerContext` assembly, structured-output contracts, prompt/agent versioning, cost controls, retries and fallbacks, AI-run auditing.
- `platform/data-privacy-and-retention`: Data classification, prompt minimization, retention windows, export and erasure across AI runs and background jobs.

### Modified Capabilities

None — `openspec/specs/` is currently empty, so every capability above is new.

## Impact

- **No application code is affected**: this repository contains documentation only. This change produces specifications and documents, not implementation.
- **Affected files**: all of `docs/00-README.md` … `docs/07-ai-automation-specification.md` are rewritten; `docs/08`–`docs/16` and `docs/ADR/` are added; `openspec/specs/**` is populated; `openspec/config.yaml` gains the locked project context so future changes inherit the stack decision.
- **Downstream**: the locked stack and the manual-publishing / manual-job-analysis decisions constrain every subsequent implementation change. Reversing them later requires a new ADR.
- **Dependencies named by these documents** (not installed here): Laravel, PostgreSQL, Redis, Next.js, OpenAI Responses API with web search, an S3-compatible store, a transactional email provider, Stripe.

## Open Questions

1. **Product name** — assumed "JB AI Career Agent" (working) with `LinkiGen` as the repo codename. If the commercial name is settled, the pack should use it instead.
2. **Job source selection** — the `JobSource` adapter interface is specified, but which concrete provider ships first (and its contract/cost) is deferred to implementation.
3. **Launch region / data residency** — the privacy document assumes GDPR-grade obligations as the strictest baseline; if launch is US-only this can be relaxed.
