@if ($canWrite)
    <div class="fixed inset-x-3 bottom-3 z-40 grid grid-cols-4 gap-2 rounded-2xl border border-pm-border bg-pm-panel/95 p-2 shadow-2xl backdrop-blur sm:hidden">
        <button type="button" class="rounded-xl bg-pm-subtle px-2 py-3 text-[10px] font-black uppercase tracking-[0.08em] text-pm-text" onclick="document.getElementById('add-schedule-item').showModal()">{{ $it ? 'Pianifica' : 'Schedule' }}</button>
        <button type="button" class="rounded-xl bg-pm-subtle px-2 py-3 text-[10px] font-black uppercase tracking-[0.08em] text-pm-text" onclick="document.getElementById('add-event-task').showModal()">{{ $it ? 'Lavoro' : 'Task' }}</button>
        <button type="button" class="rounded-xl bg-pm-subtle px-2 py-3 text-[10px] font-black uppercase tracking-[0.08em] text-pm-text" onclick="document.getElementById('add-event-note').showModal()">{{ $it ? 'Nota' : 'Note' }}</button>
        <button type="button" class="rounded-xl bg-pm-accent px-2 py-3 text-[10px] font-black uppercase tracking-[0.08em] text-white" onclick="document.getElementById('add-event-expense').showModal()">{{ $it ? 'Costo' : 'Cost' }}</button>
    </div>
@endif
