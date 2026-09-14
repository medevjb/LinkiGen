# Analytics Event Plan

> One event with properties for every metric in `02-mvp-prd.md` §9, so "Initial Success Metrics" is computable rather than aspirational. Events are product analytics — distinct from the operational metrics in `13-observability-and-runbooks.md`, though a few are emitted from the same code path.

## 1. Conventions

- Event names: `object_verb`, snake_case, past tense (`profile_confirmed`, not `confirm_profile`).
- Every event carries: `user_id` (or `anonymous_id` pre-signup), `timestamp`, `session_id`, and event-specific properties.
- No event carries profile content, post bodies, or free-text personal data — properties are identifiers, counts, enums, and durations, per the classification rule in `10` §3.
- Client-side events use `anonymous_id` until signup, then alias to `user_id`.

## 2. Events by PRD Metric

### Onboarding completion rate

| Event | Properties | Fires when |
|---|---|---|
| `registration_started` | `referrer` | Landing CTA clicked |
| `registration_completed` | — | Account created, pre-verification |
| `email_verified` | `minutes_since_registration` | Verification link consumed |
| `onboarding_step_reached` | `step` (`profile_import`\|`profile_review`\|`goals`\|`automation_setup`\|`dashboard`) | Each onboarding stage entered |
| `onboarding_completed` | `minutes_since_registration`, `path` (`linkedin_url`\|`cv`\|`manual`) | Dashboard first reached |
| `onboarding_abandoned` | `last_step`, `minutes_elapsed` | 24 h since last onboarding activity, incomplete |

**Rate** = `onboarding_completed` / `registration_completed`, cohorted by day.

### LinkedIn URL research success rate

| Event | Properties |
|---|---|
| `profile_import_started` | `path` (`linkedin_url`\|`cv`\|`manual`) |
| `profile_import_result` | `outcome` (`success`\|`partial`\|`no_data`\|`failed`), `completeness_score`, `duration_ms`, `cache_hit` |
| `profile_import_conflict_shown` | `field_count` |

**Rate** = `profile_import_result{outcome=success}` / `profile_import_started{path=linkedin_url}`.

### Career Profile confirmation rate

| Event | Properties |
|---|---|
| `profile_review_field_edited` | `field`, `prior_authority` (`research`\|`cv`) |
| `profile_confirmation_blocked` | `missing_field_count` |
| `profile_confirmed` | `completeness_score`, `fields_edited_count`, `minutes_since_import` |

**Rate** = `profile_confirmed` / `profile_import_result{outcome∈success,partial}`.

### Time to first useful result

| Event | Properties |
|---|---|
| `first_value_shown` | `components` (array: `career_score`\|`weekly_plan`\|`content_ideas`\|`job_matches`), `minutes_since_registration` |

This is the direct measurement of `02` §9's "time to first useful result".

### Weekly active users

| Event | Properties |
|---|---|
| `session_started` | `platform` |
| `dashboard_viewed` | `pending_approvals_count`, `automation_status` |

WAU is the distinct `user_id` count on `session_started` over a trailing 7-day window — computed, not a single event.

### Weekly plan completion rate

| Event | Properties |
|---|---|
| `weekly_plan_generated` | `task_count`, `total_estimated_minutes` |
| `task_status_changed` | `task_id`, `from_status`, `to_status`, `task_type` |
| `weekly_plan_closed` | `completed_count`, `skipped_count`, `carried_forward_count` |

**Rate** = completed / total tasks, from `weekly_plan_closed`.

### Post approval/use rate

| Event | Properties |
|---|---|
| `post_draft_generated` | `content_idea_id`, `pillar` |
| `post_ai_action_used` | `action` (`regenerate`\|`shorten`\|`expand`\|`change_tone`\|`improve_hook`) |
| `post_claim_flag_shown` | `flag_count` |
| `post_claim_flag_acknowledged` | `flag_count` |
| `post_approved` | `edit_count_before_approval`, `had_claim_flags` |
| `post_dismissed` | `stage` (`draft`\|`needs_approval`) |

**Rate** = `post_approved` / `post_draft_generated`.

### Number of scheduled/published posts

| Event | Properties |
|---|---|
| `post_scheduled` | `days_in_advance` |
| `post_rescheduled` | `reason` (`user`\|`publish_failure`) |
| `post_publish_attempted` | `publish_mode` (`manual`\|`provider`), `idempotency_key` |
| `post_published` | `publish_mode`, `seconds_from_scheduled_time` |
| `post_publish_failed` | `error_code`, `retry_count` |

`seconds_from_scheduled_time` on `post_published` is the product-facing view of the timeliness budget in `09` §3.

### Job match views

| Event | Properties |
|---|---|
| `job_analyzed` | `input_type` (`url`\|`description`), `recommendation` |
| `job_match_list_viewed` | `match_count`, `above_threshold_count` |
| `job_match_detail_viewed` | `job_id`, `recommendation`, `overall_score` |
| `job_discovery_unavailable_shown` | `reason` |

### Save/apply conversion

| Event | Properties |
|---|---|
| `job_saved` | `job_id`, `source` (`match`\|`manual_analysis`), `recommendation` |
| `job_skipped` | `job_id`, `source` |
| `application_status_changed` | `application_id`, `from_status`, `to_status` |

**Conversion** = `application_status_changed{to_status=applied}` / `job_saved`.

### Automation enablement rate

| Event | Properties |
|---|---|
| `automation_preset_selected` | `preset` (`assistant`\|`copilot`\|`autopilot`) |
| `automation_toggled` | `automation_key`, `enabled`, `is_override` |
| `automation_paused_global` / `automation_resumed_global` | — |
| `approval_request_created` | `action_type` |
| `approval_resolved` | `action_type`, `resolution` (`approved`\|`rejected`\|`expired`), `minutes_to_resolve` |

**Rate** = accounts with ≥1 automation enabled / active accounts.

### Paid conversion

| Event | Properties |
|---|---|
| `plan_upgraded` | `from_plan`, `to_plan` |
| `plan_downgraded` | `from_plan`, `to_plan`, `reason` (`user_choice`\|`payment_failed`) |
| `quota_warning_shown` | `entitlement` |
| `quota_exceeded_shown` | `entitlement`, `context` (`user_action`\|`automation_skip`) |
| `checkout_started` / `checkout_completed` | `plan` |
| `subscription_canceled` | `plan`, `will_end_at` |

**Conversion** = `checkout_completed` / accounts reaching `quota_exceeded_shown` or `first_value_shown`, cohorted.

### User-reported time saved

| Event | Properties |
|---|---|
| `time_saved_survey_shown` | `trigger` (`weekly_summary`\|`milestone`) |
| `time_saved_survey_answered` | `estimated_minutes_saved`, `weeks_active` |

The only metric in `02` §9 that is inherently self-reported rather than behavioral; captured as a periodic lightweight survey, not inferred from usage.

## 3. Cross-Cutting Events

Not tied to one PRD metric but required for cohorting and funnel analysis across all of the above.

| Event | Properties |
|---|---|
| `notification_received` | `type`, `category`, `channel` |
| `notification_read` | `type`, `minutes_to_read` |
| `digest_sent` | `digest_type`, `item_count` |
| `profile_refresh_proposed` | `changed_field_count` |
| `profile_refresh_change_resolved` | `resolution` (`accepted`\|`rejected`), `conflicted_with_user_edit` |
| `data_export_requested` / `completed` | — |
| `account_deletion_requested` / `canceled` | — |

## 4. Non-Goals

- No event contains free-text content — no post bodies, no profile text, no notes.
- No event is fired from client-side AI provider calls — there are none; all AI runs server-side (`ADR-003`).
- Session replay and pixel-level interaction tracking are out of scope for MVP.

## 5. Coverage Check

Every metric listed in `02-mvp-prd.md` §9 maps to at least one event above:

| `02` §9 metric | Section |
|---|---|
| Onboarding completion rate | §2.1 |
| LinkedIn URL research success rate | §2.2 |
| Career Profile confirmation rate | §2.3 |
| Time to first useful result | §2.4 |
| Weekly active users | §2.5 |
| Weekly plan completion rate | §2.6 |
| Post approval/use rate | §2.7 |
| Number of scheduled/published posts | §2.8 |
| Job match views | §2.9 |
| Save/apply conversion | §2.10 |
| Automation enablement rate | §2.11 |
| Paid conversion | §2.12 |
| User-reported time saved | §2.13 |
