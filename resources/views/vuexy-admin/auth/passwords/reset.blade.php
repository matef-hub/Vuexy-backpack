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

            <h4 class="mb-1">{{ trans('backpack::base.reset_password') }}</h4>
            <p class="mb-6">{{ trans('backpack::base.choose_new_password') }}</p>

            @if (session('status'))
              <div class="alert alert-success mt-3">
                {{ session('status') }}
              </div>
            @else
              <form class="mb-4" role="form" method="POST" action="{{ route('backpack.auth.password.reset') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="mb-6 form-control-validation">
                  <label class="form-label" for="email">{{ trans('backpack::base.email_address') }}</label>
                  <input autofocus type="email" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" name="email" id="email" value="{{ old('email') }}">
                  @if ($errors->has('email'))
                    <div class="invalid-feedback">{{ $errors->first('email') }}</div>
                  @endif
                </div>

                <div class="mb-6 form-control-validation">
                  <label class="form-label" for="password">{{ trans('backpack::base.password') }}</label>
                  <input type="password" class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}" name="password" id="password" value="">
                  @if ($errors->has('password'))
                    <div class="invalid-feedback">{{ $errors->first('password') }}</div>
                  @endif
                </div>

                <div class="mb-6 form-control-validation">
                  <label class="form-label" for="password_confirmation">{{ trans('backpack::base.confirm_password') }}</label>
                  <input type="password" class="form-control {{ $errors->has('password_confirmation') ? 'is-invalid' : '' }}" name="password_confirmation" id="password_confirmation" value="">
                  @if ($errors->has('password_confirmation'))
                    <div class="invalid-feedback">{{ $errors->first('password_confirmation') }}</div>
                  @endif
                </div>

                <div class="mb-6">
                  <button type="submit" class="btn btn-primary d-grid w-100">
                    {{ trans('backpack::base.change_password') }}
                  </button>
                </div>
              </form>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
