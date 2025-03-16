<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    // Remove the constructor with middleware calls
    // The middleware is already applied at the route level in web.php
    
    public function index()
    {
        // List all users
        // You can paginate if you have many
        $users = User::orderBy('created_at', 'desc')->paginate('10');

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
        ]);
    }

    public function create()
    {
        // Return a view for creating a new user
        return Inertia::render('Admin/Users/Create');
    }

    public function store(StoreUserRequest $request)
    {
        // Validate & create user
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        
        User::create($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully!');
    }

    public function show(User $user)
    {
        // Optional method if you want a detail page
        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
        ]);
    }

    public function edit(User $user)
    {
        // Return a view to edit an existing user
        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            // Don't overwrite existing password if not provided
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully!');
    }

    public function destroy(User $user)
    {
        // Delete user (careful if you want to preserve data or soft-delete)
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully!');
    }
}
