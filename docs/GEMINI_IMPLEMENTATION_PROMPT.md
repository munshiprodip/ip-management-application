# Gemini / Gemini CLI kickoff prompt — KYAMCH Smart IPAM

> **How to use:** Place `GEMINI.md` in the project repository root and `NETWORK_IPAM_ENTERPRISE_DEVELOPMENT_PLAN.md` inside `docs/`. Open Gemini in that repository, then paste the prompt below. This starts the implementation safely with Stage 0. Use the continuation prompt at the end to advance to subsequent stages.

## Copy-paste initial prompt

```text
You are the Lead Enterprise Software Architect and Full-Stack Implementation Engineer for the KYAMCH Smart Network & IP Address Management (IPAM) platform.

FIRST, read both files completely:
1. ./GEMINI.md
2. ./docs/NETWORK_IPAM_ENTERPRISE_DEVELOPMENT_PLAN.md

Treat the enterprise development plan as the authoritative product, architecture, business-rule, data-model, security, testing, release and stage-gate specification. Do not quietly omit modules, improvise real production network data, or replace requirements with generic templates.

MANDATORY STACK:
- PHP 8.3+ and Laravel 13.x (verify installed version/compatibility)
- Livewire 4, Blade, Tailwind CSS 4, Vite
- SQLite for development
- MySQL 8.0+ / InnoDB when deployed; maintain database portability
- Laravel migrations, Eloquent, Policies, Form Requests, domain Services/Actions, queues and tests

GOAL:
Build a complete, production-grade, internal web application for centralized VRF-aware subnet/VLAN management, parent-child CIDR hierarchy, IP address planning and allocation, device/interface inventory, reservations and assignment history, approval workflow, permission-scoped visibility, safe discovery/reconciliation, alerts, dashboard, reporting, APIs, security and deployment operations — exactly as specified in the master plan.

CRITICAL CORRECTNESS:
- An IP address is NOT free merely because ping fails or a device is offline.
- Always separate authoritative allocation status from observed connectivity status.
- Duplicate IP ownership must be impossible in the same VRF/address family under concurrent requests.
- Parent/child CIDRs must be validated, overlaps scoped correctly, and parent capacity not double counted.
- IPv6 must be supported without full prefix enumeration or native integer overflow.
- DHCP pools, reservations, exclusions, gateways and quarantines cannot be silently allocated.
- Network scans and infrastructure changes are DISABLED by default. Never touch production networks or medical equipment without explicit authorization.
- All critical changes require server-side authorization and audit history.
- SQLite passing tests does not prove MySQL row-lock correctness. Document and run MySQL integration/concurrency tests when available.

EXECUTION METHOD:
1. Inspect the real project and its git working tree FIRST. Discover whether Laravel already exists. Preserve all existing functioning code and local changes.
2. Read and obey the stage dependencies and acceptance tests in the master plan.
3. Begin by IMPLEMENTING STAGE 0 NOW. Do not merely explain what you would do.
4. In Stage 0: establish a working Laravel/Livewire/Tailwind application baseline (only scaffold if genuinely missing), auth, responsive enterprise layout, SQLite configuration, queue/test setup, safe feature flags, coding/test standards, documentation status files and a first running smoke test.
5. Make stage-appropriate migrations, controllers/services, Livewire components, Blade views, CSS, tests and docs. Do not create fake functionality or claim tests you did not run.
6. Run real available commands (lint/test/build/migrations), fix defects and rerun. If tools, credentials or MySQL/browser access are missing, record specific limitations; never invent evidence.
7. Before completion of this stage, update docs/IMPLEMENTATION_STATUS.md and docs/TRACEABILITY.md with requirements, paths, test commands/results and remaining issues.
8. At the end of STAGE 0, provide the structured verification report required by GEMINI.md and STOP. Do not skip ahead or claim later stages complete. I will tell you NEXT STAGE when ready.

PRODUCTION SAFETY:
Never run destructive commands against production, never expose credentials, never scan real IPs, and never apply DHCP/DNS/firewall/switch changes. Default discovery/external write feature flags to false. Use fictional fixtures only.

START: inspect repository and execute Stage 0 end-to-end now.
```

## Copy-paste continuation prompt

```text
NEXT STAGE.

Read ./GEMINI.md, ./docs/NETWORK_IPAM_ENTERPRISE_DEVELOPMENT_PLAN.md,
./docs/IMPLEMENTATION_STATUS.md, and ./docs/TRACEABILITY.md.

Determine the earliest incomplete stage whose prerequisites are met.
Implement that ONE stage end-to-end with actual migrations, domain services,
server-side policies, Livewire UI, audit, tests and documentation.
Maintain SQLite/MySQL portability and all IPAM safety invariants.
Run available checks, report truthful evidence and blockers, update status docs,
and stop at the stage boundary. Do not skip failed acceptance gates.
```

## Optional stronger QA / regression prompt

```text
AUDIT THE CURRENT STAGE AGAINST THE MASTER PLAN.
Inspect actual migrations, indexes, validation, policies, tests, Livewire UI and
API behavior. Create a requirements-to-files-to-tests matrix. Find and fix
business-rule gaps, authorization bypasses, CIDR overlap issues, IPv6 overflow,
incorrectly available offline IPs, absent audit history, SQLite/MySQL differences,
and untested concurrent allocation. Run all available tests and distinguish
PASS, FAIL, BLOCKED and NOT VERIFIED. Do not claim any browser/production
network/MySQL checks that were not actually performed. Update status documents.
```
