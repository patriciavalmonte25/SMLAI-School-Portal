<li class="nav-item">
    <a href="{{ route('accountant.billings.index') }}" class="nav-link {{ Route::currentRouteName() === 'accountant.billings.index' || Route::currentRouteName() === 'accountant.billings.list' ? 'active' : '' }}">
        <i class="icon-cash3"></i> <span>Billings</span>
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('accountant.notices.index') }}" class="nav-link {{ str_starts_with(Route::currentRouteName(), 'accountant.notices.') ? 'active' : '' }}">
        <i class="icon-bell3"></i> <span>Notices</span>
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('accountant.document_requests.index') }}" class="nav-link {{ str_starts_with(Route::currentRouteName(), 'accountant.document_requests.') ? 'active' : '' }}">
        <i class="icon-file-text2"></i> <span>Document Requests</span>
    </a>
</li>