# Sprint Backlog

## Sprint 1

**Sprint Goal:**

> Establish the foundation of the system by setting up the collaborative development environment and implementing secure role-based authentication and faculty attendance recording.

## Selected User Stories

| ID | Points | Description |
|---|---:|---|
| **US-01A** | 2 | Student login with credential verification and dashboard routing. |
| **US-01B** | 2 | Faculty authentication and secure credential verification. |
| **US-01C** | 2 | OSA staff authentication and routing to the centralized administrative dashboard. |
| **US-02** | 3 | Faculty attendance roster with attendance statuses and optional remarks. |
| **Total** | **9** | **Sprint commitment** |

## Task Breakdown

| Task Category | US-01A / US-01B / US-01C: Authentication | US-02: Attendance |
|---|---|---|
| **Design** | Design clean login forms and role-specific dashboard landing views. | Design the interactive student attendance roster. |
| **Frontend** | Implement credential input fields and client-side validation. | Implement attendance status controls and optional remarks fields. |
| **Backend** | Set up user credential schema and secure session handling. | Set up attendance storage and database operations for saving roster records. |
| **Testing / QA** | Test invalid login attempts, successful authentication, role routing, and sign-out behavior. | Verify attendance statuses and remarks are correctly saved and retrieved. |
| **Integration** | Integrate authentication with role-based dashboard access. | Integrate attendance recording with the faculty dashboard and database. |

## Sprint Backlog Rules

- Only the selected Sprint 1 stories are part of the committed Sprint Backlog.
- **US-05** may be considered a stretch item only if the committed 9-point work is completed and sufficient capacity remains.
- The team should prioritize completing the Sprint Goal before working on stretch items.
- Completed work must satisfy the team's Definition of Done before being considered part of the Increment.