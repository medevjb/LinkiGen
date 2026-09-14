## Purpose

Produces the user's highest-value career actions for each week automatically, so a busy user opens the product to a prepared plan rather than a blank page, and tracks each task through completion, skipping, or rescheduling.

## ADDED Requirements

### Requirement: Automatic weekly plan generation
The system SHALL generate a weekly plan automatically for every eligible user without requiring the user to request it.

#### Scenario: Plan generated on schedule
- **WHEN** the weekly trigger fires for a user with a confirmed profile, goals set, and the weekly-plan automation enabled
- **THEN** the system generates a plan for the upcoming week and notifies the user that it is ready

#### Scenario: Preconditions unmet
- **WHEN** the trigger fires for a user whose profile is unconfirmed or whose goals are unset
- **THEN** the system SHALL skip generation, record the skip with its reason, and SHALL NOT send a "plan ready" notification

#### Scenario: Plan already exists for the week
- **WHEN** generation is triggered for a week that already has a plan
- **THEN** the system SHALL NOT create a duplicate plan, and the run completes as a no-op

#### Scenario: Timezone correctness
- **WHEN** week boundaries and suggested dates are computed
- **THEN** they SHALL be derived from the user's stored timezone, not from server time

### Requirement: Plan composition
Each plan SHALL contain a bounded set of prioritized tasks, each with a type, title, description, priority, suggested date, effort estimate, automation indicator, and rationale.

#### Scenario: Task count is bounded
- **WHEN** a plan is generated
- **THEN** the number of tasks SHALL NOT exceed the configured weekly maximum, and total estimated effort SHALL respect the user's stated available time where known

#### Scenario: Tasks match the user's goal
- **WHEN** the user's primary goal is content growth only
- **THEN** the plan SHALL NOT fill itself with job-search tasks, and vice versa for a job-search-only goal

#### Scenario: No duplicate or repeated tasks
- **WHEN** a plan is generated
- **THEN** it SHALL NOT contain tasks materially identical to each other, nor to tasks the user completed or skipped in recent weeks, unless the task is explicitly recurring

#### Scenario: Automation state is visible on a task
- **WHEN** a task corresponds to work the system has already prepared or performed
- **THEN** the task SHALL indicate whether it was AI-prepared, AI-executed, or awaiting the user's approval

### Requirement: Task lifecycle
The system SHALL track each task through To Do, In Progress, Completed, and Skipped states, and SHALL allow rescheduling and replacement.

#### Scenario: Task completed
- **WHEN** the user marks a task complete
- **THEN** the system records the completion time, updates weekly progress, and stores the outcome as planning feedback

#### Scenario: Task skipped
- **WHEN** the user skips a task
- **THEN** the system records the skip as negative feedback for that task type and reduces the likelihood of proposing a materially identical task next week

#### Scenario: Task rescheduled
- **WHEN** the user moves a task to a different day
- **THEN** the system updates the suggested date and retains the task within the same weekly plan

#### Scenario: Task replaced
- **WHEN** the user requests a replacement for a task
- **THEN** the system generates a different task of comparable value and SHALL NOT return the task it just replaced

#### Scenario: Unfinished tasks at week end
- **WHEN** a week ends with incomplete tasks
- **THEN** the system SHALL close the plan, retain the incomplete tasks in history, and MAY carry forward still-relevant tasks into the next plan without duplicating them

### Requirement: Plan generation failure
The system SHALL handle plan generation failures without leaving the user without a plan or without an explanation.

#### Scenario: Generation fails
- **WHEN** a plan generation run fails after its bounded retries
- **THEN** the system records the failure, surfaces it on the dashboard and in automation history, and offers manual regeneration

#### Scenario: Empty state before first plan
- **WHEN** the user reaches the weekly plan screen before any plan exists
- **THEN** the system explains that the first plan is being prepared and when to expect it, rather than showing an unexplained empty list
