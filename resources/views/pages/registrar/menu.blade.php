<li class="nav-item">
    <a href="{{ route('users.index') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['users.index', 'users.create', 'users.show', 'users.edit']) ? 'active' : '' }}">
        <i class="icon-users4"></i> <span>Users</span>
    </a>
</li>

<li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['classes.index', 'classes.edit', 'super_admin.classes_students.show']) ? 'nav-item-expanded nav-item-open' : '' }}">
    <a href="#" class="nav-link"><i class="icon-user-check"></i> <span>Classes</span></a>
    <ul class="nav nav-group-sub" data-submenu-title="Classes">
        <li class="nav-item"><a href="{{ route('classes.index') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['classes.index', 'classes.edit']) ? 'active' : '' }}">Manage Classes</a></li>
        @foreach(App\Models\MyClass::schoolOrder()->get() as $class)
            <li class="nav-item"><a href="{{ route('super_admin.classes_students.show', $class->id) }}" class="nav-link {{ Route::is('super_admin.classes_students.show') && request()->route('class_id') == $class->id ? 'active' : '' }}">{{ $class->name }}</a></li>
        @endforeach
    </ul>
</li>

<li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['registrar.students.index', 'registrar.students.enroll', 'registrar.students.inbox', 'registrar.students.outbox']) ? 'nav-item-expanded nav-item-open' : '' }}">
    <a href="#" class="nav-link"><i class="icon-users"></i> <span>Students</span></a>
    <ul class="nav nav-group-sub" data-submenu-title="Students">
        <li class="nav-item"><a href="{{ route('registrar.students.index') }}" class="nav-link {{ Route::is('registrar.students.index') ? 'active' : '' }}">Manage Students</a></li>
        <li class="nav-item"><a href="{{ route('registrar.students.enroll') }}" class="nav-link {{ Route::is('registrar.students.enroll') ? 'active' : '' }}">Enrollment</a></li>
        <li class="nav-item"><a href="{{ route('registrar.students.inbox') }}" class="nav-link {{ Route::is('registrar.students.inbox') ? 'active' : '' }}">Inbox</a></li>
        <li class="nav-item"><a href="{{ route('registrar.students.outbox') }}" class="nav-link {{ Route::is('registrar.students.outbox') ? 'active' : '' }}">Outbox</a></li>
    </ul>
</li>