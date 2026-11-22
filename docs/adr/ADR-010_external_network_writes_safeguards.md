# ADR-010: External Network Write Safeguards & Phased Deferral

## Context
Modifying production DHCP scopes, DNS servers, switch ports, or firewall rules can trigger widespread network downtime if misconfigured.

## Decision
1. Automated network write-back (DHCP reservations, DNS zone updates, switch port changes) is **explicitly deferred to Phase 2**.
2. Global feature flag `IPAM_EXTERNAL_WRITES_ENABLED` defaults to `false`.
3. When writes are introduced in future stages, they will require:
   - Independent two-person approval
   - Dry-run validation preview
   - Idempotency tokens
   - Outbox pattern for asynchronous delivery
   - Automated rollback plan

## Consequences
- Eliminates risk of automated accidental network misconfiguration during initial deployment and onboarding.

