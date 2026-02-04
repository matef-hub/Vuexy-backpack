@extends(backpack_view('blank'))

@php
  $defaultBreadcrumbs = [
    trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
    $crud->entity_name_plural => url($crud->route),
    trans('backpack::crud.list') => false,
  ];

  $breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;
@endphp

@section('content')
  <div class="row" bp-section="crud-operation-list">
    <div class="{{ $crud->getListContentClass() }}">
      <div class="card mb-4">
        <div class="card-body">
          <x-backpack::datatable :controller="$controller" :crud="$crud" :modifiesUrl="true" />
        </div>
      </div>
    </div>
  </div>
@endsection
