# Functional Requirements

Functional requirements describe the specific functions and behaviors that the system must provide to its users.

## Functional Requirements

| ID | Phase / Module | Requirement |
|---|---|---|
| **FR-01** | Login / Role Identification | The system shall allow students, faculty, and OSA staff to register and log in using their authorized account credentials. |
| **FR-02** | Faculty Dashboard | The system shall allow faculty to record and monitor student attendance by marking students as present, absent, or late, with corresponding remarks when necessary. |
| **FR-03** | Faculty Dashboard | The system shall determine when a student meets the required absence percentage for dropping based on recorded attendance data. |
| **FR-04** | Faculty Dashboard | The system shall allow faculty to drop a student who meets the required absence percentage. |
| **FR-05** | Student Dashboard | The system shall allow a dropped student to submit and process a re-admission request after the dropping process has been completed. |
| **FR-06** | OSA Dashboard | The system shall generate dropping and re-admission data for OSA staff to organize, monitor, and report student academic transitions. |
| **FR-07** | OSA Dashboard | The system shall archive records from previous school years. |

## Functional Workflow

The core system workflow is:

```text
Student Attendance
       ↓
Faculty Records Attendance
       ↓
Absence Percentage Evaluated
       ↓
Student Meets Dropping Requirement
       ↓
Faculty Approves Dropping
       ↓
Student Becomes Eligible for Re-admission
       ↓
Student Submits Re-admission Request
       ↓
OSA Organizes, Monitors, and Processes Records
       ↓
Record Archived After School Year