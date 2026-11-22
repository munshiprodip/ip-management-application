# KYAMCH Smart IPAM — Implementation Status & Verification Checklist

> **Document ID:** KYAMCH-IPAM-STATUS-001  
> **Current Version:** 2.3.0 (Streamlined Core Architecture with Host Types & Quarantined Device Management)  
> **Last Updated:** 2026-11-22  
> **Architecture Scope:** Focused Core IPAM with Top-Level Site / Campus Segregation (Sites/Campuses, Subnets & CIDR Hierarchy, 802.1Q VLANs, IP Address Explorer & Allocations)  
> **Status:** All 85 Automated Tests Passing (100% Pass Rate)

---

## Architecture Scope Summary

Per architectural requirements, the application delivers focused, enterprise-grade IPAM segregated at the top level by Hospital Facility / Campus without unneeded peripheral layers:

### Active Core Modules
1. **Top-Level Campus / Facility Site Segregation**
   - Clean, lightweight `sites` table tracking hospital campus entities (`KYAMCH-MAIN`, `KYAMCH-ACAD`, `KYAMCH-NURS`).
   - Global **Campus Switcher** anchored in the top navigation bar (`All Campuses` vs specific facility).
   - Session-persistent active campus context scoping all dashboard metrics, subnets, VLANs, and IP allocations.
   - Dedicated Campus Management interface (`/sites`) with CRUD, audit events, and deletion protection guards.

2. **Subnet & CIDR Hierarchy Management**
   - Mathematically verified IPv4 and IPv6 hierarchy using `Brick\Math\BigInteger` (128-bit safe).
   - Strict CIDR canonicalization and parent-child containment validation.
   - Global sibling non-overlap validation per address family.
   - Dynamic CIDR calculator and visual subnet split preview tool.
   - Interactive inline expand/collapse toggles on parent subnets to view and manage child subnets directly within the hierarchy tree.
   - One-click `Expand All` and `Collapse All` tree controls.
   - Subnet-to-Campus association with automatic parent-to-child site inheritance.
   - Direct association with 802.1Q VLANs.

3. **802.1Q VLAN Management**
   - Standard 802.1Q VLAN tags (VID 1–4094, uniquely enforced).
   - Security zone classification (Core, Access, Server, DMZ, Management, Medical Restricted).
   - Campus site tagging (site-specific vs global shared infrastructure).
   - Audit trail of VLAN provisioning and lifecycle changes.

4. **IP Address Explorer & Safe Allocation**
   - Sparse virtual candidate window generation (no heavy host pre-materialization).
   - Atomic IP allocation, release, and reservation with row-level database locking.
   - Dedicated **Host Type** classification covering 8 enterprise device classes:
     - `Servers`: Database, HIS, application, VM hosts
     - `Workstations`: PCs, laptops, thin clients
     - `Network equipment`: Switches, routers, firewalls
     - `Printers`: Office and clinical printers
     - `Wireless`: APs and controllers
     - `Medical devices`: Connected clinical equipment
     - `Surveillance`: CCTV, NVR, access control
     - `Other / IoT`: UPS, sensors, virtual device
   - Dedicated Subnet Mask (`255.255.255.0` format) and Default Gateway (first usable host) displayed in context bar and table columns.
   - Separate Hostname and Assigned To columns with inline editing and quick edit modal.
   - Direct Host Type filter toolbar in explorer.
   - **Quarantined Device Management**: One-click "Make Unassigned" action on quarantined rows and within Details Drawer, backed by atomic `UnquarantineIpAction` and audit trail.
   - Excluded IP ranges and dynamic DHCP pool avoidance.
   - Post-release quarantine guardrail (configurable quarantine window).
   - Direct hostname, MAC address, and owner tracking per IP assignment.
   - Target subnets automatically filtered by active campus context.

---

## Verification Summary

| Component | Status | Verification Detail |
|---|---|---|
| **Automated Unit & Feature Tests** | **PASSED (85/85)** | PHPUnit suite with 310 assertions across models, actions, services, and Livewire components |
| **Database Schema** | **MIGRATED & SEEDED** | 5 core migrations: `sites`, `vlans`, `subnets`, `ip_pools_and_addresses`, `host_type` additions |
| **Vite & Tailwind CSS Build** | **PASSED** | Compiled cleanly via `npm run build` in 221ms |
| **Dev Server (Port 8080)** | **RUNNING** | Active and responsive on `http://127.0.0.1:8080` (Daemon task) |
| **Authentication & RBAC** | **VERIFIED** | SuperAdmin, NetworkAdmin, Operator, ReadOnly with full audit event logging |

---

## Active Test Inventory (85 Tests)

1. `AuthenticationTest`: 3 tests (login screen, user login, logout).
2. `EnterpriseShellTest`: 3 tests (layout render, navigation links including campuses, guest redirect).
3. `AuditLogTest`: 3 tests (record event, capture IP/agent, polymorphic relation).
4. `UserRoleTest`: 4 tests (superadmin, networkadmin, operator, readonly).
5. `SafetyFlagsTest`: 2 tests (discovery disabled, external writes disabled).
6. `SiteManagementTest`: 8 tests (create site, duplicate code validation, update, delete guard, empty delete, child subnet site inheritance, Livewire CRUD, top-bar campus switcher session setting).
7. `IpAddressTest`: 12 tests (IPv4/IPv6 parsing, hex conversion, normalization).
8. `IpPrefixTest`: 10 tests (CIDR parsing, network/broadcast calculation, host count).
9. `SubnetHierarchyTest`: 9 tests (containment, non-overlap, cycle detection, split preview).
10. `SubnetManagementTest`: 7 tests (create, update, delete, tree retrieval, inline expand/collapse toggle).
11. `VlanManagementTest`: 5 tests (802.1Q tags, validation, subnet association).
12. `IpAllocationTest`: 10 tests (allocate, release, reserve, quarantine, pool exclusion, idempotency, audit trail, unquarantine to unassigned, allocation with host type).
13. `IpCandidateWindowTest`: 4 tests (virtual candidate pagination, status flags).
14. `LivewireIpExplorerTest`: 5 tests (Livewire explorer rendering, direct IP allocation, query parameter subnet redirection, subnet mask & default gateway rendering, host type assignment & unquarantining).
