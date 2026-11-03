@if (session('status'))
    <p class="rounded-xl border border-pm-success/30 p-4 text-pm-text" role="status">{{ session('status') }}</p>
@endif
@if ($errors->any())
    <div class="rounded-xl border border-pm-danger/40 p-4 text-pm-text" role="alert"><ul class="list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
