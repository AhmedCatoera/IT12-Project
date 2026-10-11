@props(['status', 'type' => 'order'])
@php
    $map = [
        'order' => [
            'pending' => ['b-warning', 'Awaiting payment'],
            'confirmed' => ['b-info', 'Confirmed'],
            'in_production' => ['b-violet', 'In production'],
            'ready_for_release' => ['b-success', 'Ready for release'],
            'out_for_delivery' => ['b-orange', 'Out for delivery'],
            'completed' => ['b-neutral', 'Completed'],
            'cancelled' => ['b-danger', 'Cancelled'],
        ],
        'job' => [
            'pending' => ['b-neutral', 'Pending'],
            'in_progress' => ['b-violet', 'In progress'],
            'completed' => ['b-success', 'Completed'],
            'cancelled' => ['b-danger', 'Cancelled'],
        ],
        'payment' => [
            'unpaid' => ['b-danger', 'Unpaid'],
            'partially_paid' => ['b-warning', 'Partially paid'],
            'fully_paid' => ['b-success', 'Fully paid'],
        ],
        'active' => [
            '1' => ['b-success', 'Active'],
            '0' => ['b-neutral', 'Inactive'],
        ],
        'stock' => [
            'low' => ['b-danger', 'Low stock'],
            'ok' => ['b-success', 'In stock'],
        ],
    ];
    $key = is_bool($status) ? ($status ? '1' : '0') : (string) $status;
    [$class, $label] = $map[$type][$key] ?? ['b-neutral', ucfirst(str_replace('_', ' ', $key))];
@endphp
<span {{ $attributes->merge(['class' => "badge {$class}"]) }}>{{ $label }}</span>
