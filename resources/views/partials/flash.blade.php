@if (session('success'))
    <div class="alert alert-success" role="status"><div class="grow">{{ session('success') }}</div><button type="button" class="alert-close" aria-label="Close">&times;</button></div>
@endif
@if (session('error'))
    <div class="alert alert-error" role="alert"><div class="grow">{{ session('error') }}</div><button type="button" class="alert-close" aria-label="Close">&times;</button></div>
@endif
@if ($errors->any())
    <div class="alert alert-error" role="alert">
        <div class="grow">
            <strong>Please check the form.</strong>
            @foreach (['lines', 'items', 'action', 'counts', 'count'] as $general)
                @if ($errors->has($general))<div>{{ $errors->first($general) }}</div>@endif
            @endforeach
        </div>
        <button type="button" class="alert-close" aria-label="Close">&times;</button>
    </div>
@endif
