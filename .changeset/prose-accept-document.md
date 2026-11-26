---
"php-prose": minor
---

`Prose::format()` now also accepts a `Dom\HTMLDocument`. Only its body is formatted, in place, and the same document is returned, so DOM-based tools can be chained without re-parsing.
