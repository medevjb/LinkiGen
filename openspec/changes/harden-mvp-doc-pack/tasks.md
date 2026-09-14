## 1. Repository and decision baseline

- [x] 1.1 Initialize a git repository at the project root with a `.gitignore` and commit the current `docs/` and `openspec/` state as the pre-rewrite baseline; verify `git log` shows the baseline commit and `git status` is clean
- [x] 1.2 Create `docs/ADR/` and write `ADR-001-backend-framework.md` locking Laravel + Next.js per design D2, including the Node/NestJS and Inertia alternatives and why each was rejected; verify the file states one decision with status Accepted and a dated rationale
- [x] 1.3 Write `ADR-002-queue-and-scheduler.md` locking Laravel Queues + Redis + Laravel Scheduler, listing the background job classes from `06` §7 that it must cover; verify every job class in `06` §7 appears
- [x] 1.4 Write `ADR-003-ai-provider-and-structured-outputs.md` covering the OpenAI Responses API, web search for research, and the structured-output validation boundary required by `platform/ai-runtime`; verify it names the validation-before-persistence rule
- [x] 1.5 Write `ADR-004-career-profile-as-canonical-context.md` recording that user corrections outrank AI inference and refresh proposes rather than overwrites; verify it cross-references `career/career-profile`
- [x] 1.6 Write `ADR-005-profile-research-onboarding.md` recording LinkedIn-URL-first onboarding, the prohibited access methods, and the CV/manual fallbacks; verify it cross-references `career/profile-import`
- [x] 1.7 Write `ADR-006-publishing-and-job-source-strategy.md` locking manual-only LinkedIn publishing and manual-analysis-plus-one-pluggable-source per design D3, with the no-provider degradation rule; verify it cross-references `content/scheduling-and-publishing` and `jobs/job-discovery`
- [x] 1.8 Add the locked stack, domain summary, and documentation conventions to the `context:` block of `openspec/config.yaml` so future changes inherit them; verify `openspec instructions proposal --change harden-mvp-doc-pack --json` returns the new context

## 2. Architecture and AI documents (rewrite first — later documents reference them)

- [x] 2.1 Rewrite `docs/06-system-architecture-data-model.md` §2 to state the locked stack as a decision rather than a recommendation, removing the "Laravel or Node.js" fork and linking ADR-001/002; verify no optionality language remains in the stack section
- [x] 2.2 Add a timezone and time-storage section to `docs/06` per design D5 (UTC instants, stored IANA timezone, intended local wall-clock for user-scheduled items); verify it resolves the DST scenarios in `content/scheduling-and-publishing`
- [x] 2.3 Extend the `docs/06` entity model with the entities the specs require but the current model lacks — pending profile change sets, entitlement and usage counters, data-export and erasure requests, notification delivery state, and job source registration; verify every spec capability's persisted state has a home in the model
- [x] 2.4 Add a module-boundary section to `docs/06` mapping each of the 16 spec capabilities to an owning backend module; verify all 16 capability paths appear exactly once
- [x] 2.5 Rewrite `docs/07-ai-automation-specification.md` §4 so each agent states its input schema, output schema, version key, failure behavior, and the spec requirement it satisfies; verify each agent entry names its governing requirement
- [x] 2.6 Rewrite `docs/07` §9 so each MVP automation is expressed in the full automation definition schema from §8 with no field left implicit; verify every automation lists all twelve fields including retry policy and audit level
- [x] 2.7 Update `docs/07` §15 cost controls to reference the metering and quota mechanism owned by `billing/plans-and-entitlements` instead of restating limits; verify no quota numbers are duplicated between `07` and `11`

## 3. New implementation documents

- [x] 3.1 Write `docs/08-api-contract.md` covering the resource model, authentication scheme, pagination, idempotency keys, the standard error envelope, and the long-running-operation start/poll pattern used by every AI feature; verify each of the 16 capabilities has at least one documented endpoint or an explicit note that it is not API-exposed
- [x] 3.2 Write `docs/09-non-functional-requirements.md` with interactive latency budgets, per-job-class async completion targets, availability target, capacity assumptions, per-account AI cost ceiling, and the trigger for revising these numbers once measured; verify every background job class from `06` §7 has a stated completion target
- [x] 3.3 Write `docs/10-security-privacy-compliance.md` with the threat model, data classification table, DSR procedure, retention schedule, and prompt-minimization rules; verify the retention table covers every class named in `platform/data-privacy-and-retention` and contradicts none of its requirements
- [x] 3.4 Write `docs/11-billing-entitlements.md` with the metered-operation list, the Free/Pro/Pro+ quota matrix, warning thresholds, and upgrade, downgrade, payment-failure, and cancellation behavior; verify every scenario in `billing/plans-and-entitlements` has a concrete value or rule here
- [x] 3.5 Write `docs/12-testing-and-ai-evaluation.md` with the test pyramid, the ten evaluation cases from `07` §14 as concrete fixtures, the six evaluation dimensions, and pass thresholds gating connection to public actions; verify all ten cases and all six dimensions are present with a threshold each
- [x] 3.6 Write `docs/13-observability-and-runbooks.md` with the structured log schema, AI-run tracing fields, queue and cost alerts, and runbooks for AI provider outage, queue backlog, publish-failure spike, and cost spike; verify each runbook states detection, immediate action, and recovery
- [x] 3.7 Write `docs/14-deployment-environments-ci.md` covering environments, migration policy, secret handling, CI gates, release, and rollback; verify the CI gates include the spec validation and AI evaluation gates from tasks 3.5 and 5.3
- [x] 3.8 Write `docs/15-analytics-event-plan.md` defining one event with properties for each metric in `02` §9; verify every metric listed in `02` §9 maps to at least one event
- [x] 3.9 Write `docs/16-error-catalog.md` with stable machine-readable error codes, their HTTP mapping, user-facing message, and recovery action; verify every error and failure scenario across the 16 specs maps to a code, and that `08`'s envelope and `05`'s error states both reference this catalog

## 4. Rewrite the existing product documents

- [x] 4.1 Rewrite `docs/01-product-vision.md` so the automation and publishing claims match the manual-first MVP reality per ADR-006, and the business model is labelled a hypothesis owned by this document; verify no statement promises automated LinkedIn publishing at launch
- [x] 4.2 Rewrite `docs/02-mvp-prd.md` §4.10 and §4.11 to state the single locked approach for publishing and job discovery, removing the Option A/Option B and dual-path ambiguity; verify neither section offers a choice to the implementer
- [x] 4.3 Replace the restated acceptance criteria throughout `docs/02` with references to the governing spec requirement by capability path per design D1; verify `docs/02` §8 points at specs rather than duplicating criteria
- [x] 4.4 Add a scope-gap section to `docs/02` recording the scenarios resolved by this change — duplicate URL claims, expired publishing authorization, quota exhaustion mid-batch, downgrade below usage, deletion with in-flight jobs, DST transitions — each naming its governing capability; verify all six appear with a capability reference
- [x] 4.5 Rewrite `docs/03-personas-jtbd-user-journey.md` to align Stage 6 and Stage 7 with the locked automation defaults and the manual publishing path; verify the journey no longer implies automated publishing during first-value
- [x] 4.6 Rewrite `docs/04-information-architecture-user-flows.md` §11 and §13 for the manual publish path and the no-source discovery state, and add the missing flows for account deletion, data export, quota exhaustion, and billing lifecycle; verify each of the four added flows has a defined success and failure path
- [x] 4.7 Rewrite `docs/05-screen-requirements-ux-states.md` so every screen's error and empty states reference codes from `docs/16` and the behaviors reference their governing spec requirement; verify no screen lists an error state without a corresponding catalog code
- [x] 4.8 Add the missing screens to `docs/05` implied by the specs — pending profile change review, approval queue, usage and billing, data export and account deletion, and the automation history view; verify each new screen documents loading, empty, error, and permission states

## 5. Consistency, traceability, and verification

- [x] 5.1 Rewrite `docs/00-README.md` as the entry point: product summary, locked decisions with ADR links, reading order by role, the `docs/`-versus-specs ownership rule from design D1, and the recorded assumptions from design D8; verify the locked-decision list matches ADR-001 through ADR-006 with no contradiction
- [x] 5.2 Add the traceability table to `docs/00-README.md` mapping each of the 16 capabilities to its elaborating documents and each `02` scope section to its governing capability; verify every capability and every `02` §4 subsection appears
- [x] 5.3 Run `openspec validate harden-mvp-doc-pack --strict` and confirm it passes with all four artifacts present
- [x] 5.4 Perform a cross-document consistency pass checking that no requirement is stated normatively in two places with different wording, that every cross-reference resolves to an existing capability path or document, and that no document still offers an implementer a choice between locked alternatives; verify by walking the traceability table and recording the check outcome in the commit message
- [x] 5.5 Verify the pack is buildable end to end by tracing the four P0 flows from `04` §22 — auth, profile import and review, goal setup, weekly plan — from PRD scope through spec requirement, API endpoint, data model entity, error codes, and UX states, confirming no step is undefined; record any gap found as a follow-up task rather than leaving it unwritten
