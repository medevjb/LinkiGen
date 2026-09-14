## Context

This repository contains documentation only — `docs/00`–`07` and an empty `openspec/specs/`. There is no application code, no chosen framework, and no dependency to migrate. See `proposal.md` — Why for the motivation.

Constraints shaping this design:

- **The deliverable is documents, not code.** The "how" here is the structure, ownership, and internal consistency rules of the documentation set, plus the architecture decisions that the documents must lock so implementation can begin without re-litigating them.
- **Two sources of truth now exist.** `openspec/specs/**` holds testable behavior; `docs/**` holds narrative product, UX, and architecture context. They will drift unless the relationship between them is defined once, here.
- **Four decisions were confirmed by the product owner** (Laravel + Next.js; manual-only LinkedIn publishing for MVP; manual job analysis plus one pluggable source; full rewrite of the existing pack). These are inputs, not open choices.
- **The existing pack hedges.** It uses "should", "may", "preferred options", and "Option A / Option B" in places where implementation needs a single answer. The rewrite has to remove optionality without discarding the genuinely deferred decisions.

## Goals / Non-Goals

**Goals:**

- Establish a single, non-overlapping ownership rule for every kind of statement, so a developer knows which file answers a given question.
- Lock the architecture decisions that currently block the first line of code, and record each as an ADR with its alternatives.
- Add the nine missing implementation documents at a depth sufficient to build from, without pre-writing implementation.
- Make the scenario gaps found in the sweep — duplicate URL claims, expired publishing tokens, quota exhaustion mid-batch, downgrade below usage, deletion with in-flight jobs, DST — resolved in the specs and reflected in the narrative docs.
- Leave the pack verifiable: a reviewer can check consistency mechanically rather than by reading all 17 files.

**Non-Goals:**

- No application code, scaffolding, migrations, or dependency installation. This change ends at documents.
- No API implementation — `08-api-contract.md` defines the contract, not handlers.
- No visual design system, component library selection, or screen mockups. `05` stays behavioral.
- No vendor selection for the job source, email provider, or error tracker. Interfaces and selection criteria are specified; the choice is deferred.
- No pricing commitment. `11-billing-entitlements.md` specifies the *mechanism* and quota shape; the price points stay a hypothesis owned by `01-product-vision.md`.

## Decisions

### D1. Specs own behavior; `docs/` owns context — enforced by a stated ownership rule

Each statement type gets exactly one home:

| Statement type | Home |
|---|---|
| Testable behavior, error conditions, acceptance criteria | `openspec/specs/<capability>/spec.md` |
| Why the product exists, positioning, business model | `docs/01` |
| Scope boundary, non-goals, success metrics | `docs/02` |
| Who the user is, journeys, experience principles | `docs/03` |
| Navigation structure and flow sequence | `docs/04` |
| Screen composition and UX state inventory | `docs/05` |
| Stack, data model, module boundaries | `docs/06` |
| AI agent contracts, prompts, automation catalogue | `docs/07` |
| Implementation contracts (API, NFR, security, billing, test, ops, deploy, analytics, errors) | `docs/08`–`16` |

The rewritten `docs/02` and `docs/05` reference spec requirements by name instead of restating acceptance criteria. This removes the pack's largest drift risk: the same rule written in three files with three wordings.

**Alternative considered — put everything in `docs/` and skip specs.** Rejected: the pack's core failure is that nothing in it is testable. Narrative prose cannot be validated, and `openspec validate` already catches missing scenarios.

**Alternative considered — collapse `docs/` into specs entirely.** Rejected: personas, positioning, and business model are not behavior and would distort spec files. The pack's product reasoning has real value and should survive.

### D2. Laravel is the backend; the frontend is a separate Next.js client

Locked per the product owner's decision, and consistent with this repository living under Laravel Herd. Laravel's first-party queue and scheduler cover the entire background surface this product needs — profile research, weekly planning, content generation, discovery, matching, publishing, digests — with no extra infrastructure, which directly serves the pack's own rule against premature complexity.

Frontend and backend stay separate deployables communicating over the documented REST contract, because the UX in `05` is client-state-heavy (draft editing, calendar interaction, optimistic task updates) and because it keeps the API contract honest — it must be complete enough for a client that cannot reach into the framework.

**Alternative considered — Node/NestJS + BullMQ.** A single language across the stack is a real advantage, but it was not chosen; recorded in ADR-001 with its rationale so the trade-off is not re-argued.

**Alternative considered — Laravel with Inertia and Blade, no separate client.** Faster initially, but it produces no reusable API surface and makes the future mobile or extension path expensive. Rejected.

### D3. Every external capability that can be denied to us sits behind an interface

Three integrations are outside our control: LinkedIn publishing (requires partner approval), job feeds (require a commercial contract), and the AI provider (availability and cost). All three are specified as interfaces with a defined no-provider behavior:

- `PublishingProvider` — MVP ships with the manual path only. The specs require that the product is fully usable with no provider connected.
- `JobSource` — the specs require manual analysis to work regardless, and require discovery to report itself unavailable rather than show an empty list.
- AI runtime — the specs require non-AI features to survive a provider outage.

This is the single most important structural decision in the pack, because it removes every launch dependency on an approval or contract we do not hold.

**Alternative considered — commit to the LinkedIn API and build for it.** Rejected: it makes the content loop, which is half the product, contingent on an approval that is commonly denied to tools in this category.

### D4. Quota is checked before spend, and partial results are kept

Metering rules are specified as a cross-cutting concern in `billing/plans-and-entitlements` rather than repeated per feature. The two rules that matter architecturally: a metered operation checks entitlement *before* incurring provider cost, and a batch that exhausts quota partway persists what it already produced. Both are stated as scenarios so they are testable, and both are referenced from `09-non-functional-requirements.md` as cost-control mechanisms rather than restated.

### D5. All scheduling is computed in the user's timezone, stated once

Timezone correctness appears in three capabilities (weekly plan boundaries, post scheduling, digest delivery). Rather than restating it, `scheduling-and-publishing` carries the normative DST and timezone-change requirements, and the other two reference the user's stored timezone as the basis for their computation. `06` records the storage rule: absolute instants persisted in UTC, with the user's IANA timezone stored alongside the intended local wall-clock time for anything a user scheduled.

### D6. The nine new documents are written to contract depth, not implementation depth

Each new document answers "what must be true" and stops before "here is the code":

- `08-api-contract.md` — resource model, auth scheme, pagination, idempotency keys, the standard error envelope, and the long-running-operation pattern (every AI feature is asynchronous; the client needs one consistent way to start work and poll it).
- `09-non-functional-requirements.md` — interactive latency budgets, async-completion targets per job class, availability target, and per-account AI cost ceiling.
- `10-security-privacy-compliance.md` — threat model, data classification, DSR procedure, retention table. Mirrors `platform/data-privacy-and-retention` and adds the operational procedure that a spec should not carry.
- `11-billing-entitlements.md` — the quota matrix per plan and the metered-operation list.
- `12-testing-and-ai-evaluation.md` — test pyramid, and the ten evaluation cases from `07` §14 turned into a concrete fixture set with pass thresholds.
- `13-observability-and-runbooks.md` — log schema, AI-run tracing, queue alerts, and runbooks for the top failure modes (AI provider outage, queue backlog, publish failure spike, cost spike).
- `14-deployment-environments-ci.md` — environments, migration policy, secret handling, release and rollback.
- `15-analytics-event-plan.md` — one event per metric in `02` §9, with properties.
- `16-error-catalog.md` — stable machine-readable error codes shared by the API envelope, queue failures, and UI error states, so `05`'s error states and `08`'s envelope cannot diverge.

**Alternative considered — write these lazily during implementation.** Rejected: these are exactly the documents the pack's own `00-README.md` defers ("add detailed API docs, migrations, test plans, observability, deployment, security during implementation"), and that deferral is why the pack is not buildable.

### D7. Consistency is made checkable

The rewrite adds a traceability table in `00-README.md` mapping each spec capability to the documents that elaborate it, and each PRD scope section to its governing spec. A reviewer verifies the pack by checking that table rather than re-reading everything. Every cross-reference uses the capability path (for example `content/scheduling-and-publishing`), not a page number or section number, so references survive editing.

### D8. Assumptions recorded rather than silently applied

Product name (`JB AI Career Agent` working, `LinkiGen` codename), GDPR-grade privacy as the strictest baseline, and deferred job-source selection are written into `00-README.md` as explicit assumptions with the decision owner named, rather than being folded invisibly into prose.

## Risks / Trade-offs

- **The pack grows from 8 to 17 documents plus 16 specs, and larger packs are read less.** → `00-README.md` becomes a genuine entry point with a reading order by role (product, frontend, backend, AI) and the traceability table, so nobody needs to read all of it.
- **Specs and `docs/` drift once implementation starts.** → D1's ownership rule plus D7's table make drift detectable; `docs/` references requirements by name instead of copying them, so there is far less duplicated text to drift.
- **Locking Laravel forecloses a Node team.** → Recorded in ADR-001 with its alternatives; reversal is a new ADR, not a silent change. The decision is at the framework layer only — the data model, API contract, and every spec are framework-neutral and would survive a reversal.
- **Manual-only publishing weakens the automation promise at launch.** → The provider interface means the automated path is a configuration change, not a rewrite, and `01-product-vision.md` is rewritten so the positioning does not over-promise publishing automation before it exists.
- **Deferring the job source means automated discovery may not ship at all.** → The specs require graceful degradation, so a missing source is a documented product state rather than a broken feature. Manual analysis carries the job value proposition alone if needed.
- **Writing NFR numbers before any code means the numbers are estimates.** → They are stated as budgets to design against and to revise once measured, with the revision trigger written into `09` rather than left implicit.
- **Rewriting all eight existing documents risks losing intent the owner cared about.** → The rewrite preserves each document's purpose and section structure; changes are confined to removing optionality, folding in locked decisions, and replacing restated acceptance criteria with spec references.

## Migration Plan

No deployment or rollback applies — this change only adds and rewrites files in a documentation repository.

Sequencing that matters:

1. ADRs first, since `06` and `07` depend on the locked decisions.
2. Rewrite `06` and `07` next, because `08`–`16` reference their data model and agent contracts.
3. Rewrite `00`–`05` last, once the references they must point at exist.
4. `00-README.md`'s traceability table is written last of all, as the final consistency pass.

The previous version of each rewritten document is recoverable from version control; this repository is not currently a git repository, so initializing one before the rewrite is included as the first task.

## Open Questions

1. **Which concrete job source ships first.** Deferred safely — the `JobSource` interface and the no-source behavior are specified, so the choice changes only an adapter implementation, not the specs or the task breakdown.
2. **Whether launch is EU-facing.** The privacy document assumes GDPR-grade obligations as the strictest baseline. If launch is US-only, retention windows and DSR response times can be relaxed without changing any spec requirement.
3. **Final commercial product name.** Affects copy only; every document uses the working name consistently, so a rename is a mechanical substitution.
