<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Display a listing of system user accounts.
     * Fulfills SweetNest Document Chapter 3, Security Plan (Page 32) & Table 9.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $query = User::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $users = $query->orderBy('role', 'asc')
            ->orderBy('first_name', 'asc')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
            'staff_count' => User::where('role', 'staff')->count(),
        ];

        return view('users.index', compact('users', 'stats', 'search', 'status'));
    }

    /**
     * Show the form for creating a new user account.
     */
    public function create()
    {
        return view('users.create');
    }

    /**
     * Store a newly created user account.
     * Fulfills Page 32: "Only the Owner is authorized to create and deactivate user accounts."
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:50'],
            'middle_name' => ['nullable', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:staff,owner'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        User::create([
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        return redirect()->route('users.index')
            ->with('success', "Account for {$validated['first_name']} {$validated['last_name']} created successfully.");
    }

    /**
     * Toggle the user's active status (deactivate or reactivate).
     * Fulfills Page 32 Security Plan: Only the Owner can deactivate user accounts.
     */
    public function toggleStatus(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot deactivate your own Owner account.');
        }

        $user->update([
            'is_active' => !$user->is_active,
        ]);

        $statusText = $user->is_active ? 'reactivated' : 'deactivated';

        return back()->with('success', "User account for {$user->name} has been {$statusText}.");
    }

    /**
     * Reset a staff member's password.
     * Fulfills Page 33: "A forgotten password will be reset by the Owner rather than displayed to the user."
     */
    public function resetPassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return back()->with('success', "Password for {$user->name} has been reset successfully.");
    }
}
