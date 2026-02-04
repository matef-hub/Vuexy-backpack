@if (backpack_theme_config('breadcrumbs') && isset($breadcrumbs) && is_array($breadcrumbs) && count($breadcrumbs))
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb breadcrumb-style1 mb-4">
      @foreach ($breadcrumbs as $label => $link)
        @if ($link)
          <li class="breadcrumb-item text-capitalize"><a href="{{ $link }}">{{ $label }}</a></li>
        @else
          <li class="breadcrumb-item text-capitalize active" aria-current="page">{{ $label }}</li>
        @endif
      @endforeach
    </ol>
  </nav>
@endif
