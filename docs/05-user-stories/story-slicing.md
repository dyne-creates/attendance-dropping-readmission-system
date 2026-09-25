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

### US-03: Automatic Dropping Status

> As a faculty member, I want the system to automatically flag a student's status as "Dropped" when their absences exceed 20% of the total class hours, so that the dropping status is recorded without manual calculations.

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
| **US-03** | US-03 | The absence threshold and automatic status update are defined together as one focused behavior. |
| **US-04** | Included in US-03 | The automatic dropping status removes the need for a separate manual dropping story in the current scope. |
| **US-05** | US-04 | The re-admission request is focused on the student's submission after being dropped. |
| **US-06** | US-05 | The story is narrowed to exporting dropping and re-admission records. |
| **US-07** | US-06A, US-06B | Faculty and OSA have different requirements for accessing historical records. |