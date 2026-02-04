@php
  use App\Helpers\Helpers;

  $configData = Helper::appClasses();
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
  data-template="blank-layout"
  data-bs-theme="{{ $configData['theme'] }}"
  @if ($semiDarkEnabled) data-semidark-menu="true" @endif>
<head>
  @include(backpack_view('inc.head'))
</head>
<body>
  @yield('content')

  @include(backpack_view('inc.bottom'))
</body>
</html>
