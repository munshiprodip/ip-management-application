# ADR-002: Canonical IP Representation & Fixed-Width Hex Storage

## Context
The system must support both IPv4 and IPv6 as first-class citizens across SQLite (development) and MySQL InnoDB (production). Native 64-bit PHP integers overflow on 128-bit IPv6 calculations. Different database engines sort and compare string IP representations inconsistently.

## Decision
1. Canonicalize IP addresses via `inet_pton` and `inet_ntop`.
2. Persist IP addresses and prefixes as **fixed-width lowercase hexadecimal strings**:
   - IPv4: 8 hex characters (`00000000` to `ffffffff`)
   - IPv6: 32 hex characters (`00000000000000000000000000000000` to `ffffffffffffffffffffffffffffffff`)
3. For prefixes, store `network_hex`, `first_hex`, `last_hex`, and `prefix_length`.
4. Perform 128-bit arithmetic using `brick/math` BigInteger to ensure zero overflow.
5. In SQLite and MySQL, fixed-width hex strings sort and compare lexicographically identical to unsigned binary network order.

## Consequences
- Range containment checks (`BETWEEN start_hex AND end_hex`) are indexable and deterministic across both SQLite and MySQL.
- Large IPv6 subnets (e.g., `/64`) can be managed mathematically without enumerating individual host rows.
