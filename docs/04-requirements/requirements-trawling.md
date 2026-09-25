# Requirements Trawling

Requirements trawling was conducted to identify the needs, problems, workflows, rules, and system expectations of the project's stakeholders.

## Requirements Discovery

| Area | Question | Finding | Candidate Requirement |
|---|---|---|---|
| **User** | Who needs the system? | Faculty staff, OSA staff, and students. | The system must support role-based access for students, faculty, and OSA staff. |
| **Goal** | What must the system accomplish? | Streamline the tracking of records, course withdrawals, and re-admissions while enforcing dropping before re-admission. | The system shall manage the complete workflow of student class dropping and re-admission while enforcing prerequisite checks. |
| **Workflow** | What is the process flow? | Disjointed manual steps using Google Forms and emails cause delays and allow requirements to be bypassed, such as skipping the dropping process before re-admission. | The system must provide a unified digital workflow that links faculty approval of dropping directly to student eligibility for re-admission. |
| **Data** | What information is needed? | Data is fragmented across different fields and online or offline formats. Entries may also contain typos or be unverified or tampered with. | The system must use standardized and validated data fields, such as email and phone number formats, to improve data integrity and prevent unauthorized modifications. |
| **Rule** | What business rules apply? | Dropping must be a strict prerequisite before re-admission. Records must be associated with the current active school year. | The system shall prevent a student's re-admission application from being submitted until a course withdrawal has been formally approved by faculty. |
| **Exception** | What goes wrong? | Students may enter incorrect information, faculty may bypass the dropping process, and forms may be unverified. | The system must provide clear input validation prompts and prevent procedural bypassing by unauthorized users. |
| **Security** | Who accesses what? | Academic records may be vulnerable to falsification or unauthorized modification. | Only authorized faculty and OSA staff may approve or modify records. The system must maintain audit trails. |
| **Performance** | How fast must the system be? | Manual data entry is slow, and previous systems experienced crashes under heavy loads. | The system shall maintain reliable performance and data integrity during peak academic transition periods. |
| **Usability** | Is the system easy to use? | Existing Google Forms have cluttered interfaces, too many buttons, and poor information hierarchy. | The system must provide a clean, simple, and uncluttered interface with fewer unnecessary actions for faculty and OSA staff. |

## Key Requirements Identified

The requirements trawling process identified the following major areas:

- Role-based access
- Attendance tracking
- Dropping process
- Re-admission process
- Workflow enforcement
- Data validation
- Data integrity
- Record security
- Audit trails
- School year record management
- Performance under peak loads
- Simple and consistent user interface