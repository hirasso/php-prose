---
"php-prose": minor
---

`allowedTags` now removes disallowed `<script>` and `<style>` elements together with their content, instead of leaving their code behind as visible text. Add them to `allowedTags` to keep them.
