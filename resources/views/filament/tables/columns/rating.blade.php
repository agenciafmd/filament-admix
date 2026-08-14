@php
    $state = $getState();
@endphp

<div class="flex">
    @for ($i = 1; $i <= 5; $i++)
        <div @class([
            // TODO
            'text-gray-200' => $state < $i,
            'text-blue-500' => $state >= $i,
        ])>
            <x-filament::icon
                color="{{ $state < $i ? 'gray' : 'orange' }}"
                icon="heroicon-s-star"
                class="pointer-events-none h-6 w-6"
            />
        </div>
    @endfor
</div>
