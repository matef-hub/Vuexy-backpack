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

            <h4 class="mb-1">{{ trans('backpack::base.register') }}</h4>
            <p class="mb-6">Create your account</p>

            @include(backpack_view('auth.register.inc.form'))
          </div>
        </div>

        <p class="text-center mt-6">
          <span>Already have an account?</span>
          <a href="{{ route('backpack.auth.login') }}">{{ trans('backpack::base.login') }}</a>
        </p>
      </div>
    </div>
  </div>
@endsection
