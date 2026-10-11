@extends('layouts.plain')

@section('title', 'Files too large')

@section('content')
<div class="card mb-0">
    <div class="card-body text-center">
        <div class="text-ink-3 text-xs font-semibold">Error 413</div>
        <h1 class="text-xl font-bold tracking-[-0.02em] mt-1.5">The photos were too large</h1>
        <p class="text-ink-2 mt-2.5 mb-[18px]">Nothing was saved. Go back and attach smaller photos (screenshots usually work), or fewer photos at a time.</p>
        <a href="{{ url()->previous() }}" class="btn btn-primary">Go back</a>
    </div>
</div>
@endsection
