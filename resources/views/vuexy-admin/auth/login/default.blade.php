@extends(backpack_view('layouts.auth'))

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('content')
  <div class="container-xxl">
    <div class="authentication-wrapper authentication-basic container-p-y">
      <div class="authentication-inner py-6">
        <div class="card">
          <div class="card-body">
            <div class="app-brand justify-content-center mb-6">
              <a href="{{ backpack_url('dashboard') }}" class="app-brand-link">
                <span class="app-brand-logo demo">@include('_partials.macros')</span>
                <span class="app-brand-text demo text-heading fw-bold">{!! backpack_theme_config('project_logo') !!}</span>
              </a>
            </div>

            <h4 class="mb-1">{{ trans('backpack::base.login') }}</h4>
            <p class="mb-6">{{ trans('backpack::base.login') }}</p>

            @include(backpack_view('auth.login.inc.form'))
          </div>
        </div>

        @if (config('backpack.base.registration_open'))
          <p class="text-center mt-6">
            <span>New here?</span>
            <a href="{{ route('backpack.auth.register') }}">{{ trans('backpack::base.register') }}</a>
          </p>
        @endif
      </div>
    </div>
  </div>
@endsection
