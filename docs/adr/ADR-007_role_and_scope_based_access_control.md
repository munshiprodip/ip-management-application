# ADR-007: Scoped Role-Based Access Control (RBAC) & Persona Isolation

## Context
Different hospital departments (Biomedical, Radiology, Administration, Network Operations) have different authorities. Department operators must not view or mutate infrastructure belonging to other departments or isolated VRF contexts.

## Decision
1. Implement granular RBAC with 8 core personas:
   - Platform Super Admin
   - Network Admin
   - IP Operator
   - Department Operator
   - Auditor
   - Viewer
   - Service Agent
   - Independent Approver
2. Enforce authorization checks server-side in Laravel Policies and Form Requests.
3. Support scope isolation by Site, Department, and VRF.
4. Two-person segregation of duties: requesters cannot self-approve critical change requests.

## Consequences
- Strict compliance with hospital governance standards.
- Defense-in-depth: hidden UI elements are backed by rigorous policy evaluation.

