<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · SweetNest</title>
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body>
<div class="grid min-h-screen place-items-center bg-cream px-4 py-6">
    <div class="w-[min(400px,100%)]">
        <div class="flex items-center justify-center gap-[11px] pb-[18px] text-ink">
            <span class="grid size-[38px] place-items-center rounded-[12px] bg-linear-to-br from-brand-light to-brand text-[17px] font-bold text-white shadow-[0_6px_16px_-6px_rgba(226,56,108,0.7)]">S</span>
            <span><span class="block text-[16px] font-bold tracking-[-0.01em]">SweetNest</span><span class="block text-[11.5px] text-ink-2">Orders &amp; Inventory</span></span>
        </div>
        @yield('content')
    </div>
</div>
</body>
</html>
