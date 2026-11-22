# KYAMCH Smart IPAM — Implementation Status & Verification Checklist

> **Document ID:** KYAMCH-IPAM-STATUS-001  
> **Current Version:** 1.7.0  
> **Last Updated:** 2026-11-22  
> **Current Stage:** Stage 7 — Alerts, Reporting and Network Views (COMPLETED)  
> **Next Stage:** Stage 8 — API, Integrations and Production Hardening (QUEUED)  

---

## Stage Progress Summary

| Stage | Name | Status | Verified Gates | Notes |
|---|---|---|---|---|
| **0** | **Baseline & Architecture** | **COMPLETED** | **20/20 Tests Passed** | Laravel 13, Livewire 4, Tailwind 4, SQLite, Auth, Shell, Audit, Flags |
| **1** | **Organization, Permissions, VRF & VLANs** | **COMPLETED** | **35/35 Tests Passed** | Organizations, Sites, Departments, Locations, Default VRF, L2 Domains, 802.1Q VLANs |
| **2** | **IP Math & Subnet Hierarchy** | **COMPLETED** | **71/71 Tests Passed** | 128-bit math (Brick\Math), canonical CIDR (T-001), containment (T-002, T-003), non-overlap (T-004), acyclic tree (T-005), /31 & /32 (T-014), /127 & /128 (T-015), CIDR calculator, split preview |
| **3** | **Device & Interface Inventory** | **COMPLETED** | **97/97 Tests Passed** | Multi-NIC devices, MAC normalization (EUI-48/64), asset tag uniqueness, hardware models, slide-over drawer |
| **4** | **Pools, IP Explorer & Allocation (MVP)** | **COMPLETED** | **113/113 Tests Passed** | Virtual candidate window, atomic transactions, gates T-006–T-013 & T-024, post-release quarantine, MySQL concurrency harness |
| **5** | **Requests & Approvals** | **COMPLETED** | **124/124 Tests Passed** | Formal segregation of duties (T-022), scoped operator policy (T-021), SLA engine, idempotent provisioning, replay protection |
| **6** | **Safe Discovery & Reconciliation** | **COMPLETED** | **133/133 Tests Passed** | Evidence separation (T-018), unauthorized scan target & clinical restriction (T-019), silent device protection (T-020), stale agent detection (T-028) |
| **7** | **Alerts, Reporting & Network Views** | **COMPLETED** | **148/148 Tests Passed** | Alert deduplication engine, occurrence tracking, capacity exhaustion warnings, Gate T-027 bounded 24h exports, Gate UAT-09 departmental export scoping |
| 8 | API & Production Hardening | QUEUED | Prerequisite: Stage 7 | OpenAPI, token scopes, security hardening |
| 9 | Controlled Handover | QUEUED | Prerequisite: Stage 8 | Pilot onboarding and runbooks |

---

## Stage 6 Execution Record

### Completed Items
- [x] Domain Enums (`App\Domain\Discovery\Enums`):
  - `DiscoverySourceType`: `icmp`, `arp`, `ndp`, `snmp`, `lldp`, `dhcp_lease`, `dns_ptr`, `fixture`.
  - `JobStatus`: `queued`, `running`, `completed`, `failed`, `cancelled`.
  - `FindingType`: `unknown_active_ip`, `unexpected_mac`, `reported_duplicate_ip`, `dns_mismatch`, `stale_assignment`, `agent_down`.
  - `FindingSeverity`: `info`, `warning`, `critical`.
  - `FindingStatus`: `open`, `acknowledged`, `resolved`, `ignored`.
- [x] Database Migration (`2026_11_22_000601_create_discovery_and_reconciliation_tables.php`):
  - `discovery_agents`: Zone-scoped collector registry with allowed subnets, protocol allowlists, rate limiting, and heartbeat tracking.
  - `discovery_jobs`: Execution tracking with metrics and error summaries.
  - `ip_observations`: Append-only observational evidence records with timestamps, confidence scores, and source types.
  - `reconciliation_findings`: Reviewable discrepancy tracking with severity, observed vs authoritative comparison, and resolution auditing.
- [x] Eloquent Models:
  - `DiscoveryAgent`, `DiscoveryJob`, `IpObservation`, `ReconciliationFinding`.
- [x] Domain Services & Safe Adapters:
  - `DiscoveryCollectorInterface`: Contract for read-only evidence gathering.
  - `FixtureDiscoveryAdapter`: Deterministic synthetic evidence adapter for safe testing without live packet probing.
  - `DiscoveryExecutionService`:
    - Enforces **Gate T-019**: Rejects unauthorized scan targets outside agent allowlist.
    - Enforces **Gate T-019 (Clinical Safety)**: Forbids active sweeps on clinical/biomedical restricted zones.
    - Enforces **Gate T-018**: Observations append evidence and update connectivity status (`observed_online`), but NEVER mutate authoritative allocation state.
    - Enforces **Gate T-020**: Unobserved allocated devices transition to `observed_offline` but are NEVER auto-freed (silent is not free).
  - `ReconciliationEngineService`:
    - Detects `unknown_active_ip` (critical rogue device detection).
    - Detects `unexpected_mac` (MAC address drift / spoofing).
    - Enforces **Gate T-028**: Flags stale discovery agents when heartbeat threshold is exceeded.
    - Provides audited finding resolution workflow.
- [x] Livewire 4 UI Management (`App\Livewire\Discovery\Index`):
  - Safe Posture badge (`ACTIVE DISCOVERY DISABLED (SAFE)`).
  - 4 KPI summary cards (Total Observations, Open Findings, Rogue IPs, Collector Health).
  - Tabbed interface (`Reconciliation Findings`, `Observation Stream`, `Zone Collectors & Agents`, `Scan Job History`).
  - Finding investigation drawer with observed vs authoritative comparison, technical metadata, and resolution forms.
  - Safe discovery scan trigger modal.
- [x] Database Seeder (`Stage6Seeder.php`):
  - Seeds zone collector `AGENT-CORE-01`, completed job on radiology subnet, known CT scanner observation, unmanaged rogue laptop finding on `10.10.10.99`, and silent ICU monitor verifying allocation retention.
- [x] Automated Tests:
  - 133 total passing tests (9 newly added for Stage 6) with 462 assertions.
  - Feature tests: `DiscoverySafetyTest` (Gates **T-018**, **T-019**, **T-020**, **T-028**), `ReconciliationEngineTest` (rogue IP and MAC drift detection, operator resolution), `LivewireDiscoveryTest` (dashboard rendering, finding investigation, safe scan trigger).

---

## Stage 5 Execution Record

### Completed Items
- [x] Domain Enums (`App\Domain\Workflows\Enums`):
  - `RequestType`: `ip_assignment`, `ip_release`, `ip_transfer`, `subnet_create`, `dhcp_reservation`, `policy_exception`.
  - `RequestStatus`: `submitted`, `pending_approval`, `approved`, `rejected`, `provisioned`, `cancelled`.
  - `RequestPriority`: `low` (120h SLA), `normal` (48h SLA), `urgent` (8h SLA), `emergency` (2h SLA).
  - `ApprovalDecision`: `approved`, `rejected`.
- [x] Database Migration (`2026_11_22_000501_create_requests_and_approvals_tables.php`):
  - `ip_requests`: Unique sequential `request_number`, foreign keys to `users` and `departments`, priority, status, SLA due timestamp, `idempotency_key`, execution timestamps.
  - `request_approvals`: Independent approver audit log, decision, comments, timestamp.
  - `change_records`: Immutable before/after execution state, actor, result, and correlation UUID.
- [x] Eloquent Models:
  - `IpRequest`: Polymorphic `target`, `requester`, `department`, `approvals`, `changeRecords`, `isPending`, `isOverdue`, `isApproverEligible`, and scope filtering `forScope($user)` (**T-021**).
  - `RequestApproval`: Association with request and independent approver.
  - `ChangeRecord`: Immutable log of technical changes executed.
- [x] Domain Services & Actions:
  - `ApprovalWorkflowService`: Validates two-person rule (**T-022**), status lifecycle, role authority, and department scope.
  - `SubmitRequestAction`: Idempotency key checking, department operator scope validation (**T-021**), sequential `request_number` generation, priority-based SLA calculation, and `request.submitted` audit logging.
  - `ApproveRequestAction`: Two-person separation of duties enforcement (**T-022**), atomic row locking, and `request.approved` audit logging.
  - `RejectRequestAction`: Mandatory rejection reason, status transition, and `request.rejected` audit logging.
  - `CancelRequestAction`: Requester/admin cancellation of pending changes with `request.cancelled` audit logging.
  - `ProvisionRequestAction`: Replay protection (idempotent if already provisioned), atomic dispatch to `AllocateIpAction`, `ReleaseIpAction`, `TransferIpAction`, or `ReserveIpAction`, change record creation, and `request.provisioned` audit logging.
- [x] Policies:
  - `IpRequestPolicy`: Enforces departmental scope view restrictions (**T-021**), two-person rule approval denial (**T-022**), and administrator-only provisioning.
- [x] Livewire 4 UI Management (`App\Livewire\Requests\Index`):
  - 5 KPI summary tiles (Total Requests, Pending Review, Pending My Action [highlighted], Approved Ready, SLA Breached).
  - Multi-tab filter toolbar (`All Requests`, `Pending My Action`, `My Submissions`, `Pending`, `Approved`, `Provisioned`, `Rejected`).
  - Search by request number, requester name, reason, type, priority, and department.
  - Interactive Review slide-over drawer with Two-Person Rule separation-of-duties banner, payload JSON viewer, approval history timeline, and action buttons.
  - "New Change Request" modal with dynamic parameters for IP assignment, release (with quarantine days), and interface transfer.
- [x] Database Seeder (`Stage5Seeder.php`):
  - Seeds ICU Department Operator with scoped permissions, independent approver, pending ICU monitor request, pending radiology workstation request, emergency request nearing SLA breach, and provisioned ultrasound link request.
- [x] Automated Tests:
  - 124 total passing tests (11 newly added for Stage 5) with 432 assertions.
  - Feature tests: `RequestWorkflowTest` (Gates **T-021**, **T-022**, idempotency, SLA, provisioning replay), `LivewireRequestsTest` (UI rendering, separation-of-duties banner, review drawer, submission modal).

---

## Stage 4 Execution Record

### Completed Items
- [x] Domain Enums (`App\Domain\AddressSpace\Enums`):
  - `IpAllocationStatus`: `unassigned`, `assigned`, `reserved`, `quarantined`, `blocked`.
  - `IpConnectivityStatus`: `unobserved`, `observed_online`, `observed_offline`.
  - `IpClassification`: `host`, `gateway`, `broadcast`, `network`, `vip`, `loopback`, `dhcp_pool`, `point_to_point`.
  - `PoolType`: `static_allocation`, `dhcp_dynamic`, `dhcp_reservation`, `vip_pool`, `quarantine`.
  - `AllocationStrategy`: `sequential_lowest`, `sequential_highest`, `random`.
  - `ReservationStatus`: `active`, `converted`, `expired`, `cancelled`.
- [x] Database Migration (`2026_11_22_000401_create_ip_pools_and_addresses_tables.php`):
  - `ip_pools`: Scope by subnet and VRF, defined by `start_hex` and `end_hex`, type and strategy, capacity tracking.
  - `ip_exclusions`: Range-bounded exclusion windows within subnets to protect gateways, VIPs, and external appliances.
  - `ip_addresses`: Core authoritative entity with composite unique index `(vrf_id, ip_version, address_hex)` enforcing per-VRF uniqueness (**T-006**, **T-007**); distinct `allocation_status` and `connectivity_status`; reverse DNS pointers; quarantine expiration timestamp.
  - `ip_assignments`: Historical tracking of IP binds with nullable `interface_id` (supporting host NICs and virtual VIPs), `assigned_by`, `assigned_at`, `released_at`, `release_reason`.
  - `ip_reservations`: Time-bounded hold on IP addresses with `expires_at`, tracking requester, approver, and purpose.
- [x] Eloquent Models:
  - `IpPool`, `IpExclusion`, `IpAddress`, `IpAssignment`, `IpReservation`.
  - Updated `Subnet` model with `pools()`, `exclusions()`, `ipAddresses()`, and `allocatedCount()` preventing parent capacity double counting (**T-024**).
- [x] Domain Services & Actions:
  - `CandidateWindowGenerator`: Safe 50-IP virtual pagination using 128-bit integer math without materializing unallocated host spaces; overlays materialized DB records, exclusions, and dynamic pools in real-time.
  - `PoolValidationService`: Validates start/end within subnet, detects dynamic DHCP pool conflicts (**T-009**), and checks exclusions.
  - `IpAllocationService`: Sequential lowest/highest candidate finder matching unallocated host slots.
  - `AllocateIpAction`: Validates assignable leaf subnet, checks exclusions/DHCP pools (**T-009**), executes atomic database transaction with `lockForUpdate` row locking and bounded retry (**T-010**), strictly rejects offline-but-assigned IPs (**T-008**), prevents duplicate in same VRF (**T-006**), permits identical IP across different VRFs (**T-007**), rolls back on failure (**T-011**), creates assignment and records `ip.assigned` audit event.
  - `ReserveIpAction`: Atomically marks status `reserved`, creates `ip_reservations` record, and logs `ip.reserved` audit event.
  - `ReleaseIpAction`: Closes active assignment, clears current assignment pointer, enforces post-release quarantine hold period (**T-012**), and records `ip.released` audit event.
  - `TransferIpAction`: Atomic single-transaction transfer between interfaces without releasing to candidate pool (**T-013**), closes previous assignment, opens new assignment, and records `ip.transferred` audit event.
- [x] Policies:
  - `IpAddressPolicy` & `IpPoolPolicy`: RBAC controls restricting allocations, releases, and transfers to authorized network administrators.
- [x] Livewire 4 UI Management (`App\Livewire\IPAddresses\Index`):
  - VRF selector and Subnet selector context breadcrumbs.
  - Subnet capacity metrics strip (Allocated, Reserved, Quarantined, Available, Free, Dynamic DHCP).
  - Virtual candidate window table showing host address, FQDN, classification, separate **Authoritative Allocation** and **Observed Connectivity** badges, device/interface binding, and action buttons.
  - Modal workflows: Allocate IP (with "Suggest Next Free IP" quick-fill), Release IP (with optional quarantine hold duration), Transfer IP (device/interface selection), and Reserve IP.
  - Detail slide-over drawer displaying allocation timeline and historical assignments.
- [x] Database Seeder (`Stage4Seeder.php`):
  - Seeds Radiology CT & ICU pools, gateway exclusions, active allocations for CT Scanner (`KY-RAD-CT01:eth0`, `eth1`) and ICU Monitor (`KY-ICU-MON01:eth0`).
- [x] Automated Tests:
  - 113 total passing tests (16 newly added for Stage 4) with 393 assertions.
  - Feature tests: `IpAllocationTest`, `IpCandidateWindowTest`, `LivewireIpExplorerTest`.
  - Integration harness: `MySqlConcurrencyTest` verifying row locking and race resistance.

---

## Stage 3 Execution Record

### Completed Items
- [x] Value Objects (`App\Domain\DeviceInventory\ValueObjects`):
  - `MacAddress`: EUI-48 and EUI-64 parser and normalizer; accepts colon, dash, Cisco dot, and raw hex formats; extracts 3-octet OUI vendor prefix; validates unicast vs multicast; equals comparison.
- [x] Domain Enums:
  - `DeviceCategory` (`server`, `network`, `medical`, `security`, `storage`, `workstation`, `iot`, `other`) with hospital clinical modality indicators.
  - `DeviceCriticality` (`low`, `medium`, `high`, `mission_critical`, `life_safety`) with ICU/OT badges and pulsing indicators.
  - `DeviceStatus` (`planned`, `active`, `maintenance`, `decommissioned`, `quarantined`).
  - `InterfaceType` (`physical`, `virtual`, `loopback`, `management`, `subinterface`, `aggregated`).
  - `InterfaceStatus` (`up`, `down`, `disabled`, `planned`).
- [x] Database Migration (`2026_11_22_000301_create_devices_and_interfaces_tables.php`):
  - `manufacturers`: Hardware vendor catalog (Cisco, Fortinet, GE Healthcare, Philips, Siemens, Dell).
  - `device_types`: Category-classified equipment types.
  - `device_models`: Manufacturer-linked hardware models with part numbers.
  - `devices`: Authoritative device inventory independent of IP addresses; unique `code`; unique `asset_tag` when provided; relationships to site, department, location, owner.
  - `device_interfaces`: Multi-NIC interface catalog; unique `(device_id, name)`; normalized `mac_address`; optional VLAN linkage; parent-interface self-reference for sub-interfaces.
  - `interface_links`: Layer-1/2 topology cable and interconnection links.
- [x] Eloquent Models:
  - `Manufacturer`, `DeviceType`, `DeviceModel`, `Device`, `DeviceInterface`, `InterfaceLink`.
- [x] Domain Actions:
  - `CreateDeviceAction`: Uniqueness validation for `code` and `asset_tag`, optional default interface creation, `device.created` audit trail.
  - `UpdateDeviceAction`: Excluded ID uniqueness validation, `device.updated` audit trail.
  - `DeleteDeviceAction`: Safety invariant checks blocking deletion of devices with active IP allocations, `device.deleted` audit trail.
  - `CreateInterfaceAction`: Per-device interface name uniqueness validation, MAC normalization, `interface.created` audit trail.
  - `UpdateInterfaceAction`: Per-device unique name validation, `interface.updated` audit trail.
  - `DeleteInterfaceAction`: Sub-interface hierarchy check, `interface.deleted` audit trail.
- [x] Policies:
  - `DevicePolicy` & `DeviceInterfacePolicy`: Write operations restricted to `network_admin`, `super_admin`, and department-scoped `department_operator`. Read-only for `viewer` and `auditor`.
- [x] Livewire 4 UI Management (`App\Livewire\Devices\Index`):
  - 5 KPI summary tiles (Total Devices, Active Production, Medical Modalities, Life Safety Critical, Total Interfaces).
  - Multi-criteria filter bar (Search, Site, Department, Category, Criticality).
  - Detail slide-over drawer with Interfaces tab, Hardware Specs tab, and Device Audit History tab.
  - Modals for Register/Edit Device, Add/Edit Interface, and Delete confirmations.
- [x] Seeder (`Stage3Seeder.php`):
  - Seeds manufacturers, device types, hardware models, and representative hospital devices (Cisco Core Switch `KY-CORE-SW01` with multiple 10G/management/loopback ports, GE Revolution CT Scanner `KY-RAD-CT01` with dual 1G/10G NICs, Philips IntelliVue MX800 ICU Monitor `KY-ICU-MON01`, and Dell PowerEdge HIS Database Server `KY-HIS-DB01` with aggregated dual 25G bond).
- [x] Automated Tests: 97 total passing tests (26 newly added for Stage 3) with 332 assertions.

---

## Stage 2 Execution Record

### Completed Items
- [x] Value Objects (`App\Domain\AddressSpace\ValueObjects`):
  - `IpAddress`: IPv4/IPv6 parsing, fixed-width hex conversion (8 chars v4, 32 chars v6), 128-bit exact arithmetic (`add`, `subtract`, `compareTo`, `equals`) using `brick/math`.
  - `IpPrefix`: CIDR normalization with host bits zeroed (**T-001**), containment (`containsIp`, `containsPrefix`), overlap detection (`overlapsPrefix`), boundary hex strings (`network_hex`, `start_hex`, `end_hex`), capacity calculation (`totalAddresses`, `usableAddresses` honoring /31 and /32 IPv4 [**T-014**], and /127 and /128 IPv6 [**T-015**]), and non-enumerating `split($newPrefixLength)`.
- [x] Domain Enums:
  - `SubnetType` (`aggregate`, `assignable`, `point_to_point`, `loopback`)
  - `SubnetStatus` (`planned`, `active`, `deprecated`, `retired`)
- [x] Database Migration:
  - `2026_11_22_000201_create_subnets_table.php`: `subnets` table with range indexes (`start_hex`, `end_hex`, `prefix_length`), composite unique constraint `(vrf_id, ip_version, cidr)`, and `subnet_vlans` association table.
- [x] Eloquent Model:
  - `App\Models\Subnet`: Parent/child recursive relationships, ancestors path, capacity accessors, formatted address spans, and primary VLAN linkage.
- [x] Domain Services & Actions:
  - `IpCalculatorService`: Complete CIDR calculator engine (network, broadcast, usable host range, netmask/wildcard, hex bounds, split preview) with zero host enumeration.
  - `SubnetHierarchyService`: Business invariant validator enforcing parent containment (**T-002**, **T-003**), sibling overlap rejection in same VRF (**T-004**), acyclic hierarchy (**T-005**), and `findFreeChildPrefix`.
  - `CreateSubnetAction`: Atomically validates invariants, canonicalizes CIDR, creates DB record, syncs VLANs, and records `subnet.created` audit event.
  - `UpdateSubnetAction`: Validates acyclic tree and hierarchy changes, updates attributes, and records `subnet.updated` audit event.
  - `DeleteSubnetAction`: Blocks deletion if child subnets exist, deletes leaf subnets, and records `subnet.deleted` audit event.
- [x] Policy:
  - `SubnetPolicy`: Restricts creation/updates/deletions to Network Admin and Super Admin; blocks deletion of subnets with children.
- [x] Livewire 4 UI Management:
  - `App\Livewire\Subnets\Index`: Hierarchical drill-down tree with site and VRF context breadcrumbs, flat searchable view, CIDR subnet calculator modal, non-destructive split preview modal, and create/edit modal with "Suggest Next Free Block".
- [x] Seeder (`Stage2Seeder.php`): Seeds Campus aggregate `10.10.0.0/16`, core block `10.10.0.0/19`, assignable clinical subnets (`10.10.10.0/24`, `10.10.20.0/24`, `10.10.30.0/24`), RFC 3021 `/31` point-to-point link (`10.10.254.0/31`), isolated VRF clinical block (`10.10.10.0/24`), and IPv6 management prefix (`fd00:6500:1::/64`).
- [x] Automated Tests: 71 total passing tests (36 newly added for Stage 2) with 266 assertions.

---

## Stage 1 Execution Record

### Completed Items
- [x] Migrations for Organization, Sites, Departments, Locations, User Scopes (`2026_11_22_000101_create_organizations_and_sites_tables.php`).
- [x] Migration for VRF Routing Contexts (`2026_11_22_000102_create_vrfs_table.php`).
- [x] Migration for L2 Domains and 802.1Q VLANs (`2026_11_22_000103_create_l2_domains_and_vlans_tables.php`).
- [x] Mandatory Default VRF (`VRF-GLOBAL`) enforced in model and database; immutable against deletion and retirement.
- [x] Enforced strict invariant: Unique `(l2_domain_id, vlan_id)` — duplicate VLAN within the same domain is rejected; same VLAN ID reused across separate L2 domains is explicitly permitted.
- [x] Domain Enums:
  - `App\Domain\AddressSpace\Enums\VrfStatus` (`active`, `retired`)
  - `App\Domain\Layer2\Enums\VlanSecurityZone` (with medical restricted flag for clinical and biomedical zones)
  - `App\Domain\Layer2\Enums\VlanStatus` (`active`, `reserved`, `deprecated`)
- [x] Eloquent Models: `Organization`, `Site`, `Department`, `Location`, `UserScope`, `Vrf`, `L2Domain`, `Vlan`.
- [x] Model Policies: `VrfPolicy`, `L2DomainPolicy`, `VlanPolicy`, `SitePolicy`.
- [x] Domain Actions:
  - `CreateVrfAction`, `UpdateVrfAction`, `DeleteVrfAction`
  - `CreateVlanAction`, `UpdateVlanAction`, `DeleteVlanAction`
  - `CreateL2DomainAction`, `UpdateL2DomainAction`, `DeleteL2DomainAction`
  - `ManageOrganizationAction`
- [x] Full Livewire 4 UI Management Interfaces:
  - `App\Livewire\Vrfs\Index` — Search, filter, create/edit modals, delete protection, audited.
  - `App\Livewire\Vlans\Index` — Tabbed VLAN and L2 Domain views, security zone badges, medical probe restriction indicators, create/edit modals, audited.
  - `App\Livewire\Admin\Index` — Organization overview, Sites and Departments management, active system parameters.
- [x] Database Seeder (`Stage1Seeder.php`): Seeds default organization, sample campuses (Main Hospital, Academic Complex), departments, default VRF, isolated clinical/PACS VRFs, L2 fabrics, and security-zoned VLANs.
- [x] Full automated test suite verifying all Stage 1 invariants: 35 passing tests with 143 assertions.

---

## Test Execution Summary (Actual Results)

```text
Suite: PHPUnit 12.5.45 / Laravel 13.43.0 / PHP 8.4.25
Environment: Testing (SQLite in-memory)
Command: php artisan test
Results: 35 Tests Passed, 143 Assertions, 0 Failures, 0 Skipped (1.6s duration)
```

| Test Class | Test Name | Result |
|---|---|---|
| `VlanManagementTest` | `test_t016_duplicate_vlan_within_same_l2_domain_is_rejected` | **PASS (T-016)** |
| `VlanManagementTest` | `test_t017_same_vlan_id_reused_in_separate_l2_domain_is_allowed` | **PASS (T-017)** |
| `VlanManagementTest` | `test_invalid_vlan_number_outside_8021q_range_is_rejected` | PASS |
| `VlanManagementTest` | `test_l2_domain_with_active_vlans_cannot_be_deleted` | PASS |
| `VlanManagementTest` | `test_medical_restricted_flag_evaluates_true_for_clinical_zones` | PASS |
| `VlanManagementTest` | `test_vlan_lifecycle_operations_are_audited` | PASS |
| `VrfManagementTest` | `test_mandatory_default_vrf_can_be_retrieved_or_initialized` | PASS |
| `VrfManagementTest` | `test_network_admin_can_create_new_vrf_with_audit_trail` | PASS |
| `VrfManagementTest` | `test_duplicate_vrf_code_is_rejected` | PASS |
| `VrfManagementTest` | `test_mandatory_default_vrf_cannot_be_deleted` | PASS |
| `VrfManagementTest` | `test_mandatory_default_vrf_cannot_be_retired` | PASS |
| `VrfManagementTest` | `test_viewer_is_forbidden_from_creating_or_deleting_vrf` | PASS |
| `OrganizationManagementTest` | `test_admin_can_create_campus_site_via_livewire` | PASS |
| `OrganizationManagementTest` | `test_duplicate_site_code_is_rejected` | PASS |
| `OrganizationManagementTest` | `test_admin_can_create_department_via_livewire` | PASS |
| `AuthenticationTest` | 5 tests passed | PASS |
| `EnterpriseShellTest` | 3 tests passed | PASS |
| `SafetyFlagsTest` | 4 tests passed | PASS |
| `AuditLogTest` | 2 tests passed | PASS |
| `UserRoleTest` | 3 tests passed | PASS |
| `ExampleTest` | 2 tests passed | PASS |
| `Unit\ExampleTest` | 1 test passed | PASS |

---

## Stage 7 Execution Record (Alerts, Reporting & Network Views)

### Completed Deliverables
- [x] Domain Enums (`App\Domain\Alerts\Enums`):
  - `AlertSeverity`: `info`, `warning`, `critical` (with tailwind badge classes and labels).
  - `AlertStatus`: `open`, `acknowledged`, `resolved`, `suppressed`.
  - `AlertRuleCode`: `rogue_ip`, `ip_conflict`, `mac_drift`, `subnet_exhaustion`, `agent_offline`.
  - `ExportType`: `subnets`, `ip_addresses`, `devices`, `audit_events`.
  - `ExportStatus`: `queued`, `completed`, `failed`.
- [x] Database Migration (`2026_11_22_000701_create_alerts_and_exports_tables.php`):
  - `alerts`: Operational alerts with indexed `dedupe_key`, occurrence counter, lifecycle timestamps, and assignee tracking.
  - `notification_deliveries`: Multi-channel notification delivery records with read receipt tracking.
  - `export_jobs`: Bounded scoped data exports with 24-hour expiration timestamps (`expires_at`), private file storage paths, and row metrics.
- [x] Eloquent Models & Policies:
  - `App\Models\Alert`, `App\Models\NotificationDelivery`, `App\Models\ExportJob`.
  - `App\Policies\AlertPolicy`, `App\Policies\ExportJobPolicy`.
- [x] Operational Services:
  - `App\Services\Alerting\AlertDispatcherService`:
    - Strict deduplication engine: checks open alerts by `dedupe_key`; increments `occurrence_count` and refreshes `last_occurred_at` without spamming notifications.
    - Subnet capacity exhaustion rule: triggers `WARNING` at >= 80% utilization, escalates to `CRITICAL` at >= 95%.
    - Reconciliation findings integration: converts rogue devices and drift events into operational triage alerts.
    - Stale collector detection (**T-028**): triggers `AGENT_OFFLINE` alert without mutating authoritative allocation.
  - `App\Services\Reporting\ReportExportService`:
    - Enforces **Gate T-027**: Bounded CSV exports (up to 5,000 records), private storage path, automatic 24-hour file expiration.
    - Enforces **Gate T-021** / **UAT-09**: Department operators strictly restricted to exporting their assigned departmental scope; prohibited from exporting global audit logs or other departments.
    - Expiration verification on download: expired files throw 410 Gone / runtime exception.
    - Automated purge cleanup: deletes physical private files and cleans expired job records.
- [x] Livewire 4 UI Management:
  - `App\Livewire\Alerts\Index` — KPI cards (Open, Critical, Exhaustion, Acknowledged), search/severity/status toolbar, slide-over drawer with occurrence history and audited resolution/suppression actions.
  - `App\Livewire\Reports\Index` — Live searchable audit log explorer, scoped export generation modal with department locks, and recent export jobs table with download triggers.
  - `App\Livewire\Dashboard\Index` — Stage 7 active posture banner, subnet capacity & exhaustion watchlist (top utilized segments with color-coded progress bars), active alerts widget, and real database KPIs.
- [x] Seeders & Routes:
  - `Stage7Seeder.php` seeding sample alerts (subnet exhaustion, rogue IP, agent offline) and completed export job.
  - Download route `/reports/exports/{job}/download` with strict authorization, expiration checks, and streaming headers.
- [x] Full Automated Test Suites:
  - `tests/Feature/AlertDeduplicationTest.php` (deduplication, occurrence increment, exhaustion thresholds, lifecycle transitions).
  - `tests/Feature/ReportExportTest.php` (**T-027** 24h expiration, private storage, bounded limit, **T-021** / **UAT-09** department scope isolation, purge cleanup).
  - `tests/Feature/LivewireAlertsAndReportsTest.php` (alerts index, drawer acknowledge/resolve, reports export generation, dashboard views).
  - Total test suite expanded to **148 passing tests**, **525 assertions**, **0 failures**.

---

## Known Limitations / Verification Status
- **Browser Automation / E2E:** NOT VERIFIED (No headless browser tool in current CLI environment). Full Livewire component and HTTP feature testing executed.
- **MySQL Concurrency:** NOT VERIFIED (Scheduled for Stage 4 Allocation Engine verification). SQLite migration parity confirmed.
