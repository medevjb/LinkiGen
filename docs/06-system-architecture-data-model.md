# System Architecture & Initial Data Model

## 1. Architecture Goals

The MVP architecture should be:
- Modular
- Observable
- Queue-first for background work
- Easy to iterate
- Safe for AI/automation failures
- Capable of scaling without premature complexity
- Friendly to AI coding agents

Avoid microservices for MVP unless a proven need appears.

## 2. Recommended Baseline Stack

This is a recommendation, not an immutable requirement.

### Frontend
- Next.js
- TypeScript
- Tailwind CSS
- Component library/design system as selected during implementation

### Backend
Preferred options:
- Laravel, or
- Node.js/TypeScript

If Laravel is selected:
- Laravel API/backend
- Laravel Queues
- Laravel Scheduler

If Node.js is selected:
- NestJS or structured Node backend
- BullMQ or equivalent

### Database
- PostgreSQL

### Vector Search
- pgvector if semantic retrieval becomes useful

### Cache / Queue
- Redis

### AI
- OpenAI Responses API
- Web search tool for professional-profile research
- Structured Outputs for schema-safe responses

### Storage
- S3-compatible object storage

### Email
- Resend, Postmark, or equivalent

### Payments
- Stripe when monetization is enabled

### Observability
- Structured application logs
- Error tracking
- Background job monitoring
- AI request logging with privacy controls

## 3. Logical Architecture

`Web Client`
→ `API/Application Layer`
→ `Domain Services`
→ `PostgreSQL`

Supporting services:
- Redis queue/cache
- OpenAI
- Email
- Object storage
- Job data integrations
- LinkedIn approved integrations where available

## 4. Core Domain Modules

### Identity
- Users
- Authentication
- Sessions
- Billing

### Career
- Career Profile
- Experiences
- Education
- Skills
- Projects
- Career Goals
- Profile Analysis
- Recommendations

### Planning
- Weekly Plans
- Tasks
- Task Feedback

### Content
- Content Strategy
- Content Ideas
- Posts
- Post Schedules
- Publishing Attempts
- Post Metrics

### Jobs
- Job Sources
- Jobs
- Job Matches
- Applications
- Job Preferences

### Automation
- Automation Settings
- Automation Runs
- Approval Requests
- Notifications

### AI
- AI Runs
- Prompt/agent versions
- Structured outputs
- Feedback

## 5. Initial Entity Model

### User
Fields:
- id
- email
- password/auth provider fields
- name
- timezone
- locale
- onboarding_status
- created_at
- updated_at

### CareerProfile
One primary canonical profile per user.

Fields:
- id
- user_id
- linkedin_url
- headline
- location
- current_role
- current_company
- professional_summary
- professional_focus
- completeness_score
- confirmed_at
- last_researched_at
- created_at
- updated_at

### CareerProfileSource
Tracks research provenance.

Fields:
- id
- career_profile_id
- source_type
- source_url
- source_label
- retrieved_at
- metadata_json

### Experience
- id
- career_profile_id
- company
- title
- start_date
- end_date
- is_current
- description
- confidence
- source_metadata_json

### Education
- id
- career_profile_id
- institution
- degree
- field_of_study
- start_date
- end_date
- confidence

### Skill
- id
- name
- normalized_name

### CareerProfileSkill
- career_profile_id
- skill_id
- proficiency optional
- evidence_level optional
- confidence

### Project
- id
- career_profile_id
- name
- description
- role
- technologies_json
- project_url
- confidence

### Certification
- id
- career_profile_id
- name
- issuer
- issue_date
- expiration_date
- credential_url
- confidence

### CareerGoal
- id
- user_id
- primary_goal
- target_roles_json
- locations_json
- remote_preference
- career_direction
- salary_min
- salary_currency
- industries_json
- employment_types_json
- created_at
- updated_at

### ProfileAnalysis
- id
- user_id
- career_profile_id
- overall_score
- category_scores_json
- summary
- generated_at
- model_version

### Recommendation
- id
- user_id
- type
- priority
- title
- description
- rationale
- status
- estimated_minutes
- source_context_json
- created_at
- completed_at

### WeeklyPlan
- id
- user_id
- week_start
- week_end
- status
- generated_at

### Task
- id
- weekly_plan_id
- user_id
- type
- title
- description
- scheduled_for
- due_at
- estimated_minutes
- automation_level
- approval_required
- status
- metadata_json

### ContentStrategy
- id
- user_id
- pillars_json
- cadence_json
- tone_preferences_json
- version
- generated_at

### ContentIdea
- id
- user_id
- title
- angle
- pillar
- rationale
- status
- generated_at

### Post
- id
- user_id
- content_idea_id nullable
- title_internal
- body
- tone
- status
- approved_at
- source_context_json
- created_at
- updated_at

### PostSchedule
- id
- post_id
- scheduled_at
- timezone
- publish_mode
- status

### PublishingAttempt
- id
- post_id
- provider
- attempted_at
- status
- external_post_id
- error_code
- error_message

### PostMetric
- id
- post_id
- captured_at
- impressions nullable
- reach nullable
- reactions nullable
- comments nullable
- reposts nullable
- saves nullable
- clicks nullable
- profile_views nullable
- followers_gained nullable

### Job
- id
- source
- source_job_id
- source_url
- company
- title
- location
- remote_type
- description
- salary_min
- salary_max
- currency
- employment_type
- posted_at
- normalized_hash
- created_at

### JobMatch
- id
- user_id
- job_id
- overall_score
- skill_score
- experience_score
- seniority_score
- location_score
- career_fit_score
- matched_skills_json
- missing_skills_json
- recommendation
- rationale
- generated_at

### Application
- id
- user_id
- job_id
- job_match_id nullable
- status
- applied_at
- next_action_at
- notes
- updated_at

### AutomationSetting
- id
- user_id
- automation_key
- enabled
- automation_level
- frequency
- approval_required
- config_json
- updated_at

### AutomationRun
- id
- user_id
- automation_key
- trigger_type
- status
- started_at
- completed_at
- retry_count
- error_code
- error_message
- result_metadata_json

### ApprovalRequest
- id
- user_id
- entity_type
- entity_id
- action_type
- status
- expires_at
- created_at
- resolved_at

### Notification
- id
- user_id
- type
- title
- body
- related_entity_type
- related_entity_id
- read_at
- created_at

### AIRun
- id
- user_id
- agent_key
- model
- agent_version
- input_reference_json
- output_reference_json
- status
- token_usage_json
- latency_ms
- error
- created_at

### UserFeedback
- id
- user_id
- entity_type
- entity_id
- feedback_type
- value
- note
- created_at

## 6. Data Ownership Rules

- `CareerProfile` is the canonical professional context.
- Raw research output must not directly drive public actions before user confirmation.
- User corrections override inferred/researched values.
- AI output should reference canonical data wherever possible.
- External profile refreshes should create proposed changes, not silently overwrite confirmed career history.

## 7. Background Job Requirements

Use queues for:
- Profile research
- Profile analysis
- Weekly plan generation
- Content generation
- Job discovery
- Job matching
- Publishing
- Analytics retrieval
- Digest generation
- Email sending
- Profile refresh

Background jobs should be:
- Idempotent where practical
- Retryable
- Observable
- Auditable

## 8. Scheduler Requirements

Typical schedules:
- Weekly plan: weekly
- Content strategy refresh: weekly or event-driven
- Job discovery: daily
- Job digest: daily/weekly
- Profile health check: monthly
- Profile refresh: configurable
- Post publishing: exact scheduled time

Do not hard-code schedules per user without timezone handling.

## 9. AI Boundary

The AI should not directly mutate important database state without validation.

Pattern:

`Input Context → AI Structured Output → Validate → Domain Service → Database`

Examples:
- Profile research → validate schema → save draft profile
- Job match → validate scores/recommendation → save JobMatch
- Weekly plan → validate task limits → save plan/tasks

## 10. Security Baseline

Before production:
- Password hashing
- Secure sessions/tokens
- CSRF protection where applicable
- Rate limiting
- Input validation
- File upload validation
- Signed URLs for private files
- Secrets in environment/secret manager
- Least-privilege external API credentials
- Audit sensitive automation actions
- Encrypt sensitive data at rest where appropriate

Detailed security documentation should be expanded during implementation.

## 11. Architecture Decision Records

Create ADRs for major decisions, e.g.:
- ADR-001 backend framework
- ADR-002 queue technology
- ADR-003 OpenAI Responses API
- ADR-004 CareerProfile as canonical context
- ADR-005 profile research onboarding model
- ADR-006 publishing integration strategy

## 12. Development Rule

Do not introduce microservices, event buses, separate vector databases, or complex distributed infrastructure unless a measured requirement justifies them.
