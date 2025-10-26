<?php

namespace App\Http\Controllers;

use App\Models\EmployeeFeedback;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeeFeedbackController extends Controller
{
    // Employee: Show feedback form
    public function create()
    {
        return Inertia::render('Feedback/Create');
    }

    // Employee: Submit feedback
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:suggestion,improvement,feature_request,general',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
        ]);

        EmployeeFeedback::create([
            'user_id' => auth()->id(),
            'type' => $validated['type'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'status' => EmployeeFeedback::STATUS_PENDING,
        ]);

        return redirect()->back()->with('success', 'Thank you! Your feedback has been submitted successfully.');
    }

    // Employee: View my submitted feedback
    public function myFeedback()
    {
        $feedback = EmployeeFeedback::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Feedback/MyFeedback', [
            'feedback' => $feedback,
        ]);
    }

    // Admin: List all employee feedback
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $status = $request->get('status', '');
        $type = $request->get('type', '');

        $feedback = EmployeeFeedback::with('user')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', '%' . $search . '%')
                        ->orWhere('description', 'LIKE', '%' . $search . '%')
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'LIKE', '%' . $search . '%');
                        });
                });
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($type, function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('Admin/Feedback/Index', [
            'feedback' => $feedback,
            'search' => $search,
            'status' => $status,
            'type' => $type,
            'statusOptions' => [
                'pending' => 'Pending',
                'under_review' => 'Under Review',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
            ],
            'typeOptions' => [
                'suggestion' => 'Suggestion',
                'improvement' => 'Improvement',
                'feature_request' => 'Feature Request',
                'general' => 'General',
            ],
        ]);
    }

    // Admin: View specific feedback
    public function show(EmployeeFeedback $feedback)
    {
        $feedback->load('user');

        return Inertia::render('Admin/Feedback/Show', [
            'feedback' => $feedback,
        ]);
    }

    // Admin: Respond to feedback
    public function respond(Request $request, EmployeeFeedback $feedback)
    {
        $validated = $request->validate([
            'admin_response' => 'required|string|max:1000',
            'status' => 'required|in:pending,under_review,approved,rejected',
        ]);

        $feedback->update([
            'admin_response' => $validated['admin_response'],
            'status' => $validated['status'],
        ]);

        return redirect()->back()->with('success', 'Response sent successfully.');
    }

    // Admin: Update feedback status
    public function updateStatus(Request $request, EmployeeFeedback $feedback)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,under_review,approved,rejected',
        ]);

        $feedback->update(['status' => $validated['status']]);

        return redirect()->back()->with('success', 'Status updated successfully.');
    }
}
