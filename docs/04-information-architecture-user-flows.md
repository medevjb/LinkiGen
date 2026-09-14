# Information Architecture & User Flows

## 1. MVP Information Architecture

### Primary Navigation

- Dashboard
- Career
  - Career Profile
  - Recommendations
- Weekly Plan
- Content
  - Posts
  - Ideas
  - Calendar
- Jobs
  - Matches
  - Analyze Job
  - Applications
- Automation
- Notifications
- Settings

### Optional Utility Areas
- Help
- Billing
- Account

## 2. Navigation Principles

- Dashboard is the default authenticated landing page.
- "Weekly Plan" must remain easily accessible because it is a core habit feature.
- Automation must be a first-class destination, not hidden inside Settings.
- Content and Jobs are separate workspaces.
- Career Profile is the canonical professional context.
- Avoid deep navigation hierarchies in MVP.

## 3. Core Flow — Registration

`Landing → Sign Up → Verify Email → LinkedIn URL Onboarding`

Success:
User reaches professional profile import.

Failure states:
- Existing account
- Invalid email
- Verification expired
- Rate limited

## 4. Core Flow — LinkedIn URL Profile Import

`Enter LinkedIn URL → Validate → Start AI Research → Processing → Structured Profile Generated → Review`

### Valid URL
Proceed to research.

### Invalid URL
Show inline error:
"Enter a valid LinkedIn profile URL."

### Research Success
Show:
- Completeness
- Extracted sections
- Missing sections
- Continue to review

### Partial Success
Allow review; request only essential missing fields.

### Research Failure
Offer:
- Retry
- Resume/CV upload
- Manual essential fields

## 5. Core Flow — Career Profile Review

`Generated Profile → Review Sections → Edit → Confirm`

Sections:
- Identity
- Current position
- Experience
- Education
- Skills
- Projects
- Certifications
- Professional focus

User can:
- Edit
- Delete incorrect item
- Add missing item
- Confirm

On confirm:
Career Profile becomes canonical.

## 6. Core Flow — Goal Setup

`Primary Goal → Target Role → Location → Career Direction → Optional Preferences → Save`

Primary goal branches:

### Grow LinkedIn
Prioritize:
- Profile positioning
- Content strategy
- Posting consistency

### Find a Job
Prioritize:
- Target-role alignment
- Job discovery
- Matching
- application workflow

### Both
Balance both paths.

## 7. Core Flow — Automation Setup

`Select Preset → Review Individual Automations → Save`

Presets:
- Assistant
- Copilot
- Autopilot

Recommended default:
Copilot

Users can override individual settings.

## 8. Core Flow — First-Time Dashboard

After onboarding:

`Profile Confirmed + Goal Saved → Generate Initial Analysis → Generate Weekly Plan → Generate Content Strategy → Dashboard`

Dashboard should immediately show:
- Career Score
- Top 3 actions
- Weekly progress
- Upcoming content
- Job matches or job-analysis CTA
- Automation status

## 9. Weekly Plan Flow

`Weekly Trigger → AI Generates Plan → Dashboard Notification → User Reviews Tasks`

Task lifecycle:

`To Do → In Progress → Completed`

Alternative:
`To Do → Skipped`

User actions:
- Complete
- Skip
- Reschedule
- Replace/regenerate

Automated tasks should display:
- Automation status
- Whether action is already completed by system
- Whether approval is required

## 10. Content Strategy Flow

`Career Context → Generate Content Pillars → Generate Weekly Ideas → Draft Posts`

User can:
- Review strategy
- Edit topic preferences
- Regenerate ideas
- Select or allow system to select topics

## 11. Post Flow

`Idea → Draft Generated → Edit/Regenerate → Approve → Schedule → Publish → Analyze`

Statuses:
- Draft
- Needs Approval
- Approved
- Scheduled
- Published
- Failed

Editing an Approved post revokes the approval and returns it to Needs Approval — no unapproved text can reach LinkedIn (`content/post-authoring`).

### Manual publishing path — ships in MVP 1, every plan
`Approved → Scheduled → Reminder at Scheduled Time → Copy Post → Open LinkedIn → User Publishes → Mark Published`

If the user does not confirm within the reminder window, the system reminds once more and then leaves the post in its ready state — never silently marked Published or Failed.

### Automated publishing path — interface exists; not connected in MVP 1
`Approved → Scheduled → Background Publish Job (idempotent) → Published/Failed`

An expired or revoked authorization does not retry blindly: the integration is marked as needing reconnection and affected posts fall back to the manual path (`ADR-006`).

## 12. Content Calendar Flow

`Calendar → Select Post → View/Edit → Reschedule/Approve/Delete`

Calendar actions:
- Add draft
- Reschedule
- Open post
- Filter by status

Avoid complex social-media-management functionality in MVP.

## 13. Automatic Job Discovery Flow

`Scheduled Job Search → Registered JobSource Adapters → Normalize Jobs → Filter → AI Match → Rank → Save Top Matches → Notify`

System does not surface all collected jobs. Only useful matches above the user's threshold reach the user-facing list.

### No source configured
`Discovery Trigger → No JobSource Registered → Discovery Marked Unavailable → Manual Analysis Offered`

This is a defined, permanent product state for accounts with no job source connected, not an error (`jobs/job-discovery`). Manual analysis (§14) carries the job value proposition on its own in this state.

## 14. Manual Job Analysis Flow

`Paste Job URL/Description → Parse Job → Match Against CareerProfile → Show Score + Explanation`

Results:
- Strong Apply
- Apply
- Consider
- Skip

Actions:
- Save
- Mark applied
- Dismiss
- View recommendations

## 15. Job Match Detail Flow

`Match List → Job Detail → Match Breakdown → Recommendation → Action`

Detail should include:
- Company/title
- Source
- Match score
- Required skills
- User matches
- Missing skills
- Reasoning
- Suggested preparation
- Save/apply/skip

## 16. Application Tracker Flow

`Saved Job → Mark Applied → Interview → Offer/Rejected`

User can:
- Change status
- Add notes
- Add next step date
- Open original job URL

MVP does not require a complex CRM pipeline.

## 17. Automation Center Flow

`Automation → Category → Toggle/Configure → Save`

Categories:
- Content
- Career
- Jobs
- Notifications

Each automation should show:
- Name
- Description
- Status
- Frequency
- Approval mode
- Last run
- Next run where relevant

## 18. Notification Flow

`System Event → Notification Rule → In-App/Email → User Action`

Examples:
- Weekly plan ready
- Post needs approval
- Post failed
- Strong job match
- Profile research update
- Weekly report ready

## 19. Profile Refresh Flow

`Scheduled/Profile Refresh → Research → Compare Existing Profile → Detect Changes → Ask for Confirmation`

Never silently overwrite important career history.

## 20. Error Recovery Principles

Every failed AI/background operation should support at least one of:
- Retry
- Fallback
- Manual completion
- User notification

The user should never be left with an indefinite loading state.

## 20a. Data Export Flow

`Settings → Request Export → Queued → Notification: Export Ready → Signed Download Link → Download`

### Success
Export covers every personal data class except secrets (`10-security-privacy-compliance.md` §3). The link expires after the configured window.

### Failure
`Export Failed → Notify → Offer Retry`. No partial export is presented as complete.

Governed by `identity/account-access`.

## 20b. Account Deletion Flow

`Settings → Request Deletion → Confirm → Automation Halted Immediately → Sessions Revoked → Scheduled Content Cancelled → Erasure Window → Erasure Complete`

### Immediate effects (before the erasure window elapses)
- All automations disabled
- Scheduled publishing and queued jobs for the account cancelled
- Sessions revoked
- Any job already executing checks the deletion marker before persisting and discards its output if the marker is set

### Cancellation
`Within Erasure Window → Cancel Deletion → Account and Automation Restored`

### Completion
`Erasure Window Elapses → Data Erased or Anonymized → Confirmation Sent`

Governed by `identity/account-access` and `platform/data-privacy-and-retention`.

## 20c. Quota Exhaustion Flow

`Metered Action Requested → Entitlement Checked Before AI Call → Quota Exhausted → Refused With Limit + Reset Date → Upgrade Offered`

This check happens **before** any provider cost is incurred — the AI call never fires (`billing/plans-and-entitlements`).

### Automation variant
`Scheduled Automation Fires → Quota Exhausted → Run Skipped (not failed) → Notified Once Per Period → Resumes Automatically at Period Reset`

### Mid-batch variant
`Batch Operation Running (e.g. job matching) → Quota Exhausted Partway → Completed Items Persisted → Operation Reports Partial → Remainder Deferred`

Completed work from a partial batch is never discarded to make an accounting boundary tidy.

### Approaching a limit
`Usage Crosses 80% → Warned Once Per Entitlement Per Period`

## 20d. Billing Lifecycle Flow

`Free → Checkout → Provider-Hosted Payment → Webhook Confirms → Entitlements Upgraded Immediately`

### Downgrade
`Downgrade Requested → Takes Effect at Period End → Paid Access Continues to Period End → Excluded Automations Disabled With Explanation → No Data Deleted`

If the account is scheduling content above the new plan's entitlement, those posts are retained as Approved drafts with manual publishing available — not deleted or silently unscheduled.

### Payment failure
`Renewal Fails → Notify Immediately → 7-Day Grace Period at Full Entitlements → Provider Retries → Grace Period Elapses → Downgrade to Free (Data Intact)`

### Cancellation
`Cancel Requested → Paid Access Continues to Period End → Reverts to Free (Data Intact)`

### Billing provider unreachable
`Provider Unreachable → Entitlements Never Revoked on This Basis → Reconcile When Reachable`

Governed by `billing/plans-and-entitlements`.

## 21. Global Approval Logic

### No approval normally required
- Weekly plan generation
- Job matching
- Content idea generation
- Draft generation
- Performance analysis
- Internal recommendations

### Approval normally required
- Public post publishing by default
- Updating canonical profile from newly detected external data
- Other reputation-sensitive public actions

## 22. Flow Priority for Development

P0:
- Auth
- Profile import/review
- Goal setup
- Dashboard
- Weekly plan

P1:
- Content strategy
- Post generation
- Calendar
- Automation settings

P1:
- Job analysis/matching
- Application tracker

P2:
- Advanced analytics
- Deep optimization
