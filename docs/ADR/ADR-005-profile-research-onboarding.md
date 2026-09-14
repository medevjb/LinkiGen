# ADR-005: LinkedIn-URL-First Onboarding via Public Research

- **Status:** Accepted
- **Date:** 2026-09-14
- **Deciders:** Product owner
- **Governs:** `career/profile-import`

## Context

The product's core promise is that the user is understood once, without retyping their career history. Onboarding friction is the main risk to that promise — a long form at signup defeats it.

The constraint is that we have no authenticated access to LinkedIn profile data, and the methods that would provide it are prohibited by `docs/02-mvp-prd.md` §4.2 and by LinkedIn's terms.

## Decision

Onboarding starts with the user pasting their **LinkedIn profile URL**. The backend researches the **maximum publicly available professional information** associated with it using the AI provider's web search, normalizes the result into a structured draft profile, and presents it for review.

### Prohibited access methods

No implementation may use LinkedIn credentials, browser cookies or session tokens, CAPTCHA circumvention, or private LinkedIn endpoints. The product must never present itself to the user as having authenticated LinkedIn access, and must not claim it can always import a complete profile.

### Fallbacks are first-class, not error paths

CV upload and manual entry are always available — including on the free plan, and including when research succeeds. A user may skip the URL entirely. Research that finds nothing usable says so plainly and routes to the fallbacks; it never presents an empty profile as a successful import.

### Outcomes are classified

Every research run is success, partial success, or failure, and each has a defined forward path. Partial results ask only for the fields MVP features actually require. Conflicts — two current job titles, for instance — are surfaced with their sources for the user to resolve, never silently resolved by picking one.

### Identity is protected

A LinkedIn URL already confirmed by another account cannot be imported by a second user; the import is blocked with a support or verification path. An unconfirmed stale claim is released to the new user.

### Cost is bounded

Research results are cached per URL for a configured window. A repeat request reuses the cache unless the user explicitly forces a refresh. Research consumes plan quota; exhausting it refuses the run with a named limit and reset time, while leaving the CV and manual paths open.

## Alternatives Considered

- **A LinkedIn OAuth import.** The available scopes return a thin subset — roughly name, headline, and photo — not the experience, projects, and skills the product actually needs. It would add an integration dependency and an approval process while still requiring the research path. Rejected.
- **A conventional signup form or a CV-first flow.** Reliable and cheap, but it is precisely the friction the product exists to remove, and it makes the first experience feel like every other career tool. Retained as a fallback rather than the primary path.
- **Third-party profile-data vendors.** Adds cost, a contract, and a data-protection relationship, for data of uncertain freshness. Deferred; the adapter shape in ADR-006 would accommodate one later.

## Consequences

- Research quality varies substantially between users, so completeness scoring, confidence, and the review step (ADR-004) are load-bearing, not decorative.
- A privacy disclosure must appear before the user submits the URL, explaining that public sources will be searched and an AI provider will process the result.
- Research is the most expensive single operation in the product, making caching and quota enforcement a cost requirement rather than an optimization.
