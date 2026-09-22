@extends('layouts.app')

@section('title', 'Physical Inventory Audits')

@section('styles')
<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .page-title h2 {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--text-main);
    }

    .card {
        background-color: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.5rem;
        box-shadow: var(--shadow);
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--primary), var(--primary-hover));
        color: white;
        text-decoration: none;
        padding: 9px 18px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.88rem;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-secondary {
        background-color: white;
        border: 1px solid var(--border);
        color: var(--text-main);
        text-decoration: none;
        padding: 8px 14px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.82rem;
    }

    .table-responsive {
        overflow-x: auto;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.88rem;
    }

    .table th {
        text-align: left;
        padding: 10px 14px;
        color: var(--text-muted);
        font-weight: 700;
        border-bottom: 1px solid var(--border);
        background-color: #f8fafc;
    }

    .table td {
        padding: 12px 14px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .badge-shift {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        display: inline-block;
    }
    .shift-morning { background-color: #fef3c7; color: #b45309; }
    .shift-afternoon { background-color: #dbeafe; color: #1d4ed8; }
</style>
@endsection

@section('content')
<div class="page-header">
    <div class="page-title">
        <h2>Physical Count Audits</h2>
        <p>Twice-daily shift counts (Morning opening & Afternoon closing) for discrepancy tracking</p>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="{{ route('inventory.index') }}" class="btn-secondary">
            ← Back to Inventory
        </a>
        <a href="{{ route('audits.create') }}" class="btn-primary">
            ⚖️ + Conduct Shift Audit
        </a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Audit Date</th>
                    <th>Shift</th>
                    <th>Conducted By</th>
                    <th>Items Audited</th>
                    <th>Discrepancies</th>
                    <th>Findings / Notes</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($audits as $audit)
                @php
                    $discrepancies = $audit->items->filter(fn($i) => (float)$i->variance != 0)->count();
                @endphp
                <tr>
                    <td>
                        <strong>{{ $audit->audit_date->format('M d, Y') }}</strong>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $audit->created_at->format('h:i A') }}</div>
                    </td>
                    <td>
                        <span class="badge-shift shift-{{ $audit->audit_shift }}">
                            {{ $audit->audit_shift === 'morning' ? '🌅 Morning Shift' : '🌆 Afternoon Shift' }}
                        </span>
                    </td>
                    <td>
                        <strong>{{ $audit->conductedBy?->name ?: 'Staff' }}</strong>
                    </td>
                    <td>
                        <span style="font-weight: 600;">{{ $audit->items->count() }} ingredients</span>
                    </td>
                    <td>
                        @if($discrepancies > 0)
                            <span style="color: #e11d48; font-weight: 800; font-size: 0.88rem;">
                                ⚠️ {{ $discrepancies }} with variance
                            </span>
                        @else
                            <span style="color: #059669; font-weight: 700; font-size: 0.85rem;">
                                ✓ 100% Match
                            </span>
                        @endif
                    </td>
                    <td>
                        <span style="font-size: 0.82rem; color: var(--text-muted);">
                            {{ Str::limit($audit->notes ?: 'No remarks', 45) }}
                        </span>
                    </td>
                    <td style="text-align: right;">
                        <a href="{{ route('audits.show', $audit) }}" style="font-weight: 700; color: var(--primary); text-decoration: none;">
                            View Report →
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">
                        No physical audits logged yet. Conduct your first morning or afternoon shift audit above!
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1.5rem;">
        {{ $audits->links() }}
    </div>
</div>
@endsection
