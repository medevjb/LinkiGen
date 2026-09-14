# ADR-004: CareerProfile as Canonical Context

- **Status:** Accepted
- **Date:** 2026-09-14
- **Deciders:** Product owner
- **Governs:** `career/career-profile`, and the context rules in `platform/ai-runtime`

## Context

Every AI capability in this product reads from the user's professional data: profile analysis, weekly planning, content strategy, post generation, and job matching. That data arrives from three sources of very different trustworthiness — AI web research, an uploaded CV, and the user's own typing — and it is re-researched periodically.

Without one authority rule, three failures follow: the same context gets assembled differently per feature, a refresh silently reverts a correction the user made, and generated content asserts something the user already told us was wrong.

## Decision

The confirmed `CareerProfile` is the **single canonical professional context**. Every AI capability reads from it, and nothing else is authoritative.

### Authority ordering

From highest to lowest:

1. **User-authored values** — anything the user typed, edited, or explicitly confirmed.
2. **CV-extracted values** — user-supplied, so more authoritative than research.
3. **Researched values** — AI-derived from public sources, carrying confidence and provenance.
4. **Inferred values** — never promoted to canonical without user review.

A higher-authority value is never overwritten by a lower-authority one. When research contradicts a user correction, the conflict is shown and the user's value is kept by default.

### Confirmation gates use

A draft profile is not canonical. Until the user confirms it, it drives nothing: no recommendations, no content, no job matching. Features that depend on canonical data present themselves as blocked with a pointer to profile review, rather than generating from draft data.

### Refresh proposes, never overwrites

Re-research produces a **pending change set** for review. Each proposed change is accepted or rejected individually. A rejected proposal is not raised again unless its source evidence changes. A refresh that finds nothing material updates a timestamp and creates no notification.

### Corrections propagate

A correction does two things: it becomes the canonical value, and it enters the context used by subsequent generation — so the same mistake is not reproduced in the next post or the next recommendation.

### Staleness is explicit

When the canonical profile changes materially, dependent analysis and recommendations are marked out of date and offered for regeneration, rather than being presented as current.

## Alternatives Considered

- **Treat the LinkedIn profile as the source of truth and sync from it.** Rejected on two grounds. It requires access we have deliberately not taken (ADR-005), and it makes an external platform the owner of our core data model — the exact dependency `docs/02-mvp-prd.md` §10 warns against. LinkedIn is a channel, not our datastore.
- **Let AI merge research into the profile automatically, with an undo.** Rejected. Undo does not help when the overwrite is noticed weeks later, after generated content already used the wrong data. The trust cost of a silent overwrite is much higher than the friction cost of a review step.
- **Per-feature context assembly.** Rejected: it guarantees divergence between what the post generator and the job matcher believe about the same user.

## Consequences

- Provenance and confidence must be stored per field, separately from the value.
- The data model needs pending change sets as first-class entities, not a transient diff.
- Original researched values are retained for audit even after a user correction.
- The review step is a required part of onboarding and cannot be skipped, which is a deliberate friction cost accepted in exchange for trust.
