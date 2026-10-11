<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · SweetNest</title>
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body>
@php
    $user = auth()->user();
    $is = fn (...$patterns) => request()->routeIs(...$patterns) ? 'active' : '';
@endphp
{{-- Sidebar: fixed on a laptop, slides in on a phone (nav-open on <body>). --}}
<aside class="fixed inset-y-0 left-0 z-40 flex w-[248px] flex-col bg-linear-to-b from-side to-side-deep text-side-text max-lg:-translate-x-full max-lg:transition-transform max-lg:duration-200 nav-open:translate-x-0 nav-open:shadow-pop print:hidden" aria-label="Main menu">
    <a href="{{ route('dashboard') }}" class="flex items-center gap-[11px] px-5 pt-5 pb-4 text-inherit hover:no-underline">
        <span class="grid size-[38px] place-items-center rounded-[12px] bg-linear-to-br from-brand-light to-brand text-[17px] font-bold text-white shadow-[0_6px_16px_-6px_rgba(226,56,108,0.7)]">S</span>
        <span><span class="block text-[16px] font-bold tracking-[-0.01em]">SweetNest</span><span class="block text-[11.5px] text-side-muted">Orders &amp; Inventory</span></span>
    </a>
    <nav class="flex-1 overflow-y-auto px-3 pt-1 pb-3.5">
        {{-- Everyone's daily pages, evenly spaced; only the Owner's pages get a heading. --}}
        <a href="{{ route('dashboard') }}" class="nav-link {{ $is('dashboard') }}"><x-icon name="dashboard" /> Dashboard</a>
        <a href="{{ route('orders.index') }}" class="nav-link {{ $is('orders.*', 'payments.*') }}"><x-icon name="orders" /> Orders</a>
        <a href="{{ route('jobOrders.index') }}" class="nav-link {{ $is('jobOrders.*') }}"><x-icon name="job" /> Job Orders</a>
        <a href="{{ route('customers.index') }}" class="nav-link {{ $is('customers.*') }}"><x-icon name="customers" /> Customers</a>
        <a href="{{ route('inventory.index') }}" class="nav-link {{ $is('inventory.*', 'stockIns.*', 'adjustments.*', 'counts.*') }}"><x-icon name="stock" /> Inventory
            @if (($navLowStock ?? 0) > 0)<span class="ml-auto rounded-full bg-brand px-[7px] py-px text-2xs font-bold text-white" title="Low stock items">{{ $navLowStock }}</span>@endif
        </a>

        @owner
            <div class="mt-4">
                <div class="px-3 pb-2 text-[10.5px] font-semibold tracking-[.1em] text-side-muted uppercase">Owner</div>
                <a href="{{ route('products.index') }}" class="nav-link {{ $is('products.*') }}"><x-icon name="products" /> Products</a>
                <a href="{{ route('reports.index') }}" class="nav-link {{ $is('reports.*') }}"><x-icon name="reports" /> Reports</a>
                <a href="{{ route('users.index') }}" class="nav-link {{ $is('users.*') }}"><x-icon name="users" /> Users</a>
            </div>
        @endowner
    </nav>
    <div class="flex items-center gap-2.5 border-t border-white/8 px-4 py-3.5">
        <span class="grid size-[34px] shrink-0 place-items-center rounded-full border border-white/10 bg-side-2 text-sm font-semibold text-white">{{ strtoupper(substr($user->first_name, 0, 1)) }}</span>
        <span class="min-w-0 flex-1"><strong class="block truncate text-sm text-white">{{ $user->name }}</strong><span class="text-[12px] text-side-muted">{{ $user->role_label }}</span></span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-ghost btn-sm text-side-muted hover:bg-white/8 hover:text-white" title="Log out" aria-label="Log out"><x-icon name="logout" /></button>
        </form>
    </div>
</aside>
<div class="hidden max-lg:nav-open:block fixed inset-0 z-30 bg-side/45" data-scrim></div>

<div class="flex min-h-screen flex-col lg:ml-[248px] print:ml-0">
    <header class="sticky top-0 z-20 flex items-center gap-2.5 bg-side px-4 py-2.5 text-side-text lg:hidden print:hidden">
        <button type="button" class="btn btn-ghost btn-sm text-side-text" data-nav-toggle aria-label="Open menu"><x-icon name="menu" /></button>
        <a href="{{ route('dashboard') }}" class="flex items-center gap-[11px] text-inherit hover:no-underline"><span class="grid size-[38px] place-items-center rounded-[12px] bg-linear-to-br from-brand-light to-brand text-[17px] font-bold text-white">S</span><span class="text-[16px] font-bold tracking-[-0.01em]">SweetNest</span></a>
    </header>

    <main class="mx-auto w-full max-w-[1200px] flex-1 px-8 pt-[30px] pb-14 max-lg:px-4 max-lg:pt-[18px] max-lg:pb-10 print:max-w-none print:p-0">
        @include('partials.flash')
        @yield('content')
    </main>
</div>

<dialog id="confirmDialog" class="modal">
    <div class="modal-head"><h2 class="modal-title">Please confirm</h2></div>
    <div class="modal-body"><p data-confirm-text></p></div>
    <div class="modal-foot">
        <button type="button" class="btn" data-modal-close>Go back</button>
        <button type="button" class="btn btn-primary" data-confirm-ok>Yes, continue</button>
    </div>
</dialog>
</body>
</html>
