@extends('layouts.app')

@section('title', 'Conduct Physical Inventory Audit')

@section('styles')
<style>
    .audit-container {
        max-width: 950px;
        margin: 0 auto;
    }

    .card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 2rem;
        box-shadow: var(--shadow);
        margin-bottom: 1.5rem;
    }

    .form-header {
        margin-bottom: 1.5rem;
        border-bottom: 1px solid var(--border);
        padding-bottom: 1rem;
    }

    .form-header h2 {
        font-size: 1.4rem;
        font-weight: 800;
        color: var(--text-main);
    }

    .grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    .form-group {
        margin-bottom: 1.25rem;
    }

    .form-label {
        display: block;
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .form-input, .form-select, .form-textarea {
        width: 100%;
        padding: 10px 14px;
        border-radius: 8px;
        border: 1px solid var(--border);
        font-family: inherit;
        font-size: 0.9rem;
        color: var(--text-main);
        background-color: white;
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

    .count-input {
        width: 120px;
        padding: 6px 10px;
        border: 1px solid var(--border);
        border-radius: 6px;
        font-weight: 700;
        text-align: right;
    }

    .variance-pill {
        font-size: 0.8rem;
        font-weight: 800;
        padding: 2px 8px;
        border-radius: 4px;
        display: inline-block;
    }
    .var-match { color: #059669; }
    .var-shortage { color: #e11d48; background: #fee2e2; }
    .var-surplus { color: #2563eb; background: #dbeafe; }

    .sync-box {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 8px;
        padding: 1rem 1.25rem;
        margin-top: 1.5rem;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 1.75rem;
        border-top: 1px solid var(--border);
        padding-top: 1.25rem;
    }

    .btn-submit {
        background: linear-gradient(135deg, var(--primary), var(--primary-hover));
        color: white;
        border: none;
        padding: 11px 24px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.92rem;
        cursor: pointer;
    }

    .btn-cancel {
        background: white;
        border: 1px solid var(--border);
        color: var(--text-muted);
        text-decoration: none;
        padding: 10px 18px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.92rem;
    }
</style>
@endsection

@section('content')
<div class="audit-container">
    <div class="card">
        <div class="form-header">
            <h2>Conduct Physical Inventory Count Audit</h2>
            <p>Perform the twice-daily physical audit to verify actual ingredients against system stock</p>
        </div>

        <form method="POST" action="{{ route('audits.store') }}">
            @csrf

            <!-- Audit Metadata Header -->
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label" for="audit_date">Audit Date *</label>
                    <input 
                        type="date" 
                        id="audit_date" 
                        name="audit_date" 
                        value="{{ old('audit_date', now()->toDateString()) }}" 
                        class="form-input" 
                        required
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="audit_shift">Audit Shift *</label>
                    <select id="audit_shift" name="audit_shift" class="form-select" required>
                        <option value="morning" {{ old('audit_shift', $defaultShift) === 'morning' ? 'selected' : '' }}>
                            🌅 Morning Shift (Opening Count)
                        </option>
                        <option value="afternoon" {{ old('audit_shift', $defaultShift) === 'afternoon' ? 'selected' : '' }}>
                            🌆 Afternoon Shift (Closing Count)
                        </option>
                    </select>
                </div>
            </div>

            <!-- Interactive Physical Count Table -->
            <div style="margin-top: 1rem; overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Ingredient</th>
                            <th>Unit</th>
                            <th style="text-align: right;">System Stock</th>
                            <th style="text-align: right;">Physical Count (Actual) *</th>
                            <th style="text-align: right;">Variance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ingredients as $index => $ing)
                        <tr>
                            <td>
                                <input type="hidden" name="items[{{ $index }}][ingredient_id]" value="{{ $ing->id }}">
                                <input type="hidden" id="sys_{{ $index }}" value="{{ (float)$ing->current_stock }}">
                                <strong>{{ $ing->name }}</strong>
                            </td>
                            <td>
                                <span style="color: var(--text-muted);">{{ $ing->unit }}</span>
                            </td>
                            <td style="text-align: right; font-weight: 700;">
                                {{ number_format($ing->current_stock, 2) }}
                            </td>
                            <td style="text-align: right;">
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    min="0" 
                                    name="items[{{ $index }}][physical_stock]" 
                                    id="phys_{{ $index }}" 
                                    value="{{ old("items.{$index}.physical_stock", (float)$ing->current_stock) }}" 
                                    class="count-input" 
                                    required 
                                    oninput="calculateVariance({{ $index }})"
                                >
                            </td>
                            <td style="text-align: right;">
                                <span id="var_{{ $index }}" class="variance-pill var-match">
                                    0.00
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Auto-reconcile Checkbox -->
            <div class="sync-box">
                <input 
                    type="checkbox" 
                    id="sync_system_stock" 
                    name="sync_system_stock" 
                    value="1" 
                    checked 
                    style="width: 20px; height: 20px; cursor: pointer;"
                >
                <label for="sync_system_stock" style="font-size: 0.88rem; font-weight: 700; color: #15803d; cursor: pointer;">
                    🔄 Automatically reconcile system stock to match physical counts (creates adjustment transactions for discrepancies)
                </label>
            </div>

            <!-- Findings / Notes -->
            <div class="form-group" style="margin-top: 1.25rem;">
                <label class="form-label" for="notes">Audit Findings & Remarks</label>
                <textarea 
                    id="notes" 
                    name="notes" 
                    rows="3" 
                    class="form-textarea" 
                    placeholder="e.g. Discrepancy observed in eggs count due to morning baking breakage; all flour sacks accounted for..."
                >{{ old('notes') }}</textarea>
            </div>

            <div class="form-actions">
                <a href="{{ route('audits.index') }}" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-submit">Save Shift Audit</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function calculateVariance(index) {
        const sysVal = parseFloat(document.getElementById('sys_' + index).value) || 0;
        const physInput = document.getElementById('phys_' + index);
        const physVal = parseFloat(physInput.value);
        const varSpan = document.getElementById('var_' + index);

        if (isNaN(physVal)) {
            varSpan.textContent = '—';
            varSpan.className = 'variance-pill';
            return;
        }

        const diff = Math.round((physVal - sysVal) * 100) / 100;

        if (diff === 0) {
            varSpan.textContent = '0.00 (Match)';
            varSpan.className = 'variance-pill var-match';
        } else if (diff < 0) {
            varSpan.textContent = diff.toFixed(2) + ' (Shortage)';
            varSpan.className = 'variance-pill var-shortage';
        } else {
            varSpan.textContent = '+' + diff.toFixed(2) + ' (Surplus)';
            varSpan.className = 'variance-pill var-surplus';
        }
    }

    // Initialize variances on page load
    document.addEventListener('DOMContentLoaded', function() {
        const total = {{ count($ingredients) }};
        for (let i = 0; i < total; i++) {
            calculateVariance(i);
        }
    });
</script>
@endsection
