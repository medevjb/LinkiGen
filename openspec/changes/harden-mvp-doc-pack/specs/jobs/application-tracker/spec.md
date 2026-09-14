## Purpose

Gives the user a lightweight record of every opportunity they are pursuing — its status, notes, next action, and link back to the original posting — without imposing a full CRM pipeline.

## ADDED Requirements

### Requirement: Application records
The system SHALL create and maintain an application record for each job the user saves or applies to, linked to the job and its match analysis where one exists.

#### Scenario: Job saved
- **WHEN** the user saves a job from a match or a manual analysis
- **THEN** the system creates an application record in Saved status, linked to the job, the match, and the source URL

#### Scenario: Duplicate save
- **WHEN** the user saves a job they have already saved
- **THEN** the system SHALL NOT create a second record and SHALL surface the existing one

#### Scenario: Source posting removed
- **WHEN** the original posting is no longer reachable
- **THEN** the application record SHALL remain intact with the job details it captured, and the source link SHALL be marked as possibly expired

### Requirement: Status lifecycle
The system SHALL track applications through Saved, Applied, Interview, Offer, Rejected, and Archived, and SHALL allow the user to set any status directly.

#### Scenario: Status updated
- **WHEN** the user changes an application's status
- **THEN** the system records the new status with its timestamp and retains the prior status history

#### Scenario: Marked applied
- **WHEN** the user marks an application as Applied
- **THEN** the system records the application date and uses the signal as positive feedback for that job's attributes

#### Scenario: Non-linear status change
- **WHEN** the user moves an application backward or skips a stage
- **THEN** the system SHALL accept the change without enforcing a fixed pipeline order

### Requirement: Notes and next actions
The system SHALL let the user attach free-form notes and a next-action date to any application.

#### Scenario: Note added
- **WHEN** the user adds or edits a note
- **THEN** the system saves it against the application and shows the last-updated time

#### Scenario: Next action date set
- **WHEN** the user sets a next-action date
- **THEN** the application surfaces on the dashboard as that date approaches, computed in the user's timezone

#### Scenario: Next action overdue
- **WHEN** a next-action date passes without the application being updated
- **THEN** the system SHALL flag it as needing attention rather than silently letting it age

### Requirement: Application list
The system SHALL present applications grouped by status with the information needed to act without opening each record.

#### Scenario: List contents
- **WHEN** the user opens the applications screen
- **THEN** each record shows role, company, match score where known, status, the relevant date, and the next action

#### Scenario: Empty state
- **WHEN** the user has no applications
- **THEN** the system explains how applications get created and links to job matches or manual analysis
