# KYAMCH Smart IPAM — Requirements & Code Traceability Matrix

> **Document ID:** KYAMCH-IPAM-TRACE-001  
> **Status:** Version 2.4.0 Core Architecture with Top-Level Campus Segregation & Host Types Management Delivered  
> **Updated:** 2026-11-22  

This matrix maps core functional requirements and active modules of the streamlined KYAMCH Smart IPAM application to database tables, domain services, Livewire UI components, and automated test coverage.

---

## Active Core Traceability Matrix

| Module ID | Module Name | Core Entities / Tables | UI Components / Routes | Domain Services / Actions | Automated Tests | Architecture Status |
|---|---|---|---|---|---|---|
| **M00** | **Baseline & Enterprise Shell** | `users`, `audit_events`, `sessions` | `/login`, `/dashboard`, `AppLayout` | `AuditEvent::record()`, Auth Guards | `AuthenticationTest`, `EnterpriseShellTest`, `SafetyFlagsTest`, `AuditLogTest`, `UserRoleTest` | **ACTIVE** |
| **M01** | **Top-Level Campus / Site Segregation** | `sites` | Top Bar Switcher, `/sites`, `Sites\Index`, `Components\CampusSwitcher` | `SiteContext`, `CreateSiteAction`, `UpdateSiteAction`, `DeleteSiteAction` | `SiteManagementTest` | **ACTIVE** |
| **M02** | **802.1Q VLAN Management** | `vlans` | `/vlans`, `Vlans\Index` | `CreateVlanAction`, `UpdateVlanAction`, `DeleteVlanAction` | `VlanManagementTest` | **ACTIVE** |
| **M03** | **Subnets & CIDR Hierarchy** | `subnets` | `/subnets`, `Subnets\Index`, `_subnet-row` | `IpPrefix`, `IpAddress`, `SubnetHierarchyService`, `CreateSubnetAction`, `UpdateSubnetAction`, `DeleteSubnetAction` | `IpAddressTest`, `IpPrefixTest`, `SubnetHierarchyTest`, `SubnetManagementTest` | **ACTIVE** |
| **M04** | **Address Pools & Exclusions** | `ip_pools`, `ip_exclusions` | `/ip-addresses`, `IPAddresses\Index` | `AllocateIpAction` (Pool & exclusion validation) | `IpAllocationTest` | **ACTIVE** |
| **M05** | **IP Address Explorer & Allocations** | `ip_addresses`, `ip_assignments`, `ip_reservations` | `/ip-addresses`, `IPAddresses\Index` | `IpAllocationService`, `CandidateWindowGenerator`, `AllocateIpAction`, `ReleaseIpAction`, `ReserveIpAction`, `UnquarantineIpAction` | `IpAllocationTest`, `IpCandidateWindowTest`, `LivewireIpExplorerTest` | **ACTIVE** |
| **M14** | **Host Types Management** | `host_types` | `/host-types`, `HostTypes\Index` | `CreateHostTypeAction`, `UpdateHostTypeAction`, `DeleteHostTypeAction` | `HostTypeManagementTest` | **ACTIVE** |

---

## Decommissioned Modules

| Module ID | Module Name | Decommissioned Entities | Reason for Removal |
|---|---|---|---|
| **M06** | **Heavy Multi-Tier Organizations** | `organizations`, `departments`, `locations`, `user_scopes` | Replaced by direct top-level lightweight `sites` table |
| **M07** | **VRF Routing Contexts** | `vrfs` | Unneeded complexity; single unified routing table per address family |
| **M08** | **Physical Hardware & Device Inventory** | `devices`, `device_interfaces`, `device_types`, `manufacturers`, `device_models`, `interface_links` | Replaced with direct IP host, MAC, and assignment owner tracking |
| **M09** | **Ticket-based Request & Approval Workflows** | `ip_requests`, `request_approvals`, `change_records` | Eliminated operational overhead in favor of direct operator allocation |
| **M10** | **Active Network Discovery & Reconciliation** | `discovery_agents`, `discovery_jobs`, `ip_observations`, `reconciliation_findings` | Active probing removed to avoid clinical and network disruption risks |
| **M11** | **Alert Deduplication & Notification Engine** | `alerts`, `notification_deliveries` | Removed background queue workers and external notification dependencies |
| **M12** | **Asynchronous Export Jobs & Reports** | `export_jobs` | Removed background file processing and disk export storage |
| **M13** | **REST API & External Sync Integrations** | `api_tokens`, `integrations`, `integration_runs` | Dedicated external sync removed; all IPAM managed directly via Livewire UI |
