{{-- Inventory is one place in the menu; these tabs switch between its pages. --}}
<nav class="subnav print:hidden" aria-label="Inventory pages">
    <a href="{{ route('inventory.index') }}" class="{{ request()->routeIs('inventory.*') ? 'active' : '' }}">Stock levels</a>
    @owner
        <a href="{{ route('stockIns.index') }}" class="{{ request()->routeIs('stockIns.*') ? 'active' : '' }}">Stock in</a>
        <a href="{{ route('adjustments.index') }}" class="{{ request()->routeIs('adjustments.*') ? 'active' : '' }}">Adjustments</a>
    @endowner
    <a href="{{ route('counts.index') }}" class="{{ request()->routeIs('counts.*') ? 'active' : '' }}">Physical counts</a>
</nav>
