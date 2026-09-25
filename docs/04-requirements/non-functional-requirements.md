# Non-Functional Requirements

Non-functional requirements define the quality attributes and performance standards that the system must meet.

## Non-Functional Requirements

| ID | Category | Requirement |
|---|---|---|
| **NFR-01** | Security | The system must enforce role-based access control (RBAC), securely store user passwords using appropriate password hashing, and automatically terminate active user sessions after **15 minutes of inactivity** to help prevent unauthorized data modification. |
| **NFR-02** | Performance | The system shall process standard database queries within **2 seconds** and generate downloadable CSV reports within **5 seconds** under a simulated peak load of up to **500 active users**. |
| **NFR-03** | Usability | The interface shall follow a **3-click rule**, allowing faculty to record attendance or OSA staff to access the report dashboard from any screen within a maximum of three navigation clicks, without requiring system training. |
| **NFR-04** | Reliability | The database management system shall maintain **99.9% data consistency**, using automated database transaction rollbacks to prevent partial saves or corrupted student records during network interruptions. |

## Requirement Categories

### NFR-01: Security

The system must protect student and academic records by:

- Enforcing role-based access control.
- Restricting functions according to user roles.
- Securely hashing user passwords.
- Automatically ending inactive sessions after 15 minutes.
- Preventing unauthorized data modification.

### NFR-02: Performance

The system must remain responsive during normal and peak usage.

- Standard database queries: **within 2 seconds**
- CSV report generation: **within 5 seconds**
- Simulated peak load: **up to 500 active users**

### NFR-03: Usability

The system should be simple and easy to navigate.

- Maximum of **3 navigation clicks** for specified tasks.
- Faculty can quickly access attendance recording.
- OSA staff can quickly access the report dashboard.
- No formal system training should be required for these tasks.

### NFR-04: Reliability

The system must protect data from incomplete or corrupted transactions.

- Maintain **99.9% data consistency**.
- Use database transaction rollbacks.
- Prevent partial saves.
- Protect student records during network interruptions.