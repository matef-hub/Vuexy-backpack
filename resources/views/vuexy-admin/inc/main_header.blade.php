@php
  $containerNav = ($configData['contentLayout'] ?? 'compact') === 'compact' ? 'container-xxl' : 'container-fluid';
@endphp

<nav class="layout-navbar {{ $containerNav }} navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
  <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0">
    <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
      <i class="icon-base ti tabler-menu-2 icon-md"></i>
    </a>
  </div>

  @include(backpack_view('inc.menu'))
</nav>
