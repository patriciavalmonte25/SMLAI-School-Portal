{{--Edit Home Page--}}
<li class="nav-item">
    <a href="{{ route('edit_home_page') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['edit_home_page']) ? 'active' : '' }}"><i class="icon-home7"></i> <span> Edit Home Page</span></a>
</li>

{{--Manage Settings--}}
<li class="nav-item">
    <a href="{{ route('settings') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['settings',]) ? 'active' : '' }}"><i class="icon-gear"></i> <span>Settings</span></a>
</li>

