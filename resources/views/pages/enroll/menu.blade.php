<li class="nav-item">
    <a href="{{ route('pending_submissions') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['pending_submissions']) ? 'active' : '' }}">
        <i class="icon-clipboard2"></i>
        <span>Pending Submissions</span>
    </a>
</li>
