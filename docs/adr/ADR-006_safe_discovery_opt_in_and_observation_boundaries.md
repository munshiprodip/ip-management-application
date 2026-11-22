# ADR-006: Safe Discovery Opt-In & Evidence vs Allocation Separation

## Context
In hospital network environments, sensitive clinical instruments (ventilators, bedside monitors, infusion pumps) can experience malfunction or communication drops if subjected to aggressive ping sweeps or port scans. Furthermore, treating a lack of ICMP response as proof that an address is available creates severe risk of double-allocation when medical devices are powered off or in standby.

## Decision
1. Feature flags `IPAM_DISCOVERY_ENABLED` and `IPAM_DISCOVERY_ALLOW_LIVE_TARGETS` default to `false`.
2. Clinical device subnets are excluded from scanning by default.
3. Observations from discovery (ARP, ICMP, DHCP, DNS, SNMP) are treated strictly as **evidence**, never administrative allocation authority.
4. An offline or silent device retains its authoritative assigned status indefinitely until released through formal authorized workflow.
5. Unallocated active IPs or unexpected MAC addresses create **reconciliation findings**, requiring operator review rather than silent database mutations.

## Consequences
- Guaranteed safety for sensitive hospital biomedical systems.
- Zero risk of unauthorized address reclamation due to transient network drops or scheduled maintenance.
