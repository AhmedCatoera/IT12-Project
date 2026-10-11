<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Owner only (Security Plan): create, deactivate and reset passwords. */
class UserController extends Controller
{
    public function index()
    {
        $users = User::orderByDesc('is_active')->orderBy('role')->orderBy('first_name')->paginateOrLast(15);

        return view('users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $request->validateWithBag('createUser', [
            'first_name' => ['required', 'string', 'max:50'],
            'middle_name' => ['nullable', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create($data + ['is_active' => true]);

        return back()->with('success', "Account created for {$data['first_name']} {$data['last_name']}.");
    }

    public function toggleStatus(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        if ($user->is_active && $user->isOwner() && User::where('role', 'owner')->where('is_active', true)->count() <= 1) {
            return back()->with('error', 'At least one Owner account must stay active.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', $user->is_active
            ? "{$user->name} can log in again."
            : "{$user->name} was deactivated and is signed out.");
    }

    public function resetPassword(Request $request, User $user)
    {
        $data = $request->validateWithBag('resetPassword', [
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update(['password' => $data['new_password']]); // hashed by the User model's cast

        return back()->with('success', "Password reset for {$user->name}. Share the new password with them privately.");
    }
}
