@section('after_scripts')
<script type="module">
  let input = document.querySelector('.password-visibility-toggler input');
  let buttons = document.querySelectorAll('.password-visibility-toggler button');

  buttons.forEach(button => {
    button.addEventListener('click', (event) => {
      event.preventDefault();
      buttons.forEach(b => b.classList.toggle('d-none'));
      input.type = input.type === 'password' ? 'text' : 'password';
      input.focus();
    });
  });
</script>
@endsection

<form method="POST" action="{{ route('backpack.auth.login') }}" autocomplete="off" novalidate="" class="mb-4">
  @csrf
  <div class="mb-6 form-control-validation">
    <label class="form-label" for="{{ $username }}">{{ trans('backpack::base.'.strtolower(config('backpack.base.authentication_column_name'))) }}</label>
    <input autofocus tabindex="1" type="text" name="{{ $username }}" value="{{ old($username) }}" id="{{ $username }}"
      class="form-control {{ $errors->has($username) ? 'is-invalid' : '' }}">
    @if ($errors->has($username))
      <div class="invalid-feedback">{{ $errors->first($username) }}</div>
    @endif
  </div>

  <div class="mb-6 form-password-toggle form-control-validation">
    <label class="form-label" for="password">{{ trans('backpack::base.password') }}</label>
    <div class="input-group input-group-merge password-visibility-toggler">
      <input tabindex="2" type="password" name="password" id="password"
        class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}" value="">
      @if(backpack_theme_config('options.showPasswordVisibilityToggler'))
        <span class="input-group-text cursor-pointer">
          <button type="button" class="btn p-0 text-body-secondary">
            <i class="icon-base ti tabler-eye"></i>
          </button>
          <button type="button" class="btn p-0 text-body-secondary d-none">
            <i class="icon-base ti tabler-eye-off"></i>
          </button>
        </span>
      @endif
    </div>
    @if ($errors->has('password'))
      <div class="invalid-feedback">{{ $errors->first('password') }}</div>
    @endif
  </div>

  <div class="my-8 d-flex justify-content-between">
    <div class="form-check mb-0 ms-2">
      <input name="remember" tabindex="3" type="checkbox" class="form-check-input" id="remember-me">
      <label class="form-check-label" for="remember-me">{{ trans('backpack::base.remember_me') }}</label>
    </div>
    @if (backpack_users_have_email() && backpack_email_column() == 'email' && config('backpack.base.setup_password_recovery_routes', true))
      <a tabindex="4" href="{{ route('backpack.auth.password.reset') }}" class="text-body">{{ trans('backpack::base.forgot_your_password') }}</a>
    @endif
  </div>

  <div class="mb-6">
    <button tabindex="5" type="submit" class="btn btn-primary d-grid w-100">{{ trans('backpack::base.login') }}</button>
  </div>
</form>
