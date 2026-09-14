# ADR-001: Backend Framework and Client Architecture

- **Status:** Accepted
- **Date:** 2026-09-14
- **Deciders:** Product owner
- **Supersedes:** The "Laravel, or Node.js/TypeScript — preferred options" fork in `docs/06-system-architecture-data-model.md` §2

## Context

The documentation pack left the backend framework undecided, offering Laravel and Node.js/TypeScript as equally weighted options. No implementation can begin while that fork is open: the data model, queue design, scheduler design, and API contract all depend on it.

Constraints in play:

- The product is background-work-heavy. Profile research, weekly planning, content generation, job discovery, job matching, publishing, digests, and profile refresh all run asynchronously on schedules (`docs/06` §7–§8).
- The pack's own rule forbids premature distributed complexity: no microservices, no event bus, no separate vector database without a measured need (`docs/06` §12).
- The UX described in `docs/05-screen-requirements-ux-states.md` is client-state-heavy: draft editing with AI actions, calendar interaction, optimistic task status updates.
- This repository lives under Laravel Herd, indicating an existing local Laravel toolchain.

## Decision

The backend is **Laravel**, exposing a REST API. The frontend is a **separate Next.js + TypeScript client** consuming that API over the contract defined in `docs/08-api-contract.md`.

Supporting choices locked with this decision:

- **PostgreSQL** as the primary datastore.
- **Redis** for cache and queue backing (see ADR-002).
- Frontend and backend are **separate deployables**.

## Alternatives Considered

### Node.js / NestJS + BullMQ

A single language across the stack is a genuine advantage: shared types between API and client, one dependency toolchain, one hiring profile. BullMQ is a capable queue.

Rejected because the deciding factor was background-work ergonomics rather than language uniformity. Laravel ships queues, scheduling, retries, failed-job storage, and mail as first-party, cohesive concerns; the Node equivalent assembles them from separate libraries with more integration surface to own. The local Herd toolchain reinforced the choice. The trade-off accepted is that the frontend and backend do not share types — mitigated by the API contract in `docs/08` being explicit enough to generate client types from.

### Laravel with Inertia and Blade (no separate client)

Fastest initial delivery, and adequate for the MVP screens.

Rejected because it produces no reusable API surface. Any later mobile client, browser extension, or partner integration would require retrofitting an API onto view-coupled controllers. Keeping the client separate also forces the API contract to stay complete and honest, since the client cannot reach into framework internals.

## Consequences

- `docs/06-system-architecture-data-model.md` §2 states one stack, not a menu.
- The API contract (`docs/08`) becomes a required artifact rather than an optional one, since it is the only interface between the two deployables.
- CI, deployment, and environments must cover two deployables (`docs/14-deployment-environments-ci.md`).
- The decision is confined to the framework layer. The data model, API contract, error catalog, and all 16 capability specs are framework-neutral and would survive a reversal.
- Reversing this decision requires a superseding ADR, not a silent change.
