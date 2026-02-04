<div class="table-responsive">
  <table class="table table-borderless table-sm mb-0 align-middle">
    <tbody>
      @foreach($columns as $column)
        <tr>
          <td class="text-muted fw-medium" style="width: 30%;">
            {!! $column['label'] !!}@if(!empty($column['label'])):@endif
          </td>
          <td class="text-heading">
            @includeFirst(\Backpack\CRUD\ViewNamespaces::getViewPathsWithFallbackFor('columns', $column['type'], 'crud::columns.text'))
          </td>
        </tr>
      @endforeach

      @if($displayButtons && $crud && $crud->buttons()->where('stack', 'line')->count())
        <tr>
          <td class="text-muted fw-medium">{{ trans('backpack::crud.actions') }}</td>
          <td>
            @include('crud::inc.button_stack', ['stack' => 'line'])
          </td>
        </tr>
      @endif
    </tbody>
  </table>
</div>
