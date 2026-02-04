@extends(backpack_view('blank'))

@php
  $defaultBreadcrumbs = [
    trans('backpack::crud.admin') => backpack_url('dashboard'),
    $crud->entity_name_plural => url($crud->route),
    trans('backpack::crud.edit') => false,
  ];

  $breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;
@endphp

@section('header')
  <section class="d-flex flex-wrap align-items-center gap-2 mb-4" bp-section="page-header">
    <h1 class="h4 mb-0" bp-section="page-heading">{!! $crud->getHeading() ?? $crud->entity_name_plural !!}</h1>
    <p class="mb-0 text-body-secondary" bp-section="page-subheading">{!! $crud->getSubheading() ?? trans('backpack::crud.edit').' '.$crud->entity_name !!}.</p>
    @if ($crud->hasAccess('list'))
      <a href="{{ url($crud->route) }}" class="btn btn-sm btn-text-secondary ms-auto" bp-section="page-subheading-back-button">
        <i class="icon-base ti tabler-arrow-left"></i>
        {{ trans('backpack::crud.back_to_all') }} {{ $crud->entity_name_plural }}
      </a>
    @endif
  </section>
@endsection

@section('content')
  <div class="row" bp-section="crud-operation-update">
    <div class="{{ $crud->getEditContentClass() }}">
      @include('crud::inc.grouped_errors', ['id' => $id ?? 'crudForm'])

      <div class="card mb-4">
        <div class="card-body">
          <form method="post"
            action="{{ url($crud->route.'/'.$entry->getKey()) }}"
            id="{{ $id ?? 'crudForm' }}"
            @if ($crud->hasUploadFields('update', $entry->getKey()))
              enctype="multipart/form-data"
            @endif
          >
            {!! csrf_field() !!}
            {!! method_field('PUT') !!}

            @includeWhen($crud->model->translationEnabled(), 'crud::inc.edit_translation_notice')

            @if(view()->exists('vendor.backpack.crud.form_content'))
              @include('vendor.backpack.crud.form_content', ['fields' => $crud->fields(), 'action' => 'edit', 'id' => $id ?? 'crudForm'])
            @else
              @include('crud::form_content', ['fields' => $crud->fields(), 'action' => 'edit', 'id' => $id ?? 'crudForm'])
            @endif
            <div class="d-none" id="parentLoadedAssets">{{ json_encode(Basset::loaded()) }}</div>
            @include('crud::inc.form_save_buttons')
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
