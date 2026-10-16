{{--My Children--}}
<li class="nav-item">
    <a href="{{ route('my_children') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['my_children']) ? 'active' : '' }}"><i class="icon-users4"></i> My Children</a>
</li>
<li class="nav-item">
    <a href="{{ route('student.my_class') }}" class="nav-link {{ Route::is('student.my_class') ? 'active' : '' }}"><i class="icon-list"></i> Children's Class Lists</a>
</li>
<li class="nav-item">
    <a href="{{ route('parent_enrollment') }}" class="nav-link {{ Route::is('parent_enrollment') ? 'active' : '' }}"><i class="icon-file-plus2"></i> Enrollment</a>
</li>
<li class="nav-item">
    <a href="{{ route('document_requests.index') }}" class="nav-link {{ Route::is('document_requests.*') ? 'active' : '' }}"><i class="icon-file-text2"></i> Document Requests</a>
</li>