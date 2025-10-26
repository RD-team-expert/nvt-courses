<?php

namespace App\Http\Controllers;

use App\Models\BugReport;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class BugReportController extends Controller
{
    // Admin: List all bug reports
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $status = $request->get('status', '');
        $priority = $request->get('priority', '');

        $bugReports = BugReport::with(['reportedBy', 'assignedTo'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', '%' . $search . '%')
                        ->orWhere('description', 'LIKE', '%' . $search . '%');
                });
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($priority, function ($query) use ($priority) {
                $query->where('priority', $priority);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('Admin/BugReports/Index', [
            'bugReports' => $bugReports,
            'search' => $search,
            'status' => $status,
            'priority' => $priority,
            'statusOptions' => [
                'open' => 'Open',
                'in_progress' => 'In Progress',
                'resolved' => 'Resolved',
                'closed' => 'Closed',
            ],
            'priorityOptions' => [
                'low' => 'Low',
                'medium' => 'Medium',
                'high' => 'High',
                'critical' => 'Critical',
            ],
        ]);
    }

    // Admin: Show create bug report form
    public function create()
    {
        $users = User::select('id', 'name')->get();

        return Inertia::render('Admin/BugReports/Create', [
            'users' => $users,
        ]);
    }

    // Admin: Store new bug report
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'priority' => 'required|in:low,medium,high,critical',
            'steps_to_reproduce' => 'nullable|string|max:1000',
            'page_url' => 'nullable|url|max:500',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        BugReport::create([
            'reported_by' => auth()->id(),
            'assigned_to' => $validated['assigned_to'] ?? null,
            'priority' => $validated['priority'],
            'status' => BugReport::STATUS_OPEN,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'steps_to_reproduce' => $validated['steps_to_reproduce'],
            'page_url' => $validated['page_url'],
        ]);

        return redirect()->route('admin.bug-reports.index')
            ->with('success', 'Bug report created successfully.');
    }

    // Admin: Show specific bug report
    public function show(BugReport $bugReport)
    {
        $bugReport->load(['reportedBy', 'assignedTo']);

        return Inertia::render('Admin/BugReports/Show', [
            'bugReport' => $bugReport,
        ]);
    }

    // Admin: Show edit bug report form
    public function edit(BugReport $bugReport)
    {
        $bugReport->load(['reportedBy', 'assignedTo']);
        $users = User::select('id', 'name')->get();

        return Inertia::render('Admin/BugReports/Edit', [
            'bugReport' => $bugReport,
            'users' => $users,
        ]);
    }

    // Admin: Update bug report
    public function update(Request $request, BugReport $bugReport)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'priority' => 'required|in:low,medium,high,critical',
            'status' => 'required|in:open,in_progress,resolved,closed',
            'steps_to_reproduce' => 'nullable|string|max:1000',
            'page_url' => 'nullable|url|max:500',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $bugReport->update($validated);

        return redirect()->route('admin.bug-reports.index')
            ->with('success', 'Bug report updated successfully.');
    }

    // Admin: Delete bug report
    public function destroy(BugReport $bugReport)
    {
        $bugReport->delete();

        return redirect()->route('admin.bug-reports.index')
            ->with('success', 'Bug report deleted successfully.');
    }

    // Admin: Assign bug to developer
    public function assign(Request $request, BugReport $bugReport)
    {
        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        $bugReport->update([
            'assigned_to' => $validated['assigned_to'],
            'status' => 'in_progress'
        ]);

        return redirect()->back()->with('success', 'Bug report assigned successfully.');
    }

    // Admin: Mark bug as resolved
    public function resolve(BugReport $bugReport)
    {
        $bugReport->update([
            'status' => 'resolved',
            'resolved_at' => Carbon::now(),
        ]);

        return redirect()->back()->with('success', 'Bug report marked as resolved.');
    }
}
