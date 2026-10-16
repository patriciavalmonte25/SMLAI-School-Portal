@extends('layouts.master')
@section('page_title', 'Manage Users')
@section('content')

    <style>
        .bulk-toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-bottom: 15px;
        }

        .selection-counter {
            display: none;
            align-self: center;
            white-space: nowrap;
            cursor: default;
        }

        .user-list-heading-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 12px;
        }

        .user-list-heading-row h5,
        .user-list-heading-row .bulk-toolbar {
            margin-bottom: 0;
        }

        .user-list-heading-row .bulk-toolbar {
            flex: 1;
        }

        .user-list-filter {
            width: 100%;
            max-width: 360px;
        }

        .select-mode .select-column,
        .select-mode .select-cell {
            display: table-cell !important;
        }

        .select-column,
        .select-cell {
            display: none;
            width: 48px;
            text-align: center;
        }

        .select-mode tbody tr.selectable-row {
            cursor: pointer;
        }

        .select-mode tbody tr.row-selected {
            background-color: #d9dee3;
        }

        .select-mode tbody tr.row-selected > td {
            background-color: #d9dee3 !important;
        }

    </style>

    <div class="card">
        <div class="card-body">
            <ul class="nav nav-tabs nav-tabs-highlight">
                @if(Qs::userIsAdministrator())
                    <li class="nav-item"><a href="#ut-administrators" class="nav-link {{ request('tab', 'administrators') === 'administrators' ? 'active' : '' }}" data-toggle="tab">Administrators</a></li>
                    <li class="nav-item"><a href="#ut-registrars" class="nav-link {{ request('tab') === 'registrars' ? 'active' : '' }}" data-toggle="tab">Registrars</a></li>
                    <li class="nav-item"><a href="#ut-accountants" class="nav-link {{ request('tab') === 'accountants' ? 'active' : '' }}" data-toggle="tab">Accountants</a></li>
                    <li class="nav-item"><a href="#ut-teacher" class="nav-link {{ request('tab') === 'teacher' ? 'active' : '' }}" data-toggle="tab">Teachers</a></li>
                    <li class="nav-item"><a href="#ut-parent" class="nav-link {{ request('tab') === 'parent' ? 'active' : '' }}" data-toggle="tab">Parents</a></li>
                @else
                <li class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle active" data-toggle="dropdown">Manage Users</a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a href="#ut-administrators" class="dropdown-item {{ request('tab', 'administrators') === 'administrators' ? 'active' : '' }}" data-toggle="tab">Administrators</a>
                        <a href="#ut-registrars" class="dropdown-item {{ request('tab') === 'registrars' ? 'active' : '' }}" data-toggle="tab">Registrars</a>
                        <a href="#ut-accountants" class="dropdown-item {{ request('tab') === 'accountants' ? 'active' : '' }}" data-toggle="tab">Accountants</a>
                        <a href="#ut-teacher" class="dropdown-item {{ request('tab') === 'teacher' ? 'active' : '' }}" data-toggle="tab">Teachers</a>
                        <a href="#ut-parent" class="dropdown-item {{ request('tab') === 'parent' ? 'active' : '' }}" data-toggle="tab">Parents</a>
                    </div>
                </li>
                @endif
                @if(Qs::userIsRegistrar())
                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">Create New User</a>
                        <div class="dropdown-menu dropdown-menu-right">
                        @foreach($creatable_user_types as $ut)
                            <a href="{{ route('users.create', ['type' => $ut->title]) }}" class="dropdown-item">{{ $ut->name }}</a>
                        @endforeach
                        </div>
                    </li>
                @endif
            </ul>

            <div class="tab-content" style="padding-top:20px;">
                <div class="tab-pane fade {{ request('tab', 'administrators') === 'administrators' ? 'show active' : '' }}" id="ut-administrators">
                    <div class="bulk-delete-form" data-tab-name="Administrators">
                        <div class="user-list-heading-row">
                            <h5>Administrators<small class="d-block text-muted">{{ $users->where('user_type', 'administrator')->count() }} total</small></h5>
                            @if(Qs::userIsRegistrar())
                                <div class="bulk-toolbar">
                                    <span class="selection-counter text-muted" aria-live="polite">0 of {{ $users->where('user_type', 'administrator')->count() }} entries selected</span>
                                    <button type="button" class="btn btn-light btn-sm toggle-select-mode">Select</button>
                                    <button type="button" class="btn btn-danger btn-sm delete-selected" style="display:none;">Delete Selected</button>
                                    <button type="button" class="btn btn-outline-primary btn-sm select-all" style="display:none;">Select All</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm clear-selection" style="display:none;">Deselect All</button>
                                </div>
                            @endif
                        </div>
                        @if(Qs::userIsRegistrar())
                            <form method="POST" action="{{ route('users.delete_selected') }}" class="bulk-delete-submit-form" style="display:none;">
                                @csrf
                            </form>
                        @endif
                        <div class="form-group mb-2 user-list-filter">
                            <input type="search" class="form-control form-control-sm" data-list-filter="administrator-users-table" placeholder="Search administrators..." aria-label="Search administrators">
                            <small class="form-text text-muted" data-list-filter-empty-for="administrator-users-table" hidden>No matching administrators.</small>
                        </div>

                        <table id="administrator-users-table" class="table bulk-select-table user-list-table">
                            <thead>
                            <tr>
                                @if(Qs::userIsRegistrar())
                                    <th class="select-column"><i class="icon-checkmark-circle"></i></th>
                                @endif
                                <th>Name</th>
                                <th>Username</th>
                                @unless(Qs::userIsAdministrator() || Qs::userIsRegistrar())<th class="action-column">Action</th>@endunless
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($users->where('user_type', 'administrator') as $u)
                                <tr class="selectable-row">
                                    @if(Qs::userIsRegistrar())
                                        <td class="select-cell">
                                            <input type="checkbox" name="user_ids[]" value="{{ $u->id }}" class="bulk-select-checkbox">
                                        </td>
                                    @endif
                                    <td><a href="{{ route('users.show', Qs::hash($u->id)) }}" class="user-profile-link"><u>{{ $u->name }}</u></a></td>
                                    <td>{{ $u->username }}</td>
                                    @unless(Qs::userIsAdministrator() || Qs::userIsRegistrar())
                                    <td class="text-center action-cell action-column">
                                        <div class="list-icons">
                                            <div class="dropdown">
                                                <a href="#" class="list-icons-item" data-toggle="dropdown" title="Administrator actions">
                                                    <i class="icon-paragraph-justify3"></i>
                                                </a>

                                                <div class="dropdown-menu dropdown-menu-left">
                                                    <a href="{{ route('users.show', Qs::hash($u->id)) }}" class="dropdown-item"><i class="icon-eye"></i> View Profile</a>
                                                    @unless(Qs::userIsAdministrator())
                                                        <a href="{{ route('users.edit', Qs::hash($u->id)) }}" class="dropdown-item"><i class="icon-pencil"></i> Edit</a>
                                                    @endunless
                                                    @if(Qs::userIsRegistrar())
                                                        <a href="{{ route('users.reset_pass', Qs::hash($u->id)) }}" class="dropdown-item"><i class="icon-lock"></i> Reset password</a>
                                                        <a id="{{ Qs::hash($u->id) }}" onclick="confirmDelete(this.id)" href="#" class="dropdown-item"><i class="icon-trash"></i> Delete</a>
                                                        <form method="post" id="item-delete-{{ Qs::hash($u->id) }}" action="{{ route('users.destroy', Qs::hash($u->id)) }}" class="hidden">@csrf @method('delete')</form>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    @endunless
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Administrators</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade {{ request('tab') === 'registrars' ? 'show active' : '' }}" id="ut-registrars">
                    <div class="bulk-delete-form" data-tab-name="Registrars">
                        <div class="user-list-heading-row">
                            <h5>Registrars<small class="d-block text-muted">{{ $users->where('user_type', 'registrar')->count() }} total</small></h5>
                            @if(Qs::userIsRegistrar())
                            <div class="bulk-toolbar">
                                <span class="selection-counter text-muted" aria-live="polite">0 of {{ $users->where('user_type', 'registrar')->count() }} entries selected</span>
                                <button type="button" class="btn btn-light btn-sm toggle-select-mode">Select</button>
                                <button type="button" class="btn btn-danger btn-sm delete-selected" style="display:none;">Delete Selected</button>
                                <button type="button" class="btn btn-outline-primary btn-sm select-all" style="display:none;">Select All</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm clear-selection" style="display:none;">Deselect All</button>
                            </div>
                            @endif
                        </div>
                        @if(Qs::userIsRegistrar())
                        <form method="POST" action="{{ route('users.delete_selected') }}" class="bulk-delete-submit-form" style="display:none;">
                            @csrf
                        </form>
                        @endif
                        <div class="form-group mb-2 user-list-filter">
                            <input type="search" class="form-control form-control-sm" data-list-filter="registrar-users-table" placeholder="Search registrars..." aria-label="Search registrars">
                            <small class="form-text text-muted" data-list-filter-empty-for="registrar-users-table" hidden>No matching registrars.</small>
                        </div>
                        <table id="registrar-users-table" class="table bulk-select-table user-list-table">
                            <thead>
                            <tr>
                                @if(Qs::userIsRegistrar())
                                <th class="select-column"><i class="icon-checkmark-circle"></i></th>
                                @endif
                                <th>Name</th>
                                <th>Username</th>
                                @unless(Qs::userIsAdministrator() || Qs::userIsRegistrar())<th class="action-column">Action</th>@endunless
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($users->where('user_type', 'registrar') as $u)
                                <tr class="selectable-row">
                                    @if(Qs::userIsRegistrar())
                                    <td class="select-cell"><input type="checkbox" name="user_ids[]" value="{{ $u->id }}" class="bulk-select-checkbox"></td>
                                    @endif
                                    <td><a href="{{ route('users.show', Qs::hash($u->id)) }}" class="user-profile-link"><u>{{ $u->name }}</u></a></td>
                                    <td>{{ $u->username }}</td>
                                    @unless(Qs::userIsAdministrator() || Qs::userIsRegistrar())
                                    <td class="text-center action-cell action-column">
                                        <div class="list-icons">
                                            <div class="dropdown">
                                                <a href="#" class="list-icons-item" data-toggle="dropdown" title="Registrar actions"><i class="icon-paragraph-justify3"></i></a>
                                                <div class="dropdown-menu dropdown-menu-left">
                                                    <a href="{{ route('users.show', Qs::hash($u->id)) }}" class="dropdown-item"><i class="icon-eye"></i> View Profile</a>
                                                    @unless(Qs::userIsAdministrator())
                                                        <a href="{{ route('users.edit', Qs::hash($u->id)) }}" class="dropdown-item"><i class="icon-pencil"></i> Edit</a>
                                                    @endunless
                                                    @if(Qs::userIsRegistrar())
                                                    <a href="{{ route('users.reset_pass', Qs::hash($u->id)) }}" class="dropdown-item"><i class="icon-lock"></i> Reset password</a>
                                                    <a id="{{ Qs::hash($u->id) }}" onclick="confirmDelete(this.id)" href="#" class="dropdown-item"><i class="icon-trash"></i> Delete</a>
                                                    <form method="post" id="item-delete-{{ Qs::hash($u->id) }}" action="{{ route('users.destroy', Qs::hash($u->id)) }}" class="hidden">@csrf @method('delete')</form>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    @endunless
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted">No registrars found.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade {{ request('tab') === 'accountants' ? 'show active' : '' }}" id="ut-accountants">
                    <div class="bulk-delete-form" data-tab-name="Accountants">
                        <div class="user-list-heading-row">
                            <h5>Accountants<small class="d-block text-muted">{{ $users->where('user_type', 'accountant')->count() }} total</small></h5>
                            @if(Qs::userIsRegistrar())
                                <div class="bulk-toolbar">
                                    <span class="selection-counter text-muted" aria-live="polite">0 of {{ $users->where('user_type', 'accountant')->count() }} entries selected</span>
                                    <button type="button" class="btn btn-light btn-sm toggle-select-mode">Select</button>
                                    <button type="button" class="btn btn-danger btn-sm delete-selected" style="display:none;">Delete Selected</button>
                                    <button type="button" class="btn btn-outline-primary btn-sm select-all" style="display:none;">Select All</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm clear-selection" style="display:none;">Deselect All</button>
                                </div>
                            @endif
                        </div>
                        @if(Qs::userIsRegistrar())
                            <form method="POST" action="{{ route('users.delete_selected') }}" class="bulk-delete-submit-form" style="display:none;">
                                @csrf
                            </form>
                        @endif
                        <div class="form-group mb-2 user-list-filter">
                            <input type="search" class="form-control form-control-sm" data-list-filter="accountant-users-table" placeholder="Search accountants..." aria-label="Search accountants">
                            <small class="form-text text-muted" data-list-filter-empty-for="accountant-users-table" hidden>No matching accountants.</small>
                        </div>
                        <table id="accountant-users-table" class="table bulk-select-table user-list-table">
                            <thead>
                            <tr>
                                @if(Qs::userIsRegistrar())
                                    <th class="select-column"><i class="icon-checkmark-circle"></i></th>
                                @endif
                                <th>Name</th>
                                <th>Username</th>
                                @unless(Qs::userIsAdministrator() || Qs::userIsRegistrar())<th class="action-column">Action</th>@endunless
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($users->where('user_type', 'accountant') as $u)
                                <tr class="selectable-row">
                                    @if(Qs::userIsRegistrar())
                                        <td class="select-cell"><input type="checkbox" name="user_ids[]" value="{{ $u->id }}" class="bulk-select-checkbox"></td>
                                    @endif
                                    <td><a href="{{ route('users.show', Qs::hash($u->id)) }}" class="user-profile-link"><u>{{ $u->name }}</u></a></td>
                                    <td>{{ $u->username }}</td>
                                    @unless(Qs::userIsAdministrator() || Qs::userIsRegistrar())
                                    <td class="text-center action-cell action-column">
                                        @if(Qs::userIsAdministrator())
                                            <a href="{{ route('users.show', Qs::hash($u->id)) }}" class="list-icons-item" title="View accountant {{ $u->name }}"><i class="icon-eye"></i></a>
                                        @else
                                            <a href="{{ route('users.edit', Qs::hash($u->id)) }}" class="list-icons-item" title="Edit accountant {{ $u->name }}"><i class="icon-paragraph-justify3"></i></a>
                                        @endif
                                    </td>
                                    @endunless
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No accountants found.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade {{ request('tab') === 'teacher' ? 'show active' : '' }}" id="ut-teacher">
                    <div class="bulk-delete-form" data-tab-name="Teachers">
                        <div class="user-list-heading-row">
                            <h5>Teachers<small class="d-block text-muted">{{ $users->where('user_type', 'teacher')->count() }} total</small></h5>
                            @if(Qs::userIsRegistrar())
                                <div class="bulk-toolbar">
                                    <span class="selection-counter text-muted" aria-live="polite">0 of {{ $users->where('user_type', 'teacher')->count() }} entries selected</span>
                                    <button type="button" class="btn btn-light btn-sm toggle-select-mode">Select</button>
                                    <button type="button" class="btn btn-danger btn-sm delete-selected" style="display:none;">Delete Selected</button>
                                    <button type="button" class="btn btn-outline-primary btn-sm select-all" style="display:none;">Select All</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm clear-selection" style="display:none;">Deselect All</button>
                                </div>
                            @endif
                        </div>
                        @if(Qs::userIsRegistrar())
                            <form method="POST" action="{{ route('users.delete_selected') }}" class="bulk-delete-submit-form" style="display:none;">
                                @csrf
                            </form>
                        @endif
                        <div class="form-group mb-2 user-list-filter">
                            <input type="search" class="form-control form-control-sm" data-list-filter="teacher-users-table" placeholder="Search teachers..." aria-label="Search teachers">
                            <small class="form-text text-muted" data-list-filter-empty-for="teacher-users-table" hidden>No matching teachers.</small>
                        </div>

                        <table id="teacher-users-table" class="table bulk-select-table user-list-table">
                            <thead>
                            <tr>
                                @if(Qs::userIsRegistrar())
                                    <th class="select-column"><i class="icon-checkmark-circle"></i></th>
                                @endif
                                <th>Name</th>
                                <th>Username</th>
                                @unless(Qs::userIsAdministrator() || Qs::userIsRegistrar())<th class="action-column">Action</th>@endunless
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($users->where('user_type', 'teacher') as $u)
                                <tr class="selectable-row">
                                    @if(Qs::userIsRegistrar())
                                        <td class="select-cell">
                                            <input type="checkbox" name="user_ids[]" value="{{ $u->id }}" class="bulk-select-checkbox">
                                        </td>
                                    @endif
                                    <td><a href="{{ route('users.show', Qs::hash($u->id)) }}" class="user-profile-link"><u>{{ $u->name }}</u></a></td>
                                    <td>{{ $u->username }}</td>
                                    @unless(Qs::userIsAdministrator() || Qs::userIsRegistrar())
                                    <td class="text-center action-cell action-column">
                                        <div class="list-icons">
                                            <div class="dropdown">
                                                <a href="#" class="list-icons-item" data-toggle="dropdown">
                                                    <i class="icon-paragraph-justify3"></i>
                                                </a>

                                                <div class="dropdown-menu dropdown-menu-left">
                                                    <a href="{{ route('users.show', Qs::hash($u->id)) }}" class="dropdown-item"><i class="icon-eye"></i> View Profile</a>
                                                    @unless(Qs::userIsAdministrator())
                                                        <a href="{{ route('users.edit', Qs::hash($u->id)) }}" class="dropdown-item"><i class="icon-pencil"></i> Edit</a>
                                                    @endunless
                                                    @if(Qs::userIsRegistrar())
                                                        <a href="{{ route('users.reset_pass', Qs::hash($u->id)) }}" class="dropdown-item"><i class="icon-lock"></i> Reset password</a>
                                                        <a id="{{ Qs::hash($u->id) }}" onclick="confirmDelete(this.id)" href="#" class="dropdown-item"><i class="icon-trash"></i> Delete</a>
                                                        <form method="post" id="item-delete-{{ Qs::hash($u->id) }}" action="{{ route('users.destroy', Qs::hash($u->id)) }}" class="hidden">@csrf @method('delete')</form>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    @endunless
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Teachers</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade {{ request('tab') === 'parent' ? 'show active' : '' }}" id="ut-parent">
                    <div class="bulk-delete-form" data-tab-name="Parents">
                        <div class="user-list-heading-row">
                            <h5>Parents<small class="d-block text-muted">{{ $users->where('user_type', 'parent')->count() }} total</small></h5>
                            @if(Qs::userIsRegistrar())
                                <div class="bulk-toolbar">
                                    <span class="selection-counter text-muted" aria-live="polite">0 of {{ $users->where('user_type', 'parent')->count() }} entries selected</span>
                                    <button type="button" class="btn btn-light btn-sm toggle-select-mode">Select</button>
                                    <button type="button" class="btn btn-danger btn-sm delete-selected" style="display:none;">Delete Selected</button>
                                    <button type="button" class="btn btn-outline-primary btn-sm select-all" style="display:none;">Select All</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm clear-selection" style="display:none;">Deselect All</button>
                                </div>
                            @endif
                        </div>
                        @if(Qs::userIsRegistrar())
                            <form method="POST" action="{{ route('users.delete_selected') }}" class="bulk-delete-submit-form" style="display:none;">
                                @csrf
                            </form>
                        @endif
                        <div class="form-group mb-2 user-list-filter">
                            <input type="search" class="form-control form-control-sm" data-list-filter="parent-users-table" placeholder="Search parents..." aria-label="Search parents">
                            <small class="form-text text-muted" data-list-filter-empty-for="parent-users-table" hidden>No matching parents.</small>
                        </div>

                        <table id="parent-users-table" class="table bulk-select-table user-list-table">
                            <thead>
                            <tr>
                                @if(Qs::userIsRegistrar())
                                    <th class="select-column"><i class="icon-checkmark-circle"></i></th>
                                @endif
                                <th>Name</th>
                                <th>Grades of Child/Children</th>
                                <th>Username</th>
                                @unless(Qs::userIsAdministrator() || Qs::userIsRegistrar())<th class="action-column">Action</th>@endunless
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($users->where('user_type', 'parent') as $u)
                                <tr class="selectable-row">
                                    @if(Qs::userIsRegistrar())
                                        <td class="select-cell">
                                            <input type="checkbox" name="user_ids[]" value="{{ $u->id }}" class="bulk-select-checkbox">
                                        </td>
                                    @endif
                                    <td><a href="{{ route('users.show', Qs::hash($u->id)) }}" class="user-profile-link"><u>{{ $u->name }}</u></a></td>
                                    <td>
                                        @php($parentChildren = $parent_children_by_parent->get($u->id, collect()))
                                        @if($parentChildren->isNotEmpty())
                                            <ul class="mb-0 pl-3">
                                                @foreach($parentChildren as $child)
                                                    <li>{{ $child->name }} - {{ optional(optional($child->student_record)->my_class)->name ?: (optional(optional($child->student_enrollment)->my_class)->name ?: 'No class') }} / {{ optional(optional($child->student_record)->section)->name ?: (optional(optional($child->student_enrollment)->section)->name ?: 'No section') }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="text-muted">No children linked</span>
                                        @endif
                                    </td>
                                    <td>{{ $u->username }}</td>
                                    @unless(Qs::userIsAdministrator() || Qs::userIsRegistrar())
                                    <td class="text-center action-cell action-column">
                                        <div class="list-icons">
                                            <div class="dropdown">
                                                <a href="#" class="list-icons-item" data-toggle="dropdown">
                                                    <i class="icon-paragraph-justify3"></i>
                                                </a>

                                                <div class="dropdown-menu dropdown-menu-left">
                                                    <a href="{{ route('users.show', Qs::hash($u->id)) }}" class="dropdown-item"><i class="icon-eye"></i> View Profile</a>
                                                    @unless(Qs::userIsAdministrator())
                                                        <a href="{{ route('users.edit', Qs::hash($u->id)) }}" class="dropdown-item"><i class="icon-pencil"></i> Edit</a>
                                                    @endunless
                                                    @if(Qs::userIsRegistrar())
                                                        <a href="{{ route('users.reset_pass', Qs::hash($u->id)) }}" class="dropdown-item"><i class="icon-lock"></i> Reset password</a>
                                                        <a id="{{ Qs::hash($u->id) }}" onclick="confirmDelete(this.id)" href="#" class="dropdown-item"><i class="icon-trash"></i> Delete</a>
                                                        <form method="post" id="item-delete-{{ Qs::hash($u->id) }}" action="{{ route('users.destroy', Qs::hash($u->id)) }}" class="hidden">@csrf @method('delete')</form>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    @endunless
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Parents</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(Qs::userIsRegistrar())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.bulk-delete-form').forEach(function (form) {
                    var table = form.querySelector('.bulk-select-table');
                    var submitForm = form.querySelector('.bulk-delete-submit-form');
                    var toggleButton = form.querySelector('.toggle-select-mode');
                    var deleteButton = form.querySelector('.delete-selected');
                    var selectAllButton = form.querySelector('.select-all');
                    var clearButton = form.querySelector('.clear-selection');
                    var selectionCounter = form.querySelector('.selection-counter');
                    var checkboxes = Array.prototype.slice.call(form.querySelectorAll('.bulk-select-checkbox'));

                    if (!table || !toggleButton || !deleteButton || !selectAllButton || !clearButton || !submitForm) {
                        return;
                    }

                    function selectedCount() {
                        return checkboxes.filter(function (checkbox) {
                            return checkbox.checked;
                        }).length;
                    }

                    function updateDeleteButtonState() {
                        deleteButton.disabled = selectedCount() === 0;
                        if (selectionCounter) {
                            selectionCounter.textContent = selectedCount() + ' of ' + checkboxes.length + ' entries selected';
                        }
                    }

                    function syncRowState(checkbox) {
                        var row = checkbox.closest('tr');
                        if (!row) {
                            return;
                        }

                        if (checkbox.checked) {
                            row.classList.add('row-selected');
                        } else {
                            row.classList.remove('row-selected');
                        }
                    }

                    function clearSelection() {
                        checkboxes.forEach(function (checkbox) {
                            checkbox.checked = false;
                            syncRowState(checkbox);
                        });
                        updateDeleteButtonState();
                    }

                    checkboxes.forEach(function (checkbox) {
                        checkbox.addEventListener('change', function () {
                            syncRowState(checkbox);
                            updateDeleteButtonState();
                        });
                    });

                    table.addEventListener('click', function (event) {
                        if (!table.classList.contains('select-mode')) {
                            return;
                        }

                        if (event.target.closest('.action-cell')) {
                            return;
                        }

                        if (event.target.closest('a') || event.target.closest('button') || event.target.closest('input')) {
                            return;
                        }

                        var row = event.target.closest('tr.selectable-row');
                        if (!row) {
                            return;
                        }

                        var checkbox = row.querySelector('.bulk-select-checkbox');
                        if (!checkbox) {
                            return;
                        }

                        checkbox.checked = !checkbox.checked;
                        syncRowState(checkbox);
                        updateDeleteButtonState();
                    });

                    toggleButton.addEventListener('click', function () {
                        var selectMode = table.classList.toggle('select-mode');

                        deleteButton.style.display = selectMode ? 'inline-block' : 'none';
                        selectAllButton.style.display = selectMode ? 'inline-block' : 'none';
                        clearButton.style.display = selectMode ? 'inline-block' : 'none';
                        if (selectionCounter) {
                            selectionCounter.style.display = selectMode ? 'inline-block' : 'none';
                        }
                        toggleButton.textContent = selectMode ? 'Cancel' : 'Select';

                        if (!selectMode) {
                            clearSelection();
                        }
                    });

                    selectAllButton.addEventListener('click', function () {
                        checkboxes.forEach(function (checkbox) {
                            checkbox.checked = true;
                            syncRowState(checkbox);
                        });
                        updateDeleteButtonState();
                    });

                    clearButton.addEventListener('click', clearSelection);

                    deleteButton.addEventListener('click', function () {
                        var totalSelected = selectedCount();

                        if (totalSelected === 0) {
                            return;
                        }

                        swal({
                            title: 'Are you sure?',
                            text: 'You are about to delete ' + totalSelected + ' accounts. Proceed?',
                            icon: 'warning',
                            dangerMode: true,
                            buttons: {
                                cancel: 'Cancel',
                                confirm: 'Proceed'
                            }
                        }).then(function (confirmed) {
                            if (!confirmed) {
                                return;
                            }

                            submitForm.querySelectorAll('input[name="user_ids[]"]').forEach(function (input) {
                                input.remove();
                            });

                            checkboxes.filter(function (checkbox) {
                                return checkbox.checked;
                            }).forEach(function (checkbox) {
                                var input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'user_ids[]';
                                input.value = checkbox.value;
                                submitForm.appendChild(input);
                            });

                            submitForm.submit();
                        });
                    });

                    updateDeleteButtonState();
                });
            });
        </script>
    @endif

@endsection
