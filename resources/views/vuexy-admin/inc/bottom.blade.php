@yield('before_scripts')
@stack('before_scripts')

@include(backpack_view('inc.theme_scripts'))
@include(backpack_view('inc.scripts'))

@yield('after_scripts')
@stack('after_scripts')
