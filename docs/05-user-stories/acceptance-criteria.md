# Acceptance Criteria

Acceptance criteria define the conditions that must be satisfied for a user story to be considered complete and acceptable.

## Acceptance Criteria

| Story ID | User Story | AC-01: Successful Path | AC-02: Validation / Errors | AC-03: Data Security and Rules |
|---|---|---|---|---|
| **US-01A** | As a student, I want to log in using my Student ID and password, so that I can view my personal attendance and dropping status. | The system grants access and routes the student to the Student Attendance Dashboard when a valid Student ID and correct password are submitted. | The system denies access, keeps the student on the login page, and displays: **"Invalid Student ID or password."** | Logged-in students must only view their own attendance and dropping records and must not be able to edit the data. |
| **US-01B** | As a faculty member, I want to log in using my employee credentials, so that I can manage my assigned class rosters. | The system grants access and routes the faculty member to the Faculty Class Roster Overview when valid employee credentials are submitted. | The system denies access and displays an error notification when the account credentials are unrecognized or incorrect. | Faculty members may only view and manage course sections assigned to them for the current semester. |
| **US-01C** | As an OSA staff member, I want to log in securely using my administrative credentials, so that I can oversee school-wide academic transitions. | The system grants access and routes the OSA staff member to the Centralized OSA Dashboard when verified administrative credentials are submitted. | The system denies access and logs the failed administrative login attempt when unauthorized credentials are used. | The OSA dashboard may provide authorized administrative functions such as cross-department data, exports, and re-admission reviews. |
| **US-02** | As a faculty member, I want to mark student attendance as present, absent, or late with optional remarks, so that student attendance can be properly monitored. | Selecting an attendance status updates the attendance record for the student on the active class sheet. | If an attendance status is not selected, the student's attendance record remains marked as unsubmitted and the faculty member is prompted to complete it. | The selected attendance status and remarks must be saved to the database together when the faculty member selects **Save**. |
| **US-03** | As a faculty member, I want the system to automatically flag a student's status as "Dropped" when their absences exceed 20% of the total class hours, so that the dropping status is recorded without manual calculations. | When a student's absences exceed 20% of the total class hours, the system automatically updates the student's status to **Dropped**. | The system prevents the faculty member from recording future attendance for a student whose status is already **Dropped**. | The automatic status update must trigger an on-screen confirmation or dashboard notification for the faculty member. |
| **US-04** | As a student with a dropped status, I want to submit an online re-admission request, so that I can initiate my return to the academic program. | The system saves the re-admission request and displays: **"Your re-admission request has been successfully forwarded to the OSA."** | The submission button remains disabled until the required **Reason for Absences** field is completed and the required attachment is uploaded. | The re-admission request interface must remain unavailable to students who do not have an active **Dropped** status. |
| **US-05** | As an OSA staff member, I want to export dropping and re-admission records as a downloadable CSV file, so that I can organize and monitor student academic transitions. | Selecting the export function compiles the filtered records and initiates a download of a CSV file. | If there are no matching records for the selected filters, the system displays a warning and does not generate an empty report. | The CSV file must use standardized columns, including **Student ID, Student Name, Section Code, Drop Date, and Status**. |
| **US-06A** | As a faculty member, I want to hide previous school year class sheets from my active dashboard, so that I can keep my current records organized. | The dashboard automatically hides class sheets associated with previous school years. | A filter must remain available for the faculty member to intentionally view older school years when needed. | Historical class sheets must be read-only and must not allow accidental modifications. |
| **US-06B** | As an OSA staff member, I want to view historical student logs from past school years in read-only mode, so that previous academic records remain preserved. | Selecting **Historical Logs** displays records belonging to previous academic years. | Turning off **Historical Logs** returns the dashboard to the current active school year records. | Historical records must be read-only. Create, update, and delete functions must be disabled while viewing historical records. |

## Acceptance Criteria Rules

The system must ensure that:

1. Users can only access functions allowed by their assigned role.
2. Students cannot access another student's records.
3. Faculty members can only manage their assigned classes.
4. OSA staff can access authorized administrative functions.
5. Students can only submit re-admission requests after receiving a **Dropped** status.
6. Required fields and attachments must be completed before submission.
7. Attendance data must be recorded consistently.
8. Dropping status must follow the defined absence threshold.
9. Historical records must be preserved and protected from modification.
10. Exported records must use a standardized format.