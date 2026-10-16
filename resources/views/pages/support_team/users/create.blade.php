@extends('layouts.master')
@section('page_title', 'Create User')
@section('content')

    <style>
        /* Select2 miscalculates its own width inside flex/narrower containers unless forced. */
        .select2-container {
            width: 100% !important;
        }

        #create-user-form .actions li:has(> a[href$="#previous"]),
        #create-user-form .actions li:has(> a[href$="#finish"]) {
            display: none;
        }

        #create-user-form .actions a[href$="#previous"],
        #create-user-form .actions a[href$="#finish"] {
            display: none !important;
        }

        #create-user-form .d-flex.flex-wrap {
            gap: 15px !important;
            margin-bottom: 15px;
        }

        #create-user-form .d-flex.flex-wrap .form-group {
            margin-bottom: 0;
        }
    </style>

    <div class="card">
        <div class="card-body">
            <ul class="nav nav-tabs nav-tabs-highlight">
                <li class="nav-item">
                    <a href="{{ route('users.index') }}" class="nav-link">Manage Users</a>
                </li>
                <li class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle active" data-toggle="dropdown">Create New User</a>
                    <div class="dropdown-menu dropdown-menu-right">
                        @foreach($creatable_user_types as $ut)
                            <a href="{{ route('users.create', ['type' => $ut->title]) }}" class="dropdown-item {{ $selected_user_type && $selected_user_type->id == $ut->id ? 'active' : '' }}">{{ $ut->name }}</a>
                        @endforeach
                    </div>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active">
                    @php($typeTitle = strtolower($selected_user_type->title ?? ''))
                    @php($isLeanProfileType = in_array($typeTitle, ['administrator', 'teacher']))
                    @php($isParentType = $typeTitle === 'parent')
                    @php($isReducedProfileType = $isLeanProfileType || $isParentType)

                    <div class="row align-items-start">
                        @if($isParentType)
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="card-title mb-0">Enrollment Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="temp-enrollment-select">Select Enrollment Form:</label>
                                            <select id="temp-enrollment-select" name="source_enrollment_id" class="form-control select-search" data-placeholder="Choose an approved form...">
                                                <option value=""></option>
                                                @foreach($temporary_enrollments as $enrollment)
                                                    <option value="{{ $enrollment->id }}" {{ (string) old('source_enrollment_id', $selected_temporary_enrollment_id) === (string) $enrollment->id ? 'selected' : '' }}>{{ $enrollment->formatted_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <p id="temp-enrollment-placeholder" class="text-muted mb-0" {{ $selected_temporary_enrollment_id ? 'style=display:none;' : '' }}>Select an enrollment form to view its details.</p>

                                        @foreach($temporary_enrollments as $enrollment)
                                            <div class="temp-enrollment-detail" data-enrollment-id="{{ $enrollment->id }}" style="display:{{ (string) $selected_temporary_enrollment_id === (string) $enrollment->id ? 'block' : 'none' }}; margin-top: 15px;">
                                                <table class="table table-bordered mb-0 temporary-account-details-table">
                                                    <tbody>
                                                    <tr><th>Full Name</th><td>{{ $enrollment->formatted_name }}</td></tr>
                                                    <tr><th>Home Address</th><td>{{ $enrollment->home_town }}</td></tr>
                                                    <tr><th>Father's Name</th><td>{{ $enrollment->father_name }}</td></tr>
                                                    <tr><th>Father's Occupation</th><td>{{ $enrollment->father_occupation }}</td></tr>
                                                    <tr><th>Mother's Name</th><td>{{ $enrollment->mother_name }}</td></tr>
                                                    <tr><th>Mother's Occupation</th><td>{{ $enrollment->mother_occupation }}</td></tr>
                                                    <tr><th>Guardian's Name</th><td>{{ $enrollment->guardian_name }}</td></tr>
                                                    <tr><th>Guardian's Occupation</th><td>{{ $enrollment->guardian_occupation }}</td></tr>
                                                    <tr><th>Relation to Student</th><td>{{ $enrollment->guardian_relation }}</td></tr>
                                                    <tr><th>Contact Number</th><td>{{ $enrollment->contact_number }}</td></tr>
                                                    <tr><th>Grade Enrolling</th><td>{{ $enrollment->grade_enrolling }}</td></tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                    <div class="{{ $isParentType ? 'col-md-8' : 'col-md-12' }}">
                    <form id="create-user-form" method="post" enctype="multipart/form-data" class="wizard-form steps-validation ajax-store" action="{{ route('users.store') }}" data-fouc novalidate data-confirm-submit="true" data-confirm-title="Create Account?" data-confirm-message="Create this account with the entered details?" data-confirm-label="Create Account" data-follow-success-url="{{ $typeTitle === 'teacher' ? 'true' : 'false' }}" data-success-popup="true" data-success-title="Account Created">
                        @csrf
                        <input type="hidden" name="user_type" value="{{ $selected_user_type ? Qs::hash($selected_user_type->id) : '' }}">

                        <h6>{{ $selected_user_type ? $selected_user_type->name : 'User' }} Account Requirements</h6>
                        <fieldset>
                            <div class="d-flex flex-wrap" style="gap: 15px;">
                                <div class="form-group" style="flex: 1 1 260px;">
                                    <label>Full Name:</label>
                                    <input value="{{ old('name') }}" type="text" name="name" placeholder="Full Name" class="form-control">
                                </div>

                                <div class="form-group" style="flex: 1 1 260px;">
                                    <label>Address:</label>
                                    <input value="{{ old('address') }}" class="form-control" placeholder="Address" name="address" type="text">
                                </div>
                            </div>

                            <div class="d-flex flex-wrap" style="gap: 15px;">
                                <div class="form-group" style="flex: 1 1 200px;">
                                    <label for="gender">Gender:</label>
                                    <select class="select form-control" id="gender" name="gender" data-fouc data-placeholder="Choose..">
                                        <option value=""></option>
                                        @foreach($genders as $gender)
                                            <option {{ old('gender') == $gender ? 'selected' : '' }} value="{{ $gender }}">{{ $gender }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                @if(!$isReducedProfileType)
                                    <div class="form-group" style="flex: 1 1 200px;">
                                        <label for="nal_id">Nationality:</label>
                                        <select data-placeholder="Choose..." name="nal_id" id="nal_id" class="select-search form-control">
                                            <option value=""></option>
                                            @foreach($nationals as $nal)
                                                <option {{ old('nal_id') == $nal->id ? 'selected' : '' }} value="{{ $nal->id }}">{{ $nal->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                <div class="form-group" style="flex: 1 1 200px;">
                                    <label>Email address:</label>
                                    <input value="{{ old('email') }}" type="email" name="email" class="form-control" placeholder="your@email.com">
                                </div>

                                <div class="form-group" style="flex: 1 1 200px;">
                                    <label>Phone:</label>
                                    <input value="{{ old('phone') }}" type="text" name="phone" class="form-control" placeholder="+2341234567">
                                </div>

                                @if($typeTitle !== 'teacher')
                                    <div class="form-group" style="flex: 1 1 200px;">
                                        <label for="occupation">Occupation:</label>
                                        <input value="{{ old('occupation') }}" type="text" name="occupation" id="occupation" class="form-control" placeholder="Occupation">
                                    </div>
                                @endif
                            </div>

                            @if($typeTitle === 'administrator')
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="d-block">Role/s:</label>
                                            <div id="roles-wrapper">
                                                @php($oldRoles = old('roles', ['']))
                                                @foreach($oldRoles as $roleValue)
                                                    <div class="input-group mb-2 role-item">
                                                        <input type="text" name="roles[]" class="form-control" value="{{ $roleValue }}" placeholder="Enter role">
                                                        <div class="input-group-append">
                                                            <button type="button" class="btn btn-danger remove-role" {{ $loop->first ? 'style=display:none;' : '' }}>Remove</button>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <button type="button" id="add-role" class="btn btn-light mt-1">Add Role</button>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if($typeTitle === 'teacher')
                                <div class="form-group">
                                    <label for="adviser_class_id">Adviser</label>
                                    <select name="adviser_class_id" id="adviser_class_id" class="form-control select-search" data-placeholder="None">
                                        <option value="">None</option>
                                        @foreach($my_classes as $class)
                                            <option value="{{ $class->id }}" {{ (string) old('adviser_class_id') === (string) $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            @if($isParentType)
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="d-block">Children</label>
                                            @php($oldChildren = old('children', [['class_id' => '', 'student_id' => '']]))
                                            <div id="children-wrapper" data-next-index="{{ count($oldChildren) }}">
                                                @foreach($oldChildren as $index => $child)
                                                    <div class="row child-item mb-2" data-index="{{ $index }}">
                                                        <div class="col-md-5">
                                                            <select name="children[{{ $index }}][class_id]" class="form-control child-class select-search" data-placeholder="Choose class..." data-student-select="#child-student-{{ $index }}">
                                                                <option value=""></option>
                                                                @foreach($my_classes as $class)
                                                                    <option value="{{ $class->id }}" {{ (string)($child['class_id'] ?? '') === (string)$class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-md-5">
                                                            <select id="child-student-{{ $index }}" name="children[{{ $index }}][student_id]" class="form-control child-student" data-selected-student="{{ $child['student_id'] ?? '' }}">
                                                                <option value="">Select student</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <button type="button" class="btn btn-danger remove-child" {{ $loop->first ? 'style=display:none;' : '' }}>Remove</button>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <button type="button" id="add-child" class="btn btn-light mt-1">Add Child</button>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if($isParentType)
                                <div class="form-group mt-2">
                                    <label for="relationship_to_children">Relationship to Children:</label>
                                    <input value="{{ old('relationship_to_children') }}" type="text" name="relationship_to_children" id="relationship_to_children" class="form-control" placeholder="Relationship to Children">
                                </div>
                            @endif

                            <div class="row">
                                @if(!$isReducedProfileType)
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Telephone:</label>
                                            <input value="{{ old('phone2') }}" type="text" name="phone2" class="form-control" placeholder="">
                                        </div>
                                    </div>
                                @endif

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Date of Birth:</label>
                                        <input name="dob" value="{{ old('dob') }}" type="text" class="form-control date-pick" placeholder="MM/DD/YYYY" autocomplete="off" data-date-format="mm/dd/yyyy">
                                    </div>
                                </div>

                                @if(!$isReducedProfileType)
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="bg_id">Blood Group:</label>
                                            <select class="select form-control" id="bg_id" name="bg_id" data-fouc data-placeholder="Choose..">
                                                <option value=""></option>
                                                @foreach($blood_groups as $bg)
                                                    <option {{ old('bg_id') == $bg->id ? 'selected' : '' }} value="{{ $bg->id }}">{{ $bg->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                @endif

                                @if($selected_user_type && in_array($selected_user_type->title, Qs::getStaff()) && !$isReducedProfileType)
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Date of Employment:</label>
                                            <input autocomplete="off" name="emp_date" value="{{ old('emp_date') }}" type="text" class="form-control date-pick" placeholder="MM/DD/YYYY" data-date-format="mm/dd/yyyy">
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="row">
                                @if(!$isReducedProfileType)
                                    <div class="col-md-4">
                                        <label for="state_id">State:</label>
                                        <select onchange="getLGA(this.value)" data-placeholder="Choose.." class="select-search form-control" name="state_id" id="state_id">
                                            <option value=""></option>
                                            @foreach($states as $st)
                                                <option {{ old('state_id') == $st->id ? 'selected' : '' }} value="{{ $st->id }}">{{ $st->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="lga_id">LGA:</label>
                                        <select data-placeholder="Select State First" class="select-search form-control" name="lga_id" id="lga_id">
                                            <option value="{{ old('lga_id') ?: '' }}">{{ old('lga_id') ? 'Selected' : '' }}</option>
                                        </select>
                                    </div>
                                @endif

                                <div class="{{ $isReducedProfileType ? 'col-md-12' : 'col-md-4' }}">
                                    <div class="form-group">
                                        <label>Username:</label>
                                        <input value="{{ old('username') }}" type="text" name="username" class="form-control" placeholder="Username">
                                    </div>
                                </div>
                            </div>

                            <div class="text-right mt-3">
                                <button type="submit" class="btn btn-primary">Create {{ $selected_user_type ? $selected_user_type->name : 'User' }} <i class="icon-paperplane ml-2"></i></button>
                            </div>
                        </fieldset>
                    </form>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($typeTitle === 'administrator')
        <script>
            (function () {
                const wrapper = document.getElementById('roles-wrapper');
                const addButton = document.getElementById('add-role');
                if (!wrapper || !addButton) {
                    return;
                }

                const template = function () {
                    return '<div class="input-group mb-2 role-item">'
                        + '<input type="text" name="roles[]" class="form-control" placeholder="Enter role">'
                        + '<div class="input-group-append">'
                        + '<button type="button" class="btn btn-danger remove-role">Remove</button>'
                        + '</div>'
                        + '</div>';
                };

                addButton.addEventListener('click', function () {
                    wrapper.insertAdjacentHTML('beforeend', template());
                });

                wrapper.addEventListener('click', function (event) {
                    if (!event.target.classList.contains('remove-role')) {
                        return;
                    }

                    const items = wrapper.querySelectorAll('.role-item');
                    if (items.length === 1) {
                        const input = items[0].querySelector('input[name="roles[]"]');
                        if (input) {
                            input.value = '';
                        }
                        return;
                    }

                    event.target.closest('.role-item').remove();
                });
            })();
        </script>
    @endif

    @if($isParentType)
        <script>
            var initializeParentForm = function () {
            (function () {
                const wrapper = document.getElementById('children-wrapper');
                const addButton = document.getElementById('add-child');
                if (!wrapper || !addButton) {
                    return;
                }

                const classStudentEndpoint = '{{ route('get_class_students', '__CLASS__') }}';

                const buildRow = function (index) {
                    return '<div class="row child-item mb-2" data-index="' + index + '">'
                        + '<div class="col-md-5">'
                        + '<select name="children[' + index + '][class_id]" class="form-control child-class select-search" data-placeholder="Choose class..." data-student-select="#child-student-' + index + '">' 
                        + '<option value=""></option>'
                        + '@foreach($my_classes as $class)'
                        + '<option value="{{ $class->id }}">{{ $class->name }}</option>'
                        + '@endforeach'
                        + '</select>'
                        + '</div>'
                        + '<div class="col-md-5">'
                        + '<select id="child-student-' + index + '" name="children[' + index + '][student_id]" class="form-control child-student">'
                        + '<option value="">Select student</option>'
                        + '</select>'
                        + '</div>'
                        + '<div class="col-md-2">'
                        + '<button type="button" class="btn btn-danger remove-child">Remove</button>'
                        + '</div>'
                        + '</div>';
                };

                const resetStudentSelect = function (selectElement, placeholder) {
                    selectElement.innerHTML = '';
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = placeholder;
                    selectElement.appendChild(option);
                };

                const notifyStudentSelect = function (selectElement) {
                    selectElement.dispatchEvent(new Event('change', { bubbles: true }));
                };

                const loadStudents = function (classSelect, selectedStudent) {
                    const classId = classSelect.value;
                    const studentSelector = classSelect.getAttribute('data-student-select');
                    const studentSelect = document.querySelector(studentSelector);
                    if (!studentSelect) {
                        return;
                    }

                    if (!classId) {
                        resetStudentSelect(studentSelect, 'Select student');
                        notifyStudentSelect(studentSelect);
                        return;
                    }

                    const url = classStudentEndpoint.replace('__CLASS__', classId);
                    resetStudentSelect(studentSelect, 'Loading students...');
                    notifyStudentSelect(studentSelect);
                    fetch(url)
                        .then(function (response) {
                            if (!response.ok) {
                                throw new Error('Unable to load students');
                            }
                            return response.json();
                        })
                        .then(function (students) {
                            resetStudentSelect(studentSelect, students.length ? 'Select student' : 'No students found');
                            students.forEach(function (student) {
                                const option = document.createElement('option');
                                option.value = student.id;
                                option.textContent = student.name;
                                if (selectedStudent && selectedStudent === student.id) {
                                    option.selected = true;
                                }
                                studentSelect.appendChild(option);
                            });
                            notifyStudentSelect(studentSelect);
                        })
                        .catch(function () {
                            resetStudentSelect(studentSelect, 'Unable to load students');
                            notifyStudentSelect(studentSelect);
                        });
                };

                addButton.addEventListener('click', function () {
                    const nextIndex = parseInt(wrapper.getAttribute('data-next-index'), 10) || 0;
                    wrapper.insertAdjacentHTML('beforeend', buildRow(nextIndex));
                    wrapper.setAttribute('data-next-index', String(nextIndex + 1));

                    if (window.jQuery && window.jQuery.fn.select2) {
                        window.jQuery(wrapper.lastElementChild).find('.child-class').select2();
                    }
                });

                wrapper.addEventListener('click', function (event) {
                    if (!event.target.classList.contains('remove-child')) {
                        return;
                    }

                    const rows = wrapper.querySelectorAll('.child-item');
                    if (rows.length === 1) {
                        const classSelect = rows[0].querySelector('.child-class');
                        const studentSelect = rows[0].querySelector('.child-student');
                        if (classSelect) {
                            classSelect.value = '';
                        }
                        if (studentSelect) {
                            resetStudentSelect(studentSelect, 'Select student');
                        }
                        return;
                    }

                    event.target.closest('.child-item').remove();
                });

                window.jQuery(wrapper).on('change', '.child-class', function () {
                    loadStudents(this, null);
                });

                wrapper.querySelectorAll('.child-class').forEach(function (classSelect) {
                    const studentSelector = classSelect.getAttribute('data-student-select');
                    const studentSelect = document.querySelector(studentSelector);
                    const selectedStudent = studentSelect ? studentSelect.getAttribute('data-selected-student') : null;
                    if (classSelect.value) {
                        loadStudents(classSelect, selectedStudent);
                    }
                });
            })();

            (function () {
                var $select = window.jQuery('#temp-enrollment-select');
                var $placeholder = window.jQuery('#temp-enrollment-placeholder');
                if (!$select.length) {
                    return;
                }

                $select.on('change', function () {
                    var value = window.jQuery(this).val();
                    window.jQuery('.temp-enrollment-detail').hide();

                    if (!value) {
                        $placeholder.show();
                        return;
                    }

                    $placeholder.hide();
                    window.jQuery('.temp-enrollment-detail[data-enrollment-id="' + value + '"]').show();
                });
            })();
            };

        </script>
    @endif
@endsection

@section('scripts')
    @if($isParentType)
        <script>
            window.jQuery(function () {
                initializeParentForm();
            });
        </script>
    @endif
@endsection
