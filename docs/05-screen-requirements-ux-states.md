# Screen Requirements & UX States

> Behavior is normative in `openspec/specs/<capability>/spec.md`. This document owns screen composition and the UX state inventory. Error states cite codes from `16-error-catalog.md` — no screen invents its own error text; it uses that code's user message and recovery. Each screen names the capability that governs its behavior.

## 1. Global UX Requirements

The interface optimizes for busy users.

Principles:
- Show priorities before analytics.
- Prefer action cards over large dashboards full of metrics.
- Use progressive disclosure.
- Keep AI output editable.
- Clearly separate "AI prepared" from "AI executed."
- Clearly identify approval-required actions.
- Display failures prominently but calmly.
- Avoid blank-chat-first experiences.
- Every error state shows the `16-error-catalog.md` user message for its code, not ad hoc copy.

## 2. Screen: Sign Up / Sign In

**Governs:** `identity/account-access`

### Required
- Email, password, verification flow, forgot password, terms/privacy acknowledgement

### States
| State | Detail |
|---|---|
| Default | |
| Submitting | |
| `INVALID_CREDENTIALS` | Single generic message — never reveals which field was wrong |
| Registration success | Generic confirmation regardless of whether the email already existed |
| `EMAIL_VERIFICATION_REQUIRED` | Shown when an unverified account attempts to sign in |
| `ACCOUNT_THROTTLED` | Shows `retry_after` |
| Success | |

## 3. Screen: LinkedIn Profile Import

**Governs:** `career/profile-import`

### Required
- LinkedIn URL field
- Primary CTA: `Build My Career Profile`
- Short explanation of what happens, and a privacy disclosure that public sources will be searched and an AI provider will process the result — shown **before** submission
- Resume/CV fallback link, always visible, not only after failure

### Loading State
Real stages, matching the `08-api-contract.md` §6 operation `stages`:
- Finding professional information
- Structuring experience and skills
- Checking profile completeness
- Preparing review

No synthetic percentage.

### Error States
| Code | Recovery shown |
|---|---|
| `INVALID_LINKEDIN_URL` | Inline correction |
| `LINKEDIN_URL_ALREADY_CLAIMED` | Support/verification path |
| `RESEARCH_NO_DATA_FOUND` | Retry, upload CV, manual entry |
| `RESEARCH_FAILED` | Retry, upload CV, manual entry |
| `RESEARCH_QUOTA_EXCEEDED` | Upgrade path; CV and manual entry remain available regardless of plan |
| `CV_FILE_UNSUPPORTED` / `CV_FILE_TOO_LARGE` / `CV_FILE_INVALID` | Re-upload guidance |

## 4. Screen: Career Profile Review

**Governs:** `career/career-profile`

### Required Sections
Basic info, current role, experience, education, skills, technologies, projects, certifications, professional focus.

### Required UI
- Completeness indicator (computed, not claimed)
- Edit controls; add/delete item
- Per-field confidence indicator, visually distinct from a user-authored (highest-authority) value
- Conflict callouts, showing both candidate values with their sources when research found contradictory data
- Missing-information callouts naming which downstream feature needs each field
- CTA: `Confirm Profile`

### States
| State | Detail |
|---|---|
| High / partial completeness | |
| Conflicting data | Both values shown with sources; user chooses |
| `PROFILE_INCOMPLETE` | Blocks confirmation; names each missing field |
| Saved | |
| Unsaved changes | Warns before navigating away; preserves edits locally where the client allows |

## 5. Screen: Career Goal Setup

**Governs:** `career/goals-and-analysis`

### Required
Primary goal, target role(s), preferred location, remote preference, career direction, optional salary/industry/seniority.

### UX
One screen or a short stepper. Optional fields are genuinely skippable — `GOAL_REQUIRED` only blocks on the primary goal.

## 6. Screen: Automation Setup

**Governs:** `automation/automation-control`

### Presets
Assistant, Copilot (default), Autopilot.

### Required Controls
Content automation, career plan, job discovery, digests, and — since MVP 1 publishes manually (`ADR-006`) — no "publishing approval" toggle is offered here; approval for public publishing is a fixed default, not a first-run choice (see `docs/03` Stage 6).

### CTA
`Start My Career Agent`

### State
Selecting a preset after an individual override was already set shows exactly which overrides will be replaced before saving.

## 7. Screen: Dashboard

**Governs:** cross-cutting; surfaces `automation/automation-control` approvals first

### Purpose
Answer: **What matters today?**

### Priority Order
1. Approval Required *(promoted above Today's Actions — an expiring approval is the most time-sensitive item on the dashboard)*
2. Today's Actions
3. Weekly Progress
4. Career/Profile Score
5. Upcoming Content
6. Strong Job Matches
7. Automation Status
8. Recent Outcomes

### Empty States
| Situation | Copy |
|---|---|
| First week | "Your first weekly plan is being prepared." |
| No job matches, source configured | "No strong matches yet. Your agent will continue searching." |
| No job source configured | "Automatic job discovery isn't set up yet. Analyze a job manually any time." (`DISCOVERY_UNAVAILABLE`) |
| No upcoming content | "Your next content ideas are being prepared." |
| Automation paused | Dashboard header states the agent is paused, with a resume action |

## 8. Screen: Career Profile

**Governs:** `career/career-profile`

### Required
Profile overview, career score, sections from Profile Review, re-run/enrich option, last updated, edit mode.

### Profile Refresh States
| State | Copy |
|---|---|
| Changes proposed | "We found new professional information. Review changes." |
| Change conflicts with a user edit | Both values shown; the user's prior edit is kept by default |
| Nothing material found | No notification, no banner — only the last-researched timestamp updates |

## 9. Screen: Recommendations

**Governs:** `career/goals-and-analysis`

### Required
Cards with priority, problem, why it matters, recommended change, estimated effort, and — where the recommendation concerns rewritable text — suggested replacement text the user can apply directly.

### Actions
Copy, complete, dismiss, apply suggested text, regenerate where applicable.

### State
`ANALYSIS_UNAVAILABLE` shows the last successful analysis with its generation date, not a blank screen.

## 10. Screen: Weekly Plan

**Governs:** `planning/weekly-plan`

### Required
Week range (in the user's timezone), progress, tasks grouped by day or priority, task type, estimated time, automation indicator (AI-prepared vs. AI-executed vs. needs approval), status.

### Actions
Complete, skip, reschedule, replace/regenerate.

### Empty
"Your next weekly plan will be generated automatically."

### Error
`PLAN_GENERATION_FAILED` — shown on the dashboard and in automation history, with a manual-regenerate action.

## 11. Screen: Content Ideas

**Governs:** `content/content-strategy`

### Required
Content pillars, topic distribution, suggested ideas, reason/topic source.

### Actions
Generate draft, save, dismiss, regenerate.

### State
Thin profile evidence: fewer ideas shown, with a note naming what profile additions would improve idea quality — never silently backfilled with unsupported material.

## 12. Screen: Post Editor

**Governs:** `content/post-authoring`

### Required
Post text editor, topic, tone, status, schedule date/time (in the user's timezone), AI actions, preview, remaining `post_generation` quota for the period (`11-billing-entitlements.md` §4 — AI edits are metered).

### AI Actions
Regenerate, shorten, expand, improve hook, change tone — each preserves the prior version for one-click revert.

### Primary actions
Save Draft, Approve, Schedule, Copy.

### Warning
`UNACKNOWLEDGED_CLAIM_FLAGS` — "Review this statement before publishing." blocks Approve until each flagged statement is acknowledged.

### State
Editing an already-Approved post visibly returns it to Needs Approval.

## 13. Screen: Content Calendar

**Governs:** `content/scheduling-and-publishing`

### Required
Weekly/monthly view or schedule list, status indicators, post title/topic, publish time in the user's timezone.

### Actions
Open, edit, reschedule, approve.

### Publishing States
| State | Copy / behavior |
|---|---|
| Manual path ready | "Ready to publish" with copy + open-LinkedIn actions |
| `PUBLISH_FAILED` | "Publishing failed" prominently, with retry and manual fallback |
| `PUBLISH_AUTHORIZATION_EXPIRED` | Integration flagged as needing reconnection; affected posts show the manual path |
| `SCHEDULE_IN_PAST` | Inline rejection when rescheduling into an elapsed time |

## 14. Screen: Job Matches

**Governs:** `jobs/job-discovery`

### Required
Per card: role, company, location, match score, recommendation, top matching skills, source.

### Filters
Minimum match, location, remote, saved.

### States
| State | Copy |
|---|---|
| No strong matches | "No strong matches yet." |
| No source configured | "Automatic job discovery isn't set up yet." with a manual-analysis CTA (`DISCOVERY_UNAVAILABLE`) — never an unexplained empty list |

## 15. Screen: Analyze Job

**Governs:** `jobs/job-analysis`

### Required
Job URL, job description textarea, Analyze CTA. At least one input is required (`JOB_INPUT_REQUIRED`).

### Errors
| Code | Recovery |
|---|---|
| `JOB_URL_UNREADABLE` | Prompt to paste the description instead |
| `NOT_A_JOB_POSTING` | Explain and ask the user to check the input |
| `JOB_ANALYSIS_FAILED` | Retry; submitted content is preserved |
| `PROFILE_NOT_CONFIRMED` | Directs to profile confirmation before analysis runs |

### Result
Match score, recommendation, skills comparison, missing requirements, strengths, suggested preparation, Save/Apply/Skip.

## 16. Screen: Job Detail

**Governs:** `jobs/job-analysis`, `jobs/application-tracker`

### Required
Job information, match breakdown, AI explanation, career alignment, missing skills, preparation recommendations, source URL (flagged if possibly expired).

### Actions
Save, mark applied, skip, add note.

## 17. Screen: Applications

**Governs:** `jobs/application-tracker`

### Required
Pipeline/list across Saved, Applied, Interview, Offer, Rejected, Archived.

### Job Record
Role/company, match score, status, date, notes, next action — flagged when a next-action date has passed.

### Empty
Explains how applications get created and links to job matches and manual analysis.

## 18. Screen: Automation Center

**Governs:** `automation/automation-control`

### Required Sections

**Content:** weekly strategy, generate ideas, write drafts, schedule, publish (manual in MVP 1; shown as such, not as a broken toggle), performance optimization.

**Career:** weekly plan, profile health check, profile refresh.

**Jobs:** job discovery (shows `DISCOVERY_UNAVAILABLE` with reason if no source is registered), match analysis, minimum match threshold, digest frequency.

**Notifications:** approval alerts, strong matches, weekly report, failures.

### Each Automation Row
Toggle, description, frequency, approval requirement, last run, optional next run, and — when blocked — the exact missing precondition (`AUTOMATION_PRECONDITION_UNMET`) or plan requirement (`AUTOMATION_NOT_IN_PLAN`).

### Suspension State
`AUTOMATION_SUSPENDED` after repeated failure — shown with the reason and an explicit re-enable action, never silently reactivated.

## 19. Screen: Notifications

**Governs:** `notifications/notifications-and-digests`

### Types
Action required, automation success, automation failure, opportunity, report, profile update.

### Actions
Mark read, open related item.

## 20. Screen: Settings

Sections: Account, Career preferences, Content preferences, Job preferences, Notifications, Integrations, Billing, Data/privacy.

### Data/Privacy
Export request and status (`identity/account-access`), account deletion with the immediate-effects explanation from `04-information-architecture-user-flows.md` §20b.

### Billing
Current plan, usage against every quota with reset date, upgrade/downgrade, payment method (`billing/plans-and-entitlements`).

## 21. New Screen: Pending Profile Changes

**Governs:** `career/career-profile`

### Purpose
Review changes a refresh proposed, per change, before any reach the canonical profile.

### Required
Each proposed change shows the current value, the proposed value, its source, and — when it conflicts with a prior user edit — that conflict explicitly, with the user's value kept by default.

### Actions
Accept, reject, per change. Reject records the evidence hash so an identical proposal does not resurface.

### States
| State | Detail |
|---|---|
| Loading | Skeleton while pending change sets are fetched |
| Empty | "No pending changes." |
| Error | `AI_OUTPUT_INVALID` on the refresh run — previous canonical profile is untouched, retry offered |
| Permission Required | Not applicable — this screen has no external connection to authorize |

## 22. New Screen: Approval Queue

**Governs:** `automation/automation-control`

### Purpose
One place to find every pending approval, not only via notifications.

### Required
Each request shows the action, the entity it affects, why approval is required, and time remaining before `APPROVAL_EXPIRED`.

### Actions
Approve, reject.

### States
| State | Detail |
|---|---|
| Loading | Skeleton while pending requests are fetched |
| Empty | "Nothing needs your approval right now." |
| Error | `APPROVAL_ALREADY_RESOLVED` — shown if the user opens a request another session already resolved |
| Expired | `APPROVAL_EXPIRED` — shown with what did not happen as a result |
| Permission Required | Not applicable — approving is the permission grant itself |

## 23. New Screen: Usage & Billing

**Governs:** `billing/plans-and-entitlements`

### Required
Every entitlement with current usage, limit, and reset date; plan comparison; upgrade/downgrade actions; payment method.

### States
| State | Copy |
|---|---|
| Loading | Skeleton while usage and subscription status are fetched |
| Empty | Not applicable — every account has a plan, even Free |
| Approaching limit | Shown once per entitlement per period at 80% usage |
| Error: `QUOTA_EXCEEDED` | Names the limit and reset date; upgrade CTA |
| Error: Payment failed, in grace period | Shows days remaining at full access |
| Error: Past grace period | Explains the account is on Free, with data intact |
| Permission Required | `BILLING_PROVIDER_UNAVAILABLE` — usage shown from last known state; entitlements never revoked on this basis |

## 24. New Screen: Data Export & Account Deletion

**Governs:** `identity/account-access`, `platform/data-privacy-and-retention`

### Export
Request button, status (queued/ready), signed download link with its expiry shown, `EXPORT_FAILED` retry.

### Deletion
Confirmation step stating the immediate effects (automation halted, sessions revoked, scheduled content cancelled) before the request is submitted. Post-request: erasure window shown, cancellation available within it (`DELETION_ALREADY_PENDING` if attempted twice).

### States
| State | Detail |
|---|---|
| Loading | Skeleton while export/deletion status is fetched |
| Empty | Default state — no export or deletion in progress |
| Error | `EXPORT_FAILED` (retry), `EXPORT_LINK_EXPIRED` (request a new export), `DELETION_ALREADY_PENDING` |
| Permission Required | Re-authentication required before deletion is confirmed, since it is irreversible after the erasure window |

## 25. New Screen: Automation History

**Governs:** `automation/automation-control`

### Purpose
Let the user see what the agent actually did, not just what it is configured to do.

### Required
Chronological list of runs: automation, trigger, outcome (succeeded/partial/failed/skipped), timestamp, and — for any action performed publicly as the user — the linked approval record.

### Filters
By automation, by outcome, by date range.

### Retention
Visible for the plan's retention window (`11-billing-entitlements.md` §3 — 30 days Free, 12 months Pro/Pro+).

### States
| State | Detail |
|---|---|
| Loading | Skeleton while run history is fetched |
| Empty | "No automation activity yet." — shown for a new account before any scheduled run has fired |
| Error | Failed runs render inline (per row) with their `error_code`, not as a page-level error — one failed run must not hide the rest of the history |
| Permission Required | Not applicable — this is a read-only history view |

## 26. Required Global UX States

Every async screen or component considers:

| State | Requirement |
|---|---|
| **Loading** | Skeleton or a meaningful task state — never a bare spinner for an operation with known stages |
| **Empty** | Explains why, and what happens next |
| **Error** | Uses the `16-error-catalog.md` message and recovery for its code |
| **Partial Success** | Shows what succeeded and what did not — `partial` is a first-class outcome, not an error (`08` §6) |
| **Success** | Confirms meaningful completed actions |
| **Disabled** | Explains the exact requirement — precondition or plan — that would enable it |
| **Permission Required** | Names exactly what connection or access is missing |
| **Offline/Network Failure** | Preserves user edits locally where practical |

## 27. AI-Specific UX States

| State | Copy |
|---|---|
| Generating | "Preparing your weekly plan..." (or the relevant task-specific phrase, tied to real operation stages) |
| Low Confidence | "We found this information, but it may be outdated." |
| Conflict | "We found two different job titles. Choose the correct one." |
| `AI_OUTPUT_INVALID` / `AI_PROVIDER_UNAVAILABLE` | "We couldn't generate this right now. Retry." — non-AI features stay usable |
| `AI_INSUFFICIENT_CONTEXT` | Feature-specific: names what profile information would help |
| User Correction | Silently updates canonical context so the same mistake is not repeated — no extra confirmation dialog needed beyond the save itself |

## 28. Automation-Specific UX States

Enabled, Disabled, Paused, Waiting for approval, Scheduled, Running, Completed, Failed, Retrying, **Suspended** (after repeated failure — requires explicit re-enable).

The product never presents a failed or skipped automation as successful. A quota-skipped run is visually distinct from a failed one — nothing malfunctioned.
