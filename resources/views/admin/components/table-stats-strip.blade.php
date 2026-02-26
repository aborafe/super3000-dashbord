@php
  $stats = array_values($tableStats ?? []);
  $iconTones = ['primary', 'info', 'success', 'warning', 'danger', 'secondary'];
@endphp

@if(count($stats))
  <div class="card mb-4 table-stats-strip">
    <div class="card-body p-0">
      <div class="row g-0">
        @foreach($stats as $index => $stat)
          @php $tone = $stat['tone'] ?? $iconTones[$index % count($iconTones)]; @endphp
          <div class="col-12 col-sm-6 col-xl-3">
            <div class="d-flex align-items-center justify-content-between px-4 py-3 {{ $index < count($stats) - 1 ? 'border-end' : '' }}">
              <div>
                <h3 class="mb-1 fw-semibold">{{ $stat['value'] ?? 0 }}</h3>
                <p class="mb-0 text-muted">{{ $stat['label'] ?? '' }}</p>
              </div>
              <span class="badge icon-chip icon-chip-{{ $tone }}">
                <i class="icon-base bx {{ $stat['icon'] ?? 'bx-chart' }} icon-md"></i>
              </span>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
@endif

