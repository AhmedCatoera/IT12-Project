<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log in · SweetNest</title>
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body>
<div class="grid min-h-screen xl:grid-cols-[minmax(0,1.05fr)_minmax(0,1fr)]">
    {{-- Left side: the pitch, on a laptop only. --}}
    <aside class="relative flex flex-col justify-between overflow-hidden p-11 text-side-text max-xl:hidden" style="background: radial-gradient(120% 90% at 10% 0%, #4a2837 0%, #2a1a21 55%, #1c1015 100%)">
        <a class="flex items-center gap-[11px] text-inherit hover:no-underline" href="{{ route('login') }}">
            <span class="grid size-[38px] place-items-center rounded-[12px] bg-linear-to-br from-brand-light to-brand text-[17px] font-bold text-white shadow-[0_6px_16px_-6px_rgba(226,56,108,0.7)]">S</span>
            <span><span class="block text-[16px] font-bold tracking-[-0.01em]">SweetNest</span><span class="block text-[11.5px] text-side-muted">Homemade Order &amp; Inventory Management</span></span>
        </a>
        <div>
            <svg class="my-6 mb-2 block h-auto w-[min(100%,420px)]" viewBox="0 0 420 260" fill="none" aria-hidden="true">
                <defs>
                    <linearGradient id="plate" x1="0" x2="1"><stop offset="0" stop-color="#5a3446"/><stop offset="1" stop-color="#3a2530"/></linearGradient>
                    <linearGradient id="tier" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ffe3ec"/><stop offset="1" stop-color="#f7b8cc"/></linearGradient>
                    <linearGradient id="tier2" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff1f5"/><stop offset="1" stop-color="#f9c9d8"/></linearGradient>
                </defs>
                <ellipse cx="210" cy="232" rx="170" ry="16" fill="#1c1015" opacity=".6"/>
                <ellipse cx="210" cy="222" rx="150" ry="18" fill="url(#plate)"/>
                <rect x="95" y="150" width="230" height="66" rx="10" fill="url(#tier)"/>
                <path d="M95 160c20 18 40 18 60 0s40-18 60 0 40 18 60 0 40-18 50 0v-10H95z" fill="#fff8fa"/>
                <rect x="130" y="98" width="160" height="58" rx="9" fill="url(#tier2)"/>
                <path d="M130 108c15 14 30 14 45 0s30-14 45 0 30 14 45 0 20-14 25 0v-10H130z" fill="#fff"/>
                <rect x="163" y="54" width="94" height="50" rx="8" fill="url(#tier)"/>
                <path d="M163 64c10 10 20 10 30 0s20-10 30 0 20 10 34 0v-10h-94z" fill="#fff8fa"/>
                <g stroke="#f7b8cc" stroke-width="3" stroke-linecap="round">
                    <path d="M186 54v-16M210 54v-20M234 54v-16"/>
                </g>
                <g fill="#ffd166">
                    <ellipse cx="186" cy="34" rx="4" ry="6"/><ellipse cx="210" cy="30" rx="4" ry="6"/><ellipse cx="234" cy="34" rx="4" ry="6"/>
                </g>
                <g fill="#c4204f">
                    <circle cx="150" cy="176" r="6"/><circle cx="210" cy="180" r="6"/><circle cx="270" cy="176" r="6"/>
                    <circle cx="175" cy="126" r="5"/><circle cx="245" cy="126" r="5"/>
                    <circle cx="210" cy="80" r="5"/>
                </g>
                <g fill="#ff8fb0" opacity=".9">
                    <circle cx="60" cy="70" r="3"/><circle cx="360" cy="52" r="4"/><circle cx="380" cy="150" r="3"/><circle cx="40" cy="170" r="4"/>
                </g>
            </svg>
            <h2 class="max-w-[440px] text-3xl leading-[1.15] font-bold tracking-[-0.02em] text-white">Every order, payment and ingredient in one place.</h2>
            <p class="mt-3 max-w-[400px] text-md text-side-muted">Take orders, track the kitchen, and keep stock honest, from the counter or your phone.</p>
        </div>
        <ul class="m-0 grid list-none gap-2 p-0 text-[13.5px] text-side-muted [&_.icon]:text-brand-pink [&_li]:flex [&_li]:items-center [&_li]:gap-2.5">
            <li><x-icon name="orders" /> Orders confirmed automatically once the down payment is in (full payment on rush orders)</li>
            <li><x-icon name="job" /> Job orders tell the kitchen what to make and log what was used</li>
            <li><x-icon name="stock" /> Stock on hand is always calculated, never guessed</li>
        </ul>
    </aside>

    <main class="grid place-items-center px-6 py-10">
        <div class="w-[min(380px,100%)]">
            {{-- Small brand for phones and tablets, where the left side is hidden. --}}
            <div class="flex items-center gap-[11px] pb-[18px] text-ink xl:hidden">
                <span class="grid size-[38px] place-items-center rounded-[12px] bg-linear-to-br from-brand-light to-brand text-[17px] font-bold text-white shadow-[0_6px_16px_-6px_rgba(226,56,108,0.7)]">S</span>
                <span><span class="block text-[16px] font-bold tracking-[-0.01em]">SweetNest</span><span class="block text-[11.5px] text-ink-2">Orders &amp; Inventory</span></span>
            </div>
            <div class="card mb-0 shadow-none">
                <div class="card-body p-7">
                    <h1 class="text-xl font-bold tracking-[-0.02em]">Welcome back</h1>
                    <p class="mt-1 mb-4 text-ink-2">Log in with your Owner or Staff account.</p>

                    @if (session('success'))
                        <div class="alert alert-success"><div class="grow">{{ session('success') }}</div></div>
                    @endif

                    <form method="POST" action="{{ route('login.post') }}">
                        @csrf
                        <x-field name="email" label="Email">
                            <input id="email" type="email" name="email" value="{{ old('email') }}" class="input @error('email') is-invalid @enderror" required autofocus autocomplete="username">
                        </x-field>
                        <x-field name="password" label="Password">
                            <input id="password" type="password" name="password" class="input @error('password') is-invalid @enderror" required autocomplete="current-password">
                        </x-field>
                        <button type="submit" class="btn btn-primary btn-block mt-1.5">Log in</button>
                    </form>
                </div>
            </div>
            <p class="text-xs text-ink-2 text-center mt-3.5">Forgot your password? Ask the Owner to reset it.</p>
            @if (app()->environment('local'))
                <p class="text-xs text-ink-3 text-center mt-1.5">Demo accounts: owner@sweetnest.com or staff@sweetnest.com, password "password"</p>
            @endif
        </div>
    </main>
</div>
</body>
</html>
