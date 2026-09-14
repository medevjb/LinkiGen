## Purpose

Sets the handling rules for the personal and professional data this product collects — what may be sent to third parties, how long each class is kept, and how export and erasure requests are satisfied across the database, object storage, AI run records, and background queues.

## ADDED Requirements

### Requirement: Data classification
The system SHALL classify the data it stores and SHALL apply handling rules by class.

#### Scenario: Classes defined
- **WHEN** a new data field is introduced
- **THEN** it SHALL be assigned a class among account credentials, personal identity data, professional profile data, user-generated content, derived AI output, operational telemetry, and payment data

#### Scenario: Credential handling
- **WHEN** credentials or secrets are stored
- **THEN** passwords SHALL be stored only as salted hashes, third-party tokens SHALL be encrypted at rest, and neither SHALL appear in logs, exports, error reports, or AI prompts

#### Scenario: Payment data
- **WHEN** payment is processed
- **THEN** card details SHALL be handled by the payment provider and SHALL NOT be stored by this system

### Requirement: Third-party disclosure and consent
The system SHALL tell the user what leaves the product and to whom, before it happens.

#### Scenario: Research disclosure at onboarding
- **WHEN** the user is asked for their LinkedIn URL
- **THEN** the screen SHALL explain that the system will search publicly available information and that an AI provider will process it, before the user submits

#### Scenario: Prompt minimization
- **WHEN** data is sent to an AI provider
- **THEN** only the fields required by that task SHALL be sent, consistent with the AI runtime context rules

#### Scenario: Uploaded documents
- **WHEN** a CV is uploaded
- **THEN** it SHALL be stored in private object storage, served only through time-limited signed links, and its extracted content SHALL be subject to the same rules as profile data

### Requirement: Retention schedule
Each data class SHALL have a defined retention period, and data SHALL be removed or anonymized when it expires.

#### Scenario: Retention defined per class
- **WHEN** the retention schedule is published
- **THEN** it SHALL state a period for AI run inputs and outputs, notification history, automation run history, uploaded documents, superseded profile versions, and operational logs

#### Scenario: Expired data removed
- **WHEN** a retention period elapses for a record
- **THEN** the system SHALL remove or irreversibly anonymize it without user action

#### Scenario: Retention does not break the product
- **WHEN** a record expires that a user-facing feature references, such as an old automation run
- **THEN** the feature SHALL handle its absence gracefully rather than erroring

### Requirement: Data subject requests
The system SHALL satisfy access, export, correction, and erasure requests within the published response window.

#### Scenario: Export request
- **WHEN** a user requests their data
- **THEN** the system SHALL produce a machine-readable export covering every class of their personal data except secrets, delivered by a time-limited signed link

#### Scenario: Erasure request
- **WHEN** a user requests erasure
- **THEN** the system SHALL erase or irreversibly anonymize their personal data across the database, object storage, AI run records, notification history, and queued jobs, retaining only what a legal or financial obligation requires

#### Scenario: Erasure of retained obligations
- **WHEN** records must be retained for legal or financial reasons
- **THEN** they SHALL be reduced to the minimum required fields and SHALL be documented in the retention schedule

#### Scenario: Correction request
- **WHEN** a user corrects their data
- **THEN** the correction SHALL take effect in the canonical profile and SHALL be used by subsequent AI generation, consistent with the career profile authority rules

### Requirement: Access control and auditing
Access to personal data SHALL be restricted and administrative access SHALL be auditable.

#### Scenario: User data isolation
- **WHEN** any request reads or writes user data
- **THEN** it SHALL be scoped to the authenticated account, and SHALL NOT be able to reach another account's records by identifier manipulation

#### Scenario: Administrative access
- **WHEN** an operator accesses a user's personal data for support
- **THEN** the access SHALL be authenticated, minimized to what the task needs, and recorded in an audit log

#### Scenario: Logs exclude personal content
- **WHEN** operational logs are written
- **THEN** they SHALL identify records by identifier and SHALL NOT embed profile content, post bodies, or personal identifiers beyond what diagnosis requires

### Requirement: Breach and incident handling
The system SHALL be able to detect and respond to suspected exposure of personal data.

#### Scenario: Suspected exposure
- **WHEN** exposure of personal data is suspected
- **THEN** the incident procedure SHALL define how to assess scope from the audit and access logs, who is notified, and within what timeframe affected users are informed

#### Scenario: Credential compromise
- **WHEN** account credentials are suspected compromised
- **THEN** the system SHALL be able to invalidate all sessions for affected accounts and force a password reset
