---
status: accepted
---

# Read paths keep revalidating the suffix

`toUuid()` and `bytes()` decode through `Base32::decodeBytes()`, which checks
that the suffix is canonical — a suffix the constructor already established as
canonical. The check is genuinely redundant on every read of an existing
`TypeID`, and mutation testing confirms it: removing the guard breaks no test
that goes through `TypeID`, because no in-package caller can reach it with a
non-canonical value.

We are keeping it anyway.

## Why

Measured on 200,000 iterations (PHP 8.5, unscientific but consistent across
runs):

| Operation | Cost | Validation share |
| --- | --- | --- |
| `toUuid()` | 2.187 µs | 5.5% |
| `bytes()` / `decodeBytes()` | 1.908 µs | 6.3% |
| `isCanonicalSuffix()` alone | 0.121 µs | — |

Removing the redundancy buys about 6% of a read. Paying for it means adding a
second, trusted decode entry point to `Base32`, taking its interface from three
methods to four immediately after
[ADR-0001](0001-validation-and-codec-ownership.md) narrowed it. Callers would
then have to know which of the two to use, and a trusted path called with an
unvalidated suffix fails silently — returning wrong bytes instead of throwing.

Trading an invariant that cannot be misused for one that can, plus a wider
interface, in exchange for 6% of a sub-microsecond operation, is a bad deal.

## Consequences

The redundancy stays and is deliberate, so a future architecture review should
not re-propose it without new evidence. Reopen this only if profiling of a real
workload — not a microbenchmark — shows decode validation as a material cost.

The same reasoning covers three smaller redundancies found by mutation testing
and deliberately left in place:

- `fromString()` checks for the empty string, though an empty value would fail
  canonicality anyway. The guard exists for the message, not the outcome.
- `fromUuid()` lowercases before `hex2bin()`, though `hex2bin()` accepts
  uppercase and `bin2hex()` always emits lowercase. Explicit normalisation
  beats relying on that.
- The UUID pattern constrains length, which `encodeBytes()` would also catch.
  The pattern is still needed for character validation.
