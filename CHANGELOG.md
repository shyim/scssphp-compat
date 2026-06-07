# Changelog — shyim/scssphp-compat

This is a drop-in compatible fork of [`scssphp/scssphp`](https://github.com/scssphp/scssphp).
It keeps the `ScssPhp\ScssPhp` namespace and public API and produces byte-for-byte
identical CSS output to the upstream release it is based on.

Only changes made on top of upstream are listed here. For the upstream history,
see the [upstream changelog](https://scssphp.github.io/scssphp/docs/#changelog).

## 1.13.0.1

Based on upstream **scssphp 1.13.0**.

### Changed

- **Minimum PHP version raised to 8.2.** This allows the compiler to use modern,
  faster built-ins and syntax on its hot paths. The CI matrix now covers
  8.2 / 8.3 / 8.4.
- Fixed the PHP 8.4 "implicitly marking parameter as nullable" deprecations by
  declaring the affected parameters explicitly nullable (`?Type`).

### Performance

A Bootstrap 5 compile is roughly **20-25% faster** than upstream 1.13.0 (and
~40% faster with the OPcache tracing JIT enabled). All changes are internal and
verified to produce byte-identical CSS across the full sass-spec suite and
real-world stylesheets.

- Reduced redundant work in the value-tree interpreter (`reduce()`): skip
  re-reducing values that resolve to an already-final `Number`, and avoid the
  copy-on-write duplication of lists/maps/strings whose elements are already
  reduced.
- Memoized variable-name normalization and decoding in `get()` / `set()`.
- Cheaper native-function argument handling: cached function prototypes,
  `isset()` lookups instead of `in_array()`, direct callable invocation instead
  of `call_user_func()`.
- Fast paths for `Number` comparisons when units already match.

See the **Performance** section of the documentation for tips on getting the
most out of the compiler (OPcache/JIT, the result cache).

### Added

- Work-in-progress support for the `sass:math` built-in module
  (`@use "sass:math"`). See `tests/inputs/sass_math.scss` for covered functions.
