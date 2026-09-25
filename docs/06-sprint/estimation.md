# Estimation

The team uses relative estimation to determine the amount of work required for each user story.

## Estimation Method

The team uses a **Planning Poker relative sizing scale**:

> **1, 2, 3, 5, 8, 13**

Story points represent the relative effort, complexity, and uncertainty of a user story. They do not directly represent hours or days.

## Story Estimates

| User Story ID | Points | Reason |
|---|---:|---|
| **US-01A** | 2 | Standard student login form, credential validation, and routing to the student dashboard. |
| **US-01B** | 2 | Faculty credential verification, secure session handling, and role-based access. |
| **US-01C** | 2 | OSA login validation and routing to the centralized OSA dashboard. |
| **US-02** | 3 | Attendance interface with status selection, optional remarks, and database saving. |
| **US-03** | 5 | Higher complexity due to automatic absence calculation, the 20% threshold, status updates, and related testing. |
| **US-04** | 3 | Re-admission form, attachment handling, and conditional access based on the student's dropped status. |
| **US-05** | 2 | Database record retrieval and conversion into a standardized CSV file. |
| **US-06A** | 1 | Simple filtering of previous school year class sheets from the faculty dashboard. |
| **US-06B** | 2 | Historical record display with read-only access controls. |

## Total Product Backlog Estimate

**Total: 22 story points**

## Estimation Summary

| Points | Stories |
|---:|---|
| 1 | US-06A |
| 2 | US-01A, US-01B, US-01C, US-05, US-06B |
| 3 | US-02, US-04 |
| 5 | US-03 |