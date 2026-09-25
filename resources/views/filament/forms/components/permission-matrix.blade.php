@php
    $groups = $getGroups();
    $abilityColumns = $getAbilityColumns();
    $columnPermissions = $getColumnPermissions();
    $hasExtraPermissions = $hasExtraPermissions();
    $isDisabled = $isDisabled();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            state: $wire.$entangle(@js($getStatePath())),
            isChecked(keys) {
                return keys.length > 0 && keys.every((key) => (this.state ?? []).includes(key))
            },
            isPartial(keys) {
                return ! this.isChecked(keys) && keys.some((key) => (this.state ?? []).includes(key))
            },
            toggle(keys) {
                const state = this.state ?? []

                this.state = this.isChecked(keys)
                    ? state.filter((key) => ! keys.includes(key))
                    : [...new Set([...state, ...keys])]
            },
        }"
        class="overflow-x-auto rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10"
    >
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-white/5">
                <tr>
                    <th class="px-3 py-2 text-start align-middle font-semibold whitespace-nowrap text-gray-950 dark:text-white">
                        {{ __('Resource') }}
                    </th>
                    @foreach ($abilityColumns as $ability => $label)
                        <th class="px-3 py-2 text-start align-middle font-semibold whitespace-nowrap text-gray-950 dark:text-white">
                            <label class="inline-flex cursor-pointer items-center gap-2">
                                <input
                                    type="checkbox"
                                    class="fi-checkbox-input"
                                    x-bind:checked="isChecked(@js($columnPermissions[$ability]))"
                                    x-effect="$el.indeterminate = isPartial(@js($columnPermissions[$ability]))"
                                    x-on:change="toggle(@js($columnPermissions[$ability]))"
                                    @disabled($isDisabled || $columnPermissions[$ability] === [])
                                />
                                <span>{{ mb_ucfirst($label) }}</span>
                            </label>
                        </th>
                    @endforeach
                    @if ($hasExtraPermissions)
                        <th class="px-3 py-2 text-start align-middle font-semibold whitespace-nowrap text-gray-950 dark:text-white">
                            {{ __('Others') }}
                        </th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                @foreach ($groups as $group)
                    @php
                        $rowPermissions = array_keys($group['permissions']);
                    @endphp
                    <tr
                        wire:key="{{ $getStatePath() }}.{{ $group['resource'] }}"
                        class="hover:bg-gray-50 dark:hover:bg-white/5"
                    >
                        <td class="px-3 py-2 align-middle">
                            <label class="inline-flex cursor-pointer items-center gap-2 text-gray-950 dark:text-white">
                                <input
                                    type="checkbox"
                                    class="fi-checkbox-input"
                                    x-bind:checked="isChecked(@js($rowPermissions))"
                                    x-effect="$el.indeterminate = isPartial(@js($rowPermissions))"
                                    x-on:change="toggle(@js($rowPermissions))"
                                    @disabled($isDisabled)
                                />
                                <span>{{ $group['label'] }}</span>
                            </label>
                        </td>
                        @foreach ($abilityColumns as $ability => $label)
                            <td class="px-3 py-2 text-start align-middle">
                                @if ($permission = $group['abilities'][$ability] ?? null)
                                    <input
                                        type="checkbox"
                                        class="fi-checkbox-input"
                                        value="{{ $permission }}"
                                        x-model="state"
                                        aria-label="{{ $group['label'] }}: {{ $label }}"
                                        @disabled($isDisabled)
                                    />
                                @else
                                    <span class="inline-block w-4 text-center text-gray-400 dark:text-gray-500">—</span>
                                @endif
                            </td>
                        @endforeach
                        @if ($hasExtraPermissions)
                            <td class="px-3 py-2 align-middle">
                                <div class="flex flex-wrap gap-x-4 gap-y-1">
                                    @foreach ($group['extra'] as $permission => $label)
                                        <label class="inline-flex cursor-pointer items-center gap-2 text-gray-950 dark:text-white">
                                            <input
                                                type="checkbox"
                                                class="fi-checkbox-input"
                                                value="{{ $permission }}"
                                                x-model="state"
                                                @disabled($isDisabled)
                                            />
                                            <span>{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-dynamic-component>
