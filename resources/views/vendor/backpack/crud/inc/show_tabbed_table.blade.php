@php
  $horizontalTabs = $crud->getTabsType() == 'horizontal';
  $columnsWithoutTab = $crud->getElementsWithoutATab($crud->columns());
  $columnsWithTabs = $crud->getUniqueTabNames('columns');
@endphp

@if($columnsWithoutTab->filter(function ($value, $key) { return $value['type'] != 'hidden'; })->count())
  <div class="card mb-4">
    <div class="card-body">
      @include('crud::inc.show_table', ['columns' => $columnsWithoutTab, 'displayActionsColumn' => false])
    </div>
  </div>
@endif

<div class="{{ $horizontalTabs ? '' : 'row g-4' }} mb-4">
  <div class="{{ $horizontalTabs ? '' : 'col-lg-3' }}">
    <ul class="nav {{ $horizontalTabs ? 'nav-tabs' : 'nav-pills flex-column' }}" role="tablist">
      @foreach ($columnsWithTabs as $k => $tabLabel)
        @php
          $tabSlug = Str::slug($tabLabel);
          if(empty($tabSlug)) {
            $tabSlug = $k;
          }
        @endphp
        <li role="presentation" class="nav-item">
          <a href="#tab_{{ $tabSlug }}"
            aria-controls="tab_{{ $tabSlug }}"
            role="tab"
            data-toggle="tab"
            tab_name="{{ $tabSlug }}"
            data-name="{{ $tabSlug }}"
            data-bs-toggle="tab"
            class="nav-link {{ $k === 0 ? 'active' : '' }}">
            {{ $tabLabel }}
          </a>
        </li>
      @endforeach
    </ul>
  </div>

  <div class="{{ $horizontalTabs ? '' : 'col-lg-9' }}">
    <div class="tab-content">
      @foreach ($columnsWithTabs as $k => $tabLabel)
        @php
          $tabSlug = Str::slug($tabLabel);
          if(empty($tabSlug)) {
            $tabSlug = $k;
          }
        @endphp
        <div role="tabpanel" class="tab-pane fade {{ $k === 0 ? 'show active' : '' }}" id="tab_{{ $tabSlug }}">
          <div class="card">
            <div class="card-body">
              @include('crud::inc.show_table', ['columns' => $crud->getTabItems($tabLabel, 'columns'), 'displayActionsColumn' => false])
            </div>
          </div>
        </div>
      @endforeach
    </div>

    @if($crud->buttons()->where('stack', 'line')->count())
      <div class="mt-4">
        <div class="text-muted small mb-2">{{ trans('backpack::crud.actions') }}</div>
        <div>@include('crud::inc.button_stack', ['stack' => 'line'])</div>
      </div>
    @endif
  </div>
</div>
