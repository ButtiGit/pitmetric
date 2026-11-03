<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\RaceEvent;
use App\Models\User;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request, WorkspaceContext $workspaceContext): View
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        if (! $this->hasDatabaseAccess($user)) {
            return view('demo.workspace', ['initialSection' => 'expenses']);
        }

        if (! $workspaceContext->isCoreReady()) {
            return view('garage.unavailable');
        }

        $workspaceContext->personal($user);
        $expenses = Expense::query()->latest('occurred_at')->latest('id')->get();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        return view('expenses.index', [
            'events' => RaceEvent::withTrashed()->orderByDesc('start_date')->get(),
            'expenses' => $expenses,
            'totalCents' => (int) $expenses->sum('amount_cents'),
            'monthCents' => (int) Expense::query()
                ->whereBetween('occurred_at', [$monthStart, $monthEnd])
                ->sum('amount_cents'),
            'linkedCount' => $expenses->whereNotNull('related_type')->count(),
            'categoryTotals' => $expenses
                ->groupBy('category')
                ->map(fn ($items): int => (int) $items->sum('amount_cents'))
                ->sortDesc(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $workspaceContext): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $workspaceContext->personal($user);

        $validated = $request->validate([
            'event_id' => ['nullable', 'integer', Rule::exists('events', 'id')->where('workspace_id', app(WorkspaceContext::class)->currentId($request->user()))],
            'amount' => ['required', 'numeric', 'min:0.01', 'decimal:0,2', 'max:1000000'],
            'category' => ['required', 'string', 'max:80'],
            'description' => ['required', 'string', 'max:180'],
            'occurred_at' => ['required', 'date'],
        ]);

        Expense::create([
            'event_id' => $validated['event_id'] ?? null,
            'amount_cents' => (int) round(((float) $validated['amount']) * 100),
            'currency' => 'EUR',
            'category' => $validated['category'],
            'description' => $validated['description'],
            'occurred_at' => Carbon::parse($validated['occurred_at']),
            'created_by' => $user->getKey(),
        ]);

        return to_route('expenses.index')->with('status', __('Expense recorded.'));
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        Gate::authorize('update', $expense);

        if ($expense->related_type !== null) {
            throw ValidationException::withMessages([
                'expense' => __('Linked operational costs cannot be edited independently from their source record.'),
            ]);
        }

        $validated = $request->validate([
            'event_id' => ['nullable', 'integer', Rule::exists('events', 'id')->where('workspace_id', app(WorkspaceContext::class)->currentId($request->user()))],
            'amount' => ['required', 'numeric', 'min:0.01', 'decimal:0,2', 'max:1000000'],
            'category' => ['required', 'string', 'max:80'],
            'description' => ['required', 'string', 'max:180'],
            'occurred_at' => ['required', 'date'],
        ]);
        $expense->update([
            'event_id' => array_key_exists('event_id', $validated) ? $validated['event_id'] : $expense->event_id,
            'amount_cents' => (int) round(((float) $validated['amount']) * 100),
            'category' => $validated['category'],
            'description' => $validated['description'],
            'occurred_at' => Carbon::parse($validated['occurred_at']),
        ]);

        return to_route('expenses.index')->with('status', __('Expense updated.'));
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        Gate::authorize('delete', $expense);

        if ($expense->related_type !== null) {
            return to_route('expenses.index')->with('error', __('Linked operational costs cannot be deleted independently from their source record.'));
        }

        $expense->delete();

        return to_route('expenses.index')->with('status', __('Expense deleted.'));
    }

    private function hasDatabaseAccess(User $user): bool
    {
        return Gate::forUser($user)->allows('manage-updates') || $user->hasDatabaseAccess();
    }
}
