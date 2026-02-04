@extends(backpack_view('blank'))

@php
  $defaultBreadcrumbs = [
    trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
    $crud->entity_name_plural => url($crud->route),
    Str::of($crud->getCurrentOperation())->headline()->toString() => false,
  ];

  $breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;
@endphp

@section('header')
  <section class="d-flex flex-wrap align-items-center gap-2 mb-4" bp-section="page-header">
    <h1 class="h4 mb-0" bp-section="page-heading">{!! $crud->getHeading() ?? $crud->entity_name_plural !!}</h1>
    <p class="mb-0 text-body-secondary" bp-section="page-subheading">{!! $crud->getSubheading() ?? Str::of($operation)->headline() !!}.</p>
    @if ($crud->hasAccess('list'))
      <a href="{{ url($crud->route) }}" class="btn btn-sm btn-text-secondary ms-auto" bp-section="page-subheading-back-button">
        <i class="icon-base ti tabler-arrow-left"></i>
        {{ trans('backpack::crud.back_to_all') }} {{ $crud->entity_name_plural }}
      </a>
    @endif
  </section>
@endsection

@section('content')
  <div class="row" bp-section="crud-operation-{{ Str::of($operation)->kebab() }}">
    <div class="{{ $crud->get($operation.'.contentClass') }}">
      @include('crud::inc.grouped_errors')

      <div class="card mb-4">
        <div class="card-body">
          <form
            method="{{ $formMethod ?? 'post' }}"
            action="{{ $formAction ?? url()->current() }}"
            @if ($crud->hasUploadFields())
              enctype="multipart/form-data"
            @endif
          >
            {!! csrf_field() !!}
            {!! method_field($formMethod ?? 'post') !!}

            @if(view()->exists('vendor.backpack.crud.form_content'))
              @include('vendor.backpack.crud.form_content', [ 'fields' => $crud->fields(), 'action' => $operation ])
            @else
              @include('crud::form_content', [ 'fields' => $crud->fields(), 'action' => $operation ])
            @endif
            <div class="d-none" id="parentLoadedAssets">{{ json_encode(Basset::loaded()) }}</div>
            @include('crud::inc.form_save_buttons')
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
