## Purpose

Gives the user explicit, revocable control over how much work the agent does on their behalf — which automations run, at what level of autonomy, what requires approval — and makes every run, approval, and failure visible and auditable.

## ADDED Requirements

### Requirement: Automation levels
The system SHALL support Assistant, Copilot, and Autopilot levels, SHALL default new accounts to Copilot, and SHALL allow per-automation overrides of the selected preset.

#### Scenario: Preset applied
- **WHEN** the user selects a preset
- **THEN** the system applies that level to every automation that supports it and shows the resulting per-automation configuration before saving

#### Scenario: Default for new accounts
- **WHEN** an account completes onboarding without changing automation settings
- **THEN** the account operates at Copilot, with public publishing requiring approval

#### Scenario: Per-automation override
- **WHEN** the user overrides one automation's level
- **THEN** that override SHALL persist and SHALL NOT be reset by later preset changes without the user being told which overrides will be replaced

#### Scenario: Assistant level
- **WHEN** an automation runs at Assistant level
- **THEN** the system SHALL produce a recommendation only and SHALL NOT perform the action

#### Scenario: Autopilot boundaries
- **WHEN** an automation runs at Autopilot
- **THEN** it MAY perform permitted routine actions without approval, but actions that publish publicly as the user or overwrite confirmed career history SHALL still require explicit prior configuration and SHALL NOT be enabled by a preset alone

### Requirement: Automation configuration
Each automation SHALL be individually inspectable and configurable, and SHALL disclose what it does, when it runs, and what it needs.

#### Scenario: Automation row contents
- **WHEN** the user views the automation centre
- **THEN** each automation shows its name, plain-language description, enabled state, frequency, approval requirement, last run outcome, and next run where scheduled

#### Scenario: Automation toggled off
- **WHEN** the user disables an automation
- **THEN** no further runs SHALL be scheduled for it, already-queued runs SHALL be cancelled, and the change SHALL take effect without requiring a restart or re-login

#### Scenario: Precondition not met
- **WHEN** an automation cannot run because a precondition is unmet, such as an unconfirmed profile or a missing integration
- **THEN** the system SHALL show the automation as blocked, name the exact missing precondition, and link to where it can be resolved

#### Scenario: Plan does not include an automation
- **WHEN** the user's plan does not include a given automation
- **THEN** it SHALL be shown as unavailable with the plan requirement stated, rather than hidden or silently inert

### Requirement: Global pause
The system SHALL allow the user to pause all automation at once and SHALL honour the pause immediately.

#### Scenario: Pause activated
- **WHEN** the user pauses automation
- **THEN** no new runs start, scheduled publishing is suspended, and the dashboard clearly shows that the agent is paused

#### Scenario: Run in flight at pause time
- **WHEN** a run is already executing when the pause is activated
- **THEN** it SHALL complete any internal analysis but SHALL NOT perform an external or public action, and SHALL record that it stopped due to the pause

#### Scenario: Resume
- **WHEN** the user resumes automation
- **THEN** scheduling resumes from the next scheduled occurrence, and the system SHALL NOT retroactively execute runs missed during the pause

### Requirement: Approval requests
Actions requiring approval SHALL create an explicit approval request that the user can find, act on, and that expires safely.

#### Scenario: Approval required
- **WHEN** an automation reaches an action that requires approval at the user's configured level
- **THEN** the system creates an approval request, notifies the user, and holds the action without performing it

#### Scenario: Approval granted
- **WHEN** the user approves the request
- **THEN** the system performs the action, records who approved it and when, and links the approval to the resulting run

#### Scenario: Approval rejected
- **WHEN** the user rejects the request
- **THEN** the action is abandoned, the rejection is recorded as feedback, and the system SHALL NOT re-raise the identical request automatically

#### Scenario: Approval expires
- **WHEN** an approval request passes its expiry without a response
- **THEN** the system SHALL NOT perform the action, SHALL mark the request expired, and SHALL inform the user what did not happen

#### Scenario: Approvals are discoverable
- **WHEN** any approval request is pending
- **THEN** it SHALL be surfaced on the dashboard as requiring action, not only in a notifications list

### Requirement: Run recording and auditability
Every automation run SHALL be recorded with enough detail to explain what the system did on the user's behalf.

#### Scenario: Run recorded
- **WHEN** any automation run starts and finishes
- **THEN** the system records the automation, trigger type, start and end time, status, retry count, any error, and a reference to what it produced

#### Scenario: User inspects history
- **WHEN** the user opens automation history
- **THEN** they can see what ran, what it produced, what needed approval, and what failed, for at least the published retention window

#### Scenario: Public action audit
- **WHEN** the system performs an action that is publicly visible as the user
- **THEN** that action SHALL be recorded with its approval reference and SHALL be visible to the user afterwards

### Requirement: Failure handling
Automation failures SHALL be bounded, visible, and never misreported as success.

#### Scenario: Retryable failure
- **WHEN** a run fails for a retryable reason
- **THEN** the system retries within the bounded policy with backoff and records each attempt

#### Scenario: Retries exhausted
- **WHEN** retries are exhausted
- **THEN** the run is marked failed, the user is notified once, and the automation centre shows the failure with its reason and a retry action

#### Scenario: Repeated failure
- **WHEN** an automation fails on consecutive scheduled runs beyond the configured threshold
- **THEN** the system SHALL suspend that automation, tell the user why, and require an explicit re-enable rather than failing indefinitely

#### Scenario: Idempotency for external actions
- **WHEN** a run that performs an external action is retried
- **THEN** it SHALL use an idempotency key so the external action occurs at most once
