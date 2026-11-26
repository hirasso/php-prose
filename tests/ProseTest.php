<?php

declare(strict_types=1);

use Hirasso\Prose\Prose;
use Hirasso\Prose\ProseOptions;

it('returns empty input untouched', function () {
    expect(Prose::format(''))->toBe('');
    expect(Prose::format('   '))->toBe('   ');
});

it('marks external links', function () {
    $result = Prose::format(
        '<p><a href="https://external.com">out</a> <a href="/internal">in</a></p>',
        new ProseOptions(siteUrl: 'https://example.com'),
    );

    expect($result)
        ->toContain('data-external')
        ->toContain('target="_blank"');
});

it('appends classes to external links instead of overwriting them', function () {
    $result = Prose::format(
        '<p><a class="btn" href="https://external.com">out</a></p>',
        new ProseOptions(
            siteUrl: 'https://example.com',
            externalLinkAttributes: ['class' => 'external'],
        ),
    );

    expect($result)->toContain('class="btn external"');
});

it('sets a class on external links without one', function () {
    $result = Prose::format(
        '<p><a href="https://external.com">out</a></p>',
        new ProseOptions(
            siteUrl: 'https://example.com',
            externalLinkAttributes: ['class' => 'external'],
        ),
    );

    expect($result)->toContain('class="external"');
});

it('does not mark internal links as external', function () {
    $result = Prose::format(
        '<p><a href="/internal">in</a></p>',
        new ProseOptions(siteUrl: 'https://example.com'),
    );

    expect($result)->not->toContain('data-external');
});

it('strips tags to the allowlist', function () {
    $result = Prose::format(
        '<p>keep</p><script>alert(1)</script>',
        new ProseOptions(allowedTags: ['p']),
    );

    expect($result)->toContain('<p>keep</p>')->not->toContain('<script>');
});

it('removes empty paragraphs', function () {
    $result = Prose::format(
        '<p>kept</p><p>&nbsp;</p><p>   </p>',
        new ProseOptions(removeEmptyElements: ['p']),
    );

    expect(substr_count($result, '<p'))->toBe(1);
});

it('leaves email and phone links untouched', function () {
    $html = '<p><a href="mailto:jane@example.com">jane@example.com</a> <a href="tel:+49123456789">call</a></p>';

    expect(Prose::format($html))->toBe($html);
});

it('does not wrap output in a body tag', function () {
    $result = Prose::format('<p>hi</p>', new ProseOptions());

    expect($result)->not->toContain('<body');
});

it('throws on a full html document', function () {
    Prose::format('<html><body><p>hi</p></body></html>');
})->throws(InvalidArgumentException::class);

it('throws on a body wrapper', function () {
    Prose::format('<body>foo</body>');
})->throws(InvalidArgumentException::class);

it('throws on a doctype', function () {
    Prose::format('<!doctype html><p>hi</p>');
})->throws(InvalidArgumentException::class);

it('throws on a head tag', function () {
    Prose::format('<head><title>x</title></head><p>hi</p>');
})->throws(InvalidArgumentException::class);

it('unwraps nested disallowed tags and removes comments', function () {
    $result = Prose::format(
        '<p>a <span>b <em>c</em></span><!-- note --></p>',
        new ProseOptions(allowedTags: ['p', 'em']),
    );

    expect($result)->toBe('<p>a b <em>c</em></p>');
});

it('keeps allowed nodes of a document intact when stripping', function () {
    $doc = Dom\HTMLDocument::createFromString('<!doctype html><body><p>keep <span>x</span></p></body>', LIBXML_NOERROR);
    $p = $doc->querySelector('p');

    Prose::format($doc, new ProseOptions(allowedTags: ['p']));

    expect($doc->querySelector('p'))->toBe($p)
        ->and($p?->isConnected)->toBeTrue();
});

it('only formats the body of a document', function () {
    $doc = Dom\HTMLDocument::createFromString(
        '<!doctype html><html><head><script src="/app.js"></script><title>x</title></head><body><script></script></body></html>',
        LIBXML_NOERROR,
    );

    Prose::format($doc, new ProseOptions(allowedTags: ['p'], removeEmptyElements: ['script']));

    expect($doc->head?->innerHTML)->toBe('<script src="/app.js"></script><title>x</title>')
        ->and($doc->body?->innerHTML)->toBe('');
});

it('formats a document in place', function () {
    $doc = Dom\HTMLDocument::createFromString(
        '<!doctype html><html><head><title>x</title></head><body><p><a href="https://external.com">out</a></p><p> </p><script>alert(1)</script></body></html>',
        LIBXML_NOERROR,
    );

    $result = Prose::format($doc, new ProseOptions(
        allowedTags: ['p', 'a'],
        siteUrl: 'https://example.com',
        removeEmptyElements: ['p'],
    ));

    expect($result)->toBe($doc)
        ->and($doc->title)->toBe('x')
        ->and($doc->body?->innerHTML)->toBe('<p><a href="https://external.com" data-external="" target="_blank">out</a></p>');
});

it('removes disallowed script and style elements with their content', function () {
    $result = Prose::format(
        '<p>hi</p><script>track("x")</script><style>.a{color:red}</style>',
        new ProseOptions(allowedTags: ['p']),
    );

    expect($result)->toBe('<p>hi</p>');
});

it('keeps script and style elements when allowed', function () {
    $html = '<p>hi</p><script>track("x")</script><style>.a{color:red}</style>';

    expect(Prose::format($html, new ProseOptions(allowedTags: ['p', 'script', 'style'])))->toBe($html);
});

it('accepts a bare prose fragment', function () {
    $result = Prose::format('<p>hi</p>', new ProseOptions());

    expect($result)->toBe('<p>hi</p>');
});
