@php
  $containerFooter = ($configData['contentLayout'] ?? 'compact') === 'compact' ? 'container-xxl' : 'container-fluid';
  $developerName = backpack_theme_config('developer_name');
  $developerLink = backpack_theme_config('developer_link');
@endphp

<footer class="content-footer footer bg-footer-theme">
  <div class="{{ $containerFooter }}">
    <div class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
      <div class="text-body">
        &copy; <script>document.write(new Date().getFullYear());</script>
        {{ backpack_theme_config('project_name') }}
        @if ($developerName)
          , {{ $developerLink ? 'by' : '' }}
          @if ($developerLink)
            <a href="{{ $developerLink }}" target="_blank" class="footer-link">{{ $developerName }}</a>
          @else
            {{ $developerName }}
          @endif
        @endif
      </div>
      <div class="d-none d-lg-inline-block">
        @if (backpack_theme_config('show_powered_by'))
          <a href="https://backpackforlaravel.com" target="_blank" class="footer-link">Powered by Backpack</a>
        @endif
      </div>
    </div>
  </div>
</footer>
