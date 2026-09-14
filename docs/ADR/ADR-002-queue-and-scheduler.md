# ADR-002: Queue and Scheduler

- **Status:** Accepted
- **Date:** 2026-09-14
- **Deciders:** Product owner
- **Depends on:** ADR-001

## Context

Every AI-backed capability in this product is asynchronous. `docs/06-system-architecture-data-model.md` §7 requires queues for a specific set of job classes, and §8 requires per-user scheduling that respects each user's timezone. The specs add hard constraints on how those jobs must behave: bounded retries, idempotency for external actions, visible failure, and suspension after repeated failure (`automation/automation-control`).

## Decision

Use **Laravel Queues backed by Redis**, with **Laravel Scheduler** driving recurring work.

### Job classes this must cover

Every class named in `docs/06` §7 runs as a queued job:

| Job class | Trigger | Notes |
|---|---|---|
| Profile research | User-initiated (onboarding) | Billable; cached per `career/profile-import` |
| Profile analysis | Event-driven + monthly health check | Re-runs when the canonical profile changes materially |
| Weekly plan generation | Scheduled, weekly | Week boundaries in the user's timezone |
| Content strategy generation | Scheduled, weekly or event-driven | |
| Content idea generation | Scheduled, cadence-driven | |
| Post draft generation | Event-driven (idea selected) | |
| Job discovery | Scheduled, daily or configured | Skipped cleanly when no source is configured |
| Job matching | Event-driven (new normalized job) | Capped per run by the analysis cap |
| Publishing | Exact scheduled time | Requires an idempotency key |
| Analytics retrieval | Scheduled | Only where post metrics are available |
| Digest generation | Scheduled, daily or weekly | Suppressed when empty |
| Email sending | Event-driven | |
| Profile refresh | Scheduled, configurable | Produces proposed changes, never overwrites |
| Data export | User-initiated | Delivered by time-limited signed link |
| Account erasure | User-initiated, deferred | Must invalidate in-flight jobs for that user |

### Required job properties

- **Idempotent where practical**, and **strictly idempotent for external actions** — publishing uses an idempotency key so a retried job publishes at most once (`content/scheduling-and-publishing`).
- **Bounded retries** with backoff. Retries are never unbounded (`automation/automation-control`).
- **Observable** — every run records automation key, trigger type, start and end time, status, retry count, error, and a reference to its output.
- **Deletion-aware** — a job must check the account's erasure marker before persisting any result (`identity/account-access`).
- **Quota-aware** — a job checks entitlement before incurring provider cost, and persists partial results if quota is exhausted mid-batch (`billing/plans-and-entitlements`).

### Scheduling

Recurring work is registered once in the scheduler and fans out per user. Per-user execution times are computed from the user's stored IANA timezone; schedules are never hard-coded to server time.

## Alternatives Considered

- **BullMQ** — not applicable once ADR-001 selected Laravel.
- **Database-backed queue instead of Redis** — simpler infrastructure, one less service. Rejected because publishing requires exact-time execution and job throughput will grow with per-user fan-out; Redis gives adequate latency headroom without introducing a distributed system.
- **A managed external scheduler (e.g. cloud cron hitting webhooks)** — rejected as premature infrastructure per `docs/06` §12. Revisit if multi-region deployment happens.

## Consequences

- Redis becomes a production dependency with its own availability and monitoring requirements (`docs/13-observability-and-runbooks.md`).
- Queue depth, job latency, and failure rate are first-class operational metrics with alerts.
- A queue backlog runbook is required, because a backlog delays user-visible output such as scheduled publishing.
