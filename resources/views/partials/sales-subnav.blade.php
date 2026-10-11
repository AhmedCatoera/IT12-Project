{{-- The payments list lives inside Orders in the menu; these tabs switch between the two. --}}
<nav class="subnav print:hidden" aria-label="Orders pages">
    <a href="{{ route('orders.index') }}" class="{{ request()->routeIs('orders.*') ? 'active' : '' }}">Orders</a>
    <a href="{{ route('payments.index') }}" class="{{ request()->routeIs('payments.*') ? 'active' : '' }}">Payments</a>
</nav>
