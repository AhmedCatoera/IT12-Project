@extends('layouts.app')

@section('title', 'User Accounts Administration - SweetNest OIMS')

@section('styles')
<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .header-title h2 {
        font-size: 1.6rem;
        font-weight: 800;
        color: var(--text-main);
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
    }

    .header-title p {
        font-size: 0.88rem;
        color: var(--text-muted);
        margin-top: 4px;
    }

    .stats-bar {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1.25rem;
        margin-bottom: 1.75rem;
    }

    .stat-pill {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 1.25rem 1.5rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
        transition: transform 0.2s, box-shadow 0.2s;
        border-left: 4px solid var(--primary);
    }

    .stat-pill:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
    }

    .stat-pill-label {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--text-muted);
        letter-spacing: 0.05em;
    }

    .stat-pill-val {
        font-size: 1.75rem;
        font-weight: 800;
        color: var(--text-main);
        margin-top: 4px;
    }

    .filter-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.02);
    }

    .user-avatar {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        background-color: var(--primary-light);
        color: var(--primary);
        font-weight: 700;
    }

    .btn-action-sm {
        padding: 6px 12px;
        font-size: 0.8rem;
        border-radius: 8px;
        font-weight: 600;
    }
</style>
@endsection

@section('content')
<div class="page-header">
    <div class="header-title">
        <h2>👥 User Accounts Administration</h2>
        <p>Manage Staff Credentials and Security Controls — Document Chapter 3, Security Plan (Page 32) & Table 9</p>
    </div>

    <div>
        <a href="{{ route('users.create') }}" class="btn-primary">
            ➕ Register New Staff Account
        </a>
    </div>
</div>

<!-- Account Statistics Cards -->
<div class="stats-bar">
    <div class="stat-pill" style="border-left-color: #3b82f6;">
        <div class="stat-pill-label">Total System Accounts</div>
        <div class="stat-pill-val">{{ $stats['total'] }}</div>
    </div>

    <div class="stat-pill" style="border-left-color: #10b981;">
        <div class="stat-pill-label">Active Staff Personnel</div>
        <div class="stat-pill-val" style="color: #059669;">{{ $stats['staff_count'] }}</div>
    </div>

    <div class="stat-pill" style="border-left-color: #059669;">
        <div class="stat-pill-label">Active Status Users</div>
        <div class="stat-pill-val" style="color: #10b981;">{{ $stats['active'] }}</div>
    </div>

    <div class="stat-pill" style="border-left-color: {{ $stats['inactive'] > 0 ? '#ef4444' : '#94a3b8' }};">
        <div class="stat-pill-label">Deactivated Accounts</div>
        <div class="stat-pill-val" style="color: {{ $stats['inactive'] > 0 ? '#dc2626' : 'var(--text-muted)' }};">
            {{ $stats['inactive'] }}
        </div>
    </div>
</div>

<!-- Search & Status Filter -->
<div class="filter-card">
    <form method="GET" action="{{ route('users.index') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
        <div style="flex: 1; min-width: 260px; position: relative;">
            <input 
                type="text" 
                name="search" 
                class="form-input" 
                placeholder="🔍 Search by staff name or email..." 
                value="{{ $search }}"
            >
        </div>

        <select name="status" class="form-select" style="width: auto; min-width: 160px;">
            <option value="">All Account Statuses</option>
            <option value="active" {{ $status === 'active' ? 'selected' : '' }}>● Active Only</option>
            <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>○ Deactivated Only</option>
        </select>

        <button type="submit" class="btn-primary" style="padding: 10px 20px;">
            Search
        </button>

        @if($search || $status)
            <a href="{{ route('users.index') }}" class="btn-secondary" style="padding: 10px 16px;">
                Reset
            </a>
        @endif
    </form>
</div>

<!-- Users Table Card -->
<div class="card" style="padding: 0; overflow: hidden;">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>User Profile</th>
                    <th>Email / Login ID</th>
                    <th>Role</th>
                    <th>Account Status</th>
                    <th>Created</th>
                    <th style="text-align: right;">Security Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div class="user-avatar" style="{{ $user->role === 'owner' ? 'background: #fee2e2; color: #b91c1c;' : '' }}">
                                    {{ $user->role === 'owner' ? '👑' : '📋' }}
                                </div>
                                <div>
                                    <div style="font-weight: 700; color: var(--text-main); font-size: 0.95rem; display: flex; align-items: center; gap: 6px;">
                                        <span>{{ $user->name }}</span>
                                        @if($user->id === Auth::id())
                                            <span style="font-size: 0.7rem; background-color: #fee2e2; color: var(--primary); padding: 2px 8px; border-radius: 12px; font-weight: 700;">You</span>
                                        @endif
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                        {{ ucfirst($user->role) }} Account
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <code style="font-size: 0.85rem; color: #334155; background: #f1f5f9; padding: 4px 8px; border-radius: 6px;">
                                {{ $user->email }}
                            </code>
                        </td>
                        <td>
                            <span class="role-badge badge-{{ $user->role }}">
                                {{ strtoupper($user->role) }}
                            </span>
                        </td>
                        <td>
                            @if($user->is_active)
                                <span class="badge badge-success">
                                    ● Active
                                </span>
                            @else
                                <span class="badge badge-danger">
                                    ○ Deactivated
                                </span>
                            @endif
                        </td>
                        <td style="font-size: 0.85rem; color: var(--text-muted);">
                            {{ $user->created_at->format('M d, Y') }}
                        </td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; gap: 8px; align-items: center;">
                                <!-- Reset Password Button (Page 33) -->
                                <button 
                                    type="button" 
                                    class="btn-secondary btn-action-sm" 
                                    onclick="openResetModal({{ $user->id }}, '{{ addslashes($user->name) }}')"
                                >
                                    🔑 Reset Password
                                </button>

                                <!-- Toggle Status Button (Page 32) -->
                                @if($user->id !== Auth::id())
                                    <form method="POST" action="{{ route('users.toggleStatus', $user) }}" onsubmit="return confirm('Are you sure you want to {{ $user->is_active ? 'deactivate' : 'reactivate' }} this account?');" style="display: inline;">
                                        @csrf
                                        @method('PATCH')
                                        <button 
                                            type="submit" 
                                            class="{{ $user->is_active ? 'btn-danger' : 'btn-success' }} btn-action-sm"
                                        >
                                            {{ $user->is_active ? 'Deactivate' : 'Reactivate' }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 3rem 0;">
                            No user accounts found matching your query.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
    <div style="padding: 1.25rem; border-top: 1px solid var(--border);">
        {{ $users->links() }}
    </div>
    @endif
</div>

<!-- Reset Password Modal -->
<div id="resetModal" class="modal-overlay">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
            <div>
                <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.25rem;">
                    🔑 Reset Staff Password
                </h3>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">
                    SweetNest Document Chapter 3, Security Plan (Page 33)
                </p>
            </div>
            <button type="button" onclick="closeResetModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>

        <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 10px; padding: 10px 14px; margin-bottom: 1.25rem;">
            <span style="font-size: 0.8rem; color: var(--text-muted);">Resetting account for:</span>
            <div style="font-weight: 700; color: var(--text-main); font-size: 0.95rem;" id="resetUserName"></div>
        </div>

        <form id="resetPasswordForm" method="POST" action="">
            @csrf
            @method('PATCH')

            <div class="form-group">
                <label class="form-label" for="new_password">New Temporary Password *</label>
                <input type="password" id="new_password" name="new_password" class="form-input" required minlength="6" placeholder="At least 6 characters">
            </div>

            <div class="form-group">
                <label class="form-label" for="new_password_confirmation">Confirm Password *</label>
                <input type="password" id="new_password_confirmation" name="new_password_confirmation" class="form-input" required minlength="6" placeholder="Repeat password">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 1.75rem;">
                <button type="button" class="btn-secondary" onclick="closeResetModal()">Cancel</button>
                <button type="submit" class="btn-primary">Update Password</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openResetModal(userId, userName) {
        document.getElementById('resetUserName').textContent = userName;
        document.getElementById('resetPasswordForm').action = `/users/${userId}/reset-password`;
        document.getElementById('resetModal').style.display = 'flex';
    }

    function closeResetModal() {
        document.getElementById('resetModal').style.display = 'none';
        document.getElementById('new_password').value = '';
        document.getElementById('new_password_confirmation').value = '';
    }

    // Close on click outside modal
    window.onclick = function(event) {
        const modal = document.getElementById('resetModal');
        if (event.target === modal) {
            closeResetModal();
        }
    }
</script>
@endsection
