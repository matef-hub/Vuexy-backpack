@props([
    'label',
    'icon' => 'ti ti-circle',
    'valueKey',
    'badgeClass' => 'bg-label-secondary',
    'col' => 'col-12 col-md-6',
    'valueClass' => '',
    'valueStyle' => '',
    'bodyAlign' => 'align-items-center',
])

<div class="{{ $col }}">
    <div class="card border shadow-none h-100">
        <div class="card-body py-2 px-3 d-flex justify-content-between {{ $bodyAlign }}">
            <span class="d-flex align-items-center gap-2">
                <i class="{{ $icon }}"></i>
                <small class="text-muted fw-medium">{{ $label }}</small>
            </span>
            <span class="badge {{ $badgeClass }} {{ $valueClass }}" @if ($valueStyle) style="{{ $valueStyle }}" @endif
                data-review="{{ $valueKey }}">-</span>
        </div>
    </div>
</div>
