# Product Vision & Business Brief

## 1. Product Name

Working product name: **JB AI Career Agent**. Repository codename: **LinkiGen**. The final commercial name may change; this document and the rest of the pack use "JB AI Career Agent" consistently until it does. (Recorded assumption — see `00-README.md`.)

## 2. Product Vision

Create an AI-powered career operating system for busy professionals that continuously improves their professional positioning, LinkedIn content presence, and job-search efficiency with minimal manual effort.

The user should not need to repeatedly ask the system what to do. The product understands the user's professional context, goals, preferences, previous actions, and outcomes, then proactively prepares the next best actions.

## 3. Problem Statement

Many professionals struggle to maintain a strong LinkedIn presence and job-search process because:

- They are busy with full-time work.
- They do not know what to post.
- They do not post consistently.
- Their profile is not aligned with their career goals.
- They spend too much time searching through irrelevant jobs.
- They do not know which opportunities are actually a strong fit.
- They repeat the same manual tasks every week.
- Existing AI writing tools generate content but do not manage the whole career workflow.
- Existing job tools often focus on job discovery but not professional branding and career positioning.

## 4. Target Customers

### Primary Segment A — Busy Professionals

Professionals who want stronger visibility and credibility on LinkedIn but do not have time to consistently plan, write, schedule, and analyze content.

Examples:
- Software engineers
- Designers
- Product professionals
- Consultants
- Marketers
- Managers
- Specialists
- Freelancers

### Primary Segment B — Active Job Seekers

Professionals who want a better job but want AI to reduce the time spent discovering, evaluating, and tracking opportunities.

## 5. Jobs To Be Solved

The product helps users answer:

- What should I improve in my professional profile?
- What should I do this week?
- What should I post on LinkedIn?
- Can the system prepare my content so publishing takes one click instead of a blank page?
- Which jobs are actually worth my time?
- How strong is my match for a role?
- What should I improve before applying?
- Is my career/profile/content strategy improving over time?

## 6. Core Value Proposition

> Understand my professional profile once, then automatically manage the repetitive work required to grow my professional presence and discover better career opportunities.

## 7. Product Positioning

The product is positioned as:

**An AI Career Agent**

It is not positioned only as:
- a LinkedIn post generator,
- a social media scheduler,
- a job board,
- a resume builder,
- or a LinkedIn automation bot.

LinkedIn is an important channel and integration, but the product owns the user's broader career context, not just their LinkedIn presence.

## 8. Primary Differentiation

The MVP combines four connected systems:

1. Professional profile intelligence
2. Weekly career planning
3. LinkedIn content preparation and publishing
4. Job opportunity intelligence

The differentiator is the closed-loop model:

`Career Profile → Goals → Weekly Actions → Content/Jobs → Outcomes → Learning → Better Next Plan`

## 9. Automation Philosophy

Automation is central to the product, applied consistently across three levels (`docs/07` §7):

### Assistant
AI recommends what should happen. The user performs the action.

### Copilot
AI prepares the work. The user approves high-impact actions. The system executes what is permitted without approval.

### Autopilot
The system performs permitted, recurring actions automatically.

**Default MVP mode: Copilot.**

The product avoids requiring the user to repeatedly provide context it has already stored.

**What "automated" means at MVP 1.** Content preparation — ideas, drafts, editing, scheduling onto the calendar — is fully automated at Copilot today. Automated *publishing to LinkedIn* is designed behind an interface but ships in MVP 1 with a manual confirm-and-publish step, because it depends on LinkedIn partner approval the product does not yet hold (`ADR-006`). This is a launch-scope fact, not a positioning compromise: the manual step takes seconds, and every other part of the content loop — deciding what to say and preparing it — is where the time was actually being spent.

## 10. Onboarding Strategy

The product minimizes onboarding friction.

Primary flow:

`LinkedIn URL → AI web research → Structured professional data → User review → Career Profile`

The system researches the maximum publicly available professional information it can find and normalizes it into the Career Profile (`ADR-005`).

If essential information is unavailable:
- Ask only for the missing fields required for MVP features.
- Offer resume/CV upload as fallback or enrichment.

The application does not claim it can always import a complete LinkedIn profile.

## 11. Business Model Hypothesis

Values below are a **hypothesis**, owned by this document, and revisable without changing any specification. The quota *mechanism* enforcing them is fixed in `11-billing-entitlements.md` and `billing/plans-and-entitlements`.

### Free
- Profile import, review, and confirmation
- Basic profile analysis
- Limited weekly plans
- Limited post generation
- Manual job analysis
- Manual publishing (always available, on every plan)

### Pro — hypothesis ≈ $12/month
- Full weekly planning
- AI content strategy
- More post generation
- Content scheduling and calendar
- Automated job discovery and digests
- Career analytics

### Pro+ — hypothesis ≈ $24/month
- Higher generation ceilings
- Autopilot automation level
- Advanced career intelligence
- Resume/interview capabilities (future)

### Recruiter / B2B
Future phase only. Not part of MVP 1.

See `11-billing-entitlements.md` §3 for the concrete quota matrix these tiers imply, and §9 for the pricing questions still open.

## 12. MVP Business Objective

Validate whether busy professionals will repeatedly use and pay for a system that:

1. Understands their career profile automatically.
2. Tells them what to do each week.
3. Prepares most of their LinkedIn content.
4. Simplifies scheduling, and removes the manual publishing step once LinkedIn API access is available.
5. Finds and prioritizes relevant opportunities.
6. Reduces time spent on career maintenance.

## 13. MVP Success Signals

The MVP is promising if users:

- Complete onboarding with low friction.
- Confirm the generated Career Profile.
- Return weekly.
- Complete or approve AI-generated tasks.
- Approve and manually publish generated LinkedIn content.
- Save/apply to recommended jobs.
- Enable at least one automation.
- Report meaningful time saved.
- Convert from free to paid.

## 14. Long-Term Vision

Future product expansion may include:

- Automated LinkedIn publishing, once partner API access is secured (`ADR-006`)
- Deeper LinkedIn analytics
- Multi-network professional presence
- Resume optimization
- Interview preparation
- Networking assistance
- Company intelligence
- Recruiter/talent marketplace
- Recruiter SaaS
- Candidate discovery
- ATS integrations
- Portfolio/GitHub/Behance/Dribbble integrations
- Career skill-gap planning
- Long-term career progression intelligence

These are not MVP commitments.
