# KYAMCH Smart IPAM — Requirements & Code Traceability Matrix

> **Document ID:** KYAMCH-IPAM-TRACE-001  
> **Status:** Stage 1 Complete  
> **Updated:** 2026-11-22  

This matrix maps functional requirements and modules from `docs/NETWORK_IPAM_ENTERPRISE_DEVELOPMENT_PLAN.md` to database tables, domain services, Livewire UI components, and automated test coverage.

---

## Master Traceability Matrix

| Module ID | Module Name | Priority | Core Entities / Tables | UI Components / Routes | Domain Services / Actions | Automated Tests | Stage Delivered |
|---|---|---|---|---|---|---|---|
| **M00** | **Baseline & Enterprise Shell** | P0 | `users`, `audit_events`, `sessions` | `/login`, `/dashboard`, Layouts | `AuditEvent::record()`, Auth | `AuthenticationTest`, `EnterpriseShellTest`, `SafetyFlagsTest`, `AuditLogTest`, `UserRoleTest` | **Stage 0** |
| **M01** | **Organization & Locations** | P0 | `organizations`, `sites`, `departments`, `locations`, `user_scopes` | `/admin`, `Admin\Index` | `ManageOrganizationAction` | `OrganizationManagementTest` | **Stage 1** |
| **M02** | **VRF Routing Contexts** | P0 | `vrfs` | `/vrfs`, `Vrfs\Index` | `CreateVrfAction`, `UpdateVrfAction`, `DeleteVrfAction` | `VrfManagementTest` | **Stage 1** |
| **M03** | **L2 Domains & VLANs** | P0 | `l2_domains`, `vlans` | `/vlans`, `Vlans\Index` | `CreateVlanAction`, `UpdateVlanAction`, `DeleteVlanAction`, `CreateL2DomainAction` | `VlanManagementTest` (T-016, T-017) | **Stage 1** |
| **M04** | **Subnets & CIDR Hierarchy** | P0 | `subnets`, `subnet_vlans` | `/subnets`, `Subnets\Index` | `IpPrefix`, `IpAddressMath`, `SubnetHierarchyService` | *Planned: Stage 2 (T-001–T-005)* | Stage 2 |
| **M05** | **Address Pools & Exclusions** | P0 | `ip_pools`, `ip_exclusions` | `/pools`, `Pools\Index` | `PoolValidationService` | *Planned: Stage 4* | Stage 4 |
| **M06** | **Device & Interface Inventory** | P0 | `devices`, `device_interfaces`, `device_types` | `/devices`, `Devices\Index` | `RegisterDeviceAction`, `InterfaceLinkService` | *Planned: Stage 3* | Stage 3 |
| **M07** | **IP Address Explorer & Records** | P0 | `ip_addresses` | `/ip-addresses`, `IPAddresses\Index` | `IpExplorerService`, `CandidateWindowGenerator` | *Planned: Stage 4* | Stage 4 |
| **M08** | **Smart Allocation & Quarantine** | P0 | `ip_assignments`, `ip_addresses` | `/ip-allocations` | `AllocateIpAction`, `ReleaseIpAction`, `TransferIpAction` | *Planned: Stage 4 (T-006–T-013, MySQL Concurrency)* | Stage 4 |
| **M09** | **Approval & Change Workflow** | P1 | `ip_requests`, `request_approvals` | `/requests`, `Requests\Index` | `SubmitRequestAction`, `ApprovalWorkflowService` | *Planned: Stage 5* | Stage 5 |
| **M10** | **Safe Discovery & Observations** | P1 | `discovery_agents`, `discovery_jobs`, `ip_observations` | `/discovery`, `Discovery\Index` | `DiscoveryCollectorInterface`, `FixtureDiscoveryAdapter` | *Planned: Stage 6 (T-018–T-020)* | Stage 6 |
| **M11** | **Reconciliation & Conflicts** | P1 | `reconciliation_findings` | `/reconciliation` | `ReconciliationEngineService` | *Planned: Stage 6* | Stage 6 |
| **M12** | **Alerting & Notifications** | P1 | `alerts`, `notification_deliveries` | `/alerts`, `Alerts\Index` | `AlertDispatcherService`, `DeduplicationService` | *Planned: Stage 7* | Stage 7 |
| **M13** | **Dashboard & Reports** | P1 | Pre-aggregated metrics | `/dashboard`, `/reports` | `ReportingService`, `CsvExportJob` | *Planned: Stage 7* | Stage 7 |
| **M14** | **API & Integrations** | P1/P2 | `api_tokens`, `integrations` | `/api/v1/*` | `ApiV1Controllers`, `DhcpSyncAdapter` | *Planned: Stage 8* | Stage 8 |
| **M15** | **Security & System Admin** | P0/P1 | `settings`, `audit_events`, `users` | `/admin`, `Admin\Index` | `AuditLogger`, `FeatureFlagService` | `AuditLogTest`, `UserRoleTest` | Stage 0 / Stage 1 |
