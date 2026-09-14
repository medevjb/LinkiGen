# JB AI Career Agent — MVP Documentation Pack

## Purpose

This folder contains the minimum documentation required to design and build the first production-oriented MVP of the JB AI Career Agent using human developers or AI coding agents.

These documents are intended to function as the initial source of truth for product, UX, architecture, AI behavior, and automation behavior.

## Product Summary

JB AI Career Agent is an AI-powered SaaS for busy professionals and job seekers who do not have enough time to consistently manage their LinkedIn presence, professional positioning, content planning, and job-search workflow.

The MVP should minimize manual work. The product should understand the user once, continuously generate relevant actions, automate repetitive tasks where safe and permitted, and request approval only when human judgment is important.

## Locked MVP Decisions

1. Primary onboarding starts with a LinkedIn profile URL.
2. The backend calls the OpenAI API with web-search capability to research the maximum publicly available professional information associated with the user.
3. The result is normalized into structured `CareerProfile` JSON and stored in the application database.
4. Missing information that is essential to MVP functionality can be completed manually by the customer.
5. CV/resume upload is a fallback or enrichment source, not a mandatory first step.
6. Users must review and confirm their generated Career Profile before it becomes the canonical profile.
7. The primary target users for MVP 1 are:
   - Busy professionals who want to grow or maintain their LinkedIn presence.
   - Active job seekers who want higher-quality job discovery and matching with less manual effort.
8. Recruiter functionality is outside MVP 1.
9. Automation is a core value proposition, not an optional add-on.
10. Copilot mode is the recommended default: AI prepares and automates routine work while high-impact public actions can require user approval.
11. LinkedIn-side actions must use permitted/official integrations where available. The product must not depend on browser automation, credential sharing, CAPTCHA bypass, or private LinkedIn endpoints.

## Documents

| File | Purpose |
|---|---|
| `01-product-vision.md` | Product strategy, problem, value proposition, positioning, business model |
| `02-mvp-prd.md` | MVP requirements, scope, user stories, acceptance criteria, success metrics |
| `03-personas-jtbd-user-journey.md` | Target users, jobs-to-be-done, pain points, journeys |
| `04-information-architecture-user-flows.md` | Navigation, module structure, core flows |
| `05-screen-requirements-ux-states.md` | Screen-level requirements and UX states |
| `06-system-architecture-data-model.md` | Initial technical architecture and domain/data model |
| `07-ai-automation-specification.md` | AI agents, context model, structured outputs, automation rules |

## How AI Coding Agents Should Use These Documents

AI coding agents should:

- Treat `02-mvp-prd.md` as the primary source of product scope.
- Treat `04-information-architecture-user-flows.md` and `05-screen-requirements-ux-states.md` as the source of truth for product behavior and UX.
- Treat `06-system-architecture-data-model.md` as the baseline architecture, not as an immutable implementation detail.
- Treat `07-ai-automation-specification.md` as the source of truth for AI behavior, automation levels, triggers, approval requirements, and failure handling.
- Never add major features not listed in the MVP scope without an explicit product decision.
- Prefer small, reversible architectural decisions.
- Create Architecture Decision Records (ADRs) when changing a major architectural assumption.
- Add detailed API docs, DB migrations, test plans, observability, deployment, security, and runbooks during implementation.

## Recommended Development Order

1. Authentication and account setup
2. LinkedIn URL onboarding
3. OpenAI research + structured Career Profile generation
4. Profile review/edit/confirmation
5. Career goals and automation preferences
6. Dashboard foundation
7. Profile analysis and recommendations
8. Weekly AI plan
9. Content strategy and post generation
10. Content calendar and publishing workflow
11. Job discovery/match workflow
12. Application tracker
13. Notifications/digests
14. Automation Center
15. Analytics and learning loop

## Scope Control

If a requested feature is not clearly covered by these documents, the AI coding agent should:
1. Check whether it is required to complete an existing documented flow.
2. If yes, implement the minimum necessary behavior.
3. If no, record it as a future enhancement instead of silently expanding MVP scope.
