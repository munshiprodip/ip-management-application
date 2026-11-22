# ADR-005: Transactional Current Assignment Pointer & Immutable History

## Context
Hospital devices change interfaces, NICs get replaced, and IP addresses get transferred or released over time. If only a single assignment table is used with nullable `released_at`, MySQL unique constraints cannot reliably prevent duplicate active assignments due to standard SQL `NULL` semantics in composite indexes.

## Decision
1. Maintain authoritative current allocation state in `ip_addresses`:
   - `allocation_status` (`assigned`, `reserved`, `quarantine`, `blocked`)
   - `current_assignment_id` (foreign key to active `ip_assignments` row)
2. Store all historical assignments in `ip_assignments` (`assigned_at`, `released_at`, `assigned_by`, `release_reason`).
3. Allocation, transfer, and release operations execute inside an atomic database transaction using row-level locking (`SELECT ... FOR UPDATE` on MySQL) and conflict-aware retry.
4. History rows are append-only and never deleted during routine operations.

## Consequences
- Guaranteed single active owner per IP address in MySQL and SQLite.
- Complete auditability and forensic traceability for hospital clinical and administrative devices.
