# MVP Product Requirements Document (PRD)

## 1. Product

**JB AI Career Agent — MVP 1**

## 2. Objective

Build a usable SaaS MVP that helps busy professionals and job seekers reduce the manual effort required to maintain their professional profile, plan LinkedIn content, follow a weekly career plan, and evaluate job opportunities.

## 3. Primary User Outcomes

The MVP must enable a user to:

1. Create a professional profile with minimal manual data entry.
2. Understand how well their current profile supports their career goals.
3. Receive a weekly AI-generated plan.
4. Receive AI-generated LinkedIn content aligned with their profile and goals.
5. Organize and schedule content.
6. Configure automation preferences.
7. Discover or analyze relevant job opportunities.
8. Understand job-match quality.
9. Track application status.
10. Receive useful reminders/digests without constantly opening the app.

## 4. MVP Scope

### 4.1 Authentication

Required:
- Sign up
- Sign in
- Sign out
- Email verification
- Password reset
- Secure session handling

Social authentication is optional for initial release.

### 4.2 Professional Profile Import

Required flow:

1. User enters their LinkedIn profile URL.
2. Backend validates URL format.
3. Backend calls OpenAI with web-search capability.
4. System researches maximum publicly available professional information.
5. System returns data matching the Career Profile schema.
6. Each important field should support confidence/provenance metadata where practical.
7. System calculates approximate profile completeness.
8. User reviews generated profile.
9. User edits incorrect fields.
10. User confirms the profile.
11. Confirmed profile becomes canonical application data.

Fallback:
- Resume/CV upload or manual completion for essential missing fields.

Restrictions:
- No LinkedIn credentials.
- No browser cookie collection.
- No CAPTCHA bypass.
- No private endpoint dependency.
- No promise of 100% LinkedIn profile extraction.

### 4.3 Career Profile

Required data domains:
- Identity
- Headline
- Location
- Current role
- Current company
- Experience
- Education
- Skills
- Technologies
- Projects
- Achievements where available
- Certifications where available
- Professional focus
- Public content themes
- Career signals
- Missing fields
- Confidence/completeness metadata

User must be able to:
- View
- Edit
- Confirm
- Re-run/enrich profile research later
- Add missing data manually

### 4.4 Career Goal Setup

Required:
- Primary goal:
  - Grow LinkedIn presence
  - Find a better job
  - Both
- Target role(s)
- Preferred location/remote preference
- Career direction:
  - Individual contributor
  - Leadership
  - Freelance/consulting
  - Not sure
- Optional job preferences:
  - Salary
  - Industry
  - Seniority
  - Employment type

Do not require nonessential fields during onboarding.

### 4.5 Profile Analysis

System must generate:
- Overall Career/Profile Score
- Category scores
- High-priority improvements
- Recommended actions
- Explanation for recommendations

Suggested scoring categories:
- Professional positioning
- Headline
- Experience clarity
- Skills relevance
- Projects/evidence
- Content presence
- Career-goal alignment

User actions:
- Copy recommendation
- Regenerate where applicable
- Mark completed
- Dismiss/skip
- Edit source profile data

### 4.6 Weekly AI Plan

Core MVP feature.

System must:
- Generate a weekly plan automatically.
- Use Career Profile, Career Goals, completed/skipped tasks, content history, and job activity.
- Prioritize high-value, low-effort actions.
- Estimate effort where reasonable.
- Assign suggested day/due date.
- Avoid generating duplicate/repetitive actions.

Task statuses:
- To Do
- In Progress
- Completed
- Skipped

User actions:
- Complete
- Skip
- Reschedule
- Regenerate/replace where appropriate

### 4.7 AI Content Strategy

System must generate:
- User-specific content pillars
- Topic distribution
- Weekly content ideas
- Recommended cadence

Inputs:
- Career Profile
- Goals
- Skills
- Projects
- Professional focus
- Public activity
- Previous content
- User preferences

### 4.8 AI LinkedIn Post Generation

System must:
- Generate complete LinkedIn post drafts.
- Use user context instead of generic prompts.
- Support tone preferences.
- Avoid inventing achievements or experiences.
- Prefer authentic professional experiences supported by profile/context.

Required actions:
- Generate
- Regenerate
- Edit
- Shorten
- Expand
- Change tone
- Improve hook
- Save draft
- Approve
- Schedule
- Copy

### 4.9 Content Calendar

Required:
- Week/month calendar view or simple scheduled list
- Draft/approved/scheduled/published status
- Rescheduling
- Editing
- Manual publish fallback

Status:
- Draft
- Needs Approval
- Approved
- Scheduled
- Published
- Failed

### 4.10 LinkedIn Publishing

**MVP 1 ships the manual publishing path only** (`ADR-006`). Automated publishing through the LinkedIn API is designed behind a `PublishingProvider` interface and ships dark; MVP 1 launch has no dependency on LinkedIn partner approval. See `content/scheduling-and-publishing`.

**Manual path (ships in MVP 1, every plan):**
- At the scheduled time, the user is notified that the post is ready
- Copy post
- Open LinkedIn
- Confirm published

**Automated path (interface exists; not connected in MVP 1):**
- Publish/schedule through permitted LinkedIn API access, once available
- Uses an idempotency key so a retried job never publishes twice
- An expired or revoked authorization is never blindly retried — it falls back to manual and prompts reconnection

The product must not depend on unauthorized browser automation, LinkedIn credentials, or browser cookies.

Default MVP behavior for public posting:
- Approval required in Copilot mode, regardless of which path performs the publish.

### 4.11 Job Discovery / Job Analysis

**Manual analysis is the guaranteed MVP path** and works on every plan without any external dependency. **Automatic discovery** runs behind a pluggable `JobSource` adapter interface; with no source configured, discovery reports itself unavailable rather than showing an empty list (`ADR-006`, `jobs/job-discovery`).

#### Automatic Discovery
From one configured, permitted, public or licensed job source. LinkedIn job listings are never scraped.

#### Manual Analysis
User provides:
- Job URL, and/or
- Job description

System produces:
- Overall match score
- Skill match
- Experience match
- Seniority fit
- Location fit
- Career fit
- Missing requirements
- Strengths
- Recommendation:
  - Strong Apply
  - Apply
  - Consider
  - Skip
- Suggested changes before applying

### 4.12 Job Filtering

Automatic filtering should consider:
- Location
- Remote preference
- Salary where known
- Seniority
- Critical required skills
- Employment type
- Posting freshness
- Duplicate roles
- Career-goal alignment

### 4.13 Application Tracker

Statuses:
- Saved
- Applied
- Interview
- Offer
- Rejected
- Archived/Skipped

Required actions:
- Save
- Mark applied
- Update status
- Add notes
- Open source job URL

### 4.14 Automation Center

Required settings:

#### Content
- Weekly content strategy
- Generate post ideas
- Write post drafts
- Schedule content
- Auto publish if supported
- Performance-based optimization

#### Career
- Weekly career plan
- Profile health check
- Profile refresh frequency

#### Jobs
- Job discovery
- Match analysis
- Minimum match threshold
- Daily/weekly job digest

#### Notifications
- Post approval
- Strong job match
- Weekly plan
- Weekly performance report
- Automation failure

### 4.15 Automation Modes

The system must support a conceptual automation level:

#### Assistant
Recommendations only.

#### Copilot
AI prepares; user approves important actions.

#### Autopilot
AI performs permitted routine actions automatically.

Default:
**Copilot**

### 4.16 Notifications & Digests

MVP should support:
- In-app notifications
- Email notifications for important events

Suggested digests:
- Weekly plan ready
- Daily or weekly job matches
- Post approval needed
- Weekly career/content performance summary

### 4.17 Dashboard

Dashboard must answer:
**What should I do now?**

Priority sections:
- Today's actions
- Weekly progress
- Career/Profile score
- Upcoming content
- Strong job matches
- Automation status
- Recent outcomes

## 5. Non-Goals — MVP 1

Do not build:
- Recruiter dashboard
- Candidate sourcing marketplace
- ATS
- Automated LinkedIn connection requests
- Automated LinkedIn DMs
- Automated likes
- Automated comments without an approved API path
- Mass LinkedIn profile crawling
- Resume builder
- Full interview assistant
- Company CRM
- Team accounts
- Browser extension
- Complex BI analytics
- Enterprise permissions
- Multi-network social publishing

## 6. Core User Stories

### Profile Import
As a busy professional, I want to paste my LinkedIn URL so the system can build my professional profile without forcing me to re-enter my career history.

### Profile Review
As a user, I want to review and correct AI-researched information before it affects future recommendations.

### Weekly Plan
As a busy user, I want the system to automatically prepare my highest-value career actions each week.

### Content
As a professional, I want the system to automatically suggest and draft posts relevant to my real expertise.

### Scheduling
As a busy user, I want approved content to be scheduled without repeatedly configuring publishing details.

### Job Match
As a job seeker, I want AI to rank opportunities so I only spend time on jobs that are likely to fit.

### Automation
As a user, I want control over what the AI can do automatically and what requires my approval.

## 7. Functional Principles

- Avoid repeated manual input.
- Context should persist.
- AI output must be editable.
- Public/reputation-sensitive actions should be transparent.
- Users should understand why important recommendations were made.
- AI must not invent professional claims.
- Automation failures must be visible.
- Users must be able to pause automation.
- Every background action should be auditable where practical.

## 8. Acceptance Criteria — MVP Level

MVP is functionally ready when a new user can complete the fifteen steps below. The detailed, testable acceptance criteria for each step — including its error, empty, and partial-success paths — live in the referenced capability spec, not here. This table is the map from scope to spec; do not restate spec content in this document.

| # | User can... | Governing capability |
|---|---|---|
| 1 | Register | `identity/account-access` |
| 2 | Submit a LinkedIn URL | `career/profile-import` |
| 3 | Receive a structured Career Profile | `career/profile-import` |
| 4 | Correct and confirm profile data | `career/career-profile` |
| 5 | Set career goals | `career/goals-and-analysis` |
| 6 | Reach the dashboard | `05-screen-requirements-ux-states.md` §7 |
| 7 | Receive a profile analysis | `career/goals-and-analysis` |
| 8 | Receive a weekly plan | `planning/weekly-plan` |
| 9 | Generate and approve a LinkedIn post | `content/post-authoring` |
| 10 | Place a post on the content calendar | `content/scheduling-and-publishing` |
| 11 | Configure automation preferences | `automation/automation-control` |
| 12 | Analyze or receive at least one job match | `jobs/job-analysis`, `jobs/job-discovery` |
| 13 | Save/update a job application | `jobs/application-tracker` |
| 14 | Receive an in-app or email notification | `notifications/notifications-and-digests` |
| 15 | View automation/task history sufficiently to understand what happened | `automation/automation-control` |

The full traceability table in `00-README.md` maps every scope section in this document to its governing capability, not just these fifteen steps.

## 9. Initial Success Metrics

Track:
- Onboarding completion rate
- LinkedIn URL research success rate
- Career Profile confirmation rate
- Time to first useful result
- Weekly active users
- Weekly plan completion rate
- Post approval/use rate
- Number of scheduled/published posts
- Job match views
- Save/apply conversion
- Automation enablement rate
- Paid conversion
- User-reported time saved

## 10. Risks

### Data Completeness
Public professional data varies significantly between users.

Mitigation:
- Completeness score
- Confidence
- User review
- CV/manual fallback

### AI Hallucination
AI could infer unsupported experience.

Mitigation:
- Source-aware prompting
- Explicit uncertainty
- User confirmation
- Never auto-create unsupported achievements

### LinkedIn Platform Dependency
Capabilities may depend on approved APIs.

Mitigation:
- Treat LinkedIn as a channel, not the canonical datastore.
- Maintain manual fallbacks.
- Do not depend on prohibited browser automation.

### Automation Trust
Users may fear AI posting incorrect content.

Mitigation:
- Default to Copilot.
- Approval workflow.
- Activity history.
- Easy pause/disable controls.

## 10a. Scenarios Resolved by This Revision

A scenario sweep of the original documentation pack found real gaps: situations the product would hit on day one with no defined behavior. Each is now a tested requirement in its governing capability, not an open question.

| Scenario | What was missing | Now governed by |
|---|---|---|
| **Duplicate LinkedIn URL claims** | Nothing stopped one user from importing a profile another account had already confirmed | `career/profile-import` — *Duplicate profile URL claims* |
| **Expired publishing authorization** | Undefined whether a failed publish should retry blindly | `content/scheduling-and-publishing` — *Authorization expired* |
| **Quota exhausted mid-batch** | Unclear whether partially completed AI work (e.g. job matching) was kept or discarded | `billing/plans-and-entitlements` — *Quota reached mid-batch* |
| **Downgrade below current usage** | No rule for a plan change that leaves the account holding more than its new quota allows | `billing/plans-and-entitlements` — *Subscription lifecycle* |
| **Account deletion with in-flight automation** | No rule preventing a running background job from writing results for a user who requested erasure | `identity/account-access` — *Account deletion* |
| **DST transitions on scheduled posts** | No rule for a scheduled time falling in a skipped or repeated hour | `content/scheduling-and-publishing` — *Timezone and DST correctness* |

## 11. Future Scope

Future items require separate approval and PRD updates.
