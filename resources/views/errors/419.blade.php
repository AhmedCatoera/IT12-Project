@extends('layouts.plain')

@section('title', 'Page expired')

@section('content')
<div class="card mb-0">
    <div class="card-body text-center">
        <div class="text-ink-3 text-xs font-semibold">Error 419</div>
        <h1 class="text-xl font-bold tracking-[-0.02em] mt-1.5">Page expired</h1>
        <p class="text-ink-2 mt-2.5 mb-[18px]">The page was open too long, so it expired for your security. Go back, refresh, and try again.</p>
        @auth
            <a href="{{ route('dashboard') }}" class="btn btn-primary">Back to dashboard</a>
        @else
            <a href="{{ route('login') }}" class="btn btn-primary">Go to log in</a>
        @endauth
    </div>
</div>
@endsection
