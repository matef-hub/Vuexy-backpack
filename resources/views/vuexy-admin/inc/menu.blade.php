<div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">
  @if (backpack_auth()->check())
    <div class="navbar-nav align-items-center">
      @include(backpack_view('inc.topbar_left_content'))
    </div>
  @endif

  <ul class="navbar-nav flex-row align-items-center ms-auto">
    @if (backpack_auth()->guest())
      <li class="nav-item">
        <a class="nav-link" href="{{ route('backpack.auth.login') }}">{{ trans('backpack::base.login') }}</a>
      </li>
      @if (config('backpack.base.registration_open'))
        <li class="nav-item">
          <a class="nav-link" href="{{ route('backpack.auth.register') }}">{{ trans('backpack::base.register') }}</a>
        </li>
      @endif
    @else
      @include(backpack_view('inc.topbar_right_content'))
      @include(backpack_view('inc.menu_user_dropdown'))
    @endif
  </ul>
</div>
