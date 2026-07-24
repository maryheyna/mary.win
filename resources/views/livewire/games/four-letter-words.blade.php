<div>
    <div class="flw" wire:ignore x-data="flwComposer()" x-init="init()" @keydown.window="onKey($event)">
        <div class="wr-rainbow" aria-hidden="true"></div>

        <div class="flw__wrap">
            <header class="flw__head">
                <span class="flw__mark">FOUR·LETTER·WORDS</span>
                <a href="{{ route('home') }}" class="flw__back">← mary.win</a>
            </header>

            {{-- PLAYING --}}
            <main class="flw__stage" x-show="status === 'playing'">
                <p class="flw__prompt" x-text="promptText()"></p>

                <div class="flw__streak" x-show="streak > 0">STREAK&nbsp; <b x-text="streak"></b></div>

                <div class="flw__boxes">
                    <template x-for="(l, i) in letters" :key="i">
                        <button type="button" class="flw__box" tabindex="-1"
                                :class="{ 'is-selected': cursor === i }"
                                @click="select(i)" x-text="l"></button>
                    </template>
                </div>

                <button type="button" class="flw__submit" x-show="armed" x-cloak
                        :class="{ 'is-focused': cursor === 'submit' }"
                        @click="trySubmit()">SELECT →</button>
            </main>

            {{-- LOST --}}
            <main class="flw__stage" x-show="status === 'lost'" x-cloak>
                <p class="flw__over-title">you lost.</p>
                <p class="flw__over-sub">STREAK&nbsp; <b x-text="finalStreak"></b></p>

                <ol class="flw__log">
                    <template x-for="w in log" :key="w"><li x-text="w"></li></template>
                </ol>

                @auth
                    <p class="flw__note">signed in — saved best streaks arrive in the next update.</p>
                @else
                    <p class="flw__note"><a href="{{ route('register') }}">sign up</a> to keep your best streak.</p>
                @endauth

                <button type="button" class="flw__again" @click="playAgain()">play again →</button>
            </main>
        </div>
    </div>
</div>
