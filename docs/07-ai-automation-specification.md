# AI & Automation Specification

## 1. Purpose

AI and automation are core product capabilities. This document defines how the MVP should use AI safely, consistently, and predictably.

## 2. Core AI Context Model

All major AI features should use a shared logical context named:

**CareerContext**

CareerContext may include:
- Confirmed Career Profile
- Career Goals
- Job Preferences
- Content Preferences
- Automation Preferences
- Previous weekly tasks
- Completed/skipped tasks
- Content history
- Post-performance data where available
- Job-match history
- Application history
- User corrections
- User feedback
- Current date/time/timezone

The exact payload should be minimized per task. Do not send unnecessary personal data to every AI call.

## 3. AI Principles

AI must:
- Use confirmed user data as highest priority.
- Distinguish known facts from inference.
- Avoid inventing professional achievements.
- Avoid inventing employers, roles, projects, or skills.
- Return structured outputs for backend-controlled workflows.
- Be versioned.
- Be observable.
- Support retries/fallbacks.
- Store enough metadata to debug important failures.

## 4. Agent/Capability Definitions

These are logical agents/capabilities. They do not need to be separate services.

### 4.1 ProfessionalProfileResearchAgent

#### Goal
Research maximum publicly available professional information associated with a user-provided LinkedIn profile URL.

#### Inputs
- LinkedIn profile URL
- Optional known name
- Optional user-provided resume/CV
- Current date

#### Tools
- OpenAI web search
- File extraction if CV provided

#### Output
`RawProfessionalProfile`

Should include where available:
- Identity
- Headline
- Location
- Current role/company
- Experience
- Education
- Skills
- Technologies
- Projects
- Certifications
- Public content/activity themes
- Professional focus
- Conflicts
- Missing fields
- Confidence
- Sources/provenance metadata

#### Rules
- Use publicly available information only.
- Do not imply authenticated LinkedIn access.
- Do not invent missing data.
- Surface conflicts.
- Do not treat low-confidence third-party claims as confirmed facts.

### 4.2 CareerProfileNormalizer

#### Goal
Convert raw research into canonical application schema.

#### Inputs
- RawProfessionalProfile
- Existing confirmed CareerProfile if enrichment
- User corrections if available

#### Output
Structured `CareerProfileDraft`

#### Rules
- Normalize technology names.
- Remove obvious duplicates.
- Preserve source/confidence metadata.
- Never overwrite confirmed user corrections during refresh.

### 4.3 ProfileAnalyzerAgent

#### Goal
Evaluate profile strength relative to user's goals.

#### Inputs
- CareerContext

#### Output
- Overall score
- Category scores
- Priority recommendations
- Rationale
- Estimated effort
- Suggested replacement text where appropriate

#### Rules
- Recommendations must relate to user goals.
- Avoid generic advice if specific context exists.
- Limit high-priority recommendations to a manageable number.

### 4.4 WeeklyPlannerAgent

#### Goal
Prepare the user's most valuable weekly actions automatically.

#### Inputs
- CareerContext
- User's available time if known
- Upcoming scheduled content
- Current job-search state

#### Output
`WeeklyPlan`

Each task should contain:
- Type
- Title
- Description
- Priority
- Suggested date
- Estimated minutes
- Automation level
- Approval requirement
- Rationale

#### Rules
- Avoid duplicate tasks.
- Respect time budget.
- Prefer a small number of high-value actions.
- Include both content and career/job actions only when relevant to user's goals.

### 4.5 ContentStrategyAgent

#### Goal
Create and update content pillars/cadence.

#### Inputs
- CareerContext
- Post history
- Performance history where available

#### Output
- Content pillars
- Distribution
- Recommended cadence
- Themes to increase/decrease
- Rationale

### 4.6 ContentIdeaAgent

#### Goal
Generate relevant post ideas automatically.

#### Rules
Ideas should be grounded in:
- Real expertise
- Projects
- Career lessons
- Public professional activity
- Target positioning

Avoid:
- Fake personal stories
- Unsupported achievements
- Repetitive generic topics

### 4.7 PostGeneratorAgent

#### Goal
Generate complete LinkedIn-ready drafts.

#### Inputs
- CareerContext
- Selected topic
- Tone preferences
- Previous posts to avoid repetition

#### Output
- Body
- Internal title/topic
- Optional suggested CTA
- Optional hashtags if enabled

#### Quality requirements
- Human-readable
- Professional
- Not overly generic
- No invented facts
- Appropriate length
- Strong first lines
- Consistent user voice over time

### 4.8 JobParserAgent

#### Goal
Normalize a job URL/description into structured job data.

#### Output
- Role
- Company
- Location
- Seniority
- Responsibilities
- Required skills
- Preferred skills
- Experience requirements
- Salary where available
- Employment type

### 4.9 JobMatchingAgent

#### Goal
Evaluate job fit against CareerContext.

#### Output
- Overall score
- Skill score
- Experience score
- Seniority score
- Location score
- Career-fit score
- Matching strengths
- Missing requirements
- Recommendation
- Preparation actions

#### Recommendation enum
- STRONG_APPLY
- APPLY
- CONSIDER
- SKIP

### 4.10 PerformanceOptimizationAgent

#### Goal
Use historical outcomes to improve future content strategy and weekly planning.

#### Inputs
- Post metrics
- Approvals/rejections
- Task completion
- Job interactions

#### Output
- Strategy adjustments
- Preference updates
- Recommended experiments

Do not overfit to one post or one week.

## 5. Structured Output Policy

Backend-consumed AI calls should use Structured Outputs whenever practical.

Each schema should:
- Reject unknown enum values.
- Define nullable fields explicitly.
- Separate confidence from actual field value.
- Include failure/insufficient-data cases where necessary.

Never parse free-form AI text with brittle regex when a structured schema can be used.

## 6. Confidence & Data Quality

Suggested conceptual levels:
- High
- Medium
- Low
- Unknown

UI may map these to simpler labels:
- Confirmed
- Likely
- Needs Review

Confidence is not proof.

Confirmed user edits must have higher authority than AI confidence.

## 7. Automation Levels

### Assistant
AI recommends.
User executes.

### Copilot
AI prepares.
User approves high-impact action.
System executes where permitted.

### Autopilot
System executes permitted recurring tasks automatically.

Default:
**Copilot**

## 8. Automation Definition Schema

Every automation should conceptually define:

- `automation_key`
- `description`
- `trigger`
- `frequency`
- `preconditions`
- `action`
- `ai_capability`
- `approval_required`
- `retry_policy`
- `failure_behavior`
- `notification_behavior`
- `audit_level`

## 9. MVP Automations

### 9.1 Weekly Career Plan

Trigger:
Weekly.

Preconditions:
- User active
- Career Profile confirmed
- Automation enabled

Action:
Generate next WeeklyPlan.

Approval:
No.

Notification:
"Your plan for this week is ready."

Retry:
Yes.

### 9.2 Weekly Content Strategy

Trigger:
Weekly or when significant context changes.

Action:
Review/update content priorities.

Approval:
No for internal strategy.

### 9.3 Post Idea Generation

Trigger:
Based on weekly cadence.

Action:
Generate enough ideas for upcoming schedule.

Approval:
No.

### 9.4 Post Draft Generation

Trigger:
Idea selected automatically or manually.

Action:
Generate post draft.

Approval:
Drafting: no.
Publishing: separate rule.

### 9.5 Content Scheduling

Trigger:
Approved content + cadence configuration.

Action:
Assign schedule slot.

Approval:
Depends on user mode.

### 9.6 LinkedIn Publishing

Trigger:
Scheduled time.

Preconditions:
- Post approved when required
- LinkedIn/API integration available
- User permission valid

Action:
Publish through permitted integration.

Default approval:
Yes in Copilot mode.

Failure:
- Retry according to provider-safe policy
- Mark failed
- Notify user
- Offer manual fallback

### 9.7 Job Discovery

Trigger:
Daily or configured schedule.

Action:
Collect jobs from permitted configured sources.

Approval:
No.

### 9.8 Job Matching

Trigger:
New normalized job.

Action:
Generate match against CareerContext.

Approval:
No.

### 9.9 Job Digest

Trigger:
Daily/weekly.

Action:
Send top matches only.

Approval:
No.

### 9.10 Profile Health Check

Trigger:
Monthly.

Action:
Re-run profile analysis against current goals.

Approval:
No.

### 9.11 Public Profile Refresh

Trigger:
Configured schedule/manual.

Action:
Re-research public professional information.

Output:
Proposed changes.

Approval:
Yes before canonical profile overwrite.

### 9.12 Weekly Performance Summary

Trigger:
Weekly.

Action:
Summarize:
- Tasks
- Content
- Performance
- Jobs
- Progress
- Next focus

Approval:
No.

## 10. Automation Safety Rules

- A user can pause/disable automations.
- Public/reputation-sensitive actions should not be hidden.
- Failures must be recorded.
- Retries must be bounded.
- Avoid duplicate publishing or duplicate notifications.
- Use idempotency keys for external actions where possible.
- Do not silently change confirmed career history.
- Do not execute unsupported LinkedIn website automation.
- Do not use user LinkedIn credentials or browser cookies.

## 11. Approval Rules

### Usually no approval
- Internal analysis
- Weekly plan generation
- Content ideas
- Draft generation
- Job matching
- Job filtering
- Digests
- Performance analysis

### Approval by default
- Public publishing
- Confirming new externally researched profile data
- Any future action that directly communicates publicly as the user unless explicitly configured and supported

## 12. AI Feedback Loop

Capture:
- Accepted recommendation
- Dismissed recommendation
- Regenerated post
- Edited post
- Approved post
- Skipped task
- Completed task
- Saved job
- Applied job
- Rejected job suggestion

Use feedback to improve future recommendations.

Do not claim the model is retrained on one user's data unless that is actually implemented.

## 13. Prompt Versioning

Every production AI capability should have:
- `agent_key`
- `agent_version`
- prompt/config version
- model name
- schema version

When behavior changes materially, increment the version.

## 14. Evaluation Requirements

Before production, build representative evaluation cases for:
- Rich LinkedIn profile
- Sparse public profile
- Conflicting public data
- Career changer
- Senior professional
- Junior professional
- User with no public posts
- Job with poor fit
- Job with excellent fit
- Content generation with limited evidence

Evaluate:
- Factuality
- Schema validity
- Relevance
- Repetition
- Unsupported claims
- Match-quality consistency

## 15. Cost Controls

AI/web research can be expensive.

Use:
- Cached profile research
- Refresh intervals
- Smaller models for simple transformations
- Larger/reasoning models only where they create value
- Token limits
- Deduplicated job analysis
- Batch operations where appropriate

## 16. Privacy Controls

Only send context needed for the current AI task.

Do not place:
- passwords
- authentication tokens
- payment secrets
- private credentials
into AI prompts.

User-facing privacy policy and retention rules must be finalized before production launch.

## 17. Development Rule for AI Coding Agents

When implementing an AI feature:
1. Define input schema.
2. Define output schema.
3. Define prompt/agent version.
4. Add validation.
5. Add error/fallback behavior.
6. Add logging/observability.
7. Add tests/evals.
8. Only then connect it to irreversible/public actions.
