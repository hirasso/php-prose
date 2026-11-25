---
"php-prose": major
---

Remove email/phone obfuscation. Prose no longer depends on `hirasso/html-obfuscator`, and the `obfuscate` option on `ProseOptions` is gone. Run `obfuscate(Prose::format($html))->saveHTML()` yourself if you need it (see README). @see https://github.com/hirasso/prose/issues/4
