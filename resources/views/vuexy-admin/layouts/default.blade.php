@php
  use App\Helpers\Helpers;

  $configData = Helper::appClasses();
  $container = ($configData['contentLayout'] ?? 'compact') === 'compact' ? 'container-xxl' : 'container-fluid';
  $contentLayout = ($configData['contentLayout'] ?? 'compact') === 'compact' ? 'layout-compact' : 'layout-wide';

  $navbarType = $configData['navbarType'] ?? '';
  $menuFixed = $configData['menuFixed'] ?? '';
  $menuCollapsed = $configData['menuCollapsed'] ?? '';
  $footerFixed = $configData['footerFixed'] ?? '';

  $skinName = $configData['skinName'] ?? 'default';
  $semiDarkEnabled = $configData['semiDark'] ?? false;
@endphp
<!DOCTYPE html>
<html lang="{{ session()->get('locale') ?? app()->getLocale() }}"
  class="{{ $navbarType }} {{ $contentLayout }} {{ $menuFixed }} {{ $menuCollapsed }} {{ $footerFixed }}"
  dir="{{ $configData['textDirection'] }}"
  data-skin="{{ $skinName }}"
  data-assets-path="{{ asset('/assets') . '/' }}"
  data-base-url="{{ url('/') }}"
  data-framework="laravel"
  data-template="vertical-menu-template"
  data-bs-theme="{{ $configData['theme'] }}"
  @if ($semiDarkEnabled) data-semidark-menu="true" @endif>
<head>
  @include(backpack_view('inc.head'))
</head>
<body>
  <div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
      @include(backpack_view('inc.sidebar'))

      <div class="layout-page">
        @include(backpack_view('inc.main_header'))

        <div class="content-wrapper">
          <div class="{{ $container }} flex-grow-1 container-p-y">
            @yield('before_breadcrumbs_widgets')
            @includeWhen(isset($breadcrumbs), backpack_view('inc.breadcrumbs'))
            @yield('after_breadcrumbs_widgets')

            @yield('header')

            @yield('before_content_widgets')
            @yield('content')
            @yield('after_content_widgets')
          </div>

          @include(backpack_view('inc.footer'))
          <div class="content-backdrop fade"></div>
        </div>
      </div>
    </div>

    <div class="layout-overlay layout-menu-toggle"></div>
    <div class="drag-target"></div>
  </div>

  @include(backpack_view('inc.bottom'))
</body>
</html>
