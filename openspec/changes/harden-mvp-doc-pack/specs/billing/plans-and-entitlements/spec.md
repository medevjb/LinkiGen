## Purpose

Defines what each plan includes, meters the AI-backed work that costs money, enforces those limits before work begins, and keeps the product coherent when a subscription is upgraded, downgraded, lapsed, or cancelled.

## ADDED Requirements

### Requirement: Plans and entitlements
The system SHALL define each plan as an explicit set of entitlements and quotas, and SHALL evaluate feature access against the account's current entitlements.

#### Scenario: Entitlement checked
- **WHEN** a user invokes a metered or gated feature
- **THEN** the system checks the account's entitlements before performing the work

#### Scenario: Free plan retains core value
- **WHEN** an account is on the free plan
- **THEN** profile import, profile confirmation, manual job analysis, and manual publishing SHALL remain available within their quotas, so the product is usable without payment

#### Scenario: Gated feature displayed
- **WHEN** a feature is not included in the account's plan
- **THEN** it SHALL be shown with its plan requirement and an upgrade path, rather than hidden or failing without explanation

### Requirement: Metering and quotas
The system SHALL meter consumption of AI-backed operations per billing period and SHALL make current usage visible to the user.

#### Scenario: Usage recorded
- **WHEN** a metered operation completes
- **THEN** the system increments that account's usage counter for the current period

#### Scenario: Failed operation not charged
- **WHEN** a metered operation fails and produces no usable result
- **THEN** it SHALL NOT consume quota

#### Scenario: Usage visible
- **WHEN** the user views their account
- **THEN** they can see current usage against each quota and the date the period resets

#### Scenario: Period reset
- **WHEN** a billing period rolls over
- **THEN** usage counters reset and any quota-suspended automations resume

### Requirement: Quota enforcement
The system SHALL enforce quotas before starting billable work and SHALL fail predictably when a limit is reached.

#### Scenario: Quota exhausted on a user-initiated action
- **WHEN** the user requests a metered operation with no remaining quota
- **THEN** the system refuses before incurring cost, names the limit and its reset date, and offers an upgrade path

#### Scenario: Quota exhausted for an automation
- **WHEN** a scheduled automation would exceed quota
- **THEN** the system SHALL skip the run, record the skip with a quota reason, notify the user at most once per period, and SHALL NOT mark the automation as failed

#### Scenario: Approaching a limit
- **WHEN** usage crosses the configured warning threshold
- **THEN** the system notifies the user once before the limit is reached

#### Scenario: Quota reached mid-batch
- **WHEN** a batch operation such as job matching exhausts quota partway through
- **THEN** the system SHALL persist the results already produced, stop cleanly, and report a partial result rather than discarding completed work

### Requirement: Subscription lifecycle
The system SHALL keep account state coherent across upgrade, downgrade, payment failure, and cancellation.

#### Scenario: Upgrade
- **WHEN** a user upgrades
- **THEN** the new entitlements apply immediately and quotas increase within the current period

#### Scenario: Downgrade below current usage
- **WHEN** a user downgrades to a plan whose quota is below their usage, or which excludes a feature they are using
- **THEN** the system SHALL NOT delete existing user data, SHALL disable the excluded automations with an explanation, and SHALL block further metered work until the next period

#### Scenario: Scheduled content exceeds downgraded entitlement
- **WHEN** a downgrade removes scheduling entitlement while posts are already scheduled
- **THEN** those posts SHALL be retained as approved drafts with the manual publishing path available, and the user SHALL be told what changed

#### Scenario: Payment fails
- **WHEN** a renewal payment fails
- **THEN** the system notifies the user, retains access through the configured grace period, and downgrades to free entitlements only after the grace period elapses

#### Scenario: Cancellation
- **WHEN** a user cancels
- **THEN** paid entitlements persist until the end of the paid period, after which the account reverts to free entitlements with its data intact

#### Scenario: Billing provider unavailable
- **WHEN** the billing provider cannot be reached
- **THEN** the system SHALL NOT revoke entitlements on that basis and SHALL reconcile once the provider is reachable
