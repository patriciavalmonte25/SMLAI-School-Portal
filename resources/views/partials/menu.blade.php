<div class="sidebar sidebar-dark sidebar-main sidebar-expand-md">

    <!-- Sidebar mobile toggler -->
    <div class="sidebar-mobile-toggler text-center">
        <a href="#" class="sidebar-mobile-main-toggle">
            <i class="icon-arrow-left8"></i>
        </a>
        Navigation
        <a href="#" class="sidebar-mobile-expand">
            <i class="icon-screen-full"></i>
            <i class="icon-screen-normal"></i>
        </a>
    </div>
    <!-- /sidebar mobile toggler -->

    <!-- Sidebar content -->
    <div class="sidebar-content">

        <!-- User menu -->
        <div class="sidebar-user">
            <div class="card-body">
                <div class="media">
                    <div class="media-body">
                        <div class="media-title font-weight-semibold">{{ Auth::user()->name }}</div>
                        <div class="font-size-xs opacity-50">
                            <i class="icon-user font-size-sm"></i> &nbsp;{{ ucwords(str_replace('_', ' ', Auth::user()->user_type)) }}
                        </div>
                    </div>

                    <div class="ml-3 align-self-center">
                        <a href="{{ route('my_account') }}" class="text-white"><i class="icon-cog3"></i></a>
                    </div>
                </div>
            </div>
        </div>
        <!-- /user menu -->

        <!-- Main navigation -->
        <div class="card card-sidebar-mobile">
            <ul class="nav nav-sidebar" data-nav-type="accordion">

                <!-- Main -->
                @if(Auth::user()->user_type !== 'enroll')
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ (Route::is('dashboard')) ? 'active' : '' }}">
                        <i class="icon-home4"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                @endif

                @if(Auth::user()->user_type === 'student')
                <li class="nav-item">
                    <a href="{{ route('document_requests.index') }}" class="nav-link {{ Route::is('document_requests.*') ? 'active' : '' }}">
                        <i class="icon-file-text2"></i>
                        <span>Document Requests</span>
                    </a>
                </li>
                @endif

                @if(in_array(Auth::user()->user_type, ['teacher', 'student', 'parent']))
                <li class="nav-item">
                    <a href="{{ route('tt.index') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['tt.index', 'ttr.edit', 'ttr.show', 'ttr.manage', 'ttr.print']) ? 'active' : '' }}">
                        <i class="icon-calendar3"></i>
                        <span>Timetables</span>
                    </a>
                </li>
                @endif

                {{--Administrative--}}
                @if(Qs::userIsAdministrative() && !Qs::userIsAdministrator() && !Qs::userIsAccountant())
                    <li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['payments.index', 'payments.create', 'payments.invoice', 'payments.receipts', 'payments.edit', 'payments.manage', 'payments.show',]) ? 'nav-item-expanded nav-item-open' : '' }} ">
                        <a href="#" class="nav-link"><i class="icon-office"></i> <span> Administrative</span></a>

                        <ul class="nav nav-group-sub" data-submenu-title="Administrative">

                            {{--Payments--}}
                            @if(Qs::userIsTeamAccount())
                            <li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['payments.index', 'payments.create', 'payments.edit', 'payments.manage', 'payments.show', 'payments.invoice']) ? 'nav-item-expanded' : '' }}">

                                <a href="#" class="nav-link {{ in_array(Route::currentRouteName(), ['payments.index', 'payments.edit', 'payments.create', 'payments.manage', 'payments.show', 'payments.invoice']) ? 'active' : '' }}">Payments</a>

                                <ul class="nav nav-group-sub">
                                    <li class="nav-item"><a href="{{ route('payments.create') }}" class="nav-link {{ Route::is('payments.create') ? 'active' : '' }}">Create Payment</a></li>
                                    <li class="nav-item"><a href="{{ route('payments.index') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['payments.index', 'payments.edit', 'payments.show']) ? 'active' : '' }}">Manage Payments</a></li>
                                    <li class="nav-item"><a href="{{ route('payments.manage') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['payments.manage', 'payments.invoice', 'payments.receipts']) ? 'active' : '' }}">Student Payments</a></li>

                                </ul>

                            </li>
                            @endif
                        </ul>
                    </li>
                @endif

                {{--Manage Students--}}
                @if(Qs::userIsTeamSAT() && !Qs::userIsAdministrator())
                    <li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['students.create', 'students.list', 'students.edit', 'students.show', 'students.promotion', 'students.promotion_manage', 'students.graduated']) ? 'nav-item-expanded nav-item-open' : '' }} ">
                        <a href="#" class="nav-link"><i class="icon-users"></i> <span> Students</span></a>

                        <ul class="nav nav-group-sub" data-submenu-title="Manage Students">
                            {{--Admit Student--}}
                            @if(Qs::userIsTeamSA())
                                <li class="nav-item">
                                    <a href="{{ route('students.create') }}"
                                       class="nav-link {{ (Route::is('students.create')) ? 'active' : '' }}">Admit Student</a>
                                </li>
                            @endif

                            {{--Student Information--}}
                            <li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['students.list', 'students.edit', 'students.show']) ? 'nav-item-expanded' : '' }}">
                                <a href="#" class="nav-link {{ in_array(Route::currentRouteName(), ['students.list', 'students.edit', 'students.show']) ? 'active' : '' }}">Student Information</a>
                                <ul class="nav nav-group-sub">
                                    @foreach(App\Models\MyClass::schoolOrder()->get() as $c)
                                        <li class="nav-item"><a href="{{ route('students.list', $c->id) }}" class="nav-link ">{{ $c->name }}</a></li>
                                    @endforeach
                                </ul>
                            </li>

                            @if(Qs::userIsTeamSA())

                            {{--Student Promotion--}}
                            <li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['students.promotion', 'students.promotion_manage']) ? 'nav-item-expanded' : '' }}"><a href="#" class="nav-link {{ in_array(Route::currentRouteName(), ['students.promotion', 'students.promotion_manage' ]) ? 'active' : '' }}">Student Promotion</a>
                            <ul class="nav nav-group-sub">
                                <li class="nav-item"><a href="{{ route('students.promotion') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['students.promotion']) ? 'active' : '' }}">Promote Students</a></li>
                                <li class="nav-item"><a href="{{ route('students.promotion_manage') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['students.promotion_manage']) ? 'active' : '' }}">Manage Promotions</a></li>
                            </ul>

                            </li>

                            {{--Student Graduated--}}
                            <li class="nav-item"><a href="{{ route('students.graduated') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['students.graduated' ]) ? 'active' : '' }}">Students Graduated</a></li>
                                @endif

                        </ul>
                    </li>
                @endif

                @if(Qs::userIsTeamSA())
                    @if(!Qs::userIsAdministrator())
                    {{--School Calendar--}}
                    <li class="nav-item">
                        <a href="{{ route('events.index') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['events.index', 'events.edit']) ? 'active' : '' }}"><i class="icon-calendar5"></i> <span> School Calendar</span></a>
                    </li>

                    {{--Manage Announcements--}}
                    <li class="nav-item">
                        <a href="{{ route('announcements.index') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['announcements.index', 'announcements.edit']) ? 'active' : '' }}"><i class="icon-megaphone"></i> <span> Announcements</span></a>
                    </li>

                    {{--Edit Home Page--}}
                    <li class="nav-item">
                        <a href="{{ route('edit_home_page') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['edit_home_page']) ? 'active' : '' }}"><i class="icon-home7"></i> <span> Edit Home Page</span></a>
                    </li>
                    @endif

                    {{--Manage Classes--}}
                    @if(!Qs::userIsAdministrator())
                        <li class="nav-item">
                            <a href="{{ route('classes.index') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['classes.index','classes.edit']) ? 'active' : '' }}"><i class="icon-windows2"></i> <span> Classes</span></a>
                        </li>
                    @endif

                    {{--Manage Sections--}}
                    @if(!Qs::userIsAdministrator())
                    <li class="nav-item">
                        <a href="{{ route('sections.index') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['sections.index','sections.edit',]) ? 'active' : '' }}"><i class="icon-fence"></i> <span>Sections</span></a>
                    </li>

                    {{--Manage Subjects--}}
                    <li class="nav-item">
                        <a href="{{ route('subjects.index') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['subjects.index','subjects.edit',]) ? 'active' : '' }}"><i class="icon-pin"></i> <span>Subjects</span></a>
                    </li>
                    @endif
                @endif

                {{--End Exam--}}

                @if(!Qs::userIsAdministrator())
                    @include('pages.'.Qs::getUserType().'.menu')
                @endif

                    {{--Administrator Enrollment--}}
                    @if(Qs::userIsAdministrator())
                        <li class="nav-item">
                            <a href="{{ route('events.index') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['events.index', 'events.edit']) ? 'active' : '' }}"><i class="icon-calendar5"></i> <span> School Calendar</span></a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('announcements.index') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['announcements.index', 'announcements.edit']) ? 'active' : '' }}"><i class="icon-megaphone"></i> <span> Announcements</span></a>
                        </li>

                        <li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['super_admin.classes_students.show', 'classes.index', 'classes.edit']) ? 'nav-item-expanded nav-item-open' : '' }} ">
                            <a href="#" class="nav-link"><i class="icon-user-check"></i> <span> Classes and Students</span></a>
                            <ul class="nav nav-group-sub" data-submenu-title="Classes and Students">
                                <li class="nav-item"><a href="{{ route('classes.index') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['classes.index', 'classes.edit']) ? 'active' : '' }}">Manage Classes</a></li>
                                @foreach(App\Models\MyClass::schoolOrder()->get() as $c)
                                    <li class="nav-item"><a href="{{ route('super_admin.classes_students.show', $c->id) }}" class="nav-link {{ Route::is('super_admin.classes_students.show') && request()->route('class_id') == $c->id ? 'active' : '' }}">{{ $c->name }}</a></li>
                                @endforeach
                            </ul>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('users.index') }}" class="nav-link {{ Route::is('users.index') ? 'active' : '' }}"><i class="icon-users4"></i> <span>Users</span></a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('tt.index') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['tt.index', 'ttr.edit', 'ttr.show', 'ttr.manage', 'ttr.print']) ? 'active' : '' }}">
                                <i class="icon-calendar3"></i>
                                <span>Timetables</span>
                            </a>
                        </li>

                        @include('pages.super_admin.menu')
                    @endif

                    @if(Auth::user()->user_type !== 'enroll' && !Qs::userIsAdministrator())
                    @if(Auth::user()->user_type === 'student')
                    <li class="nav-item">
                        <a href="{{ route('account_inbox') }}" class="nav-link {{ Route::is('account_inbox') ? 'active' : '' }}"><i class="icon-envelop"></i> <span>Inbox</span></a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('student.my_class') }}" class="nav-link {{ Route::is('student.my_class') ? 'active' : '' }}"><i class="icon-user-check"></i> <span>Classes</span></a>
                    </li>
                    @endif
                    <a href="{{ route('my_account') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['my_account']) ? 'active' : '' }}"><i class="icon-user"></i> <span>My Account</span></a>
                    @endif
                </li>

                </ul>
            </div>
        </div>
</div>
