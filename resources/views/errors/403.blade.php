@extends('layouts.plain')

@section('title', 'No access')

@section('content')
<div class="card mb-0">
    <div class="card-body text-center">
        <div class="text-ink-3 text-xs font-semibold">Error 403</div>
        <h1 class="text-xl font-bold tracking-[-0.02em] mt-1.5">No access</h1>
        <p class="text-ink-2 mt-2.5 mb-[18px]">{{ $exception->getMessage() ?: 'Your account doesn\'t have access to this page. Ask the Owner if you think this is a mistake.' }}</p>
        @auth
            <a href="{{ route('dashboard') }}" class="btn btn-primary">Back to dashboard</a>
        @else
            <a href="{{ route('login') }}" class="btn btn-primary">Go to log in</a>
        @endauth
    </div>
</div>
@endsection
