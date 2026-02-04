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

            <p class="mb-4">{{ trans('backpack::base.verify_email.email_verification_required') }}</p>

            @if (session('status') == 'verification-link-sent')
              <div class="alert alert-success">
                {{ trans('backpack::base.verify_email.verification_link_sent') }}
              </div>
            @endif

            <div class="d-flex gap-2 mt-4">
              <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn btn-primary">{{ trans('backpack::base.verify_email.resend_verification_link') }}</button>
              </form>
              <form method="POST" action="{{ backpack_url('logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline-secondary">{{ trans('backpack::base.logout') }}</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
