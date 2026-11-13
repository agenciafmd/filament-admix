<div class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1 bg-gray-900 p-2 text-sm text-white">
    <p>
        {{ __('You are accessing as :name.', ['name' => $user->name]) }}
    </p>
    <form method="POST" action="{{ route('filament.admix.impersonation.leave') }}">
        @csrf
        <button type="submit" class="font-semibold underline underline-offset-2">
            {{ __('Back to my account') }}
        </button>
    </form>
</div>
