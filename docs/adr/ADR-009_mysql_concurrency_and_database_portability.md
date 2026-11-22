# ADR-009: MySQL Concurrency & SQLite Cross-Engine Database Portability

## Context
SQLite is chosen for rapid local development and unit testing, while MySQL 8.0+ InnoDB is mandatory for production. However, SQLite does not implement true multi-process row locking (`SELECT ... FOR UPDATE`), meaning SQLite tests alone cannot verify race condition safety.

## Decision
1. All database migrations use portable Laravel Schema Builder types and composite indexes.
2. Application-level validation prevents race conditions, backed by database-level unique constraints:
   - `(vrf_id, ip_version, address_hex)`
   - `(vrf_id, ip_version, network_hex, prefix_length)`
   - `(l2_domain_id, vlan_id)`
3. Stage 4 allocation tests must include multi-process MySQL concurrency verification before the allocation engine is declared production-ready.
4. Avoid MySQL-specific raw SQL in domain logic; use Eloquent and database query builder.

## Consequences
- Frictionless local development and lightning-fast automated test execution.
- Verified transactional safety and lock behavior in production.

