---
"php-prose": minor
---

`Prose::format()` no longer throws on full (or partial) HTML document strings. Only the body is formatted; the doctype, `<head>`, body attributes, comments and anything wrapping the body are preserved verbatim.
