@extends(backpack_view('blank'))

@php
  $defaultBreadcrumbs = [
    trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
    $crud->entity_name_plural => url($crud->route),
    trans('backpack::crud.preview') => false,
  ];

  $breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;
@endphp

@section('header')
  <section class="d-flex flex-wrap align-items-center gap-2 mb-4" bp-section="page-header">
    <div>
      <h1 class="h4 mb-0" bp-section="page-heading">{!! $crud->getHeading() ?? $crud->entity_name_plural !!}</h1>
      <p class="mb-0 text-body-secondary" bp-section="page-subheading">{!! $crud->getSubheading() ?? mb_ucfirst(trans('backpack::crud.preview')).' '.$crud->entity_name !!}</p>
    </div>
    <div class="ms-auto d-flex align-items-center gap-2">
      @if ($crud->hasAccess('list'))
        <a href="{{ url($crud->route) }}" class="btn btn-sm btn-text-secondary" bp-section="page-subheading-back-button">
          <i class="icon-base ti tabler-arrow-left"></i>
          {{ trans('backpack::crud.back_to_all') }} {{ $crud->entity_name_plural }}
        </a>
      @endif
      <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
        <i class="icon-base ti tabler-printer"></i>
      </button>
    </div>
  </section>
@endsection

@section('content')
  <div class="row" bp-section="crud-operation-show">
    <div class="{{ $crud->getShowContentClass() }}">
      <div class="card mb-4">
        <div class="card-body">
          @if ($crud->model->translationEnabled())
            <div class="d-flex justify-content-end mb-3" bp-section="show-operation-language-dropdown">
              <div class="btn-group">
                <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  {{ trans('backpack::crud.language') }}: {{ $crud->model->getAvailableLocales()[request()->input('_locale') ? request()->input('_locale') : App::getLocale()] }}
                </button>
                <ul class="dropdown-menu">
                  @foreach ($crud->model->getAvailableLocales() as $key => $locale)
                    <li><a class="dropdown-item" href="{{ url($crud->route.'/'.$entry->getKey().'/show') }}?_locale={{ $key }}">{{ $locale }}</a></li>
                  @endforeach
                </ul>
              </div>
            </div>
          @endif

          @if($crud->tabsEnabled() && count($crud->getUniqueTabNames('columns')))
            @include('crud::inc.show_tabbed_table')
          @else
            <div class="card mb-0">
              @include('crud::inc.show_table', ['columns' => $crud->columns()])
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
@endsection
