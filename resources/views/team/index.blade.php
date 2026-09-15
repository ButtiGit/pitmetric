<x-layouts::app :title="__('Team')">
    @php
        $it = app()->getLocale() === 'it';
        $roleLabels = [
            'owner' => $it ? 'Proprietario' : 'Owner',
            'manager' => 'Manager',
            'mechanic_engineer' => $it ? 'Meccanico / Ingegnere' : 'Mechanic / Engineer',
            'driver' => $it ? 'Pilota' : 'Driver',
            'viewer' => $it ? 'Solo lettura' : 'Viewer',
        ];
        $statusLabels = [
            'active' => $it ? 'Attivo' : 'Active',
            'suspended' => $it ? 'Sospeso' : 'Suspended',
        ];
    @endphp

    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1360px] space-y-5">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-pm-accent">TEAM</p>
                <x-pitmetric.page-header
                    :title="$it ? 'Team e accessi' : 'Team and access'"
                    :description="$it ? 'Seleziona il team operativo, invita collaboratori e assegna permessi coerenti con il loro ruolo.' : 'Select the active team, invite collaborators and assign permissions that match their role.'"
                />
            </div>

            @if (session('status'))
                <div class="rounded-xl border border-pm-success/25 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">{{ session('status') }}</div>
            @endif
            @if (session('invite_url'))
                <div class="rounded-xl border border-pm-accent/30 bg-pm-accent/10 p-4">
                    <p class="text-sm font-bold text-pm-text">{{ $it ? 'Link invito creato' : 'Invitation link created' }}</p>
                    <p class="mt-1 text-xs text-pm-text-secondary">{{ $it ? 'Condividilo con la persona invitata. Il link scade dopo 7 giorni e funziona solo con l’email indicata.' : 'Share it with the invited person. The link expires after 7 days and only works with the invited email.' }}</p>
                    <input class="pm-input mt-3 w-full font-mono text-xs" readonly value="{{ session('invite_url') }}" onclick="this.select()">
                </div>
            @endif
            @if ($errors->any())
                <div class="rounded-xl border border-pm-danger/30 bg-pm-danger-subtle p-4 text-sm text-pm-danger">
                    <ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <section class="grid gap-4 xl:grid-cols-[1.3fr_0.7fr]">
                <article class="pm-panel p-5 sm:p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'Team corrente' : 'Current team' }}</p>
                            <h2 class="mt-2 text-2xl font-black text-pm-text">{{ $workspace->name }}</h2>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <x-pitmetric.status-badge :label="$roleLabels[$currentRole] ?? $currentRole" variant="success" />
                                <span class="rounded-full border border-pm-border bg-pm-subtle px-2.5 py-1 text-xs text-pm-muted">{{ $members->count() }} {{ $it ? 'membri' : 'members' }}</span>
                            </div>
                        </div>
                        <a href="{{ route('dashboard') }}" class="pm-ghost-button">{{ $it ? 'Torna al gestionale' : 'Back to manager' }}</a>
                    </div>

                    @if ($teams->count() > 1)
                        <div class="mt-5 border-t border-pm-border pt-5">
                            <p class="pm-label">{{ $it ? 'Cambia team' : 'Switch team' }}</p>
                            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                @foreach ($teams as $team)
                                    <form method="POST" action="{{ route('team.switch', $team) }}">
                                        @csrf
                                        <button type="submit" class="w-full rounded-xl border px-4 py-3 text-left transition {{ $team->is($workspace) ? 'border-pm-accent/40 bg-pm-accent/10' : 'border-pm-border bg-pm-subtle hover:border-pm-border-strong' }}">
                                            <span class="font-bold text-pm-text">{{ $team->name }}</span>
                                            <span class="mt-1 block text-xs text-pm-muted">{{ $roleLabels[$team->pivot->role] ?? $team->pivot->role }}</span>
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </article>

                <article class="pm-panel p-5 sm:p-6">
                    <h2 class="text-lg font-black text-pm-text">{{ $it ? 'Nuovo team' : 'New team' }}</h2>
                    <p class="mt-1 text-sm leading-6 text-pm-text-secondary">{{ $it ? 'Crea un ambiente separato per un’altra squadra o progetto. Diventerai owner del nuovo team.' : 'Create a separate workspace for another squad or project. You will become its owner.' }}</p>
                    <form method="POST" action="{{ route('team.store') }}" class="mt-4 grid gap-3">
                        @csrf
                        <label class="grid gap-2">
                            <span class="pm-label">{{ $it ? 'Nome team' : 'Team name' }}</span>
                            <input class="pm-input" name="name" required maxlength="120" placeholder="Butti Racing">
                        </label>
                        <button class="pm-race-button justify-center" type="submit">{{ $it ? 'Crea e seleziona' : 'Create and select' }}</button>
                    </form>
                </article>
            </section>

            @if ($canManage)
                <section class="pm-panel p-5 sm:p-6">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-pm-accent">INVITE</p>
                        <h2 class="mt-2 text-lg font-black text-pm-text">{{ $it ? 'Invita una persona' : 'Invite someone' }}</h2>
                        <p class="mt-1 text-sm text-pm-text-secondary">{{ $it ? 'Manager e owner possono invitare. Il nuovo membro riceve i permessi del ruolo scelto appena accetta il link.' : 'Managers and owners can invite. The new member receives the selected role permissions after accepting the link.' }}</p>
                    </div>
                    <form method="POST" action="{{ route('team.invitations.store') }}" class="mt-5 grid gap-4 md:grid-cols-[1fr_0.7fr_auto] md:items-end">
                        @csrf
                        <label class="grid gap-2">
                            <span class="pm-label">Email</span>
                            <input class="pm-input" name="email" type="email" required maxlength="255" value="{{ old('email') }}">
                        </label>
                        <label class="grid gap-2">
                            <span class="pm-label">{{ $it ? 'Ruolo' : 'Role' }}</span>
                            <select class="pm-input" name="role" required>
                                @foreach (\App\Models\WorkspaceMembership::INVITABLE_ROLES as $role)
                                    <option value="{{ $role }}" @selected(old('role', 'mechanic_engineer') === $role)>{{ $roleLabels[$role] }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button class="pm-race-button justify-center" type="submit">{{ $it ? 'Crea invito' : 'Create invite' }}</button>
                    </form>
                </section>
            @endif

            <section class="pm-panel overflow-hidden">
                <div class="border-b border-pm-border p-5 sm:p-6">
                    <h2 class="text-lg font-black text-pm-text">{{ $it ? 'Membri del team' : 'Team members' }}</h2>
                    <p class="mt-1 text-sm text-pm-text-secondary">{{ $it ? 'I permessi operativi sono applicati server-side, non solo nascosti nell’interfaccia.' : 'Operational permissions are enforced server-side, not merely hidden in the interface.' }}</p>
                </div>
                <div class="divide-y divide-pm-border">
                    @foreach ($members as $member)
                        <div class="grid gap-4 p-5 sm:p-6 lg:grid-cols-[1fr_auto] lg:items-center">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-black text-pm-text">{{ $member->name }}</p>
                                    <x-pitmetric.status-badge :label="$roleLabels[$member->pivot->role] ?? $member->pivot->role" :variant="$member->pivot->role === 'owner' ? 'success' : 'neutral'" />
                                    <x-pitmetric.status-badge :label="$statusLabels[$member->pivot->status] ?? $member->pivot->status" :variant="$member->pivot->status === 'active' ? 'success' : 'warning'" />
                                </div>
                                <p class="mt-1 text-sm text-pm-text-secondary">{{ $member->email }}</p>
                                <p class="mt-1 text-xs text-pm-muted">{{ $it ? 'Dal' : 'Joined' }} {{ $member->pivot->joined_at?->format('d/m/Y') ?? '—' }}</p>
                            </div>

                            @if ($isOwner && $member->pivot->role !== 'owner' && $member->id !== auth()->id())
                                <form method="POST" action="{{ route('team.members.update', $member) }}" class="grid gap-2 sm:grid-cols-[180px_140px_auto] sm:items-end">
                                    @csrf
                                    @method('PATCH')
                                    <label class="grid gap-1">
                                        <span class="text-[10px] font-bold uppercase tracking-[0.1em] text-pm-muted">{{ $it ? 'Ruolo' : 'Role' }}</span>
                                        <select class="pm-input" name="role">
                                            @foreach (\App\Models\WorkspaceMembership::INVITABLE_ROLES as $role)
                                                <option value="{{ $role }}" @selected($member->pivot->role === $role)>{{ $roleLabels[$role] }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label class="grid gap-1">
                                        <span class="text-[10px] font-bold uppercase tracking-[0.1em] text-pm-muted">Status</span>
                                        <select class="pm-input" name="status">
                                            @foreach (\App\Models\WorkspaceMembership::STATUSES as $status)
                                                <option value="{{ $status }}" @selected($member->pivot->status === $status)>{{ $statusLabels[$status] }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <button class="pm-ghost-button justify-center" type="submit">{{ $it ? 'Aggiorna' : 'Update' }}</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>

            @if ($canManage && $invitations->isNotEmpty())
                <section class="pm-panel p-5 sm:p-6">
                    <h2 class="text-lg font-black text-pm-text">{{ $it ? 'Inviti in attesa' : 'Pending invitations' }}</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($invitations as $invitation)
                            <div class="rounded-xl border border-pm-border bg-pm-subtle p-4">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                    <div>
                                        <p class="font-bold text-pm-text">{{ $invitation->email }}</p>
                                        <p class="mt-1 text-xs text-pm-muted">{{ $roleLabels[$invitation->role] ?? $invitation->role }} · {{ $it ? 'scade' : 'expires' }} {{ $invitation->expires_at->format('d/m/Y H:i') }}</p>
                                    </div>
                                    <form method="POST" action="{{ route('team.invitations.destroy', $invitation) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-xs font-bold text-pm-danger hover:underline" type="submit">{{ $it ? 'Revoca' : 'Revoke' }}</button>
                                    </form>
                                </div>
                                <input class="pm-input mt-3 w-full font-mono text-xs" readonly value="{{ $inviteLinks[$invitation->id] }}" onclick="this.select()">
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                @foreach ([
                    ['owner', $it ? 'Controllo completo, membri, ruoli e operazioni.' : 'Full control, members, roles and operations.'],
                    ['manager', $it ? 'Gestisce operazioni e invita nuovi membri.' : 'Runs operations and invites members.'],
                    ['mechanic_engineer', $it ? 'Può creare e modificare dati tecnici e operativi.' : 'Can create and edit technical and operational data.'],
                    ['driver', $it ? 'Accesso in lettura al team e ai dati operativi.' : 'Read access to team and operational data.'],
                    ['viewer', $it ? 'Consultazione senza modifiche.' : 'Read-only access without changes.'],
                ] as [$role, $copy])
                    <article class="rounded-xl border border-pm-border bg-pm-surface p-4">
                        <p class="font-bold text-pm-text">{{ $roleLabels[$role] }}</p>
                        <p class="mt-2 text-xs leading-5 text-pm-text-secondary">{{ $copy }}</p>
                    </article>
                @endforeach
            </section>
        </div>
    </div>
</x-layouts::app>
