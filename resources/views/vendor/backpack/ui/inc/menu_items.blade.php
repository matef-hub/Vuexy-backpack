{{-- Vuexy-styled menu items for Backpack v7 --}}
@php
  $adminPrefix = trim(config('backpack.base.route_prefix', 'admin'), '/');
  $isDashboard = request()->is($adminPrefix) || request()->is($adminPrefix . '/dashboard*');
@endphp

<li class="menu-item {{ $isDashboard ? 'active' : '' }}">
  <a class="menu-link" href="{{ backpack_url('dashboard') }}">
    <i class="menu-icon icon-base ti tabler-smart-home"></i>
    <div>{{ trans('backpack::base.dashboard') }}</div>
  </a>
</li>

@php
  $isCompanyFiles = request()->is($adminPrefix . '/company-file*');
@endphp
<li class="menu-item {{ $isCompanyFiles ? 'active' : '' }}">
  <a class="menu-link" href="{{ backpack_url('company-file') }}">
    <i class="menu-icon la la-folder"></i>
    <div>ملفات الشركة</div>
  </a>
</li>
