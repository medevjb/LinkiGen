# JB AI Career Agent — MVP Documentation Pack

## Purpose

This folder is the implementation-ready specification for the first production MVP of the JB AI Career Agent (repository codename: **LinkiGen**), for human developers or AI coding agents.

It is the source of truth for product, UX, architecture, AI behavior, and automation behavior — together with `openspec/specs/`, which is the source of truth for testable behavior.

## Product Summary

JB AI Career Agent is an AI-powered SaaS for busy professionals and job seekers who do not have enough time to consistently manage their LinkedIn presence, professional positioning, content planning, and job-search workflow.

The MVP minimizes manual work. The product understands the user once, continuously generates relevant actions, automates repetitive tasks where safe and permitted, and requests approval only when human judgment is important.

## How This Pack Is Organized

Two kinds of statement live here, and each has exactly one home. Do not state the same rule normatively in both places.

| If you're writing... | It goes in... |
|---|---|
| Testable behavior, error conditions, acceptance criteria | `openspec/specs/<capability>/spec.md` |
| Why the product exists, positioning, business model | `01-product-vision.md` |
| Scope boundary, non-goals, success metrics | `02-mvp-prd.md` |
| Who the user is, journeys, experience principles | `03-personas-jtbd-user-journey.md` |
| Navigation structure and flow sequence | `04-information-architecture-user-flows.md` |
| Screen composition and UX state inventory | `05-screen-requirements-ux-states.md` |
| Stack, data model, module boundaries | `06-system-architecture-data-model.md` |
| AI agent contracts, prompts, automation catalogue | `07-ai-automation-specification.md` |
| Implementation contracts | `08`–`16` (API, NFRs, security, billing, testing, observability, deployment, analytics, errors) |
| Why a major architectural decision was made | `ADR/` |

`docs/01`–`07` reference spec requirements by capability path (e.g. `content/scheduling-and-publishing`), never by restating their acceptance criteria. If you find a requirement duplicated with different wording in two places, that is a defect — fix it by deleting the restatement and leaving the reference.

## Locked MVP Decisions

Each decision below has a full ADR with its alternatives and rationale. Nothing here is a recommendation left open for implementation to choose.

| # | Decision | ADR |
|---|---|---|
| 1 | Backend is Laravel exposing a REST API; frontend is a separate Next.js + TypeScript client; PostgreSQL + Redis | `ADR-001` |
| 2 | Background work runs on Laravel Queues (Redis) with Laravel Scheduler; every AI-backed operation is a queued job | `ADR-002` |
| 3 | AI runs on the OpenAI Responses API with Structured Outputs; model output is always schema-validated before a domain service writes it — never parsed from free text | `ADR-003` |
| 4 | The confirmed `CareerProfile` is the single canonical context; user corrections always outrank AI inference; refresh proposes changes, never overwrites | `ADR-004` |
| 5 | Onboarding is LinkedIn-URL-first via public web research; no credentials, cookies, CAPTCHA bypass, or private endpoints; CV and manual entry are permanent first-class fallbacks | `ADR-005` |
| 6 | **MVP 1 publishing is manual only**, behind a `PublishingProvider` interface; job discovery is manual analysis plus one pluggable `JobSource` adapter; the product is fully usable with neither connected | `ADR-006` |

Supporting product decisions, not yet needing their own ADR:

7. Primary target users for MVP 1: busy professionals growing their LinkedIn presence, and active job seekers wanting better-matched opportunities with less manual effort.
8. Recruiter functionality is outside MVP 1.
9. Automation is a core value proposition, not an optional add-on.
10. Default automation level is Copilot: AI prepares and automates routine work; high-impact public actions require approval by default.

## Recorded Assumptions

Stated explicitly rather than folded silently into prose. Each names its owner so a reader knows who can change it.

| Assumption | Owner | If wrong |
|---|---|---|
| Working product name "JB AI Career Agent"; repo codename "LinkiGen" | Product | Mechanical rename across the pack; no spec changes |
| GDPR-grade privacy obligations applied as the strictest baseline (`10-security-privacy-compliance.md`) | Product/Legal | If launch is confirmed US-only, retention windows and DSR response times in `10` §5–§6 may relax; no spec requirement changes |
| Concrete `JobSource` adapter selection is deferred | Product | Changes an adapter implementation only — the interface and no-source behavior are already specified (`ADR-006`, `jobs/job-discovery`) |
| Pricing in `01-product-vision.md` §11 and the quota matrix in `11-billing-entitlements.md` §3 are hypotheses | Product | Revisable without touching `billing/plans-and-entitlements` — the mechanism is fixed, the numbers are not |

## Documents

| File | Purpose |
|---|---|
| `01-product-vision.md` | Product strategy, problem, value proposition, positioning, business model |
| `02-mvp-prd.md` | MVP requirements, scope, user stories, acceptance criteria, success metrics |
| `03-personas-jtbd-user-journey.md` | Target users, jobs-to-be-done, pain points, journeys |
| `04-information-architecture-user-flows.md` | Navigation, module structure, core flows |
| `05-screen-requirements-ux-states.md` | Screen-level requirements and UX states |
| `06-system-architecture-data-model.md` | Stack, architecture, domain/data model, module boundaries |
| `07-ai-automation-specification.md` | AI agent contracts, context model, structured outputs, automation catalogue |
| `08-api-contract.md` | REST resource model, auth, pagination, idempotency, error envelope, async operations |
| `09-non-functional-requirements.md` | Latency budgets, async completion targets, availability, capacity, AI cost ceilings |
| `10-security-privacy-compliance.md` | Threat model, data classification, retention schedule, DSR procedure, incident response |
| `11-billing-entitlements.md` | Metered operations, plan quota matrix, subscription lifecycle rules |
| `12-testing-and-ai-evaluation.md` | Test pyramid, the ten AI evaluation cases, quality gates |
| `13-observability-and-runbooks.md` | Log schema, metrics, alerts, runbooks for the top failure modes |
| `14-deployment-environments-ci.md` | Environments, migrations, CI gates, release, rollback |
| `15-analytics-event-plan.md` | Event schema behind every success metric in `02` §9 |
| `16-error-catalog.md` | The single source of error codes referenced by `05`, `08`, and `13` |
| `ADR/ADR-001` … `ADR-006` | Architecture decisions with alternatives considered |
| `openspec/specs/**` | Testable behavior — 16 capabilities, see the traceability table below |

## Reading Order by Role

Nobody needs to read all seventeen documents. Start here:

**Product / stakeholder review:** `01` → `02` → `03` → the Locked Decisions and traceability table above.

**Frontend implementation:** `04` → `05` → `08` (contract) → `16` (error codes) → the relevant `openspec/specs/` capability.

**Backend implementation:** `06` → `ADR/` → `08` → the relevant `openspec/specs/` capability → `07` (if the capability is AI-backed) → `09` (its performance budget).

**AI capability implementation:** `07` → `platform/ai-runtime` spec → `12` (evaluation cases and gates) → the specific capability's spec.

**Operations / launch readiness:** `09` → `13` → `14` → `10` §9 (pre-launch checklist).

## Traceability Table

Every capability maps to the documents that elaborate it. Every PRD scope section (`02` §4) maps to its governing capability. Use this table to verify pack consistency instead of re-reading every file.

| Capability | PRD scope (`02` §4) | Elaborating documents |
|---|---|---|
| `identity/account-access` | §4.1 Authentication | `03` Stage 2 · `05` §2, §24 · `08` §7 · `10` §6 |
| `career/profile-import` | §4.2 Professional Profile Import | `01` §10 · `03` Stage 3 · `04` §4 · `05` §3 · `07` §4.1–4.2 · `ADR-005` |
| `career/career-profile` | §4.3 Career Profile | `03` Stage 4 · `04` §5, §19 · `05` §4, §8, §21 · `06` §6–7.3 · `ADR-004` |
| `career/goals-and-analysis` | §4.4 Career Goal Setup, §4.5 Profile Analysis | `03` Stage 5 · `04` §6 · `05` §5, §9 · `07` §4.3 |
| `planning/weekly-plan` | §4.6 Weekly AI Plan | `03` Stage 8 · `04` §9 · `05` §10 · `07` §4.4, §9.1 |
| `content/content-strategy` | §4.7 AI Content Strategy | `04` §10 · `05` §11 · `07` §4.5–4.6, §9.2–9.3 |
| `content/post-authoring` | §4.8 AI LinkedIn Post Generation | `04` §11 · `05` §12 · `07` §4.7, §9.4 |
| `content/scheduling-and-publishing` | §4.9 Content Calendar, §4.10 LinkedIn Publishing | `01` §9 · `04` §11–12 · `05` §13 · `06` §5 · `07` §9.5–9.6 · `09` §3 · `ADR-006` |
| `jobs/job-analysis` | §4.11 Job Discovery / Job Analysis | `04` §14 · `05` §15–16 · `07` §4.8–4.9 |
| `jobs/job-discovery` | §4.11 Job Discovery, §4.12 Job Filtering | `04` §13 · `05` §14, §18 · `07` §9.7–9.9 · `ADR-006` |
| `jobs/application-tracker` | §4.13 Application Tracker | `04` §16 · `05` §17 |
| `automation/automation-control` | §4.14 Automation Center, §4.15 Automation Modes | `01` §9 · `04` §17, §20d, §21 · `05` §6, §18, §22, §25 · `07` §7–§13 |
| `notifications/notifications-and-digests` | §4.16 Notifications & Digests | `04` §18 · `05` §19 |
| `billing/plans-and-entitlements` | *(not in original §4; added by this revision)* | `01` §11 · `04` §20c–20d · `05` §23 · `09` §6 · `11` |
| `platform/ai-runtime` | Cuts across §4.5–4.9, §4.11 | `06` §9 · `07` · `08` §6 · `09` §6 · `12` §5 |
| `platform/data-privacy-and-retention` | *(not in original §4; added by this revision)* | `04` §20a–20b · `05` §24 · `10` |

Dashboard (`02` §4.17) is not a capability — it composes several. See `05` §7.

## For AI Coding Agents

- Treat `02-mvp-prd.md` for scope and `openspec/specs/**` for acceptance criteria as the primary sources of truth — together, not either alone.
- Treat `04` and `05` as the source of truth for product behavior and UX.
- Treat `06` as the locked architecture (`ADR-001`, `ADR-002`), not a recommendation.
- Treat `07` and `platform/ai-runtime` as the source of truth for AI behavior, automation levels, triggers, approval requirements, and failure handling.
- Never add a feature not covered by these documents without an explicit product decision — see Scope Control below.
- Prefer small, reversible architectural decisions. Record a new ADR when changing a major architectural assumption.
- `08`–`16` are implementation-ready, not placeholders — build against them.

## Recommended Development Order

1. Repository and CI scaffolding per `14`
2. Authentication and account setup (`identity/account-access`)
3. LinkedIn URL onboarding (`career/profile-import`)
4. AI research + structured Career Profile generation
5. Profile review/edit/confirmation (`career/career-profile`)
6. Career goals and automation preferences (`career/goals-and-analysis`, `automation/automation-control`)
7. Dashboard foundation
8. Profile analysis and recommendations
9. Weekly AI plan (`planning/weekly-plan`)
10. Content strategy and post generation (`content/content-strategy`, `content/post-authoring`)
11. Content calendar and manual publishing (`content/scheduling-and-publishing`)
12. Job discovery/match workflow (`jobs/job-analysis`, `jobs/job-discovery`)
13. Application tracker (`jobs/application-tracker`)
14. Notifications/digests (`notifications/notifications-and-digests`)
15. Automation Center (full `automation/automation-control`)
16. Billing and entitlements (`billing/plans-and-entitlements`)
17. Analytics instrumentation (`15`) and the learning loop

## Scope Control

If a requested feature is not clearly covered by these documents, the AI coding agent should:
1. Check whether it is required to complete an existing documented flow.
2. If yes, implement the minimum necessary behavior, and add the missing scenario to the relevant spec first.
3. If no, record it as a future enhancement (`01` §14) instead of silently expanding MVP scope.
