{{-- Mounts a React component from resources/js/components. The props are passed as JSON
     and the component renders its own fields, so the surrounding Blade <form> still posts normally. --}}
@props(['name', 'props' => []])
<div {{ $attributes }} data-react="{{ $name }}" data-props="{{ json_encode($props, JSON_UNESCAPED_UNICODE) }}"></div>
