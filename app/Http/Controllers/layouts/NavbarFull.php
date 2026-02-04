<?php

namespace App\Http\Controllers\layouts;

use App\Http\Controllers\Controller;

class NavbarFull extends Controller
{
  public function index()
  {
    $pageConfigs = ['myLayout' => 'horizontal'];

    return view('content.dashboard.dashboards-analytics', ['pageConfigs' => $pageConfigs]);
  }
}
