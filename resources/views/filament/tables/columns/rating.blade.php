@php
    $state = $getState();
@endphp

<div class="flex">
    @for($i = 1; $i <= 5; $i++)
        <div
                @class([
                    // TODO
                    'text-gray-200' => $state < $i,
                    'text-blue-500' => $state >= $i,
                ])
        >
            <x-filament::icon
                    color="{{ $state < $i ? 'gray' : 'orange' }}"
                    icon="heroicon-s-star"
                    class="w-6 h-6 pointer-events-none"
            />
        </div>
    @endfor
</div>