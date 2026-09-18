@props(['action', 'label' => null, 'message' => null, 'method' => 'DELETE', 'permission' => 'team-write'])
@php($dialogId = 'delete-record-'.substr(hash('sha256', $action), 0, 16))
<x-crud-modal :id="$dialogId" :title="$label ?? __('crud.delete')" :description="$message ?? __('crud.confirm_delete')" :trigger="$label ?? __('crud.delete')" trigger-class="pm-row-action pm-row-action-danger" :permission="$permission" size="max-w-lg">
    <form method="POST" action="{{ $action }}" class="flex justify-end">
        @csrf
        @method($method)
        <button type="submit" class="pm-row-action pm-row-action-danger">{{ $label ?? __('crud.delete') }}</button>
    </form>
</x-crud-modal>
