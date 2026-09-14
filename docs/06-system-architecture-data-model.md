# System Architecture & Data Model

> **Behavior lives in the specs.** This document owns the stack, the data model, module boundaries, and background-work design. Testable behavior and acceptance criteria live in `openspec/specs/<capability>/spec.md` and are referenced here by capability path.

## 1. Architecture Goals

The MVP architecture is:

- Modular
- Observable
- Queue-first for background work
- Easy to iterate
- Safe for AI and automation failures
- Capable of scaling without premature complexity

Microservices are not used for the MVP.

## 2. Stack

The stack is **decided**, not recommended. See `ADR/ADR-001-backend-framework.md` and `ADR/ADR-002-queue-and-scheduler.md`.

| Layer | Choice |
|---|---|
| Backend | **Laravel**, exposing a REST API |
| Frontend | **Next.js + TypeScript**, a separate deployable |
| Styling | Tailwind CSS |
| Database | **PostgreSQL** |
| Queue and cache | **Redis**, via Laravel Queues |
| Scheduling | **Laravel Scheduler** |
| AI | **OpenAI Responses API** — web search tool for research, Structured Outputs for every backend-consumed call (`ADR-003`) |
| Object storage | S3-compatible, private buckets with signed URLs |
| Email | Transactional provider (selection deferred; see §11) |
| Payments | Stripe |
| Observability | Structured logs, error tracking, queue monitoring, AI run records |

Frontend and backend communicate **only** over the contract in `08-api-contract.md`. The client has no access to framework internals, which keeps that contract complete.

**Semantic search** (pgvector) is not part of MVP 1. Adopt it only when a measured retrieval requirement appears.

## 3. Logical Architecture

```
Next.js client
      │  REST, per 08-api-contract.md
      ▼
Laravel API layer ──────► Domain services ──────► PostgreSQL
      │                        ▲
      │                        │ validated output only
      ├──► Redis (queue/cache) │
      │        │               │
      │        ▼               │
      │   Queued jobs ─────────┘
      │        │
      │        ├──► OpenAI (research, analysis, generation, matching)
      │        ├──► Email provider
      │        ├──► Object storage
      │        ├──► JobSource adapters      (ADR-006, may be absent)
      │        └──► PublishingProvider      (ADR-006, absent in MVP 1)
      │
      └──► Stripe
```

Every arrow leaving the process is an interface with a defined absent-provider behavior.

## 4. Module Boundaries

Each capability spec has exactly one owning backend module. No module reads another module's tables directly; cross-module reads go through the owning module's service.

| Module | Owns capability | Key responsibilities |
|---|---|---|
| **Identity** | `identity/account-access` | Registration, verification, sessions, password reset, export and erasure orchestration |
| **Career/Profile** | `career/profile-import` | URL intake, research orchestration, CV extraction, duplicate-claim resolution |
| **Career/Profile** | `career/career-profile` | Canonical profile, provenance, confirmation, pending change sets |
| **Career/Goals** | `career/goals-and-analysis` | Goal capture, profile scoring, recommendations |
| **Planning** | `planning/weekly-plan` | Plan generation, task lifecycle, time budgeting |
| **Content/Strategy** | `content/content-strategy` | Pillars, distribution, cadence, idea generation |
| **Content/Posts** | `content/post-authoring` | Draft generation, AI edit actions, claim flagging, approval |
| **Content/Publishing** | `content/scheduling-and-publishing` | Calendar, scheduling, publish jobs, `PublishingProvider` |
| **Jobs/Analysis** | `jobs/job-analysis` | Manual intake, parsing, match scoring |
| **Jobs/Discovery** | `jobs/job-discovery` | `JobSource` adapters, normalization, filtering, ranking |
| **Jobs/Applications** | `jobs/application-tracker` | Application records, status history, next actions |
| **Automation** | `automation/automation-control` | Settings, levels, runs, approvals, pause, suspension |
| **Notifications** | `notifications/notifications-and-digests` | Notification raising, delivery, digests, suppression |
| **Billing** | `billing/plans-and-entitlements` | Entitlements, metering, quota enforcement, subscription lifecycle |
| **Platform/AI** | `platform/ai-runtime` | Context assembly, schema validation, versioning, cost control, evals |
| **Platform/Privacy** | `platform/data-privacy-and-retention` | Classification, retention sweeps, DSR execution, audit log |

Cross-cutting concerns and their single owner:

- **Quota checks** — Billing. Every metered operation calls Billing before spending (`billing/plans-and-entitlements`).
- **Context assembly** — Platform/AI. No module builds its own AI context (`platform/ai-runtime`).
- **Timezone resolution** — Platform, per §5. No module computes schedules from server time.
- **Audit logging** — Platform/Privacy.

## 5. Time, Timezone, and Scheduling

Timezone correctness affects weekly plan boundaries, post scheduling, digest delivery, and next-action dates. The rules are stated once here; `content/scheduling-and-publishing` carries the normative DST requirements.

### Storage rules

| Kind of time | Stored as |
|---|---|
| An event that happened (created, published, approved) | UTC instant |
| A time the **user chose** (scheduled post, digest hour, next action) | UTC instant **plus** the intended local wall-clock time **plus** the IANA timezone it was expressed in |
| A user's timezone | IANA identifier (e.g. `Europe/Berlin`), never a fixed UTC offset |

Storing the intended wall-clock alongside the instant is what makes the two hard cases correct:

- **User changes timezone.** Scheduled items keep their intended local wall-clock time; the UTC instant is recomputed, and the user is shown what moved in absolute terms.
- **DST transition.** When a chosen wall-clock time falls in a skipped or repeated hour, resolution is deterministic — skipped times move forward to the first valid instant, repeated times take the first occurrence. The post publishes exactly once and is never dropped.

### Scheduling rules

- Recurring work registers once in the scheduler and fans out per user.
- Week boundaries for `planning/weekly-plan` are computed in the user's timezone.
- Digests send at the configured local hour in the user's timezone.
- Schedules are never hard-coded to server time.

## 6. Data Ownership Rules

Per `ADR-004`:

- The confirmed `CareerProfile` is the canonical professional context.
- Authority order: **user-authored > CV-extracted > researched > inferred**. A higher-authority value is never overwritten by a lower one.
- Raw research output never drives public actions before confirmation.
- External refreshes create **proposed changes**, never silent overwrites.
- Original researched values are retained for audit after a user correction.

## 7. Entity Model

Field lists are indicative. Types, constraints, and indexes are settled in migrations.

### 7.1 Identity

**User** — `id`, `email`, `password_hash`, `name`, `timezone` (IANA), `locale`, `onboarding_status`, `email_verified_at`, `deletion_requested_at`, `erasure_completed_at`, timestamps

**Session** — `id`, `user_id`, `token_hash`, `ip`, `user_agent`, `last_used_at`, `expires_at`, `revoked_at`

**VerificationToken** — `id`, `user_id`, `purpose` (`email_verify` | `password_reset`), `token_hash`, `expires_at`, `consumed_at`

**DataExportRequest** — `id`, `user_id`, `status`, `requested_at`, `completed_at`, `artifact_path`, `link_expires_at`

**ErasureRequest** — `id`, `user_id`, `status`, `requested_at`, `scheduled_for`, `completed_at`, `retained_categories_json`

> The `deletion_requested_at` marker is what queued jobs check before persisting any result (`identity/account-access`).

### 7.2 Career Profile

**CareerProfile** — `id`, `user_id`, `linkedin_url`, `linkedin_url_claim_status`, `headline`, `location`, `current_role`, `current_company`, `professional_summary`, `professional_focus`, `completeness_score`, `confirmed_at`, `last_researched_at`, timestamps

**CareerProfileSource** — `id`, `career_profile_id`, `source_type`, `source_url`, `source_label`, `retrieved_at`, `metadata_json`

**FieldProvenance** — `id`, `entity_type`, `entity_id`, `field_name`, `authority` (`user` | `cv` | `research` | `inference`), `confidence`, `source_id`, `original_value_json`, `set_at`

> `original_value_json` preserves the pre-correction researched value required by `career/career-profile`.

**Experience** — `id`, `career_profile_id`, `company`, `title`, `start_date`, `end_date`, `is_current`, `description`

**Education** — `id`, `career_profile_id`, `institution`, `degree`, `field_of_study`, `start_date`, `end_date`

**Skill** — `id`, `name`, `normalized_name`

**CareerProfileSkill** — `career_profile_id`, `skill_id`, `proficiency`, `evidence_level`

**Project** — `id`, `career_profile_id`, `name`, `description`, `role`, `technologies_json`, `project_url`

**Certification** — `id`, `career_profile_id`, `name`, `issuer`, `issue_date`, `expiration_date`, `credential_url`

**UploadedDocument** — `id`, `user_id`, `kind` (`cv`), `storage_path`, `mime_type`, `size_bytes`, `extracted_at`, `uploaded_at`

### 7.3 Profile Refresh (proposed changes)

**ProfileChangeSet** — `id`, `career_profile_id`, `status` (`pending` | `partially_resolved` | `resolved`), `research_run_id`, `created_at`, `resolved_at`

**ProposedProfileChange** — `id`, `change_set_id`, `entity_type`, `entity_id`, `field_name`, `current_value_json`, `proposed_value_json`, `conflicts_with_user_edit` (bool), `evidence_hash`, `status` (`pending` | `accepted` | `rejected`), `resolved_at`

> `evidence_hash` is what prevents a rejected proposal from resurfacing unless its underlying evidence actually changes (`career/career-profile`).

### 7.4 Goals and Analysis

**CareerGoal** — `id`, `user_id`, `primary_goal`, `target_roles_json`, `locations_json`, `remote_preference`, `career_direction`, `salary_min`, `salary_currency`, `industries_json`, `employment_types_json`, timestamps

**ProfileAnalysis** — `id`, `user_id`, `career_profile_id`, `overall_score`, `category_scores_json`, `summary`, `generated_at`, `agent_version`, `is_stale`, `stale_reason`

**Recommendation** — `id`, `user_id`, `analysis_id`, `type`, `priority`, `title`, `description`, `rationale`, `suggested_text`, `estimated_minutes`, `status`, `fingerprint`, `created_at`, `completed_at`

> `fingerprint` prevents regenerating a materially identical recommendation the user already dismissed.

### 7.5 Planning

**WeeklyPlan** — `id`, `user_id`, `week_start`, `week_end`, `timezone`, `status`, `generated_at`

**Task** — `id`, `weekly_plan_id`, `user_id`, `type`, `title`, `description`, `priority`, `scheduled_for`, `due_at`, `estimated_minutes`, `automation_level`, `approval_required`, `status`, `fingerprint`, `rationale`, `metadata_json`

### 7.6 Content

**ContentStrategy** — `id`, `user_id`, `pillars_json`, `distribution_json`, `cadence_json`, `tone_preferences_json`, `user_overrides_json`, `version`, `generated_at`

**ContentIdea** — `id`, `user_id`, `title`, `angle`, `pillar`, `rationale`, `status`, `fingerprint`, `generated_at`

**Post** — `id`, `user_id`, `content_idea_id`, `title_internal`, `body`, `tone`, `status`, `approved_by`, `approved_at`, `source_context_json`, timestamps

**PostVersion** — `id`, `post_id`, `body`, `origin` (`generated` | `ai_edit` | `manual`), `ai_action`, `created_at`

> Required by `content/post-authoring`: every AI edit preserves the prior version so the user can revert.

**PostClaimFlag** — `id`, `post_id`, `post_version_id`, `statement`, `reason`, `acknowledged_at`, `acknowledged_by`

**PostSchedule** — `id`, `post_id`, `scheduled_at_utc`, `intended_local_time`, `timezone`, `publish_mode` (`manual` | `provider`), `status`

**PublishingAttempt** — `id`, `post_id`, `provider`, `idempotency_key`, `attempted_at`, `status`, `external_post_id`, `error_code`, `error_message`

**PostMetric** — `id`, `post_id`, `captured_at`, `impressions`, `reach`, `reactions`, `comments`, `reposts`, `saves`, `clicks`, `profile_views`, `followers_gained` (all nullable)

### 7.7 Jobs

**JobSourceRegistration** — `id`, `adapter_key`, `display_name`, `enabled`, `config_json`, `last_run_at`, `last_status`

**JobSourceRun** — `id`, `job_source_registration_id`, `user_id`, `started_at`, `completed_at`, `status`, `jobs_collected`, `error_code`, `error_message`

**Job** — `id`, `source`, `source_job_id`, `source_url`, `company`, `title`, `location`, `remote_type`, `description`, `required_skills_json`, `preferred_skills_json`, `seniority`, `salary_min`, `salary_max`, `currency`, `employment_type`, `posted_at`, `normalized_hash`, `created_at`

**JobMatch** — `id`, `user_id`, `job_id`, `overall_score`, `skill_score`, `experience_score`, `seniority_score`, `location_score`, `career_fit_score`, `matched_skills_json`, `missing_skills_json`, `recommendation`, `rationale`, `preparation_json`, `agent_version`, `generated_at`

**Application** — `id`, `user_id`, `job_id`, `job_match_id`, `status`, `applied_at`, `next_action_at`, `notes`, `updated_at`

**ApplicationStatusHistory** — `id`, `application_id`, `from_status`, `to_status`, `changed_at`

### 7.8 Automation

**AutomationSetting** — `id`, `user_id`, `automation_key`, `enabled`, `automation_level`, `frequency`, `approval_required`, `is_user_override`, `suspended_at`, `suspension_reason`, `consecutive_failures`, `config_json`, `updated_at`

**AutomationPause** — `id`, `user_id`, `paused_at`, `resumed_at`

**AutomationRun** — `id`, `user_id`, `automation_key`, `trigger_type`, `status`, `skip_reason`, `started_at`, `completed_at`, `retry_count`, `error_code`, `error_message`, `result_metadata_json`

**ApprovalRequest** — `id`, `user_id`, `entity_type`, `entity_id`, `action_type`, `status`, `expires_at`, `created_at`, `resolved_at`, `resolved_by`

**IdempotencyKey** — `id`, `scope`, `key`, `entity_type`, `entity_id`, `result_json`, `created_at`

### 7.9 Notifications

**Notification** — `id`, `user_id`, `type`, `category` (`action_required` | `outcome` | `report`), `title`, `body`, `related_entity_type`, `related_entity_id`, `dedupe_key`, `read_at`, `created_at`

**NotificationDelivery** — `id`, `notification_id`, `channel` (`in_app` | `email`), `status`, `provider_message_id`, `sent_at`, `failed_at`, `failure_reason`

**EmailSuppression** — `id`, `email`, `reason` (`hard_bounce` | `complaint` | `unsubscribe`), `bounce_count`, `suppressed_at`

**DigestRun** — `id`, `user_id`, `digest_type`, `window_start`, `window_end`, `item_count`, `status`, `sent_at`

### 7.10 Billing

**Plan** — `id`, `key` (`free` | `pro` | `pro_plus`), `display_name`, `price_cents`, `currency`, `interval`

**PlanEntitlement** — `id`, `plan_id`, `entitlement_key`, `quota_limit` (null = unlimited), `is_feature_gate`

**Subscription** — `id`, `user_id`, `plan_id`, `provider_subscription_id`, `status`, `current_period_start`, `current_period_end`, `grace_period_ends_at`, `cancel_at_period_end`

**UsageCounter** — `id`, `user_id`, `entitlement_key`, `period_start`, `period_end`, `used`, `warned_at`

**MeteredOperation** — `id`, `user_id`, `entitlement_key`, `ai_run_id`, `consumed`, `created_at`

### 7.11 Platform

**AIRun** — `id`, `user_id`, `agent_key`, `agent_version`, `prompt_version`, `schema_version`, `model`, `input_reference_json`, `output_reference_json`, `status`, `validation_error`, `token_usage_json`, `cost_cents`, `latency_ms`, `created_at`

**UserFeedback** — `id`, `user_id`, `entity_type`, `entity_id`, `feedback_type`, `value`, `note`, `created_at`

**AuditLog** — `id`, `actor_type` (`user` | `system` | `operator`), `actor_id`, `action`, `entity_type`, `entity_id`, `approval_request_id`, `metadata_json`, `created_at`

> Public actions taken as the user are recorded here with their approval reference (`automation/automation-control`).

**RetentionPolicy** — `id`, `data_class`, `entity_type`, `retention_days`, `strategy` (`delete` | `anonymize`), `last_swept_at`

## 8. Background Work

Job classes, required properties, and scheduling are specified in `ADR-002`. Summarized:

- Every AI-backed operation is a queued job.
- Jobs are **idempotent for external actions** (publishing uses `IdempotencyKey`), **bounded-retry**, **observable** (`AutomationRun`, `AIRun`), **deletion-aware** (check `deletion_requested_at` before persisting), and **quota-aware** (check Billing before spending).
- A job that exhausts quota mid-batch persists what it produced and reports a partial result.

## 9. The AI Boundary

Per `ADR-003`, model output never reaches the database directly:

```
Minimized context → AI call with declared schema → Validate → Domain service → Database
```

Validation failure is recorded on `AIRun.validation_error` and never persisted as a result. Out-of-enum values are rejected, not coerced.

## 10. Security Baseline

Required before production:

- Password hashing, secure session tokens, server-side revocation
- CSRF protection on cookie-authenticated routes; rate limiting on auth, research, and AI endpoints
- Input validation on every endpoint; file upload type, size, and content validation
- Private object storage with signed, time-limited URLs
- Secrets in a secret manager, never in the repository
- Least-privilege credentials for every external API
- Row-level scoping so no request can reach another account's records by identifier manipulation
- Encryption at rest for third-party tokens
- Audit logging for sensitive automation and operator access

Threat model, DSR procedures, and the retention schedule are in `10-security-privacy-compliance.md`.

## 11. Deferred Decisions

These are safely deferrable — they change an adapter or a vendor, not the data model, the API contract, or any spec:

| Decision | Owner | Notes |
|---|---|---|
| Which `JobSource` adapter ships first | Product | `ADR-006`; the no-source state is specified |
| Transactional email provider | Engineering | Interface is provider-agnostic |
| Error tracking vendor | Engineering | Log schema is defined in `13` |
| pgvector adoption | Engineering | Only on a measured retrieval requirement |

## 12. Architecture Decision Records

| ADR | Decision |
|---|---|
| `ADR-001` | Laravel backend + separate Next.js client |
| `ADR-002` | Laravel Queues on Redis + Laravel Scheduler |
| `ADR-003` | OpenAI Responses API and the structured-output validation boundary |
| `ADR-004` | CareerProfile as canonical context |
| `ADR-005` | LinkedIn-URL-first onboarding via public research |
| `ADR-006` | Manual publishing and pluggable job sources |

Record a new ADR when changing a major architectural assumption. Do not introduce microservices, event buses, separate vector databases, or distributed infrastructure without a measured requirement.
