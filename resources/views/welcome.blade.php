@extends('layouts.app')

@section('content')
    {{-- Hero Section --}}
    <section class="min-h-screen flex flex-col items-center justify-center px-4 py-12 relative">
        <div class="max-w-6xl mx-auto grid lg:grid-cols-2 gap-12 lg:gap-16 items-center relative z-10">
            {{-- Left: Content --}}
            <div class="text-center lg:text-left order-2 lg:order-1">
                {{-- Logo --}}
                <div class="mb-6 animate-slide-up">
                    <x-logo align="left" />
                </div>

                {{-- Tagline --}}
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-4 animate-slide-up delay-100" style="opacity: 0;">
                    Challenge friends to a<br>
                    <span class="gradient-text">battle of words</span>
                </h1>

                <p class="text-lg sm:text-xl mb-8 max-w-lg mx-auto lg:mx-0 animate-slide-up delay-200" style="color: var(--color-text-secondary); opacity: 0;">
                    The classic word game you love, now on your phone and tablet. Play with friends anytime, anywhere. Available in Dutch and English.
                </p>

                {{-- Badges --}}
                <div class="flex flex-wrap gap-2 justify-center lg:justify-start mb-6 animate-slide-up delay-300" style="opacity: 0;">
                    <x-free-badge text="100% Free" />
                    <x-free-badge text="No Ads" />
                </div>

                {{-- Download buttons --}}
                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start animate-slide-up delay-400" style="opacity: 0;">
                    <x-app-store-button store="ios" />
                    <x-app-store-button store="android" />
                </div>
            </div>

            {{-- Right: Device showcase with iPad and iPhone --}}
            <div class="order-1 lg:order-2 flex justify-center lg:justify-end animate-slide-up delay-200" style="opacity: 0;">
                <div class="relative inline-block lg:mr-[-160px] lg:mt-[-40px]">
                    {{-- Glow effect --}}
                    <div class="device-glow"></div>

                    {{-- iPad mockup (landscape, behind) --}}
                    <x-tablet-mockup class="hidden lg:block">
                        <img
                            src="/screenshots/ipad-appstore/02-game-board-landscape.png"
                            alt="WordStockt on iPad"
                            class="w-full"
                        >
                    </x-tablet-mockup>

                    {{-- Phone mockup (in front, bottom left, overlapping iPad) --}}
                    <div class="phone-mockup w-40 sm:w-44 lg:w-56 phone-overlap">
                        <div class="phone-screen screenshot-carousel" id="phone-carousel">
                            <img src="/screenshots/ios-appstore/01-game-board.png" alt="WordStockt game board" class="w-full active">
                            <img src="/screenshots/ios-appstore/02-games-list.png" alt="WordStockt games list" class="w-full">
                            <img src="/screenshots/ios-appstore/04-leaderboard.png" alt="WordStockt leaderboard" class="w-full">
                            <img src="/screenshots/ios-appstore/03-profile.png" alt="WordStockt profile" class="w-full">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Scroll indicator --}}
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 animate-bounce hidden sm:block">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-text-secondary);">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
            </svg>
        </div>
    </section>

    {{-- Features Section with Screenshots --}}
    <section class="py-28 px-4 relative">
        <div class="max-w-6xl mx-auto">
            {{-- Section header --}}
            <div class="text-center mb-20 scroll-animate">
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-6">
                    Everything you love about word games
                </h2>
                <p class="text-lg sm:text-xl max-w-2xl mx-auto" style="color: var(--color-text-secondary);">
                    Classic gameplay meets modern convenience. Challenge friends, track your progress, and become the ultimate word master.
                </p>
            </div>

            {{-- Featured feature 1: Play with Friends --}}
            <div class="grid lg:grid-cols-2 gap-12 items-center mb-24 scroll-animate">
                <div class="order-2 lg:order-1">
                    <x-feature-title text="Play with Friends" />
                    <p class="text-lg mb-6" style="color: var(--color-text-secondary);">
                        Invite friends to a game and take turns whenever it suits you. No need to be online at the same time. Build your friends list and track your head-to-head stats.
                    </p>
                    <ul class="space-y-3" style="color: var(--color-text-secondary);">
                        <x-check-list-item text="Add friends by username" />
                        <x-check-list-item text="See ELO ratings at a glance" />
                        <x-check-list-item text="Start a game with one tap" />
                    </ul>
                </div>
                <div class="order-1 lg:order-2 flex justify-center">
                    <x-phone-mockup>
                        <img src="/screenshots/ios-appstore/06-friends.png" alt="Friends list" class="w-full" loading="lazy">
                    </x-phone-mockup>
                </div>
            </div>

            {{-- Featured feature 2: Your Personal Stats --}}
            <div class="grid lg:grid-cols-2 gap-12 items-center mb-24 scroll-animate">
                <div class="flex justify-center">
                    <x-phone-mockup>
                        <img src="/screenshots/ios-appstore/03-profile.png" alt="Profile stats" class="w-full" loading="lazy">
                    </x-phone-mockup>
                </div>
                <div>
                    <x-feature-title text="Your Personal Stats" />
                    <p class="text-lg mb-6" style="color: var(--color-text-secondary);">
                        Track your progress with detailed personal statistics. See your total games played, words formed, and personal bests all in one place.
                    </p>
                    <ul class="space-y-3" style="color: var(--color-text-secondary);">
                        <x-check-list-item text="Total games won and played" />
                        <x-check-list-item text="Words played and bingos scored" />
                        <x-check-list-item text="Highest scoring word and move" />
                    </ul>
                </div>
            </div>

            {{-- Featured feature 3: Compete on the Leaderboard --}}
            <div class="grid lg:grid-cols-2 gap-12 items-center mb-24 scroll-animate">
                <div class="order-2 lg:order-1">
                    <x-feature-title text="Compete on the Leaderboard" />
                    <p class="text-lg mb-6" style="color: var(--color-text-secondary);">
                        See how you rank against other players. Climb the leaderboard and prove you're the ultimate word master.
                    </p>
                    <ul class="space-y-3" style="color: var(--color-text-secondary);">
                        <x-check-list-item text="Monthly and yearly leaderboards" />
                        <x-check-list-item text="ELO rating system" />
                        <x-check-list-item text="Compare scores with friends" />
                    </ul>
                </div>
                <div class="order-1 lg:order-2 flex justify-center">
                    <x-phone-mockup>
                        <img src="/screenshots/ios-appstore/04-leaderboard.png" alt="Leaderboard" class="w-full" loading="lazy">
                    </x-phone-mockup>
                </div>
            </div>

            {{-- Featured feature 4: Unlock Achievements --}}
            <div class="grid lg:grid-cols-2 gap-12 items-center mb-24 scroll-animate">
                <div class="flex justify-center">
                    <x-phone-mockup>
                        <img src="/screenshots/ios-appstore/05-achievements.png" alt="Achievements" class="w-full" loading="lazy">
                    </x-phone-mockup>
                </div>
                <div>
                    <x-feature-title text="Unlock Achievements" />
                    <p class="text-lg mb-6" style="color: var(--color-text-secondary);">
                        Earn achievements as you play and track your progress. From your first victory to mastering rare words, there's always a new goal to chase.
                    </p>
                    <ul class="space-y-3" style="color: var(--color-text-secondary);">
                        <x-check-list-item text="26 unique achievements to unlock" />
                        <x-check-list-item text="Game milestones and word mastery" />
                        <x-check-list-item text="Track your progress over time" />
                    </ul>
                </div>
            </div>

            {{-- Remaining features grid --}}
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <x-feature-card title="Instant Updates" description="See moves the moment they're played. No refreshing needed." delay="1">
                    <x-slot:icon>
                        <svg class="w-6 h-6" style="color: var(--color-text-secondary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </x-slot:icon>
                </x-feature-card>

                <x-feature-card title="Dutch & English" description="Play in your preferred language with complete dictionaries." delay="2">
                    <x-slot:icon>
                        <svg class="w-6 h-6" style="color: var(--color-text-secondary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                        </svg>
                    </x-slot:icon>
                </x-feature-card>

                <x-feature-card title="Classic Rules" description="Double/triple word scores, 7-tile bonuses, and more." delay="3">
                    <x-slot:icon>
                        <svg class="w-6 h-6" style="color: var(--color-text-secondary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                        </svg>
                    </x-slot:icon>
                </x-feature-card>

                <x-feature-card title="No Ads" description="Play without interruptions. No banners, no video ads." delay="4">
                    <x-slot:icon>
                        <svg class="w-6 h-6" style="color: var(--color-text-secondary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                        </svg>
                    </x-slot:icon>
                </x-feature-card>
            </div>
        </div>
    </section>

    {{-- Fair Play Section --}}
    <section class="py-28 px-4 relative">
        <div class="max-w-4xl mx-auto">
            {{-- Section header --}}
            <div class="text-center mb-16 scroll-animate">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full mb-6" style="background: rgba(74, 144, 217, 0.1); border: 1px solid rgba(74, 144, 217, 0.2);">
                    <svg class="w-4 h-4" style="color: var(--color-primary);" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm font-medium" style="color: var(--color-primary);">What makes us different</span>
                </div>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-6">
                    Designed for fair play
                </h2>
                <p class="text-lg sm:text-xl max-w-2xl mx-auto" style="color: var(--color-text-secondary);">
                    We've added special rules to reduce the luck factor and make every game more balanced.
                </p>
            </div>

            {{-- Rules grid --}}
            <div class="grid sm:grid-cols-3 gap-6">
                <x-rule-card title="One free swap" description="Every player gets one tile swap per game without losing their turn. Bad luck? Trade it away." delay="1">
                    <x-slot:icon>
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                    </x-slot:icon>
                </x-rule-card>

                <x-rule-card title="Guaranteed blank tile" description="Both players are guaranteed to receive at least one blank tile during the game. No more unfair advantages." delay="2">
                    <x-slot:icon>
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </x-slot:icon>
                </x-rule-card>

                <x-rule-card title="Long word bonus" description="Play longer words, earn extra points. Rewards vocabulary and strategy over lucky tile draws." delay="3">
                    <x-slot:icon>
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </x-slot:icon>
                </x-rule-card>
            </div>
        </div>
    </section>

    {{-- Final CTA Section --}}
    <section class="py-28 px-4 relative overflow-hidden">
        <div class="max-w-3xl mx-auto text-center relative z-10 scroll-animate">
            {{-- Animated tiles spelling "PLAY" --}}
            <div class="flex justify-center gap-2 mb-8">
                @foreach(['P', 'L', 'A', 'Y'] as $index => $letter)
                    <div class="tile w-14 h-14 sm:w-16 sm:h-16 rounded-lg flex flex-col items-center justify-center font-bold shadow-lg animate-tile-bounce" style="background-color: var(--color-tile); color: var(--color-tile-text); animation-delay: {{ $index * 0.15 }}s;">
                        <span class="text-2xl sm:text-3xl">{{ $letter }}</span>
                    </div>
                @endforeach
            </div>

            <h2 class="text-3xl sm:text-4xl font-bold mb-4">
                Ready to prove your word skills?
            </h2>
            <p class="text-lg mb-8" style="color: var(--color-text-secondary);">
                Download WordStockt now and start your first game today. It's free!
            </p>

            {{-- Download buttons --}}
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <x-app-store-button store="ios" size="large" />
                <x-app-store-button store="android" size="large" />
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="py-12 px-4">
        <div class="max-w-6xl mx-auto">
            {{-- Mobile: stacked layout --}}
            <div class="flex flex-col items-center gap-6 sm:hidden">
                <x-logo size="small" />
                <p class="text-sm flex items-center gap-1.5" style="color: var(--color-text-secondary);">
                    Made by
                    <a href="https://spatie.be" target="_blank" rel="noopener" class="hover:opacity-80 transition-opacity inline-flex">
                        <x-spatie-logo />
                    </a>
                    in Antwerp, Belgium
                </p>
                <div class="flex items-center gap-4 text-sm" style="color: var(--color-text-secondary);">
                    <a href="/support" class="hover:underline transition-colors" style="color: var(--color-text-secondary);">Support</a>
                    <span style="opacity: 0.5;">|</span>
                    <a href="/privacy" class="hover:underline transition-colors" style="color: var(--color-text-secondary);">Privacy</a>
                </div>
            </div>

            {{-- Desktop: three-column grid with true centering --}}
            <div class="hidden sm:grid sm:grid-cols-3 items-center">
                <div class="flex items-center">
                    <x-logo size="small" />
                </div>
                <p class="text-sm flex items-center justify-center gap-1.5" style="color: var(--color-text-secondary);">
                    Made by
                    <a href="https://spatie.be" target="_blank" rel="noopener" class="hover:opacity-80 transition-opacity inline-flex">
                        <x-spatie-logo />
                    </a>
                    in Antwerp, Belgium
                </p>
                <div class="flex items-center justify-end gap-4 text-sm" style="color: var(--color-text-secondary);">
                    <a href="/support" class="hover:underline transition-colors" style="color: var(--color-text-secondary);">Support</a>
                    <span style="opacity: 0.5;">|</span>
                    <a href="/privacy" class="hover:underline transition-colors" style="color: var(--color-text-secondary);">Privacy</a>
                </div>
            </div>
        </div>
    </footer>
@endsection
