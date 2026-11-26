---
"php-prose": minor
---

`allowedTags` now strips on the parsed DOM instead of via `strip_tags()`: disallowed elements are unwrapped and comments removed. Invalid nesting is now fixed by the HTML parser before stripping, so output can differ, e.g. `<p><div>x</div></p>` with `allowedTags: ['p']` now gives `<p></p>x<p></p>` instead of `<p>x</p>`.
