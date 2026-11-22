# ADR-003: VRF-Scoped Uniqueness and L2-Domain VLAN Namespaces

## Context
Hospital networks often use segmented routing domains (VRFs) for biomedical devices, PACS imaging, administrative traffic, guest WiFi, and DMZs. The same private IP address (e.g., `10.10.1.5`) may legitimately exist in two distinct routing contexts. Similarly, different physical campuses or isolated switch fabrics may each use VLAN ID `100`.

## Decision
1. IP address and prefix uniqueness is strictly enforced per **`(vrf_id, address_family, address_hex)`**, never globally.
2. A mandatory non-null default VRF row (`VRF-GLOBAL`) is created in the database. Nullable VRF foreign keys are strictly prohibited to avoid database composite unique index bypasses.
3. VLAN uniqueness is enforced per Layer 2 domain **`(l2_domain_id, vlan_id)`**.

## Consequences
- Prevents accidental address collisions while legitimately supporting multi-VRF network topologies.
- Enforces relational referential integrity without relying on nullable database constraints.
