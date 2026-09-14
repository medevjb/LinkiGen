## Purpose

Turns a LinkedIn profile URL into a structured draft career profile using AI web research of publicly available information, with CV upload and manual entry as fallbacks, so a new user reaches useful output without retyping their career history.

## ADDED Requirements

### Requirement: LinkedIn URL intake and validation
The system SHALL accept a LinkedIn profile URL as the primary onboarding input and SHALL validate its shape before spending any AI budget on it.

#### Scenario: Well-formed profile URL
- **WHEN** the user submits a URL matching a LinkedIn public profile pattern
- **THEN** the system normalizes it, stores it against the account, and starts a research run

#### Scenario: Malformed or non-profile URL
- **WHEN** the submitted value is not a LinkedIn profile URL, including a company page, a job posting, or an unrelated domain
- **THEN** the system rejects it inline with "Enter a valid LinkedIn profile URL" and SHALL NOT start a research run

#### Scenario: User skips the URL
- **WHEN** the user chooses to skip the LinkedIn URL
- **THEN** the system offers CV upload or manual entry and allows onboarding to continue without a URL

### Requirement: Public professional research
The system SHALL research publicly available professional information associated with the submitted URL and SHALL restrict itself to permitted access methods.

#### Scenario: Research executes
- **WHEN** a research run starts
- **THEN** the system performs the research asynchronously and reports meaningful stage progress to the user rather than an indeterminate spinner or a fabricated percentage

#### Scenario: Prohibited access methods
- **WHEN** any research path is implemented
- **THEN** it SHALL NOT use LinkedIn credentials, browser cookies, session tokens, CAPTCHA circumvention, or private LinkedIn endpoints, and SHALL NOT present itself to the user as authenticated LinkedIn access

#### Scenario: Provenance is recorded
- **WHEN** research returns information
- **THEN** every populated section records the sources it was derived from and a confidence level, and unsupported fields are returned as missing rather than guessed

### Requirement: Research outcome handling
The system SHALL classify every research run as success, partial success, or failure, and SHALL give the user a forward path in every case.

#### Scenario: Rich result
- **WHEN** research returns a profile above the configured completeness threshold
- **THEN** the system presents the draft profile for review with a completeness indicator and a list of sections it could not populate

#### Scenario: Sparse result
- **WHEN** research returns a profile below the completeness threshold but with some usable data
- **THEN** the system presents what it found, marks the profile as partial, and asks only for the fields that MVP features actually require

#### Scenario: No usable public data
- **WHEN** research finds no usable professional information
- **THEN** the system says so plainly and offers retry, CV upload, and manual entry, and SHALL NOT present an empty profile as a successful import

#### Scenario: Research request fails
- **WHEN** the research run errors or times out
- **THEN** the system records the failure, notifies the user, and offers retry plus the CV and manual fallbacks, and SHALL NOT leave the user on an indefinite loading state

#### Scenario: Conflicting information found
- **WHEN** research finds contradictory values for the same field, such as two current job titles
- **THEN** the system surfaces both candidates with their sources and asks the user to choose rather than silently picking one

### Requirement: CV and manual fallback
The system SHALL accept a resume or CV upload as an alternative or enrichment source and SHALL allow every required field to be entered manually.

#### Scenario: CV uploaded
- **WHEN** the user uploads a supported document within the size limit
- **THEN** the system extracts structured professional data from it and merges it into the draft profile, keeping per-field provenance distinct from web-research provenance

#### Scenario: Unsupported or oversized file
- **WHEN** the uploaded file has an unsupported type, exceeds the size limit, or fails content validation
- **THEN** the system rejects it with a specific reason and does not store the file

#### Scenario: CV conflicts with research
- **WHEN** CV data conflicts with researched data for the same field
- **THEN** the CV value SHALL take precedence as the more authoritative user-supplied source, and the conflict SHALL remain visible in review

### Requirement: Duplicate profile URL claims
The system SHALL detect when a LinkedIn URL is already associated with another account and SHALL NOT let one user silently import another person's profile.

#### Scenario: URL already claimed by a confirmed profile
- **WHEN** a user submits a LinkedIn URL that another account has already confirmed
- **THEN** the system blocks the import, explains that the profile is already claimed, and offers a support or verification path

#### Scenario: URL claimed but unconfirmed
- **WHEN** a user submits a LinkedIn URL held by another account that never confirmed it
- **THEN** the system allows the new import and releases the stale unconfirmed claim

### Requirement: Research cost control
The system SHALL bound the cost of profile research per account.

#### Scenario: Repeated research of the same URL
- **WHEN** research is requested for a URL already researched within the configured cache window
- **THEN** the system reuses the cached result instead of issuing a new billable research run, unless the user explicitly forces a refresh

#### Scenario: Research quota exhausted
- **WHEN** the account has consumed its plan's research allowance
- **THEN** the system refuses the run with a quota error naming the limit and the reset time, and offers the CV and manual paths, which SHALL remain available on every plan
