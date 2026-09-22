@extends('layouts.app')

@section('title', 'Physical Audit Report - ' . ucfirst($audit->audit_shift) . ' ' . $audit->audit_date->format('M d, Y'))

@section('styles')
<style>
    .audit-container {
        max-width: 950px;
        margin: 0 auto;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.5rem;
        box-shadow: var(--shadow);
        margin-bottom: 1.5rem;
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

    .btn-print {
        background-color: #f1f5f9;
        color: #334155;
        border: 1px solid var(--border);
        padding: 8px 14px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.82rem;
        cursor: pointer;
    }

    .grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    .info-label {
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 2px;
    }

    .info-value {
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--text-main);
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
        padding: 10px 14px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .badge-shift {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        display: inline-block;
    }
    .shift-morning { background-color: #fef3c7; color: #b45309; }
    .shift-afternoon { background-color: #dbeafe; color: #1d4ed8; }

    @media print {
        .navbar, .action-bar, .footer {
            display: none !important;
        }
        body {
            background-color: white !important;
        }
        .card {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
        }
    }
</style>
@endsection

@section('content')
<div class="audit-container">
    <div class="page-header action-bar">
        <div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <h2 style="font-size: 1.5rem; font-weight: 800;">
                    Physical Inventory Count Report #AUD-{{ str_pad($audit->id, 4, '0', STR_PAD_LEFT) }}
                </h2>
                <span class="badge-shift shift-{{ $audit->audit_shift }}">
                    {{ $audit->audit_shift === 'morning' ? '🌅 Morning Shift' : '🌆 Afternoon Shift' }}
                </span>
            </div>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 4px;">
                Conducted on {{ $audit->audit_date->format('F d, Y') }} by <strong>{{ $audit->conductedBy?->name ?: 'Staff' }}</strong>
            </p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('audits.index') }}" class="btn-secondary">← Back to Audits</a>
            <button onclick="window.print()" class="btn-print">🖨️ Print Audit Report</button>
        </div>
    </div>

    <!-- Overview Card -->
    <div class="card">
        <div class="grid-2">
            <div>
                <div style="margin-bottom: 0.75rem;">
                    <div class="info-label">Audit Shift</div>
                    <div class="info-value">
                        {{ ucfirst($audit->audit_shift) }} ({{ $audit->audit_shift === 'morning' ? 'Opening Shift Count' : 'Closing Shift Count' }})
                    </div>
                </div>
                <div style="margin-bottom: 0.75rem;">
                    <div class="info-label">Conducted By</div>
                    <div class="info-value">
                        {{ $audit->conductedBy?->name ?: 'Staff' }} ({{ $audit->conductedBy?->email }})
                    </div>
                </div>
            </div>

            <div>
                <div style="margin-bottom: 0.75rem;">
                    <div class="info-label">Items Audited</div>
                    <div class="info-value">{{ $audit->items->count() }} ingredients</div>
                </div>
                <div style="margin-bottom: 0.75rem;">
                    <div class="info-label">Discrepancy Status</div>
                    <div class="info-value">
                        @if($discrepancyCount > 0)
                            <span style="color: #e11d48; font-weight: 800;">
                                ⚠️ {{ $discrepancyCount }} discrepancies detected
                            </span>
                        @else
                            <span style="color: #059669; font-weight: 800;">
                                ✓ 100% Balanced - No Discrepancies
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if($audit->notes)
        <div style="margin-top: 1rem; padding: 0.85rem; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px;">
            <div class="info-label" style="color: #b45309;">Auditor Findings / Remarks</div>
            <div style="font-size: 0.88rem; color: #92400e; margin-top: 2px;">{{ $audit->notes }}</div>
        </div>
        @endif
    </div>

    <!-- Audited Items Breakdown -->
    <div class="card">
        <h3 style="font-size: 1.15rem; font-weight: 800; margin-bottom: 1rem;">
            Ingredient Variance Breakdown
        </h3>

        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ingredient</th>
                        <th>Unit</th>
                        <th style="text-align: right;">System Stock (Expected)</th>
                        <th style="text-align: right;">Physical Count (Actual)</th>
                        <th style="text-align: right;">Variance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($audit->items as $item)
                    <tr style="{{ (float)$item->variance != 0 ? 'background-color: #fffaf0;' : '' }}">
                        <td>
                            <strong>{{ $item->ingredient->name }}</strong>
                        </td>
                        <td>
                            <span style="color: var(--text-muted);">{{ $item->ingredient->unit }}</span>
                        </td>
                        <td style="text-align: right; font-weight: 600;">
                            {{ number_format($item->system_stock, 2) }}
                        </td>
                        <td style="text-align: right; font-weight: 700;">
                            {{ number_format($item->physical_stock, 2) }}
                        </td>
                        <td style="text-align: right; font-weight: 800;">
                            @if((float)$item->variance == 0)
                                <span style="color: #059669;">0.00 (Match)</span>
                            @elseif((float)$item->variance < 0)
                                <span style="color: #e11d48; background: #fee2e2; padding: 2px 8px; border-radius: 4px;">
                                    {{ number_format($item->variance, 2) }} (Shortage)
                                </span>
                            @else
                                <span style="color: #2563eb; background: #dbeafe; padding: 2px 8px; border-radius: 4px;">
                                    +{{ number_format($item->variance, 2) }} (Surplus)
                                </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
