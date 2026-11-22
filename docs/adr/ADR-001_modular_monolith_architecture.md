# ADR-001: Modular Monolith Architecture & Isolation of Network Probes

## Context
KYAMCH IPAM is an enterprise hospital infrastructure system managing critical network topology, IP address allocations, VLANs, and routing domains. While discovery capabilities are needed to ingest network evidence (ARP/NDP, ICMP, DHCP, DNS), microservices introduce unnecessary operational overhead, deployment complexity, and network latency for a single-hospital internal application.

## Decision
1. Implement KYAMCH IPAM as a **Modular Monolith** in Laravel 13 with PHP 8.4+, Livewire 4, and Tailwind CSS 4.
2. Structure bounded contexts within the monolith:
   - `IdentityAndAccess`
   - `Organization`
   - `AddressSpace`
   - `Layer2`
   - `Inventory`
   - `Allocation`
   - `Discovery`
   - `Operations`
3. Network discovery operations must **never** run synchronously inside HTTP request cycles. Discovery jobs must be queued, rate-limited, and executed via supervised workers or dedicated zone collectors.
4. Livewire full-page components handle interactive UI without requiring separate frontend build servers or client-side single-page app complexity.

## Consequences
- Single unified codebase, atomic database transactions, simplified local testing with SQLite and production deployment on MySQL.
- Clear internal boundaries prevent domain logic pollution.
- HTTP requests remain fast and protected from network timeout risks.
