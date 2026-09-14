# ADR-003: AI Provider and the Structured-Output Boundary

- **Status:** Accepted
- **Date:** 2026-09-14
- **Deciders:** Product owner
- **Governs:** `platform/ai-runtime`

## Context

AI is a core capability, not a feature bolted on. The product's output drives user-visible professional content and, eventually, public actions. Two failure modes are unacceptable: model output reaching the database unvalidated, and generated content asserting professional facts the user's profile does not support.

The pack also requires that a provider outage must not take the whole product down (`platform/ai-runtime`), and that AI spend is bounded per account and in aggregate.

## Decision

Use the **OpenAI Responses API**, with its **web search tool** for public professional research and **Structured Outputs** for every backend-consumed call.

### The validation boundary

Model output never reaches the database directly. Every AI-backed write follows one path:

```
Assemble minimized context
  → AI call with a declared output schema
  → Validate against that schema
  → Domain service applies business rules
  → Database
```

Rules that make this enforceable:

- **Validation before persistence.** Output failing schema validation is not persisted. The failure is recorded with the offending output, and the operation retries or fails per its policy.
- **No enum coercion.** A value outside a declared enum — for example a job recommendation that is not one of `STRONG_APPLY`, `APPLY`, `CONSIDER`, `SKIP` — causes rejection, never a default.
- **No free-text parsing.** Programmatic consumers use the schema. Extracting values from prose with pattern matching is prohibited.
- **Insufficient data is representable.** Each schema can express "I could not produce a valid result from this context", which the system handles as a defined outcome rather than an error.
- **Confidence is separate from value.** An inferred field carries a confidence level stored alongside, not embedded in, the value.

### Versioning

Every production capability records `agent_key`, `agent_version`, prompt/config version, model identifier, and schema version on each run. A material behavior change increments the version so past output stays attributable.

### Degradation

Provider unavailability fails the dependent feature cleanly with an explanation. Manual entry, manual publishing, and the application tracker remain usable.

## Alternatives Considered

- **Multi-provider abstraction from day one** — rejected as premature. The cost is a lowest-common-denominator interface that loses structured outputs and the web search tool, which are exactly what this design depends on. The validation boundary above is the portability mechanism: it already isolates the rest of the system from provider response shapes, so adding a second provider later is contained.
- **Free-form completions with prompt-enforced JSON** — rejected. It moves schema enforcement into the prompt, where it fails silently and intermittently, and it is precisely the brittle parsing the specs forbid.
- **Fine-tuning on user data** — out of scope, and the specs forbid claiming it unless actually implemented.

## Consequences

- Each AI capability owns an explicit input schema and output schema as a reviewable artifact.
- Schema-validity rate becomes a measured quality metric with a threshold gate (`docs/12-testing-and-ai-evaluation.md`).
- Run records store inputs and outputs, so they fall under the same retention and erasure rules as other personal data (`platform/data-privacy-and-retention`).
- Cost controls — result reuse, model tiering, token ceilings, aggregate spend thresholds — are the AI runtime's responsibility, with per-plan quotas owned by `billing/plans-and-entitlements`.
