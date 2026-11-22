# Assumptions and Open Decisions — KYAMCH Smart IPAM

> **Document ID:** KYAMCH-IPAM-DEC-001  
> **Status:** Stage 0 Baseline Established  
> **Updated:** 2026-11-22  

This document tracks known assumptions, site-specific variables, and open governance questions as mandated by Section 25 of `docs/NETWORK_IPAM_ENTERPRISE_DEVELOPMENT_PLAN.md`.

---

## 1. Baseline Assumptions (Safe Defaults Applied in Stage 0)

| ID | Topic | Safe Default Applied | Rationale |
|---|---|---|---|
| A-01 | Network Scanning & Discovery | `IPAM_DISCOVERY_ENABLED=false` | Hospital patient safety: never probe clinical or infrastructure networks without explicit authorization. |
| A-02 | Live Target Probes | `IPAM_DISCOVERY_ALLOW_LIVE_TARGETS=false` | Prevents unauthorized ICMP/SNMP sweeps. |
| A-03 | Network Configuration Writes | `IPAM_EXTERNAL_WRITES_ENABLED=false` | Read-only IPAM posture; write-backs deferred until Phase 2 change control. |
| A-04 | Default Timezone | `Asia/Dhaka` | KYAMCH operational location (UTC stored internally in database). |
| A-05 | Primary Database | SQLite (Dev) / MySQL 8.0+ InnoDB (Prod) | Enables ultra-fast test execution while preserving MySQL concurrency lock requirements. |
| A-06 | Address Ownership | Scoped to (VRF + address family) | Allows overlapping private ranges across separate routing contexts (e.g. Biomedical vs Guest). |
| A-07 | Released IP Quarantine | 14 Days | Prevents ARP/DNS cache poisoning and stale routing conflicts. |
| A-08 | Authentication Scheme | Local Database with Bcrypt / RBAC | Can integrate with hospital LDAP/Active Directory in Stage 8 upon approval. |

---

## 2. Open Decisions Requiring Site Verification (Section 25)

1. **Existing CIDR Plan & Campus Topology:**
   - Exact production subnet scheme, VLAN list, and physical campus breakdown (Main Hospital, College, Hostels, Diagnostics) to be confirmed before production import.
2. **Biomedical & Clinical VLANs:**
   - Formal list of sensitive medical device VLANs to ensure permanent exclusion from discovery probes.
3. **VRF Inventory:**
   - Whether VRF segmentation is currently deployed at core switches or planned for the IPAM rollout.
4. **Active Directory / SSO Integration:**
   - Choice of corporate authentication provider for single sign-on (SSO/LDAP) in Phase 2.
5. **Authoritative DHCP / DNS Servers:**
   - Make and model of internal DHCP/DNS engines (Windows Server DHCP/DNS, Infoblox, Cisco IOS, etc.) for read-only sync adapters in Stage 6.
