# INVEST Analysis

The INVEST framework is used to evaluate whether each user story is well-formed and suitable for Agile development.

## INVEST Criteria

| Letter | Criterion | Meaning |
|---|---|---|
| **I** | Independent | The story can be developed with minimal dependency on other stories. |
| **N** | Negotiable | The details can be discussed and refined with the team. |
| **V** | Valuable | The story provides clear value to a user or stakeholder. |
| **E** | Estimable | The development team can reasonably estimate the work required. |
| **S** | Small | The story is small enough to be completed within a suitable development period. |
| **T** | Testable | The story has clear conditions that can be used to verify its completion. |

## Legend

- **/** = Acceptable
- **?** = Needs Discussion
- **X** = Requires Refinement

## INVEST Review

| Story ID | I | N | V | E | S | T |
|---|---|---|---|---|---|---|
| **US-01** | / | / | X | / | X | / |
| **US-02** | / | ? | / | / | / | / |
| **US-03** | / | / | / | ? | / | ? |
| **US-04** | X | / | / | ? | / | ? |
| **US-05** | X | / | / | / | / | / |
| **US-06** | / | ? | / | / | / | / |
| **US-07** | ? | / | / | X | X | X |

## Analysis

### US-01

**Issues:**
- Covers three different user roles in one story.
- The login requirements differ between students, faculty, and OSA staff.
- The story is too large to represent one focused user need.

**Action:** Slice the story into separate login stories for each user role.

### US-02

**Issues:**
- The story is generally clear.
- The exact attendance behavior and optional remarks may require further discussion.

**Action:** Clarify the attendance recording behavior during implementation.

### US-03

**Issues:**
- The faculty member must be able to make the drop decision without an absence threshold.
- The student and OSA need an auditable record of who made the decision and when.

**Action:** Record faculty-initiated drops and notify the student; attendance totals remain informational.

### US-04

**Issues:**
- Re-admission depends on the student having a faculty-recorded Dropped status.
- Re-admission requirements and attachments must be specified separately.

**Action:** Keep re-admission as a separate workflow that follows a faculty drop decision.

### US-05

**Issues:**
- The story depends on a student already having a dropped status.
- The re-admission process contains several possible steps and requirements.

**Action:** Define the eligibility condition and split the process into smaller stories if necessary.

### US-06

**Issues:**
- Generating, organizing, monitoring, and reporting data may represent multiple functions.

**Action:** Clarify whether the story focuses on data export, monitoring, reporting, or all three.

### US-07

**Issues:**
- Archiving records involves different needs for faculty and OSA staff.
- The story includes hiding, viewing, and preserving historical records.
- These functions can be separated into smaller stories.

**Action:** Split the story into separate faculty and OSA historical record stories.
