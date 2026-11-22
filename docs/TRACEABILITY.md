# KYAMCH Smart IPAM — Requirements & Code Traceability Matrix

> **Document ID:** KYAMCH-IPAM-TRACE-001  
> **Status:** Version 2.0.0 Streamlined Core Architecture Delivered  
> **Updated:** 2026-11-22  

This matrix maps core functional requirements and active modules of the streamlined KYAMCH Smart IPAM application to database tables, domain services, Livewire UI components, and automated test coverage.

---

## Active Core Traceability Matrix

| Module ID | Module Name | Core Entities / Tables | UI Components / Routes | Domain Services / Actions | Automated Tests | Architecture Status |
|---|---|---|---|---|---|---|
| **M00** | **Baseline & Enterprise Shell** | `users`, `audit_events`, `sessions` | `/login`, `/dashboard`, `AppLayout` | `AuditEvent::record()`, Auth Guards | `AuthenticationTest`, `EnterpriseShellTest`, `SafetyFlagsTest`, `AuditLogTest`, `UserRoleTest` | **ACTIVE** |
| **M01** | **802.1Q VLAN Management** | `vlans` | `/vlans`, `Vlans\Index` | `CreateVlanAction`, `UpdateVlanAction`, `DeleteVlanAction` | `VlanManagementTest` | **ACTIVE** |
| **M02** | **Subnets & CIDR Hierarchy** | `subnets` | `/subnets`, `Subnets\Index` | `IpPrefix`, `IpAddress`, `SubnetHierarchyService`, `CreateSubnetAction`, `UpdateSubnetAction`, `DeleteSubnetAction` | `IpAddressTest`, `IpPrefixTest`, `SubnetHierarchyTest`, `SubnetManagementTest` | **ACTIVE** |
| **M03** | **Address Pools & Exclusions** | `ip_pools`, `ip_exclusions` | `/ip-addresses`, `IPAddresses\Index` | `AllocateIpAction` (Pool & exclusion validation) | `IpAllocationTest` | **ACTIVE** |
| **M04** | **IP Address Explorer & Allocations** | `ip_addresses`, `ip_assignments`, `ip_reservations` | `/ip-addresses`, `IPAddresses\Index` | `IpAllocationService`, `CandidateWindowGenerator`, `AllocateIpAction`, `ReleaseIpAction`, `ReserveIpAction` | `IpAllocationTest`, `IpCandidateWindowTest`, `LivewireIpExplorerTest` | **ACTIVE** |

---

## Decommissioned Modules

| Module ID | Module Name | Decommissioned Entities | Reason for Removal |
|---|---|---|---|
| **M05** | **Organizations & Multi-Site Scopes** | `organizations`, `sites`, `departments`, `locations`, `user_scopes` | Replaced by direct global enterprise network space |
| **M06** | **VRF Routing Contexts** | `vrfs` | Unneeded complexity; single unified routing table per address family |
| **M07** | **Physical Hardware & Device Inventory** | `devices`, `device_interfaces`, `device_types`, `manufacturers`, `device_models`, `interface_links` | Replaced with direct IP host, MAC, and assignment owner tracking |
| **M08** | **Ticket-based Request & Approval Workflows** | `ip_requests`, `request_approvals`, `change_records` | Eliminated operational overhead in favor of direct operator allocation |
| **M09** | **Active Network Discovery & Reconciliation** | `discovery_agents`, `discovery_jobs`, `ip_observations`, `reconciliation_findings` | Active probing removed to avoid clinical and network disruption risks |
| **M10** | **Alert Deduplication & Notification Engine** | `alerts`, `notification_deliveries` | Removed background queue workers and external notification dependencies |
| **M11** | **Asynchronous Export Jobs & Reports** | `export_jobs` | Removed background file processing and disk export storage |
| **M12** | **REST API & External Sync Integrations** | `api_tokens`, `integrations`, `integration_runs` | Dedicated external sync removed; all IPAM managed directly via Livewire UI |
