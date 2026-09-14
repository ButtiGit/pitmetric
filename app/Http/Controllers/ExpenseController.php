<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\User;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
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
            'amount' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'category' => ['required', 'string', 'max:80'],
            'description' => ['required', 'string', 'max:180'],
            'occurred_at' => ['required', 'date'],
        ]);

        Expense::create([
            'amount_cents' => (int) round(((float) $validated['amount']) * 100),
            'currency' => 'EUR',
            'category' => $validated['category'],
            'description' => $validated['description'],
            'occurred_at' => Carbon::parse($validated['occurred_at']),
            'created_by' => $user->getKey(),
        ]);

        return to_route('demo.expenses')->with('status', __('Expense recorded.'));
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        Gate::authorize('delete', $expense);

        if ($expense->related_type !== null) {
            return to_route('demo.expenses')->with('error', __('Linked operational costs cannot be deleted independently from their source record.'));
        }

        $expense->delete();

        return to_route('demo.expenses')->with('status', __('Expense deleted.'));
    }

    private function hasDatabaseAccess(User $user): bool
    {
        return Gate::forUser($user)->allows('manage-updates') || $user->hasDatabaseAccess();
    }
}
