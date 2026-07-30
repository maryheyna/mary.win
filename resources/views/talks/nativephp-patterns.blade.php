@php
    // Fillable slots — Mary supplies these when known.
    $eventDate = null; // e.g. 'meetup ’26' — shown in the meta line under the title.
    $slidesUrl = null; // link to the deck, once it exists.
    $recordingUrl = null; // link to the talk recording, once it exists.

    $sources = [
        ['id' => 's1', 'label' => 'S1', 'title' => 'Render, Publish, and Mount', 'url' => 'https://nativephp.com/docs/mobile/4/architecture/render-publish-mount', 'host' => 'nativephp.com'],
        ['id' => 's2', 'label' => 'S2', 'title' => 'Subtree Reuse', 'url' => 'https://nativephp.com/docs/mobile/4/architecture/subtree-reuse', 'host' => 'nativephp.com'],
        ['id' => 's3', 'label' => 'S3', 'title' => 'Threading Model', 'url' => 'https://nativephp.com/docs/mobile/4/architecture/threading-model', 'host' => 'nativephp.com'],
        ['id' => 's4', 'label' => 'S4', 'title' => 'Embedded PHP', 'url' => 'https://nativephp.com/docs/mobile/4/architecture/embedded-php', 'host' => 'nativephp.com'],
        ['id' => 's5', 'label' => 'S5', 'title' => 'Cross-Platform Implementation', 'url' => 'https://nativephp.com/docs/mobile/4/architecture/cross-platform-implementation', 'host' => 'nativephp.com'],
        ['id' => 's6', 'label' => 'S6', 'title' => 'Glossary', 'url' => 'https://nativephp.com/docs/mobile/4/architecture/glossary', 'host' => 'nativephp.com'],
        ['id' => 's7', 'label' => 'S7', 'title' => 'SuperNative Introduction (docs)', 'url' => 'https://nativephp.com/docs/mobile/4/architecture/super-native', 'host' => 'nativephp.com'],
        ['id' => 's8', 'label' => 'S8', 'title' => 'About the New Architecture', 'url' => 'https://nativephp.com/docs/mobile/4/architecture/about-the-new-architecture', 'host' => 'nativephp.com'],
        ['id' => 's9', 'label' => 'S9', 'title' => 'Blog: SuperNative', 'url' => 'https://nativephp.com/blog/supernative', 'host' => 'nativephp.com'],
        ['id' => 's10', 'label' => 'S10', 'title' => 'Reference app (super-native)', 'url' => 'https://github.com/NativePHP/super-native', 'host' => 'github.com'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>Design Patterns in NativePHP — v4’s render cycle</title>
    <meta name="description"
        content="A 10-minute talk on NativePHP v4’s SuperNative render cycle, told as one button press across a language border — with every claim checked against the docs." />

    <link rel="canonical" href="{{ route('talks.nativephp-patterns') }}" />
    <meta property="og:type" content="article" />
    <meta property="og:site_name" content="mary.win" />
    <meta property="og:title" content="Design Patterns in NativePHP — v4’s render cycle" />
    <meta property="og:description"
        content="A 10-minute talk on NativePHP v4’s SuperNative render cycle, told as one button press across a language border — with every claim checked against the docs." />
    <meta property="og:url" content="{{ route('talks.nativephp-patterns') }}" />
    <meta name="twitter:card" content="summary" />

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    {{-- Two families, no exceptions. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400;1,700;1,900&family=IBM+Plex+Mono:ital,wght@0,400;0,500;0,600;1,400&display=swap"
        rel="stylesheet">

    {{-- Restore theme before first paint so it doesn't flash. --}}
    <script>
        (function () {
            try {
                var t = localStorage.getItem('winrar-theme');
                if (t === 'light' || t === 'dark') document.documentElement.dataset.theme = t;
            } catch (e) { }
        })();
    </script>

    @vite(['resources/css/winrar.css'])

    {{-- Talk-page composition — built on the winrar tokens only, no new primitives. --}}
    <style>
        .np-talkmeta {
            margin-top: 20px;
        }

        .np-prose {
            max-width: min(64ch, 100%);
            display: grid;
            gap: 16px;
        }

        .np-prose p {
            font-size: var(--text-body-size);
            line-height: 1.9;
            color: var(--soft);
        }

        .np-prose code,
        .np-note code,
        .np-table code,
        .np-footnote code {
            font-family: var(--font-mono);
            font-size: 0.95em;
            background: var(--plate-2);
            border: 1px solid var(--hair);
            padding: 1px 5px;
            white-space: nowrap;
        }

        .np-note {
            font-size: 12px;
            line-height: 2;
            color: var(--muted);
            max-width: min(72ch, 100%);
            margin: 0 0 18px;
        }

        /* type tags — color never carries the meaning alone; the label does */
        .np-tag {
            display: inline-block;
            font-size: var(--text-label-size);
            letter-spacing: 0.1em;
            text-transform: uppercase;
            font-weight: 600;
            border: 1px solid currentColor;
            padding: 2px 7px;
            white-space: nowrap;
        }

        .np-tag--doc {
            color: var(--accent-2);
        }

        .np-tag--interp {
            color: var(--pop);
        }

        .np-tablewrap {
            overflow-x: auto;
        }

        .np-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 12px;
        }

        .np-table th {
            font-size: var(--text-label-size);
            letter-spacing: var(--text-label-tracking);
            text-transform: uppercase;
            color: var(--muted);
            font-weight: 600;
            padding: 11px 16px;
            border-bottom: 1px solid var(--line);
            white-space: nowrap;
        }

        .np-table td {
            padding: 13px 16px;
            border-bottom: 1px dotted var(--hair);
            vertical-align: top;
            line-height: 1.7;
        }

        .np-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .np-claim {
            color: var(--ink);
            font-weight: 500;
            min-width: 220px;
        }

        .np-quote {
            color: var(--soft);
            min-width: 280px;
        }

        .np-src {
            min-width: 170px;
        }

        .np-src a {
            display: block;
        }

        /* below ~640px the table folds into stacked cards; nothing overflows */
        @media (max-width: 640px) {
            .np-table thead {
                display: none;
            }

            .np-table,
            .np-table tbody,
            .np-table tr,
            .np-table td {
                display: block;
            }

            .np-table tr {
                border-bottom: 1px solid var(--hair);
                padding: 14px 0;
            }

            .np-table tbody tr:last-child {
                border-bottom: 0;
            }

            .np-table td {
                border: 0;
                padding: 5px 16px;
                min-width: 0;
            }

            .np-table td::before {
                content: attr(data-label);
                display: block;
                font-size: var(--text-label-size);
                letter-spacing: var(--text-label-tracking);
                text-transform: uppercase;
                color: var(--muted);
                font-weight: 600;
                margin-bottom: 3px;
            }

            .np-src {
                min-width: 0;
            }
        }

        .np-sources {
            list-style: none;
            margin: 0;
        }

        .np-sources li {
            scroll-margin-top: 84px;
        }

        .np-sources__id {
            font-weight: 600;
            color: var(--accent-2);
            width: 30px;
            flex: none;
        }

        .np-footnote {
            font-size: var(--text-caption-size);
            line-height: 1.8;
            color: var(--muted);
            max-width: min(74ch, 100%);
        }

        .np-footnote+.np-footnote {
            margin-top: 8px;
        }
    </style>
</head>

<body>
    <div class="wr">
        {{-- ============ HEADER ============ --}}
        <header class="wr-header">
            <div class="wr-rainbow" aria-hidden="true"></div>
            <div class="wr-header__bar">
                <a href="{{ route('home') }}" class="wr-wordmark" style="text-decoration:none;">MARY.WIN</a>
                <div class="wr-header__right">
                    <nav class="wr-nav" aria-label="Primary">
                        <a href="{{ route('home') }}#talks">TALKS</a>
                        <a href="{{ route('home') }}#games">GAMES</a>
                        <a href="{{ route('home') }}#me">ME</a>
                    </nav>
                    <button type="button" class="wr-toggle" data-theme-toggle>☾ LIGHTS OFF</button>
                </div>
            </div>
        </header>

        <main>
            {{-- ============ TITLE ============ --}}
            <section class="wr-hero">
                <div class="wr-hero__aurora" aria-hidden="true"></div>
                <div class="wr-wrap wr-hero__inner">
                    <p class="wr-entry"><span class="wr-dot" aria-hidden="true"></span>TALK</p>
                    <h1 class="wr-display wr-hero__title">Design Patterns<br><span class="wr-grad">in NativePHP</span></h1>
                    <p class="wr-hero__lede">Design patterns under pressure in the NativePHP v4 render cycle</p>
                    <p class="wr-meta np-talkmeta">10-minute talk · for Laravel
                        developers{{ $eventDate ? ' · '.$eventDate : '' }}</p>
                </div>
            </section>

            {{-- ============ SUMMARY ============ --}}
            <section class="wr-wrap wr-section" style="padding-top: 22px;">
                <h2 class="wr-display wr-display--h2">The talk, <span class="wr-em">in short.</span></h2>
                <div class="np-prose">
                    <p>So here's the claim I want to open with: we mostly teach design patterns like they're good
                        manners — a tidy thing you do so a reviewer doesn't have to ask you to. And I want to walk you
                        through a codebase where that's not what they are at all, where they're load-bearing, and
                        pulling one out doesn't make the code uglier, it makes it impossible.</p>
                    <p>That codebase is NativePHP v4 — specifically SuperNative, the new mobile architecture, where
                        your PHP is driving real SwiftUI and Jetpack Compose views directly. No web view, no
                        serialization sitting in the middle of the UI, and the PHP runtime living right inside the app
                        process, sharing memory with the native side. So we're going to do one small thing together:
                        follow a single button press — one <code>@@press="refresh"</code> — across the language border
                        between PHP and the phone, and name every pattern it touches on the way.</p>
                    <p>And the thing you notice, if you follow it all the way down, is that almost none of these
                        patterns are really a choice. A closure can't cross a process boundary and an integer can, so
                        the press handler becomes a callback ID — that's the Command pattern, and it's the only legal
                        move. There are two target platforms, so there's a Bridge. There's a frame budget, so scrolling
                        and dragging and animation get their own lane on the UI thread that never waits on PHP. My
                        opinion is this: in a CRUD app you pick your patterns and you could usually have picked
                        otherwise, and at a language border you don't get a vote — and that turns out to be my favorite
                        place to learn them. It makes me really happy.</p>
                    <p>Now, everything here is the documented architecture of a beta, so I've tried to be careful — the
                        mechanisms are the docs' claims, and the benchmarks are theirs, not mine. And that's what the
                        rest of this page is for: every architectural claim in the talk, put next to the exact line in
                        the NativePHP docs that backs it, so you can check my work. Some of these are facts straight
                        from the documentation, and some are my own reading — the Gang-of-Four names are mine to argue
                        for, not the docs' — and I've kept the two clearly marked. So if you've ever shipped something
                        across a boundary like this and felt the shape of the solution before you had a name for it —
                        then, welcome in. And if you want to learn design patterns, my real suggestion is to stop
                        reading catalogues and go find a boundary.</p>
                </div>
            </section>

            {{-- ============ CITATIONS ============ --}}
            <section class="wr-wrap wr-section">
                <h2 class="wr-display wr-display--h2">Check my <span class="wr-em">work.</span></h2>

                <p class="np-note">Every row below is a claim from the talk.
                    <span class="np-tag np-tag--doc">Documented</span> rows quote the NativePHP v4 docs verbatim — the
                    exact line is shown. <span class="np-tag np-tag--interp">Interpretation</span> rows are my own
                    reading: the Gang-of-Four pattern names are mine to argue for, not the docs'. Quotes are unedited;
                    links go to the page they're pulled from.</p>

                <div class="wr-plate">
                    <div class="wr-plate__head">
                        <span class="wr-label">CLAIM × DOC LINE</span>
                        <span class="wr-plate__bar" aria-hidden="true"></span>
                    </div>
                    <div class="np-tablewrap">
                        <table class="np-table">
                            <thead>
                                <tr>
                                    <th scope="col">Claim in the talk</th>
                                    <th scope="col">Type</th>
                                    <th scope="col">Source</th>
                                    <th scope="col">Exact line from the docs</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">The PHP runtime is embedded
                                        inside the app process; it talks to native over shared memory, not sockets.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/embedded-php" rel="noopener">Embedded PHP</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">"PHP ships inside your
                                        app as a library"; "there's no FastCGI, no sockets, no per-request process to
                                        spawn"; "shared memory only works when both sides share a process."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">The Element Runtime is a PHP
                                        extension — native code compiled into libphp itself.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/embedded-php" rel="noopener">Embedded PHP</a> <a href="https://nativephp.com/docs/mobile/4/architecture/glossary" rel="noopener">Glossary</a>
                                    </td>
                                    <td class="np-quote" data-label="Exact line from the docs">"The Element Runtime is
                                        a PHP extension — native code compiled into libphp itself, alongside the
                                        engine."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">It ships as prebuilt binaries
                                        pinned to each release.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/embedded-php" rel="noopener">Embedded PHP</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">"every release of
                                        nativephp/mobile pins exact binary builds… produced and shipped together, from
                                        matching sources."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">The pipeline has three phases:
                                        Render, Publish, Mount.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/render-publish-mount" rel="noopener">Render, Publish, and Mount</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">Doc page titled "Render,
                                        Publish, and Mount," describing all three stages.</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">Render: PHP builds an Element
                                        Tree.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/render-publish-mount" rel="noopener">Render, Publish, and Mount</a> <a href="https://nativephp.com/docs/mobile/4/architecture/glossary" rel="noopener">Glossary</a>
                                    </td>
                                    <td class="np-quote" data-label="Exact line from the docs">"PHP builds an Element
                                        Tree describing what the screen should look like."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">Each <code>native:</code> tag
                                        emits an Element — a plain PHP object.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/render-publish-mount" rel="noopener">Render, Publish, and Mount</a> <a href="https://nativephp.com/docs/mobile/4/architecture/glossary" rel="noopener">Glossary</a>
                                    </td>
                                    <td class="np-quote" data-label="Exact line from the docs">"Blade compiles the
                                        template, and each <code>native:</code> tag emits an Element: a plain PHP
                                        object describing one piece of UI."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">Utility classes like
                                        <code>p-4</code> / <code>text-2xl</code> are parsed into layout and style
                                        values in PHP, not on the native side.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/render-publish-mount" rel="noopener">Render, Publish, and Mount</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">"Utility classes like
                                        <code>p-4</code> and <code>text-2xl</code> are parsed into concrete layout and
                                        style values at this stage."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">Composed EDGE components
                                        flatten; only primitives reach the published tree.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/render-publish-mount" rel="noopener">Render, Publish, and Mount</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">"Higher-level EDGE
                                        components you compose yourself flatten into these primitives; only primitive
                                        elements appear in the tree."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk"><code>@@press="refresh"</code>
                                        is registered and replaced with a stable callback ID.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/render-publish-mount" rel="noopener">Render, Publish, and Mount</a> <a href="https://nativephp.com/docs/mobile/4/architecture/glossary" rel="noopener">Glossary</a>
                                    </td>
                                    <td class="np-quote" data-label="Exact line from the docs">"event handlers like
                                        <code>@@press="refresh"</code> are registered and replaced with stable callback
                                        IDs"; Callback ID: "native events carry the ID back, and PHP resolves it to
                                        your method."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">Publish: each element is
                                        written into shared memory as a node — a compact, fixed-layout binary record
                                        (type, layout, style, refs to props and handlers).</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/render-publish-mount" rel="noopener">Render, Publish, and Mount</a> <a href="https://nativephp.com/docs/mobile/4/architecture/glossary" rel="noopener">Glossary</a>
                                    </td>
                                    <td class="np-quote" data-label="Exact line from the docs">"writes each element
                                        into shared memory as a node: a compact, fixed-layout binary record carrying
                                        the element's type, layout, style, and references to its props and handlers."
                                    </td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">The same pipeline runs for
                                        first paint and for every update after.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/render-publish-mount" rel="noopener">Render, Publish, and Mount</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">"The same pipeline runs
                                        for the first paint of a screen and for every update after it."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">Unchanged subtrees become tiny
                                        reuse markers instead of being re-encoded.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/subtree-reuse" rel="noopener">Subtree Reuse</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">"any subtree whose
                                        fingerprint matches the previous frame is written as a single tiny reuse marker
                                        instead of being re-encoded."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">An identical frame isn't
                                        published at all.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/subtree-reuse" rel="noopener">Subtree Reuse</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">"Identical frames are
                                        dropped on the spot — the native side is never even woken."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">On mount, reuse markers are
                                        spliced from the previous tree without decoding.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/subtree-reuse" rel="noopener">Subtree Reuse</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">"The native reader
                                        splices the corresponding subtree from the tree it already has, without
                                        decoding anything."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">A dedicated native reader
                                        thread decodes frames and diffs against the previous tree.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/render-publish-mount" rel="noopener">Render, Publish, and Mount</a> <a href="https://nativephp.com/docs/mobile/4/architecture/threading-model" rel="noopener">Threading Model</a>
                                    </td>
                                    <td class="np-quote" data-label="Exact line from the docs">"Each platform has a
                                        background thread that receives published frames, decodes them, and diffs them
                                        against the previous tree."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">Publishing uses atomic version
                                        counters; it runs on the PHP thread, off the UI thread.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/threading-model" rel="noopener">Threading Model</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">"atomic version counters
                                        on the shared region"; "the PHP thread wakes, runs your handler, re-renders and
                                        publishes."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">The loop: press event (carrying
                                        a callback ID) → PHP thread wakes → runs the handler → publishes → reader
                                        thread diffs → UI thread mounts.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/render-publish-mount" rel="noopener">Render, Publish, and Mount</a> <a href="https://nativephp.com/docs/mobile/4/architecture/threading-model" rel="noopener">Threading Model</a>
                                        <a href="https://nativephp.com/docs/mobile/4/architecture/glossary" rel="noopener">Glossary</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">"fires a press event
                                        carrying its callback ID into the event channel"; "the PHP thread wakes, runs
                                        your handler, re-renders and publishes → the reader thread diffs → the UI
                                        thread mounts the change."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">Scroll, drag, and
                                        SharedValue-driven animation run on the UI thread frame-by-frame; PHP gets one
                                        discrete event, not a per-frame consult.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/threading-model" rel="noopener">Threading Model</a> <a href="https://nativephp.com/docs/mobile/4/architecture/about-the-new-architecture" rel="noopener">About the New Architecture</a>
                                        <a href="https://nativephp.com/docs/mobile/4/architecture/glossary" rel="noopener">Glossary</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">"Gestures and animations
                                        driven by SharedValues are evaluated directly on the UI thread at the display's
                                        frame rate… PHP receives one event when the gesture completes."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">The value lives on the native
                                        side; PHP holds a handle to it.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/glossary" rel="noopener">Glossary</a> <a href="https://nativephp.com/docs/mobile/4/architecture/about-the-new-architecture" rel="noopener">About the New Architecture</a>
                                    </td>
                                    <td class="np-quote" data-label="Exact line from the docs">SharedValue: "A value
                                        that lives on the native side… PHP holds a handle."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">One node abstraction, two
                                        native implementations; flexbox values drive a per-platform Layout.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/cross-platform-implementation" rel="noopener">Cross-Platform Implementation</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">"each platform
                                        implements flexbox inside its own layout system — a pure-Swift Layout on iOS
                                        and a Compose Layout on Android."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">It renders to SwiftUI on iOS
                                        and Jetpack Compose on Android.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/render-publish-mount" rel="noopener">Render, Publish, and Mount</a> <a href="https://nativephp.com/docs/mobile/4/architecture/cross-platform-implementation" rel="noopener">Cross-Platform Implementation</a>
                                        <a href="https://nativephp.com/docs/mobile/4/architecture/super-native" rel="noopener">SuperNative Introduction (docs)</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">"a renderer that maps
                                        each node type to a SwiftUI view or a composable."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">SuperNative is the default
                                        architecture in v4; the web view is opt-in per screen.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/blog/supernative" rel="noopener">Blog: SuperNative</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">"SuperNative is the
                                        default architecture"; web view is "explicitly opt-in" via
                                        <code>&lt;native:webview&gt;</code>.</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">The UI render/update path has
                                        no serialization step and no web-view bridge.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/super-native" rel="noopener">SuperNative Introduction (docs)</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">"no network round-trip,
                                        no serialization overhead, and no waiting on a web view bridge."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk"><code>super-native</code> is a
                                        beta reference app, not for production.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://github.com/NativePHP/super-native" rel="noopener">Reference app (super-native)</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">"Not for production.
                                        This is a reference app for exploring NativePHP's Element rendering system."
                                    </td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">Reading the render pipeline as
                                        a chain of Gang-of-Four patterns — Interpreter, Composite, Command, Bridge,
                                        Proxy (plus Producer/Consumer and Reconciliation, which aren't GoF) — and
                                        arguing each is a "forced move" at a boundary.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--interp">Interpretation</span></td>
                                    <td class="np-src" data-label="Source">—</td>
                                    <td class="np-quote" data-label="Exact line from the docs">The speaker's framing.
                                        Every pattern sits on a documented mechanism cited above; the names and the
                                        "forced move" thesis are the argument of the talk, not claims in the docs.</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">The node-vs-platform split,
                                        presented as the Gang-of-Four Bridge pattern.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--interp">Interpretation</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/cross-platform-implementation" rel="noopener">Cross-Platform Implementation</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">Mechanism is documented
                                        (see the per-platform Layout row); calling it "Bridge" is the speaker's
                                        reading.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            {{-- ============ SOURCES ============ --}}
            <section class="wr-wrap" style="padding-bottom: 80px;">
                <h2 class="wr-display wr-display--h2">The <span class="wr-em">sources.</span></h2>
                <div class="wr-plate" style="margin-bottom: 26px;">
                    <div class="wr-plate__head">
                        <span class="wr-label">EXTERNAL LINKS — S1–S10</span>
                        <span class="wr-plate__bar wr-plate__bar--sm" aria-hidden="true"></span>
                    </div>
                    <ol class="np-sources wr-plate__body">
                        @foreach ($sources as $source)
                            <li class="wr-row" id="{{ $source['id'] }}">
                                <span class="np-sources__id">{{ $source['label'] }}</span>
                                <a href="{{ $source['url'] }}" rel="noopener"
                                    class="wr-row__name">{{ $source['title'] }}</a>
                                <span class="wr-leader" aria-hidden="true"></span>
                                <span class="wr-row__status">{{ $source['host'] }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>

                <p class="np-footnote">Claims verified against the live NativePHP v4 docs on 2026-07-30. NativePHP v4
                    is a public beta; APIs may change and the reference app is explicitly not for production.</p>
                {{-- Slides / recording: set $slidesUrl / $recordingUrl at the top when Mary supplies them. --}}
                @if ($slidesUrl || $recordingUrl)
                    <p class="np-footnote">
                        @if ($slidesUrl)
                            <a href="{{ $slidesUrl }}" rel="noopener">slides →</a>
                        @endif
                        @if ($recordingUrl)
                            <a href="{{ $recordingUrl }}" rel="noopener">recording →</a>
                        @endif
                    </p>
                @endif
            </section>
        </main>

        {{-- ============ FOOTER ============ --}}
        <footer class="wr-footer">
            <div class="wr-wrap wr-footer__bar">
                <span>MARY.WIN — SEASON {{ now()->year }}</span>
                <span>FINAL SCORE: MARY 42 · DOUBT 0</span>
            </div>
            <div class="wr-rainbow" aria-hidden="true"></div>
        </footer>
    </div>

    <script>
        (function () {
            var root = document.documentElement;
            var toggle = document.querySelector('[data-theme-toggle]');

            function sync() {
                toggle.textContent = root.dataset.theme === 'dark' ? '☀ LIGHTS ON' : '☾ LIGHTS OFF';
            }

            toggle.addEventListener('click', function () {
                root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
                try { localStorage.setItem('winrar-theme', root.dataset.theme); } catch (e) { }
                sync();
            });

            sync();
        })();
    </script>
</body>

</html>
