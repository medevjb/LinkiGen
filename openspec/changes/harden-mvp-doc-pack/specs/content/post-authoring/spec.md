## Purpose

Generates complete, publishable LinkedIn post drafts in the user's voice from their own professional context, and gives the user AI-assisted editing controls plus a safeguard against publishing claims the profile does not support.

## ADDED Requirements

### Requirement: Post draft generation
The system SHALL generate a complete LinkedIn-ready draft from a selected idea and the user's career context, not from a generic prompt.

#### Scenario: Draft generated
- **WHEN** a draft is requested for an idea
- **THEN** the system returns a complete post body, an internal topic label, and optionally a suggested call to action and hashtags if the user has enabled them

#### Scenario: Draft uses real context
- **WHEN** a draft is generated
- **THEN** it SHALL draw on the user's actual skills, experience, and projects, and SHALL NOT assert employers, roles, projects, credentials, metrics, or outcomes absent from the canonical profile

#### Scenario: Voice consistency
- **WHEN** the user has stored tone preferences or has edited previous drafts
- **THEN** generation SHALL apply those preferences and SHALL avoid repeating the structure and opening patterns of the user's recent posts

#### Scenario: Generation fails
- **WHEN** draft generation fails after its bounded retries
- **THEN** the system preserves the idea, reports the failure with a retry action, and SHALL NOT save an empty or truncated draft as a usable post

### Requirement: AI editing actions
The system SHALL offer regenerate, shorten, expand, change tone, and improve hook as actions on an existing draft, and SHALL keep the result editable by the user.

#### Scenario: Editing action applied
- **WHEN** the user applies an AI editing action
- **THEN** the system returns a revised body, preserves the prior version so the user can revert, and does not change the post's status

#### Scenario: Editing preserves factual content
- **WHEN** an editing action runs
- **THEN** it SHALL NOT introduce new factual claims that were not present in the input draft

#### Scenario: Manual edit wins
- **WHEN** the user edits the draft text directly
- **THEN** the manual text becomes the current body and subsequent AI actions operate on it

### Requirement: Unsupported claim detection
The system SHALL check drafts for statements the canonical profile does not support and SHALL warn the user before approval.

#### Scenario: Unsupported statement detected
- **WHEN** a draft contains a claim not grounded in the canonical profile, such as an unverifiable metric or an unlisted role
- **THEN** the system flags the specific statement with "Review this statement before publishing" and SHALL NOT block editing

#### Scenario: User accepts a flagged claim
- **WHEN** the user acknowledges a flagged statement and approves the post
- **THEN** the system records the acknowledgement and allows approval to proceed

### Requirement: Post approval
The system SHALL treat approval as an explicit user act that gates publication, and SHALL track post status through Draft, Needs Approval, Approved, Scheduled, Published, and Failed.

#### Scenario: Draft approved
- **WHEN** the user approves a draft
- **THEN** the system records the approving user and time, moves the post to Approved, and makes it eligible for scheduling

#### Scenario: Approved post edited
- **WHEN** an approved post is edited before publishing
- **THEN** the system SHALL revoke the approval and return the post to Needs Approval, so no unapproved text can be published

#### Scenario: Approval feedback captured
- **WHEN** a post is approved, heavily edited, or discarded
- **THEN** the system records the outcome as content feedback for future generation
