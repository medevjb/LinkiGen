# Screen Requirements & UX States

## 1. Global UX Requirements

The interface should optimize for busy users.

Principles:
- Show priorities before analytics.
- Prefer action cards over large dashboards full of metrics.
- Use progressive disclosure.
- Keep AI output editable.
- Clearly separate "AI prepared" from "AI executed."
- Clearly identify approval-required actions.
- Display failures prominently but calmly.
- Avoid blank-chat-first experiences.

## 2. Screen: Sign Up / Sign In

### Purpose
Create or access account.

### Required
- Email
- Password
- Verification flow
- Forgot password
- Terms/privacy acknowledgement

### States
- Default
- Submitting
- Invalid credentials
- Email already exists
- Verification required
- Success

## 3. Screen: LinkedIn Profile Import

### Purpose
Start professional profile creation with minimum friction.

### Required
- LinkedIn URL field
- Primary CTA: `Build My Career Profile`
- Short explanation of what happens
- Privacy/data-use explanation
- Resume/CV fallback link

### Loading State
Show meaningful progress stages, e.g.:
- Finding professional information
- Structuring experience and skills
- Checking profile completeness
- Preparing review

Do not show fake percentage unless backed by measurable progress.

### Error States
- Invalid URL
- No useful public data found
- AI request failed
- Temporary rate limit

### Recovery
- Retry
- Upload CV
- Add information manually

## 4. Screen: Career Profile Review

### Purpose
Establish trusted canonical professional data.

### Required Sections
- Basic info
- Current role
- Experience
- Education
- Skills
- Technologies
- Projects
- Certifications
- Professional focus

### Required UI
- Completeness indicator
- Edit controls
- Add/delete item
- Confidence/uncertainty indicator where useful
- Missing information callouts
- CTA: `Confirm Profile`

### States
- High completeness
- Partial completeness
- Conflicting data
- Missing required data
- Saved
- Unsaved changes

## 5. Screen: Career Goal Setup

### Required
- Primary goal
- Target role(s)
- Preferred location
- Remote preference
- Career direction
- Optional salary/industry/seniority

### UX
Keep one screen or short stepper.
Do not create a long questionnaire.

## 6. Screen: Automation Setup

### Purpose
Let users choose how much work the system can do.

### Presets
- Assistant
- Copilot
- Autopilot

### Default
Copilot

### Required Controls
- Content automation
- Career plan
- Job discovery
- Digests
- Publishing approval

### CTA
`Start My Career Agent`

## 7. Screen: Dashboard

### Purpose
Answer:
**What matters today?**

### Priority Order
1. Today's Actions
2. Weekly Progress
3. Approval Required
4. Career/Profile Score
5. Upcoming Content
6. Strong Job Matches
7. Automation Status
8. Recent Outcomes

### Primary CTA
Contextual, e.g.:
- Review today's tasks
- Approve post
- Review job matches

### Empty States
First week:
"Your first weekly plan is being prepared."

No jobs:
"No strong matches yet. Your agent will continue searching."

No upcoming content:
"Your next content ideas are being prepared."

## 8. Screen: Career Profile

### Purpose
View and maintain canonical profile.

### Required
- Profile overview
- Career score
- Sections from Profile Review
- Re-run/enrich option
- Last updated
- Edit mode

### Profile Refresh State
If changes are detected:
"We found new professional information. Review changes."

## 9. Screen: Recommendations

### Required
Recommendation cards with:
- Priority
- Problem
- Why it matters
- Recommended change
- Estimated effort
- Action

Actions:
- Copy
- Complete
- Dismiss
- Regenerate where applicable

## 10. Screen: Weekly Plan

### Required
- Week range
- Progress
- Tasks grouped by day or priority
- Task type
- Estimated time
- Automation indicator
- Status

### Actions
- Complete
- Skip
- Reschedule
- Replace/regenerate

### Empty
"Your next weekly plan will be generated automatically."

## 11. Screen: Content Ideas

### Required
- Recommended content pillars
- Topic distribution
- Suggested ideas
- Reason/topic source where useful

### Actions
- Generate draft
- Save
- Dismiss
- Regenerate

## 12. Screen: Post Editor

### Required
- Post text editor
- Topic
- Tone
- Status
- Schedule date/time
- AI actions
- Preview

### AI Actions
- Regenerate
- Shorten
- Expand
- Improve hook
- Change tone

### Primary actions
- Save Draft
- Approve
- Schedule
- Copy

### Warning
If unsupported claims are detected:
"Review this statement before publishing."

## 13. Screen: Content Calendar

### Required
- Weekly/monthly view or schedule list
- Status indicators
- Post title/topic
- Publish time

### Actions
- Open
- Edit
- Reschedule
- Approve

### Failed Post State
Clearly show:
`Publishing failed`
with retry or manual fallback.

## 14. Screen: Job Matches

### Required
For each card:
- Role
- Company
- Location
- Match score
- Recommendation
- Top matching skills
- Source

### Filters
Keep minimal:
- Minimum match
- Location
- Remote
- Saved

### Empty
"No strong matches yet."

## 15. Screen: Analyze Job

### Required
- Job URL
- Job description textarea
- Analyze CTA

At least one input is required.

### Result
- Match score
- Recommendation
- Skills comparison
- Missing requirements
- Strengths
- Suggested preparation
- Save/Apply/Skip

## 16. Screen: Job Detail

### Required
- Job information
- Match breakdown
- AI explanation
- Career alignment
- Missing skills
- Preparation recommendations
- Source URL

### Actions
- Save
- Mark Applied
- Skip
- Add Note

## 17. Screen: Applications

### Required
Simple pipeline/list:
- Saved
- Applied
- Interview
- Offer
- Rejected

### Job Record
- Role/company
- Match score
- Status
- Date
- Notes
- Next action

## 18. Screen: Automation Center

### Required Sections

#### Content
- Weekly strategy
- Generate ideas
- Write drafts
- Schedule
- Auto publish if supported
- Performance optimization

#### Career
- Weekly plan
- Profile health check
- Profile refresh

#### Jobs
- Job discovery
- Match analysis
- Minimum match threshold
- Digest frequency

#### Notifications
- Approval alerts
- Strong matches
- Weekly report
- Failures

### Each Automation Row
- Toggle
- Description
- Frequency
- Approval requirement
- Last run
- Optional next run

## 19. Screen: Notifications

### Types
- Action required
- Automation success
- Automation failure
- Opportunity
- Report
- Profile update

### Actions
- Mark read
- Open related item

## 20. Screen: Settings

Sections:
- Account
- Career preferences
- Content preferences
- Job preferences
- Notifications
- Integrations
- Billing
- Data/privacy

## 21. Required Global UX States

Every async screen/component should consider:

### Loading
Use skeleton or meaningful task state.

### Empty
Explain why empty and what happens next.

### Error
State what failed and what the user can do.

### Partial Success
Show what succeeded and what did not.

### Success
Confirm meaningful completed actions.

### Disabled
Explain requirement if disabled.

### Permission Required
Explain exactly what connection/access is missing.

### Offline/Network Failure
Preserve user edits locally where practical.

## 22. AI-Specific UX States

### Generating
"Preparing your weekly plan..."

### Low Confidence
"We found this information, but it may be outdated."

### Conflict
"We found two different job titles. Choose the correct one."

### AI Failure
"We couldn't generate this right now. Retry."

### User Correction
Corrections should update canonical context so the same mistake is not repeated.

## 23. Automation-Specific UX States

- Enabled
- Disabled
- Paused
- Waiting for approval
- Scheduled
- Running
- Completed
- Failed
- Retrying

The product should never present a failed automation as successful.
