# AI & Automation Specification

> **Behavior lives in the specs.** This document owns agent contracts, the automation catalogue, and AI operating rules. Normative behavior is in `openspec/specs/platform/ai-runtime/spec.md` and `openspec/specs/automation/automation-control/spec.md`, referenced here by capability path.

## 1. Purpose

AI and automation are core product capabilities. This document defines how the MVP uses AI safely, consistently, and predictably, and catalogues every automation that runs on a user's behalf.

## 2. CareerContext

All major AI features read from one logical context, **CareerContext**, assembled by Platform/AI. No module builds its own.

CareerContext may draw on: the confirmed Career Profile, career goals, job preferences, content preferences, automation preferences, previous weekly tasks, completed and skipped tasks, content history, post performance, job-match history, application history, user corrections, user feedback, and the current date and time in the user's timezone.

Three rules constrain it (`platform/ai-runtime`):

- **Minimized per task.** Only the fields a task needs are sent. The user's full record never goes to every call.
- **Confirmed data only.** An unconfirmed profile draft is never used for content generation, job matching, or any public-facing output (`ADR-004`).
- **No secrets.** Passwords, session tokens, API credentials, and payment details never enter a prompt.

## 3. AI Principles

AI must use confirmed user data as highest authority, distinguish fact from inference, never invent professional claims, return structured output for backend-controlled workflows, be versioned, be observable, support retries and fallbacks, and store enough metadata to debug failures.

## 4. Agent Contracts

Logical capabilities, not separate services. Each declares an input schema, an output schema, a version key, failure behavior, and the spec requirement it satisfies.

---

### 4.1 ProfessionalProfileResearchAgent

| | |
|---|---|
| **Agent key** | `profile_research` |
| **Governing requirement** | `career/profile-import` — *Public professional research*, *Research outcome handling* |
| **Input schema** | `{ linkedin_url, known_name?, cv_text?, current_date }` |
| **Output schema** | `RawProfessionalProfile` — identity, headline, location, current role and company, experience[], education[], skills[], technologies[], projects[], certifications[], public content themes[], professional focus, `conflicts[]`, `missing_fields[]`, per-field `confidence`, `sources[]` with provenance. Includes an explicit `insufficient_data` outcome. |
| **Tools** | OpenAI web search; file extraction when a CV is supplied |
| **Model tier** | Reasoning tier (most expensive operation in the product) |
| **Failure behavior** | Bounded retry. On exhaustion: record failure, notify, offer retry + CV + manual entry. Never emit an empty profile as success. |
| **Version key** | `profile_research@v1` |

**Rules.** Publicly available information only. Never imply authenticated LinkedIn access. Never invent missing data — absent fields are returned as missing. Surface conflicts rather than resolving them. Low-confidence third-party claims are not confirmed facts.

---

### 4.2 CareerProfileNormalizer

| | |
|---|---|
| **Agent key** | `profile_normalize` |
| **Governing requirement** | `career/career-profile` — *Canonical profile contents*, *User corrections outrank AI* |
| **Input schema** | `{ raw_profile, existing_profile?, user_corrections? }` |
| **Output schema** | `CareerProfileDraft` — canonical shape with `field_provenance[]` carrying authority and confidence per field |
| **Model tier** | Cheap tier (transformation, not reasoning) |
| **Failure behavior** | Schema validation failure is not persisted; retry once, then fail the import with the raw result retained |
| **Version key** | `profile_normalize@v1` |

**Rules.** Normalize technology names. Remove obvious duplicates. Preserve source and confidence metadata. Never overwrite confirmed user corrections during refresh.

---

### 4.3 ProfileAnalyzerAgent

| | |
|---|---|
| **Agent key** | `profile_analyze` |
| **Governing requirement** | `career/goals-and-analysis` — *Profile analysis*, *Recommendations* |
| **Input schema** | Minimized CareerContext: profile, goals, content presence summary |
| **Output schema** | `{ overall_score, category_scores{positioning, headline, experience_clarity, skills_relevance, projects_evidence, content_presence, goal_alignment}, summary, recommendations[{type, priority, title, description, rationale, suggested_text?, estimated_minutes}] }` |
| **Model tier** | Reasoning tier |
| **Failure behavior** | Show last successful analysis with its date; report failure; offer retry |
| **Version key** | `profile_analyze@v1` |

**Rules.** Recommendations relate to the user's stated goals, not generic best practice. High-priority recommendations are capped at the configured maximum. Low scores on a sparse profile are attributed to missing information, not weak experience.

---

### 4.4 WeeklyPlannerAgent

| | |
|---|---|
| **Agent key** | `weekly_plan` |
| **Governing requirement** | `planning/weekly-plan` — *Plan composition* |
| **Input schema** | CareerContext + available time + upcoming scheduled content + job-search state + recent task fingerprints |
| **Output schema** | `{ tasks[{type, title, description, priority, suggested_date, estimated_minutes, automation_level, approval_required, rationale, fingerprint}] }` |
| **Model tier** | Reasoning tier |
| **Failure behavior** | Bounded retry; on exhaustion surface on dashboard and automation history, offer manual regeneration |
| **Version key** | `weekly_plan@v1` |

**Rules.** Respect the weekly task cap and the user's time budget. No duplicates within the plan or against recent completed/skipped tasks. Task mix follows the user's primary goal.

---

### 4.5 ContentStrategyAgent

| | |
|---|---|
| **Agent key** | `content_strategy` |
| **Governing requirement** | `content/content-strategy` — *Content pillar generation*, *Strategy refresh* |
| **Input schema** | CareerContext + post history + performance history + user pillar overrides |
| **Output schema** | `{ pillars[{name, rationale, evidence_refs[]}], distribution{}, recommended_cadence, themes_to_increase[], themes_to_decrease[] }` |
| **Model tier** | Reasoning tier |
| **Failure behavior** | Retain the previous strategy version; report failure; retry on next schedule |
| **Version key** | `content_strategy@v1` |

**Rules.** Every pillar traces to profile evidence. Cadence stays within the user's stated time budget. Never restructure on one post or one week of data. User overrides outrank automated emphasis changes.

---

### 4.6 ContentIdeaAgent

| | |
|---|---|
| **Agent key** | `content_ideas` |
| **Governing requirement** | `content/content-strategy` — *Content idea generation* |
| **Input schema** | CareerContext + active strategy + used/dismissed/pending idea fingerprints |
| **Output schema** | `{ ideas[{title, angle, pillar, rationale, fingerprint}] }` |
| **Model tier** | Mid tier |
| **Failure behavior** | Retain existing ideas; retry on next cadence trigger |
| **Version key** | `content_ideas@v1` |

**Rules.** Grounded in real expertise, projects, career lessons, and public activity. No fabricated anecdotes or unsupported achievements. No repetition of used, dismissed, or pending ideas. On thin evidence, generate fewer safer ideas and tell the user which profile additions would help.

---

### 4.7 PostGeneratorAgent

| | |
|---|---|
| **Agent key** | `post_generate` |
| **Governing requirement** | `content/post-authoring` — *Post draft generation*, *AI editing actions* |
| **Input schema** | CareerContext + selected idea + tone preferences + recent post bodies (repetition avoidance) + for edits: current body and `ai_action` |
| **Output schema** | `{ body, title_internal, suggested_cta?, hashtags?, claim_flags[{statement, reason}] }` |
| **Model tier** | Reasoning tier |
| **Failure behavior** | Preserve the idea; never save an empty or truncated draft as usable; offer retry |
| **Version key** | `post_generate@v1` |

**Rules.** Never assert employers, roles, projects, credentials, metrics, or outcomes absent from the canonical profile. Edit actions must not introduce new factual claims. Avoid repeating the structure and openings of recent posts. Every AI edit writes a `PostVersion` so the user can revert.

---

### 4.8 JobParserAgent

| | |
|---|---|
| **Agent key** | `job_parse` |
| **Governing requirement** | `jobs/job-analysis` — *Job parsing* |
| **Input schema** | `{ job_url?, job_description? }` (at least one) |
| **Output schema** | `{ is_job_posting, role, company, location, remote_type, seniority, responsibilities[], required_skills[], preferred_skills[], experience_requirements, salary_min?, salary_max?, currency?, employment_type }` |
| **Model tier** | Cheap tier |
| **Failure behavior** | Unfetchable URL → ask for a pasted description. `is_job_posting: false` → report, produce no score. |
| **Version key** | `job_parse@v1` |

**Rules.** Fields absent from the posting are recorded as unknown, never inferred.

---

### 4.9 JobMatchingAgent

| | |
|---|---|
| **Agent key** | `job_match` |
| **Governing requirement** | `jobs/job-analysis` — *Match analysis*; `jobs/job-discovery` — *Match generation and ranking* |
| **Input schema** | Parsed job + minimized CareerContext (profile, goals, preferences) |
| **Output schema** | `{ overall_score, skill_score, experience_score, seniority_score, location_score, career_fit_score, matched_skills[], missing_requirements[], strengths[], recommendation, rationale, preparation[] }` |
| **Recommendation enum** | `STRONG_APPLY` \| `APPLY` \| `CONSIDER` \| `SKIP` — out-of-enum values are rejected, never coerced |
| **Model tier** | Mid tier |
| **Failure behavior** | Preserve submitted job content so the user need not re-paste; offer retry |
| **Version key** | `job_match@v1` |

**Rules.** The recommendation must be consistent with the overall score thresholds. Reasoning references the user's own profile content. Partial fits get concrete preparation advice, not just a gap report. Matching is blocked before profile confirmation.

---

### 4.10 PerformanceOptimizationAgent

| | |
|---|---|
| **Agent key** | `performance_optimize` |
| **Governing requirement** | `platform/ai-runtime` — *Feedback capture*; `content/content-strategy` — *Strategy refresh* |
| **Input schema** | Post metrics + approval/rejection history + task completion + job interactions, over a multi-week window |
| **Output schema** | `{ strategy_adjustments[], preference_updates[], recommended_experiments[] }` |
| **Model tier** | Mid tier |
| **Failure behavior** | Skip the cycle; the existing strategy stands |
| **Version key** | `performance_optimize@v1` |

**Rules.** Never overfit to one post or one week. Never claim the model is retrained on user data.

---

## 5. Structured Output Policy

Per `ADR-003` and `platform/ai-runtime`:

- Every backend-consumed call declares an output schema and is validated before a domain service writes anything.
- Schemas reject unknown enum values, define nullable fields explicitly, keep confidence separate from the value, and include an explicit insufficient-data case.
- Free-form text is never parsed programmatically when a schema can be used.
- Validation failures are recorded on `AIRun.validation_error` with the offending output, and are never persisted as a result.

## 6. Confidence & Data Quality

Internal levels: **High / Medium / Low / Unknown**. The interface may present these as *Confirmed / Likely / Needs Review*.

Confidence is not proof. Confirmed user edits always outrank AI confidence (`ADR-004`).

## 7. Automation Levels

| Level | Behavior |
|---|---|
| **Assistant** | AI recommends only. The user executes. |
| **Copilot** | AI prepares. The user approves high-impact actions. The system executes what is permitted. |
| **Autopilot** | The system executes permitted recurring actions automatically. |

**Default: Copilot.** Actions that publish publicly as the user, or that overwrite confirmed career history, still require explicit prior configuration and are never enabled by a preset alone (`automation/automation-control`).

## 8. Automation Definition Schema

Every automation declares all twelve fields:

`automation_key` · `description` · `trigger` · `frequency` · `preconditions` · `action` · `ai_capability` · `approval_required` · `retry_policy` · `failure_behavior` · `notification_behavior` · `audit_level`

## 9. MVP Automation Catalogue

Preconditions common to all: the account is active, not pending erasure, automation is not globally paused, and the automation is enabled and not suspended.

---

### 9.1 Weekly Career Plan
- **automation_key:** `weekly_career_plan`
- **description:** Prepares the user's highest-value career actions for the coming week.
- **trigger:** Scheduler
- **frequency:** Weekly, at the user's configured local day and hour
- **preconditions:** Career Profile confirmed; career goals set
- **action:** Generate the next `WeeklyPlan` and its tasks
- **ai_capability:** `weekly_plan@v1`
- **approval_required:** No
- **retry_policy:** 3 attempts, exponential backoff
- **failure_behavior:** Mark run failed; surface on dashboard and automation history; offer manual regeneration; suspend after consecutive-failure threshold
- **notification_behavior:** "Your plan for this week is ready." On failure, notify once
- **audit_level:** Run record only

### 9.2 Weekly Content Strategy
- **automation_key:** `weekly_content_strategy`
- **description:** Reviews and updates content pillars, distribution, and cadence.
- **trigger:** Scheduler, or material context change
- **frequency:** Weekly
- **preconditions:** Career Profile confirmed
- **action:** Generate a new `ContentStrategy` version
- **ai_capability:** `content_strategy@v1`
- **approval_required:** No — internal strategy is not a public action
- **retry_policy:** 2 attempts
- **failure_behavior:** Previous strategy version stands; retry next cycle
- **notification_behavior:** None (routine internal work)
- **audit_level:** Run record only

### 9.3 Post Idea Generation
- **automation_key:** `content_idea_generation`
- **description:** Keeps enough unused ideas available to cover the upcoming schedule.
- **trigger:** Cadence check
- **frequency:** Per configured posting cadence
- **preconditions:** Active content strategy exists
- **action:** Generate ideas up to the unused-idea floor
- **ai_capability:** `content_ideas@v1`
- **approval_required:** No
- **retry_policy:** 2 attempts
- **failure_behavior:** Existing ideas retained; retry next trigger
- **notification_behavior:** None
- **audit_level:** Run record only

### 9.4 Post Draft Generation
- **automation_key:** `post_draft_generation`
- **description:** Turns a selected idea into a complete draft.
- **trigger:** Idea selected, automatically or by the user
- **frequency:** Event-driven
- **preconditions:** Idea exists and is unused
- **action:** Generate a `Post` in Draft or Needs Approval status
- **ai_capability:** `post_generate@v1`
- **approval_required:** No for drafting. Publishing is governed separately by 9.6
- **retry_policy:** 2 attempts
- **failure_behavior:** Idea preserved; no empty draft saved; offer retry
- **notification_behavior:** Notify only when a draft awaits approval
- **audit_level:** Run record only

### 9.5 Content Scheduling
- **automation_key:** `content_scheduling`
- **description:** Assigns approved content to calendar slots per cadence.
- **trigger:** Post approved, or cadence slot opens
- **frequency:** Event-driven
- **preconditions:** Post is Approved; a future slot is available
- **action:** Create a `PostSchedule` at the user's local slot time
- **ai_capability:** None
- **approval_required:** Depends on automation level — required at Assistant, not at Copilot or Autopilot
- **retry_policy:** 2 attempts
- **failure_behavior:** Post stays Approved and unscheduled; surfaced on the calendar
- **notification_behavior:** None unless approval is required
- **audit_level:** Run record only

### 9.6 LinkedIn Publishing
- **automation_key:** `linkedin_publishing`
- **description:** Publishes an approved post at its scheduled time. **MVP 1 ships the manual path only** (`ADR-006`).
- **trigger:** Scheduled time reached
- **frequency:** Exact-time, per scheduled post
- **preconditions:** Post approved; schedule exists; if a provider path is used, the integration is connected and authorization is valid
- **action:** Manual path — notify the user, offer copy and "open LinkedIn", await confirmation. Provider path — publish via `PublishingProvider` with an idempotency key
- **ai_capability:** None
- **approval_required:** **Yes** by default in Copilot mode
- **retry_policy:** Bounded retries for transient failures; provider-directed backoff on rate limits; **no blind retry on expired or revoked authorization**
- **failure_behavior:** Mark post Failed; show "Publishing failed" prominently; notify; offer retry and manual fallback. Expired authorization marks the integration as needing reconnection and falls back to manual
- **notification_behavior:** Notify on ready-to-publish (manual), on success, and on failure
- **audit_level:** **Full audit** — public action recorded with its approval reference

### 9.7 Job Discovery
- **automation_key:** `job_discovery`
- **description:** Collects jobs from configured permitted sources.
- **trigger:** Scheduler
- **frequency:** Daily, or as configured
- **preconditions:** Goals set; discovery enabled; plan includes discovery; **at least one `JobSource` registered**
- **action:** Query registered adapters, normalize, de-duplicate
- **ai_capability:** None (collection only)
- **approval_required:** No
- **retry_policy:** 2 attempts per adapter; one failing adapter does not fail the run
- **failure_behavior:** Record per-adapter failure. With no source registered, the run is skipped and discovery is reported unavailable — not failed
- **notification_behavior:** None
- **audit_level:** Run record with per-adapter outcome

### 9.8 Job Matching
- **automation_key:** `job_matching`
- **description:** Scores filtered jobs against CareerContext.
- **trigger:** New normalized job passes filtering
- **frequency:** Event-driven, capped per run
- **preconditions:** Career Profile confirmed; quota available
- **action:** Generate a `JobMatch`
- **ai_capability:** `job_match@v1`
- **approval_required:** No
- **retry_policy:** 2 attempts
- **failure_behavior:** Skip that job, continue the batch. Quota exhaustion mid-batch persists completed matches and reports a partial result
- **notification_behavior:** None directly — strong matches surface via 9.9
- **audit_level:** Run record only

### 9.9 Job Digest
- **automation_key:** `job_digest`
- **description:** Sends only matches above the user's threshold since the last digest.
- **trigger:** Scheduler
- **frequency:** Daily or weekly, at the user's local hour
- **preconditions:** At least one match above threshold in the window
- **action:** Compose and send the digest
- **ai_capability:** None
- **approval_required:** No
- **retry_policy:** 2 attempts
- **failure_behavior:** In-app notification retained even if email fails
- **notification_behavior:** **Suppressed entirely when empty**
- **audit_level:** Digest run record

### 9.10 Profile Health Check
- **automation_key:** `profile_health_check`
- **description:** Re-runs profile analysis against current goals.
- **trigger:** Scheduler
- **frequency:** Monthly
- **preconditions:** Career Profile confirmed
- **action:** Generate a new `ProfileAnalysis` and recommendations
- **ai_capability:** `profile_analyze@v1`
- **approval_required:** No
- **retry_policy:** 2 attempts
- **failure_behavior:** Previous analysis stands, shown with its date
- **notification_behavior:** Notify only if the score moved materially or a new high-priority recommendation appeared
- **audit_level:** Run record only

### 9.11 Public Profile Refresh
- **automation_key:** `profile_refresh`
- **description:** Re-researches public professional information and proposes changes.
- **trigger:** Scheduler or manual
- **frequency:** Configurable; defaults to infrequent (research is the most expensive operation)
- **preconditions:** Career Profile confirmed; research quota available; cache window elapsed
- **action:** Research, diff against canonical, create a `ProfileChangeSet`
- **ai_capability:** `profile_research@v1` + `profile_normalize@v1`
- **approval_required:** **Yes** — canonical profile is never overwritten without per-change approval (`ADR-004`)
- **retry_policy:** 2 attempts
- **failure_behavior:** Canonical profile untouched; notify; offer manual retry
- **notification_behavior:** "We found new professional information." **No notification when nothing material changed**
- **audit_level:** **Full audit** — records what was proposed, accepted, and rejected

### 9.12 Weekly Performance Summary
- **automation_key:** `weekly_performance_summary`
- **description:** Summarizes the week's tasks, content, publishing, jobs, and next focus.
- **trigger:** Scheduler
- **frequency:** Weekly, at the user's local hour
- **preconditions:** At least one week of activity
- **action:** Compose and send the summary
- **ai_capability:** `performance_optimize@v1` for the next-focus section
- **approval_required:** No
- **retry_policy:** 2 attempts
- **failure_behavior:** Skip the week; no partial summary sent
- **notification_behavior:** Suppressed when there is no activity to report
- **audit_level:** Run record only

---

## 10. Automation Safety Rules

- Any automation can be paused or disabled, and the change takes effect immediately.
- A run already executing when a pause activates completes internal analysis but performs **no external or public action**.
- Public and reputation-sensitive actions are never hidden.
- Failures are recorded; retries are bounded; repeated failure suspends the automation and requires explicit re-enable.
- External actions use idempotency keys — no duplicate publishing, no duplicate notifications.
- Confirmed career history is never silently changed.
- No unsupported LinkedIn website automation, credentials, or browser cookies (`ADR-005`).

## 11. Approval Rules

**No approval required:** internal analysis, weekly plan generation, content ideas, draft generation, job matching, job filtering, digests, performance analysis.

**Approval required by default:** public publishing; confirming newly researched profile data into the canonical profile; any action that communicates publicly as the user unless explicitly configured and supported.

Approval requests expire. An expired request means the action **does not happen**, and the user is told what did not happen (`automation/automation-control`).

## 12. AI Feedback Loop

Captured signals: accepted and dismissed recommendations, regenerated and edited posts, approved posts, skipped and completed tasks, saved and applied jobs, rejected job suggestions, rejected profile-change proposals.

These inform future generation so rejected patterns are not repeated. The product does not claim a model is retrained on one user's data.

## 13. Prompt Versioning

Every production capability records `agent_key`, `agent_version`, prompt/config version, model name, and schema version on each `AIRun`. A material behavior change increments the version so past output stays attributable.

## 14. Evaluation

Ten representative cases, six measured dimensions, and threshold gates are specified in `12-testing-and-ai-evaluation.md`. A capability is not connected to a public or irreversible action until it meets those thresholds (`platform/ai-runtime`).

## 15. Cost Controls

AI research and generation are the dominant variable cost. Two mechanisms, with distinct owners:

**Platform/AI owns technical cost control** (`platform/ai-runtime`):

- **Result reuse** — cached profile research and de-duplicated job analysis within their configured windows, bypassed only by an explicit user-forced refresh.
- **Model tiering** — cheap tier for transformations (`profile_normalize`, `job_parse`), reasoning tier only where it creates value.
- **Token ceilings** — per-capability input and output limits.
- **Aggregate spend threshold** — crossing it alerts operators and can suspend non-essential automated generation without affecting user-initiated work.
- **Batching** where an operation naturally batches.

**Billing owns per-account limits.** Quota values, the metered-operation list, warning thresholds, and enforcement behavior live in `11-billing-entitlements.md` and `billing/plans-and-entitlements`. They are **not duplicated here**. The rule that matters across both: entitlement is checked **before** provider cost is incurred, and a batch exhausting quota partway persists what it already produced.

## 16. Privacy Controls

Only task-necessary context is sent to the AI provider. Passwords, tokens, payment secrets, and credentials never enter a prompt. `AIRun` inputs and outputs are personal data and fall under the retention and erasure rules in `10-security-privacy-compliance.md` and `platform/data-privacy-and-retention`.

## 17. Implementation Order for a New AI Feature

1. Define the input schema.
2. Define the output schema, including the insufficient-data case.
3. Assign the agent key and version.
4. Add validation at the domain-service boundary.
5. Define failure and fallback behavior.
6. Add run recording and observability.
7. Add evaluation cases and meet the thresholds.
8. **Only then** connect it to an irreversible or public action.
