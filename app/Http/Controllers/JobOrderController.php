<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\JobOrder;
use App\Models\Order;
use App\Models\StockOutItem;
use App\Models\User;
use App\Services\JobOrderWorkflow;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JobOrderController extends Controller
{
    public function index(Request $request)
    {
        $tabs = ['active' => 'Active'] + JobOrder::STATUSES + ['all' => 'All'];
        $tab = $this->textFilter($request, 'status');
        $tab = array_key_exists((string) $tab, $tabs) ? $tab : 'active';
        $search = $this->textFilter($request, 'search');

        $query = JobOrder::with(['order.customer', 'assignee'])
            ->when($tab === 'active', fn ($q) => $q->whereIn('status', ['pending', 'in_progress']))
            ->when(array_key_exists($tab, JobOrder::STATUSES), fn ($q) => $q->where('status', $tab))
            ->when($search, fn ($q) => $q->whereHas('order', fn ($o) => $o->search($search)));

        in_array($tab, ['completed', 'cancelled', 'all'], true)
            ? $query->orderByDesc('production_date')->orderByDesc('id')
            : $query->orderBy('production_date')->orderBy('id');

        $jobOrders = $query->paginateOrLast(15)->withQueryString();

        // Confirmed orders still waiting for a job order, five at a time (its own page number,
        // so paging it doesn't move the job order list below).
        $waiting = Order::with('customer')
            ->where('status', 'confirmed')
            ->whereDoesntHave('jobOrder')
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->paginateOrLast(5, 'waiting')
            ->withQueryString();

        $counts = JobOrder::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $counts['active'] = ($counts['pending'] ?? 0) + ($counts['in_progress'] ?? 0);
        $counts['all'] = $counts->only(array_keys(JobOrder::STATUSES))->sum();
        $staff = User::active()->orderBy('first_name')->get();

        return view('job-orders.index', compact('jobOrders', 'waiting', 'tabs', 'tab', 'search', 'counts', 'staff'));
    }

    public function store(Request $request, Order $order, JobOrderWorkflow $workflow)
    {
        $latest = $order->latestProductionDate();

        $data = $request->validateWithBag('jobOrder', [
            // The kitchen has to make it before it is due, so the production date can't go past the due date.
            'production_date' => ['required', 'date', 'before_or_equal:' . $latest->toDateString()],
            'assigned_to' => ['nullable', Rule::exists('users', 'id')->where('is_active', true)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'production_date.before_or_equal' => $order->scheduled_at->lt(today())
                ? 'This order is already overdue, so the production date can\'t be after today.'
                : 'The production date can\'t be after the due date, ' . $latest->format('D, M j') . '.',
        ]);

        $job = $workflow->create($order, $data);

        return redirect()->route('jobOrders.show', $job)->with('success', "Job order {$job->number} created for {$order->order_number}.");
    }

    public function show(JobOrder $jobOrder)
    {
        $jobOrder->load(['order.customer', 'order.items.product', 'assignee', 'stockOuts.items.ingredient', 'stockOuts.recorder']);

        // Total used per ingredient on this job order.
        $usage = StockOutItem::query()
            ->join('stock_outs', 'stock_outs.id', '=', 'stock_out_items.stock_out_id')
            ->join('ingredients', 'ingredients.id', '=', 'stock_out_items.ingredient_id')
            ->where('stock_outs.job_order_id', $jobOrder->id)
            ->groupBy('ingredients.id', 'ingredients.name', 'ingredients.unit')
            ->orderBy('ingredients.name')
            ->selectRaw('ingredients.id, ingredients.name, ingredients.unit, SUM(stock_out_items.quantity) as total')
            ->get();

        $ingredients = Ingredient::active()->withStock()->orderBy('name')->get();
        $actions = JobOrderWorkflow::availableActions($jobOrder);

        return view('job-orders.show', compact('jobOrder', 'usage', 'ingredients', 'actions'));
    }

    public function transition(Request $request, JobOrder $jobOrder, JobOrderWorkflow $workflow)
    {
        $action = $request->validate([
            'action' => ['required', Rule::in(array_keys(JobOrderWorkflow::ACTIONS))],
        ])['action'];

        $workflow->apply($jobOrder, $action);

        $messages = [
            'start' => 'Baking started. The order is now "In production".',
            'complete' => 'Job order done. The order is now "Ready for release".',
            'cancel' => 'Job order cancelled. The order is back to "Confirmed".',
            'reopen' => 'Job order reopened.',
        ];

        return back()->with('success', $messages[$action]);
    }
}
