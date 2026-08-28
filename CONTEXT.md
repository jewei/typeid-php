# TypeID

A PHP implementation of [TypeID](https://github.com/jetify-com/typeid): type-safe, K-sortable identifiers that pair a human-readable type label with a UUID. This glossary is anchored to **TypeID specification 0.3.0**, vendored at [`spec/README.md`](spec/README.md); where this package's language differs from the spec's, the difference is noted below.

The vendored specification and its conformance vectors are copied verbatim from
[`jetify-com/typeid`](https://github.com/jetify-com/typeid/tree/main/spec) and were last verified
byte-identical to upstream `main` on 27 August 2026. `spec/valid.json` and `spec/invalid.json` are
derivations of the `.yml` files, and carry the same 9 valid and 21 invalid cases. This package
implements no deviation from the specification.

## Language

### The value and its parts

**TypeID**:
A type prefix and a UUID suffix, joined by a separator. The whole value, not either half.
_Avoid_: ID, identifier, TID

**Type prefix**:
The label denoting what kind of entity the TypeID names — `user`, `order`, `invoice`. Lowercase ASCII letters and underscores only, at most 63 characters, starting and ending with a letter. May be empty.
_Avoid_: type, namespace, tag, scope

**Separator**:
The single underscore between type prefix and suffix. Omitted entirely when the prefix is empty.
_Avoid_: delimiter, divider

**Suffix**:
The 26-character base32 encoding of the TypeID's 128 bits. Always present, always exactly 26 characters.
_Avoid_: UUID suffix (the spec's term — reserved here for when the distinction from the decoded UUID matters), base32 string, code

**Bare TypeID**:
A TypeID whose type prefix is empty, rendered as the suffix alone with no separator. Valid, and used where an application wants to elide type information.
_Avoid_: prefixless, anonymous TypeID, naked TypeID

### Encoding

**Strict alphabet**:
The 32 symbols `0123456789abcdefghjkmnpqrstvwxyz`. Crockford's alphabet, but without Crockford's leniency: lowercase only, no hyphens, and no two symbols ever decode to the same value.
_Avoid_: Crockford base32 (implies the lenient decoding this package rejects)

**Canonical suffix**:
A suffix that uses only the strict alphabet and decodes to exactly 128 bits. Because 26 base32 characters can hold 130 bits, the first character must be `7` or lower; `8zzzzzzzzzzzzzzzzzzzzzzzzz` is well-formed base32 but not canonical.
_Avoid_: valid suffix (too weak — it hides the overflow rule that makes this term necessary)

**Overflow**:
The condition a canonical suffix excludes: a 26-character string whose leading character exceeds `7`, and which would therefore denote a value larger than 128 bits.
_Avoid_: out of range, too large

### Zero and nil

**Nil UUID**:
The all-zero 128-bit UUID. A property of the decoded UUID.
_Avoid_: null UUID, empty UUID

**Zero TypeID**:
A TypeID whose suffix encodes the nil UUID. Distinct from the nil UUID: *nil* describes the 128 bits, *zero* describes the TypeID built from them. A zero TypeID still carries a type prefix, so `user_00000000000000000000000000` and `order_00000000000000000000000000` are both zero and are not equal.
_Avoid_: nil TypeID, empty TypeID, null TypeID

### Properties and conformance

**K-sortable**:
The property that TypeIDs sharing a type prefix and backed by UUIDv7 sort lexicographically by their millisecond timestamps. Values generated in the same millisecond by separate processes have no guaranteed creation order. Ramsey's default generator adds monotonic ordering only among successive calls in one process. Zero TypeIDs and TypeIDs built from other UUID versions do not have this property.
_Avoid_: sortable, time-ordered, monotonic

**Spec vector**:
A named conformance case vendored from the TypeID specification, in [`spec/valid.json`](spec/valid.json) or [`spec/invalid.json`](spec/invalid.json). The authority on what this package must accept and reject.
_Avoid_: fixture, test case, sample

## Not in this glossary

**The supported surface** — which symbols carry a compatibility promise — is consumer-facing policy and lives in the [README](README.md).

**The trust model** — which entry points validate which inputs, and which paths may rely on an established invariant — is an implementation decision and lives in [`docs/adr/`](docs/adr/).

Both were considered for this file and deliberately excluded: this is a glossary of the domain, not a specification of the package.
