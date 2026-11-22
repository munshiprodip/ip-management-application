# ADR-008: Immutable Audit Logging, Export Sanitization & Retention

## Context
Hospital accreditation and IT security policies require a complete forensic trail of all network address ownership changes, user logins, and administrative operations. CSV/Excel exports must not expose sensitive credentials or enable CSV injection (formula injection).

## Decision
1. Every state-changing operation writes an immutable `audit_events` row inside the same database transaction.
2. Audit records capture `actor_id`, `actor_name`, `event_type`, `subject_type`, `subject_id`, `ip_address`, `before_json`, `after_json`, and `reason`.
3. Audit rows are append-only; hard deletion is prohibited.
4. Data exports sanitize formula prefixes (`=`, `+`, `-`, `@`) to prevent spreadsheet execution vulnerabilities.
5. Sensitive secrets (SNMP community strings, discovery tokens) are strictly stripped from audit JSON and logs.

## Consequences
- Guaranteed audit compliance for regulatory inspections.
- Safe data exports for management reporting.

