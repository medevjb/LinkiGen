## Purpose

Defines the canonical career profile that every other AI feature reads from — how it is reviewed and confirmed, how user corrections outrank AI inference, and how later re-research is proposed rather than silently applied.

## ADDED Requirements

### Requirement: Profile review and confirmation
The system SHALL require the user to review and confirm a generated draft profile before it becomes canonical, and SHALL NOT use an unconfirmed draft to drive recommendations, content, or job matching.

#### Scenario: User confirms the profile
- **WHEN** the user confirms the reviewed draft
- **THEN** the system marks the profile canonical, records the confirmation time, and unblocks downstream generation

#### Scenario: Unconfirmed draft
- **WHEN** a profile draft exists but has not been confirmed
- **THEN** features depending on canonical profile data SHALL be presented as blocked with an explanation pointing to profile review, rather than generating output from draft data

#### Scenario: Required data still missing at confirmation
- **WHEN** the user attempts to confirm while a field required by MVP features is empty
- **THEN** the system blocks confirmation, names each missing field, and explains which feature needs it

#### Scenario: Unsaved edits
- **WHEN** the user navigates away with unsaved profile edits
- **THEN** the system warns before discarding and preserves the edits locally where the client allows

### Requirement: Canonical profile contents
The canonical profile SHALL cover identity, headline, location, current role and company, experience, education, skills, technologies, projects, certifications, achievements, professional focus, public content themes, and career signals, and SHALL carry completeness and confidence metadata.

#### Scenario: Completeness is derived, not claimed
- **WHEN** the system displays a completeness score
- **THEN** the score SHALL be computed from which required and optional sections are populated, and SHALL be recomputed whenever the profile changes

#### Scenario: Confidence is separate from value
- **WHEN** a field was produced by AI research rather than entered by the user
- **THEN** its confidence level SHALL be stored separately from the field value and SHALL be displayable in the interface

### Requirement: User corrections outrank AI
A value the user has edited or confirmed SHALL have higher authority than any AI-inferred or researched value, and SHALL NOT be overwritten by later automated processing.

#### Scenario: User edits a researched field
- **WHEN** the user corrects a field populated by research
- **THEN** the system stores the value as user-authored, raises its authority above research output, and retains the original researched value for audit

#### Scenario: Correction informs future generation
- **WHEN** the user corrects a field
- **THEN** subsequent AI generation SHALL use the corrected value, so the same mistake is not reproduced

### Requirement: Profile editing
The system SHALL allow the user to edit, add, and delete individual profile items at any time after confirmation.

#### Scenario: Item added or removed
- **WHEN** the user adds or deletes an experience, education, skill, project, or certification entry
- **THEN** the system persists the change, recomputes completeness, and marks downstream analysis as stale

#### Scenario: Downstream staleness
- **WHEN** the canonical profile changes materially after analysis was generated
- **THEN** the system SHALL mark the existing profile analysis and recommendations as out of date and offer regeneration rather than presenting them as current

### Requirement: Profile refresh proposes changes
Re-researching a profile SHALL produce proposed changes for approval and SHALL NOT silently overwrite confirmed career history.

#### Scenario: Refresh finds new information
- **WHEN** a scheduled or manual refresh detects information not present in the canonical profile
- **THEN** the system creates a pending change set, notifies the user with "We found new professional information", and leaves the canonical profile untouched until the user responds

#### Scenario: User reviews proposed changes
- **WHEN** the user opens a pending change set
- **THEN** they can accept or reject each proposed change individually, and rejecting a change SHALL prevent that same proposal from being raised again unless its source evidence changes

#### Scenario: Refresh contradicts a user correction
- **WHEN** a refresh proposes a value that contradicts a field the user previously corrected
- **THEN** the system SHALL present the conflict explicitly, showing both the user's value and the newly found value, and SHALL default to keeping the user's value

#### Scenario: Refresh finds nothing new
- **WHEN** a refresh completes with no material differences
- **THEN** the system updates the last-researched timestamp and SHALL NOT create a notification or a change set
