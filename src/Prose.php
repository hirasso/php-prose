<?php

declare(strict_types=1);

namespace Hirasso\Prose;

use Dom\Element;
use Dom\HTMLDocument;
use Dom\XPath;
use InvalidArgumentException;

/**
 * Formats rich text / prose HTML (e.g. WYSIWYG field values).
 *
 * Framework-agnostic: pass a string, get a string back. Wire it into your
 * CMS / framework yourself (e.g. a WordPress `acf/format_value` filter).
 */
final class Prose
{
    /**
     * Format a prose HTML fragment or the body of a `Dom\HTMLDocument`.
     *
     * A string returns the formatted HTML. A `Dom\HTMLDocument` is modified in
     * place and returned.
     *
     * @return ($source is string ? string : HTMLDocument)
     */
    public static function format(
        string|HTMLDocument $source,
        ?ProseOptions $options = null,
    ): string|HTMLDocument {
        $options ??= new ProseOptions();

        if ($source instanceof HTMLDocument) {
            self::formatDocument($source, $options);

            return $source;
        }

        if (trim($source) === '') {
            return $source;
        }

        self::assertIsFragment($source);

        $doc = HTMLDocument::createFromString($source, LIBXML_NOERROR);
        self::formatDocument($doc, $options);

        return $doc->body->innerHTML ?? '';
    }

    private static function formatDocument(HTMLDocument $doc, ProseOptions $options): void
    {
        $body = $doc->body;

        if ($body === null) {
            return;
        }

        if ($options->allowedTags !== null) {
            self::stripTags($doc, $body, $options->allowedTags);
        }

        if ($options->siteUrl !== null) {
            self::markExternalLinks($body, $options);
        }

        self::removeEmptyElements($body, $options->removeEmptyElements);
    }

    /**
     * Unwrap elements not in $allowedTags (keeping their content) and remove comments, like `strip_tags()`.
     * Disallowed `<script>` / `<style>` are removed with their content.
     * @param list<string> $allowedTags
     */
    private static function stripTags(HTMLDocument $doc, Element $root, array $allowedTags): void
    {
        $allowed = array_map(strtolower(...), $allowedTags);

        foreach ($root->querySelectorAll('*') as $el) {
            $tag = strtolower($el->localName);

            if (in_array($tag, $allowed, true)) {
                continue;
            }

            in_array($tag, ['script', 'style'], true)
                ? $el->remove()
                : $el->replaceWith(...iterator_to_array($el->childNodes));
        }

        foreach ((new XPath($doc))->query('.//comment()', $root) as $comment) {
            $comment->parentNode?->removeChild($comment);
        }
    }

    /**
     * Guard against full HTML documents: Prose only formats prose fragments.
     */
    private static function assertIsFragment(string $html): void
    {
        if (preg_match('/<!doctype[\s>]|<(?:html|head|body)[\s>]/i', $html) === 1) {
            throw new InvalidArgumentException(
                'Prose::format() expects a prose fragment, not a full HTML document '
                . '(<!doctype>, <html>, <head> or <body> found).'
            );
        }
    }

    /**
     * Add the configured attributes to every external link.
     *
     * The `class` attribute is appended to any existing classes; all other
     * attributes overwrite their existing value.
     */
    private static function markExternalLinks(Element $root, ProseOptions $options): void
    {
        $siteUrl = (string) $options->siteUrl;

        foreach ($root->querySelectorAll('a[href]') as $el) {
            if (!self::isExternalUrl($el->getAttribute('href') ?? '', $siteUrl)) {
                continue;
            }
            foreach ($options->externalLinkAttributes as $name => $value) {
                if ($name === 'class') {
                    $classes = preg_split('/\s+/', trim($value), flags: PREG_SPLIT_NO_EMPTY);
                    if ($classes !== false && $classes !== []) {
                        $el->classList->add(...$classes);
                    }

                    continue;
                }
                $el->setAttribute($name, $value);
            }
        }
    }

    /**
     * Is the given URL external relative to $siteUrl?
     *
     * URLs without a host (relative paths, fragments, mailto:, tel:, …) are
     * treated as internal.
     */
    private static function isExternalUrl(string $url, string $siteUrl): bool
    {
        if ($url === '') {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if ($host === null || $host === false) {
            return false;
        }

        $siteHost = parse_url($siteUrl, PHP_URL_HOST);

        return strcasecmp($host, is_string($siteHost) ? $siteHost : '') !== 0;
    }

    /**
     * Remove whitespace-only elements matching $selector.
     * @param list<string> $selectors
     */
    private static function removeEmptyElements(Element $root, array $selectors): void
    {
        $toRemove = [];
        $selectorString = implode(',', $selectors);

        if (trim($selectorString) === '') {
            return;
        }

        foreach ($root->querySelectorAll($selectorString) as $node) {
            if (self::isWhitespaceOnly((string) $node->textContent)) {
                $toRemove[] = $node;
            }
        }

        foreach ($toRemove as $node) {
            $node->remove();
        }
    }

    /**
     * Is the text only whitespace, including zero-width and non-breaking chars?
     */
    private static function isWhitespaceOnly(string $text): bool
    {
        $stripped = preg_replace(
            '/[\s\p{Z}\x{200B}\x{200C}\x{200D}\x{FEFF}\x{2060}\x{00AD}]+/u',
            '',
            $text
        );

        return $stripped === '';
    }
}
