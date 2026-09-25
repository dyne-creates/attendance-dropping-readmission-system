# Product Backlog

The Product Backlog contains the prioritized user stories for the Attendance, Dropping, Admission, and Re-Admission System.

## Prioritized Product Backlog

| Rank | ID | User Story | Priority | Estimate | Status |
|---|---|---|---|---:|---|
| 1 | **US-01A** | As a student, I want to log in using my Student ID and password, so that I can view my personal attendance and dropping status. | High | 2 | Backlog |
| 2 | **US-01B** | As a faculty member, I want to log in using my employee credentials, so that I can manage my assigned class rosters. | High | 2 | Backlog |
| 3 | **US-01C** | As an OSA staff member, I want to log in securely using my administrative credentials, so that I can oversee school-wide academic transitions. | High | 2 | Backlog |
| 4 | **US-02** | As a faculty member, I want to mark student attendance as present, absent, or late with optional remarks, so that student attendance can be properly monitored. | High | 3 | Backlog |
| 5 | **US-03** | As a faculty member, I want the system to automatically flag a student's status as "Dropped" when their absences exceed 20% of the total class hours, so that the dropping status is recorded without manual calculations. | High | 5 | Backlog |
| 6 | **US-04** | As a student with a dropped status, I want to submit an online re-admission request, so that I can initiate my return to the academic program. | Medium | 3 | Backlog |
| 7 | **US-05** | As an OSA staff member, I want to export dropping and re-admission records as a downloadable CSV file, so that I can organize and monitor student academic transitions. | Medium | 2 | Backlog |
| 8 | **US-06A** | As a faculty member, I want to hide previous school year class sheets from my active dashboard, so that I can keep my current records organized. | Low | 1 | Backlog |
| 9 | **US-06B** | As an OSA staff member, I want to view historical student logs from past school years in read-only mode, so that previous academic records remain preserved. | Low | 2 | Backlog |

## Backlog Prioritization

### Ranks 1 to 3: Authentication

Authentication is prioritized first because each user role needs secure access before using the system's other functions. The three login stories are small, focused stories with an estimate of 2 points each.

### Ranks 4 to 5: Attendance and Automatic Dropping

Attendance is the next priority because it provides the data needed for the dropping process. Automatic dropping is a core workflow and has a higher estimate because it requires automated calculations, status updates, and testing.

### Ranks 6 to 7: Re-admission and Data Export

Re-admission follows the dropping process because students must have a dropped status before submitting a re-admission request. CSV export supports OSA in organizing and monitoring academic transition records.

### Ranks 8 to 9: Historical Records

Historical record functions are lower priority because they support record organization and preservation after the core attendance and dropping workflow has been established.