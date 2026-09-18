@props(['id', 'title', 'action', 'trigger' => null, 'method' => 'PUT', 'permission' => 'team-write'])
<x-crud-modal :id="$id" :title="$title" :trigger="$trigger ?? __('crud.edit')" trigger-class="pm-row-action" :permission="$permission">
    <form method="POST" action="{{ $action }}" class="grid gap-4 sm:grid-cols-2">
        @csrf
        @method($method)
        {{ $slot }}
        <div class="sm:col-span-2 flex justify-end"><button type="submit" class="pm-race-button">{{ __('crud.save') }}</button></div>
    </form>
</x-crud-modal>
