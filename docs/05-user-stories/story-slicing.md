# Story Slicing

Story slicing divides large or complex user stories into smaller stories that can be developed, tested, and delivered more easily.

## Sliced User Stories

### US-01A: Student Login

> As a student, I want to log in using my Student ID and password, so that I can view my personal attendance and dropping status.

### US-01B: Faculty Login

> As a faculty member, I want to log in using my employee credentials, so that I can manage my assigned class rosters.

### US-01C: OSA Login

> As an OSA staff member, I want to log in securely using my administrative credentials, so that I can oversee school-wide academic transitions.

### US-02: Attendance Recording

> As a faculty member, I want to mark student attendance as present, absent, or late with optional remarks, so that student attendance can be properly monitored.

### US-03: Faculty Drop Decision

> As a faculty member, I want to manually drop a student from my course at my discretion, so that my dropping decision is properly recorded.

### US-04: Re-admission Request

> As a student with a dropped status, I want to submit an online re-admission request, so that I can initiate my return to the academic program.

### US-05: OSA Data Export

> As an OSA staff member, I want to export dropping and re-admission records as a downloadable CSV file, so that I can organize and monitor student academic transitions.

### US-06A: Faculty Historical Records

> As a faculty member, I want to hide previous school year class sheets from my active dashboard, so that I can keep my current records organized.

### US-06B: OSA Historical Records

> As an OSA staff member, I want to view historical student logs from past school years in read-only mode, so that previous academic records remain preserved.

## Story Slicing Summary

| Original Story | Sliced Stories | Reason |
|---|---|---|
| **US-01** | US-01A, US-01B, US-01C | Different user roles have different login requirements and access levels. |
| **US-02** | US-02 | The story is already small enough after clarification. |
| **US-03** | US-03 | A faculty member's manual drop decision and its recordkeeping are one focused behavior. |
| **US-04** | US-04 | Re-admission remains a separate student workflow after the faculty records a drop. |
| **US-05** | US-04 | The re-admission request is focused on the student's submission after being dropped. |
| **US-06** | US-05 | The story is narrowed to exporting dropping and re-admission records. |
| **US-07** | US-06A, US-06B | Faculty and OSA have different requirements for accessing historical records. |
