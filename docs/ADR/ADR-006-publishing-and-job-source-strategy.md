# ADR-006: Publishing and Job Source Strategy

- **Status:** Accepted
- **Date:** 2026-09-14
- **Deciders:** Product owner
- **Governs:** `content/scheduling-and-publishing`, `jobs/job-discovery`
- **Supersedes:** The "Option A / Option B" publishing fork in `docs/02-mvp-prd.md` §4.10 and the dual-path job discovery language in §4.11

## Context

Two of the product's headline capabilities depend on access we do not control:

- **Automated LinkedIn publishing** requires LinkedIn partner API approval. Approval is slow and commonly denied to tools in this category.
- **Automated job discovery** requires a licensed or officially provided job feed. Scraping LinkedIn job listings is prohibited by `docs/02-mvp-prd.md` §5 and by LinkedIn's terms.

The pack left both as open options. Treating either as a launch dependency would make shipping contingent on an approval or a contract we do not hold.

## Decision

**Both capabilities sit behind an interface, and the product is fully functional with no provider connected.**

### Publishing: manual path ships, API path is designed but dark

MVP 1 ships the **manual publishing path only**: at the scheduled time the user is notified, offered copy-to-clipboard and an "open LinkedIn" action, and confirms publication themselves. This path is available on every plan and requires no integration.

Automated publishing is implemented behind a `PublishingProvider` interface. With no provider configured, automated-publish controls are shown as unavailable with an explanation — not hidden, and not broken. When a provider exists, it must record the external post identifier and use an **idempotency key** so a retried job publishes at most once.

Failure handling is defined regardless of provider: bounded retries for transient failures, provider-directed backoff on rate limits, and — critically — an **expired or revoked authorization is never retried blindly**. It marks the integration as needing reconnection, notifies the user, and falls back to the manual path.

### Jobs: manual analysis is the guaranteed path, discovery is pluggable

**Manual job analysis** — paste a URL or a description — works on every plan and carries the job value proposition on its own.

Automated discovery is implemented behind a `JobSource` adapter interface. Adapters return jobs in the normalized shape; no filtering, matching, or ranking behavior depends on which source produced a job. With no source configured, discovery reports itself unavailable with an explanation rather than showing an unexplained empty list.

Adapters may only use permitted public, licensed, or officially provided feeds. Scraping LinkedIn job listings and circumventing access controls are prohibited.

## Alternatives Considered

- **Commit to the LinkedIn API as the primary publishing path.** Rejected: it makes the content loop — half the product — contingent on an approval commonly denied, with no fallback if it never arrives.
- **Build both paths fully in MVP, with the API path behind a flag.** A reasonable middle option and the product owner's second choice. Rejected for MVP 1 because it doubles the publishing spec surface and the test matrix to serve a path that may never activate. The interface means adding it later is a contained change rather than a rewrite.
- **Cut automated job discovery from MVP entirely.** Simplest scope, but it weakens the "the agent finds jobs for me" promise to nothing. The pluggable-adapter approach keeps the promise reachable without making it a launch dependency.
- **Commit now to a licensed job feed.** Rejected: a contract and cost commitment before there is evidence users want automated discovery over manual analysis.

## Consequences

- No launch dependency on any third-party approval or contract. This is the primary benefit and the reason for the decision.
- `docs/01-product-vision.md` must not promise automated LinkedIn publishing at launch; the positioning is rewritten accordingly.
- "No source configured" and "no provider connected" are **documented product states with defined UX**, not error conditions.
- Which concrete job source ships first is deliberately deferred. It changes an adapter implementation only — not the specs, the data model, or the task breakdown.
- If LinkedIn approval is later granted, enabling automated publishing is a configuration and adapter change. It does not alter any user-facing requirement already specified.
