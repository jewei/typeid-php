---
status: accepted
---

# Each validation rule lives with the concept it defines

Validation was centralised into a `Validator` module across three commits
(`c9871e6`, `dffa309`, `303da8a`) to remove duplicated validate-and-throw
blocks. That succeeded at removing the duplication, but the result was a
shallow module: eight public methods over three regexes, with
`isValidSuffix`, `assertValidSuffix` and `assertValidBase32` expressing one
rule under three names. Every rule in it had exactly one production caller,
so it provided no leverage — it was a pass-through that made "what is a
valid suffix?" a two-file question.

We are moving each rule to the module that owns the concept, and deleting
`Validator`. This reverses part of those three commits deliberately; the
duplication they removed does not return, because message rendering now
belongs to `ValidationException` (see the named constructors added
alongside this decision).

## The four decisions

**A. `Base32` accepts bytes, not UUID text.** Its interface becomes
16 bytes ⇄ a canonical 26-character suffix. It keeps its own domain
validation — alphabet, length, and the 128-bit overflow rule — because
byte-only does not mean validation-free. A decoder that accepts
`8zzzzzzzzzzzzzzzzzzzzzzzzz` is wrong regardless of who called it.

**B. UUID text belongs to `TypeID`.** Parsing, case normalisation, hyphen
handling and formatting move to `fromUuid()` and `toUuid()`, which exchange
raw 16-byte values with the codec. Previously `Base32::encode()` validated
UUID strings and `Base32::decode()` formatted them, which is three
UUID-text responsibilities inside something named after a base32 codec.

**C. `Validator` is deleted.** The prefix grammar goes to `TypeID`, suffix
canonicality to `Base32`, UUID grammar to `TypeID`, and message rendering
was already on `ValidationException`. Nothing is left.

**D. `fromString()` owns the whole parse.** Splitting a string and
validating its parts becomes one private path, rather than
`Validator::parseTypeID()` checking two rules and handing a tuple back for
the constructor to finish. Both already raised `ValidationException`; what
changes is that one module now knows the complete grammar.

## Considered and rejected

- **Keep `Validator` as a non-public policy object.** Its strongest
  retention argument was uniform exception wording. That argument
  disappeared when `ValidationException` gained named constructors, and
  what remained was still a shallow module.
- **Move only parsing and the prefix rules.** Leaves suffix canonicality
  split between the codec and a helper, preserving the two-file question
  this decision exists to close.
- **Keep small private guards duplicated in each owner.** Acceptable, but
  strictly worse than co-location once each rule has exactly one caller.

## Consequences

`TypeID\Validator` is removed. It was marked `@internal` and documented as
unsupported in v3.0.0, so this carries no compatibility promise, but it is
observable: code calling it directly will fatal. The removal is a breaking
change for anyone who ignored the `@internal` marker, and should be
released as such.

The `isValidPrefix()` / `isValidSuffix()` / `isValidUuid()` predicates
disappear with it. Callers wanting a boolean should catch
`ValidationException` from the relevant `TypeID` factory. If a supported
predicate is wanted later, it should be added to `TypeID` deliberately
rather than recovered by reinstating the helper.

`TypeID` now depends only on `Base32`, and `Base32` depends on nothing.
The architecture check's production allow-list narrows accordingly.
