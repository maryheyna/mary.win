<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the nativephp patterns talk page renders', function () {
    $this->get('/talks/nativephp-patterns')
        ->assertOk()
        ->assertSee('One Frame,', false)
        ->assertSee('Nine Patterns')
        ->assertSee('Design patterns under pressure in the NativePHP v4 render cycle')
        ->assertSee('Documented')
        ->assertSee('Interpretation')
        ->assertSee('Claims verified against the live NativePHP v4 docs on 2026-07-30');
});

test('all ten sources link to the exact urls from the citation ledger', function () {
    $response = $this->get('/talks/nativephp-patterns')->assertOk();

    collect([
        'https://nativephp.com/docs/mobile/4/architecture/render-publish-mount',
        'https://nativephp.com/docs/mobile/4/architecture/subtree-reuse',
        'https://nativephp.com/docs/mobile/4/architecture/threading-model',
        'https://nativephp.com/docs/mobile/4/architecture/embedded-php',
        'https://nativephp.com/docs/mobile/4/architecture/cross-platform-implementation',
        'https://nativephp.com/docs/mobile/4/architecture/glossary',
        'https://nativephp.com/docs/mobile/4/architecture/super-native',
        'https://nativephp.com/docs/mobile/4/architecture/about-the-new-architecture',
        'https://nativephp.com/blog/supernative',
        'https://github.com/NativePHP/super-native',
    ])->each(fn (string $url) => $response->assertSee($url, false));
});

test('no private presenter notes leak onto the public page', function () {
    $html = $this->get('/talks/nativephp-patterns')->assertOk()->getContent();

    expect($html)->not->toContain('before you present')
        ->not->toContain('reword')
        ->not->toContain('Q&A')
        ->not->toContain('⚑');
});

test('the home page links the talk from the given list', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('one frame, nine patterns')
        ->assertSee(route('talks.nativephp-patterns'));
});
