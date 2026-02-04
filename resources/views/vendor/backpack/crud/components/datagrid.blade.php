<div class="row g-4">
  @foreach($columns as $column)
    @php
      $size = (int) ($column['size'] ?? 3);
      $size = $size < 1 ? 1 : ($size > 12 ? 12 : $size);
    @endphp
    <div class="col-12 col-lg-{{ $size }}">
      <div class="card shadow-none border">
        <div class="card-body">
          <div class="text-uppercase text-muted small fw-medium mb-1">{!! $column['label'] !!}</div>
          <div class="text-heading">
            @includeFirst(\Backpack\CRUD\ViewNamespaces::getViewPathsWithFallbackFor('columns', $column['type'], 'crud::columns.text'))
          </div>
        </div>
      </div>
    </div>
  @endforeach

  @if($displayButtons && $crud && $crud->buttons()->where('stack', 'line')->count())
    <div class="col-12">
      <div class="card shadow-none border">
        <div class="card-body">
          <div class="text-uppercase text-muted small fw-medium mb-1">{{ trans('backpack::crud.actions') }}</div>
          <div>
            @include('crud::inc.button_stack', ['stack' => 'line'])
          </div>
        </div>
      </div>
    </div>
  @endif
</div>
