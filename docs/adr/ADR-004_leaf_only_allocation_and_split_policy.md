# ADR-004: Leaf-Only Allocation and Controlled Subnet Split Policy

## Context
When parent subnets contain children (e.g., `/16` aggregate containing `/24` departments), allocating addresses from the aggregate parent risks double-counting address capacity and conflicting with child allocations.

## Decision
1. Subnets are classified as `aggregate`, `assignable`, `point_to_point`, or `loopback`.
2. For the MVP, **IP addresses can only be allocated from assignable leaf subnets** (subnets with no active child subnets).
3. If an occupied subnet is split into child subnets, a preview and migration workflow must resolve existing address assignments before child creation is committed.
4. Capacity calculations count distinct space at the leaf level, preventing parent/child double-counting.

## Consequences
- Eliminates ambiguous address ownership between parent aggregates and child blocks.
- Provides mathematically sound utilization metrics.

