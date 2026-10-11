@extends('layouts.plain')

@section('title', 'Something went wrong')

@section('content')
<div class="card mb-0">
    <div class="card-body text-center">
        <div class="text-ink-3 text-xs font-semibold">Error 500</div>
        <h1 class="text-xl font-bold tracking-[-0.02em] mt-1.5">Something went wrong</h1>
        <p class="text-ink-2 mt-2.5 mb-[18px]">The system hit an error. Please try again. If it keeps happening, tell the Owner what you were doing.</p>
        @auth
            <a href="{{ route('dashboard') }}" class="btn btn-primary">Back to dashboard</a>
        @else
            <a href="{{ route('login') }}" class="btn btn-primary">Go to log in</a>
        @endauth
    </div>
</div>
@endsection
