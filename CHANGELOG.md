# php-prose

## 1.1.0

### Minor Changes

- 2083f82: `Prose::format()` now also accepts a `Dom\HTMLDocument`. Only its body is formatted, in place, and the same document is returned, so DOM-based tools can be chained without re-parsing.
- 233c571: `Prose::format()` no longer throws on full (or partial) HTML document strings. Only the body is formatted; the doctype, `<head>`, body attributes, comments and anything wrapping the body are preserved verbatim.
- 2083f82: `allowedTags` now strips on the parsed DOM instead of via `strip_tags()`: disallowed elements are unwrapped and comments removed. Invalid nesting is now fixed by the HTML parser before stripping, so output can differ, e.g. `<p><div>x</div></p>` with `allowedTags: ['p']` now gives `<p></p>x<p></p>` instead of `<p>x</p>`.
- 2083f82: `allowedTags` now removes disallowed `<script>` and `<style>` elements together with their content, instead of leaving their code behind as visible text. Add them to `allowedTags` to keep them.

### Patch Changes

- f9bb8e2: Preserve whitespace around and inside the body of string input verbatim. Leading whitespace was dropped, and whitespace after `</body>` was duplicated into the body.

## 1.0.0

### Major Changes

- b505894: Remove autolinking of raw URLs & emails. Prose no longer depends on `asika/autolink`, and the `autolink` option on `ProseOptions` is gone.
- 4cdfa2b: Remove email/phone obfuscation. Prose no longer depends on `hirasso/html-obfuscator`, and the `obfuscate` option on `ProseOptions` is gone. Run `obfuscate(Prose::format($html))->saveHTML()` yourself if you need it (see README). @see https://github.com/hirasso/prose/issues/4

### Minor Changes

- 2096d12: Change the default of the `removeEmptyElements` option from `['p']` to `[]`. Empty elements are now kept unless explicitly configured.

### Patch Changes

- 7027458: External link `class` attributes are now appended to any existing classes on the link instead of overwriting them. All other `externalLinkAttributes` still overwrite as before.

## 0.1.0

### Minor Changes

- 5235fc1: `Prose::format()` now throws an `InvalidArgumentException` when passed a full HTML document (`<!doctype>`, `<html>`, `<head>` or `<body>`) instead of trying to preserve the wrapper. Prose only formats prose fragments.
