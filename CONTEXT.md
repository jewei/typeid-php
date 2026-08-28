# TypeID terminology

This glossary defines the terms used in the source, tests, and README. The definitions follow [TypeID specification 0.3.0](spec/README.md).

The repository vendors the specification and its conformance vectors in [`spec/`](spec/). The YAML files come from [`jetify-com/typeid`](https://github.com/jetify-com/typeid/tree/main/spec). The JSON files contain the same vectors in the format used by the test suite. This package implements the specification without deviations.

## Value and parts

### TypeID

A complete value made of a type prefix and a suffix joined by a separator.

Avoid: ID, identifier, TID

### Type prefix

The label that names the entity kind. Examples include `user`, `order`, and `invoice`. A type prefix can be empty. A non-empty type prefix contains at most 63 lowercase ASCII letters and underscores. It starts and ends with a letter. Consecutive underscores are valid.

Avoid: type, namespace, tag, scope

### Separator

The single underscore between a non-empty type prefix and its suffix. A bare TypeID has no separator.

Avoid: delimiter, divider

### Suffix

The 26-character base32 encoding of the TypeID's 128-bit UUID. Every TypeID has one suffix.

"UUID suffix" is reserved for text that distinguishes the encoded suffix from the decoded UUID.

Avoid: base32 string, code

### Bare TypeID

A TypeID with an empty type prefix. Its canonical string contains only the suffix.

Avoid: prefixless TypeID, anonymous TypeID, naked TypeID

## Encoding

### Strict alphabet

The 32 characters `0123456789abcdefghjkmnpqrstvwxyz`. The decoder accepts lowercase characters only. It rejects hyphens and character aliases.

Avoid: Crockford base32. That name implies lenient decoding.

### Canonical suffix

A suffix that contains 26 characters from the strict alphabet and encodes a 128-bit value. The first character cannot exceed `7`.

A base32 string of 26 characters can hold 130 bits. The first-character limit keeps the leading two bits at zero. For example, `8zzzzzzzzzzzzzzzzzzzzzzzzz` uses the strict alphabet but is not canonical.

Avoid: valid suffix. That phrase does not name the 128-bit limit.

### Overflow

A 26-character base32 value greater than `7zzzzzzzzzzzzzzzzzzzzzzzzz`. Such a value needs more than 128 bits, so the decoder rejects it.

Avoid: out of range, too large

## Zero and nil values

### Nil UUID

A UUID whose 128 bits are all zero.

Avoid: null UUID, empty UUID

### Zero TypeID

A TypeID whose suffix encodes the nil UUID. "Nil" describes the decoded UUID. "Zero" describes the TypeID.

The type prefix remains part of a zero TypeID. The values `user_00000000000000000000000000` and `order_00000000000000000000000000` are both zero TypeIDs, but they are not equal.

Avoid: nil TypeID, empty TypeID, null TypeID

## Ordering and conformance

### K-sortable

The ordering property of TypeIDs that share a type prefix and encode UUIDv7 values. Their lexicographic order follows the millisecond timestamps in those UUIDs.

Separate processes can generate values in the same millisecond without a shared creation order. Ramsey's default UUIDv7 generator orders successive calls only within one PHP process. Zero TypeIDs and TypeIDs created from other UUID versions are not K-sortable.

Avoid: sortable, time-ordered, monotonic

### Spec vector

A named valid or invalid case from [`spec/valid.json`](spec/valid.json) or [`spec/invalid.json`](spec/invalid.json). Spec vectors define the strings that the package must accept or reject.

Avoid: fixture, test case, sample
