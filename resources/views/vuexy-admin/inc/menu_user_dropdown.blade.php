<li class="nav-item navbar-dropdown dropdown-user dropdown">
  <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
    <div class="avatar avatar-online">
      <img src="{{ backpack_avatar_url(backpack_auth()->user()) }}" alt="{{ backpack_user()->name }}"
        class="rounded-circle" onerror="this.style.display='none'" />
    </div>
  </a>
  <ul class="dropdown-menu dropdown-menu-end">
    <li>
      <a class="dropdown-item mt-0" href="{{ config('backpack.base.setup_my_account_routes') ? route('backpack.account.info') : backpack_url('dashboard') }}">
        <div class="d-flex align-items-center">
          <div class="flex-shrink-0 me-2">
            <div class="avatar avatar-online">
              <img src="{{ backpack_avatar_url(backpack_auth()->user()) }}" alt="{{ backpack_user()->name }}"
                class="rounded-circle" onerror="this.style.display='none'" />
            </div>
          </div>
          <div class="flex-grow-1">
            <h6 class="mb-0">{{ backpack_user()->name }}</h6>
            <small class="text-body-secondary">{{ trans('backpack::crud.admin') }}</small>
          </div>
        </div>
      </a>
    </li>
    <li>
      <div class="dropdown-divider my-1 mx-n2"></div>
    </li>
    @if(config('backpack.base.setup_my_account_routes'))
      <li>
        <a class="dropdown-item" href="{{ route('backpack.account.info') }}">
          <i class="icon-base ti tabler-user me-3 icon-md"></i><span class="align-middle">{{ trans('backpack::base.my_account') }}</span>
        </a>
      </li>
    @endif
    <li>
      <a class="dropdown-item" href="{{ backpack_url('logout') }}">
        <i class="icon-base ti tabler-logout-2 me-3 icon-md"></i><span class="align-middle">{{ trans('backpack::base.logout') }}</span>
      </a>
    </li>
  </ul>
</li>
