## Purpose

Lets a user paste a job URL or description and get a structured, explained assessment of how well it fits their career profile and goals, with a clear recommendation and concrete preparation advice.

## ADDED Requirements

### Requirement: Manual job intake
The system SHALL accept a job for analysis by URL, by pasted description, or both, and SHALL require at least one of them.

#### Scenario: Description pasted
- **WHEN** the user submits a job description of at least the configured minimum length
- **THEN** the system parses it into structured job data and runs a match analysis

#### Scenario: URL submitted alone
- **WHEN** the user submits only a job URL
- **THEN** the system attempts to retrieve and parse the posting, and on success proceeds to analysis

#### Scenario: URL cannot be retrieved
- **WHEN** the URL cannot be fetched, is behind authentication, or yields no usable job content
- **THEN** the system explains that it could not read the posting and asks the user to paste the description instead, rather than analyzing an empty job

#### Scenario: Neither input provided
- **WHEN** the user submits with no URL and no description
- **THEN** the system blocks submission with an inline error naming the requirement

#### Scenario: Submitted content is not a job posting
- **WHEN** parsing determines the content is not a job posting
- **THEN** the system reports this and SHALL NOT produce a match score

### Requirement: Job parsing
The system SHALL normalize a job posting into structured fields before matching.

#### Scenario: Structured fields extracted
- **WHEN** a job is parsed
- **THEN** the system extracts role, company, location, remote type, seniority, responsibilities, required skills, preferred skills, experience requirements, employment type, and salary where stated

#### Scenario: Field absent from the posting
- **WHEN** a field such as salary is not stated in the posting
- **THEN** the system SHALL record it as unknown and SHALL NOT infer a value

### Requirement: Match analysis
The system SHALL score a parsed job against the user's canonical profile and goals and SHALL explain the result.

#### Scenario: Scores produced
- **WHEN** a match analysis runs
- **THEN** it returns an overall score plus skill, experience, seniority, location, and career-fit sub-scores, each on a consistent published scale

#### Scenario: Recommendation produced
- **WHEN** an analysis completes
- **THEN** it returns exactly one recommendation from STRONG_APPLY, APPLY, CONSIDER, or SKIP, and the recommendation SHALL be consistent with the overall score thresholds

#### Scenario: Explanation provided
- **WHEN** the user views a result
- **THEN** the system shows matching strengths, missing requirements, and reasoning that references the user's own profile content

#### Scenario: Preparation advice
- **WHEN** a job is a partial fit
- **THEN** the system SHALL suggest concrete preparation or profile changes to improve the application, rather than only reporting the gap

#### Scenario: Analysis without a confirmed profile
- **WHEN** analysis is requested before the profile is confirmed
- **THEN** the system SHALL block it and direct the user to profile confirmation, since scores would be unreliable

#### Scenario: Analysis fails
- **WHEN** the analysis run fails after its bounded retries
- **THEN** the system reports the failure with a retry action and preserves the submitted job content so the user does not re-paste it

### Requirement: Duplicate analysis avoidance
The system SHALL avoid re-billing and re-running analysis for a job it has already analyzed for the same user.

#### Scenario: Same job submitted again
- **WHEN** the user submits a job that normalizes to one already analyzed for them within the configured window
- **THEN** the system returns the existing analysis and offers an explicit re-analyze action

### Requirement: Result actions
The system SHALL allow the user to act on an analysis result without leaving the screen.

#### Scenario: Result saved
- **WHEN** the user saves the analyzed job
- **THEN** the system creates an application record in Saved status linked to the match

#### Scenario: Result dismissed
- **WHEN** the user dismisses a result
- **THEN** the system records the dismissal as job-preference feedback and does not resurface that job
