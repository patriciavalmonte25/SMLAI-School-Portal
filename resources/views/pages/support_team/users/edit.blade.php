@extends('layouts.master')
@section('page_title', 'Edit User')
@section('content')

    <div class="card">
        <div class="card-header header-elements-inline">
            <h6 class="card-title">Edit User Details</h6>
            <div class="ml-auto d-flex align-items-center">
                <button type="submit" form="edit-user-form" class="btn btn-primary">Save</button>
                {!! Qs::getPanelOptions() !!}
            </div>
        </div>

        <div class="card-body">
            @php
                $isAutoUsername = in_array($user->user_type, Qs::getStaff())
                    && !in_array($user->user_type, Qs::getTeamSA())
                    && !in_array($user->user_type, ['registrar', 'accountant'])
                    && !Qs::userIsRegistrar();
                $employmentDate = optional($user->staff->first())->emp_date;
            @endphp
            <form id="edit-user-form" method="post" enctype="multipart/form-data" class="ajax-update" action="{{ route('users.update', Qs::hash($user->id)) }}" data-fouc>
                @csrf @method('PUT')
                <h6>Personal Information</h6>
                <fieldset>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="user_type"> Select User: <span class="text-danger">*</span></label>
                                <select disabled="disabled" class="form-control select" id="user_type">
                                    <option value="">{{ strtoupper($user->user_type) }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    @if($user->user_type === 'parent')
                        <h6 class="font-weight-semibold">Parent Information</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Full Name:</label>
                                    <input value="{{ old('name', $user->name) }}" type="text" name="name" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Date of Birth:</label>
                                    <input value="{{ old('dob', $user->dob ? \Carbon\Carbon::parse($user->dob)->format('m/d/Y') : '') }}" type="text" name="dob" class="form-control date-pick" placeholder="MM/DD/YYYY" autocomplete="off" data-date-format="mm/dd/yyyy">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Gender:</label>
                                    <select class="form-control select" name="gender" data-placeholder="Choose...">
                                        <option value=""></option>
                                        @foreach($genders as $gender)
                                            <option value="{{ $gender }}" {{ old('gender', $user->gender) === $gender ? 'selected' : '' }}>{{ $gender }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email address:</label>
                                    <input value="{{ old('email', $user->email) }}" type="email" name="email" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Phone:</label>
                                    <input value="{{ old('phone', $user->phone) }}" type="text" name="phone" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Address:</label>
                                    <input value="{{ old('address', $user->address) }}" type="text" name="address" class="form-control">
                                </div>
                            </div>
                            @if($user->user_type !== 'teacher')
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Occupation:</label>
                                        <input value="{{ old('occupation', $user->occupation) }}" type="text" name="occupation" class="form-control">
                                    </div>
                                </div>
                            @endif
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Relationship to Children:</label>
                                    <input value="{{ old('relationship_to_children', $user->relationship_to_children) }}" type="text" name="relationship_to_children" class="form-control">
                                </div>
                            </div>
                        </div>

                        <h6 class="font-weight-semibold mt-3">Children</h6>
                        <input type="hidden" name="children_present" value="1">
                        @php
                            $oldParentChildren = old('children');
                            if (!is_array($oldParentChildren)) {
                                $oldParentChildren = $parent_children ?: [['class_id' => '', 'student_id' => '']];
                            }
                        @endphp
                        <div id="parent-children-wrapper" data-next-index="{{ count($oldParentChildren) }}">
                            @foreach($oldParentChildren as $index => $child)
                                <div class="row parent-child-item mb-2" data-index="{{ $index }}">
                                    <div class="col-md-5">
                                        <select name="children[{{ $index }}][class_id]" class="form-control parent-child-class select-search" data-placeholder="Choose class..." data-student-select="#parent-child-student-{{ $index }}">
                                            <option value=""></option>
                                            @foreach($my_classes as $class)
                                                <option value="{{ $class->id }}" {{ (string)($child['class_id'] ?? '') === (string)$class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <select id="parent-child-student-{{ $index }}" name="children[{{ $index }}][student_id]" class="form-control parent-child-student select-search" data-selected-student="{{ $child['student_id'] ?? '' }}" data-placeholder="Select student">
                                            <option value="">Select student</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-danger remove-parent-child">Remove</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" id="add-parent-child" class="btn btn-light mb-3">Add Child</button>
                    @endif

                    @if(!in_array($user->user_type, ['parent', 'student']))
                        <h6 class="font-weight-semibold">Profile Information</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Name:</label>
                                    <input value="{{ old('name', $user->name) }}" type="text" name="name" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Date of Birth:</label>
                                    <input value="{{ old('dob', $user->dob ? \Carbon\Carbon::parse($user->dob)->format('m/d/Y') : '') }}" type="text" name="dob" class="form-control date-pick" placeholder="MM/DD/YYYY" autocomplete="off" data-date-format="mm/dd/yyyy">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Gender:</label>
                                    <select class="form-control select" name="gender" data-placeholder="Choose...">
                                        <option value=""></option>
                                        @foreach($genders as $gender)
                                            <option value="{{ $gender }}" {{ old('gender', $user->gender) === $gender ? 'selected' : '' }}>{{ $gender }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Home Address:</label>
                                    <input value="{{ old('address', $user->address) }}" type="text" name="address" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email:</label>
                                    <input value="{{ old('email', $user->email) }}" type="email" name="email" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Phone:</label>
                                    <input value="{{ old('phone', $user->phone) }}" type="text" name="phone" class="form-control">
                                </div>
                            </div>
                            @if($user->user_type !== 'teacher')
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Occupation:</label>
                                        <input value="{{ old('occupation', $user->occupation) }}" type="text" name="occupation" class="form-control">
                                    </div>
                                </div>
                            @endif
                            @if(!in_array($user->user_type, ['administrator', 'teacher']))
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Telephone:</label>
                                        <input value="{{ old('phone2', $user->phone2) }}" type="text" name="phone2" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Blood Group:</label>
                                        <select class="form-control select" name="bg_id" data-placeholder="Choose...">
                                            <option value=""></option>
                                            @foreach($blood_groups as $bloodGroup)
                                                <option value="{{ $bloodGroup->id }}" {{ (string)old('bg_id', $user->bg_id) === (string)$bloodGroup->id ? 'selected' : '' }}>{{ $bloodGroup->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Nationality:</label>
                                        <select class="form-control select-search" name="nal_id" data-placeholder="Choose...">
                                            <option value=""></option>
                                            @foreach($nationals as $nationality)
                                                <option value="{{ $nationality->id }}" {{ (string)old('nal_id', $user->nal_id) === (string)$nationality->id ? 'selected' : '' }}>{{ $nationality->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>State:</label>
                                        <select class="form-control select-search" name="state_id" data-placeholder="Choose...">
                                            <option value=""></option>
                                            @foreach($states ?? [] as $state)
                                                <option value="{{ $state->id }}" {{ (string)old('state_id', $user->state_id) === (string)$state->id ? 'selected' : '' }}>{{ $state->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>LGA:</label>
                                        <select class="form-control select-search" name="lga_id" data-placeholder="Choose...">
                                            <option value=""></option>
                                            @foreach($states ?? [] as $state)
                                                @foreach($lgas->where('state_id', $state->id) as $lga)
                                                    <option value="{{ $lga->id }}" {{ (string)old('lga_id', $user->lga_id) === (string)$lga->id ? 'selected' : '' }}>{{ $lga->name }}</option>
                                                @endforeach
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                @if($user->user_type === 'accountant' || $user->user_type === 'registrar')
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Date of Employment:</label>
                                            <input value="{{ old('emp_date', $employmentDate ? \Carbon\Carbon::parse($employmentDate)->format('m/d/Y') : '') }}" type="text" name="emp_date" class="form-control date-pick" placeholder="MM/DD/YYYY" autocomplete="off" data-date-format="mm/dd/yyyy">
                                        </div>
                                    </div>
                                @endif
                            @endif
                        </div>

                        @if($user->user_type === 'administrator')
                            @php
                                $userRoles = old('roles', $user->roles ?: ['']);
                            @endphp
                            <div class="form-group">
                                <label class="d-block">Role/s:</label>
                                <div id="edit-roles-wrapper">
                                    @foreach($userRoles as $role)
                                        <div class="input-group mb-2 edit-role-item">
                                            <input type="text" name="roles[]" class="form-control" value="{{ $role }}" placeholder="Enter role">
                                            <div class="input-group-append"><button type="button" class="btn btn-danger remove-edit-role" {{ $loop->first ? 'style=display:none;' : '' }}>Remove</button></div>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="button" id="add-edit-role" class="btn btn-light btn-sm">Add Role</button>
                            </div>
                        @endif

                        @if($user->user_type === 'teacher')
                            <div class="form-group">
                                <label for="adviser_class_id">Adviser</label>
                                <select name="adviser_class_id" id="adviser_class_id" class="form-control select-search" data-placeholder="None">
                                    <option value="">None</option>
                                    @foreach($my_classes as $class)
                                        <option value="{{ $class->id }}" {{ (string) old('adviser_class_id', $adviser_class_id) === (string) $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    @endif

                    <div class="row">
                        @if(in_array($user->user_type, Qs::getStaff()))
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Date of Employment:</label>
                                            <input autocomplete="off" name="emp_date" value="{{ old('emp_date', $employmentDate ? \Carbon\Carbon::parse($employmentDate)->format('m/d/Y') : '') }}" type="text" class="form-control date-pick" placeholder="MM/DD/YYYY" data-date-format="mm/dd/yyyy">

                                </div>
                            </div>
                        @endif

                    </div>

                    @if($user->user_type === 'student')
                        <div class="row mb-2">
                            <div class="col-12">
                                <h6 class="font-weight-semibold">Enrollment Information</h6>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <h6 class="font-weight-semibold">1. FULL NAME</h6>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Surname:</label>
                                    <input value="{{ old('surname', $student_enrollment->surname) }}" type="text" name="surname" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>First Name:</label>
                                    <input value="{{ old('first_name', $student_enrollment->first_name) }}" type="text" name="first_name" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Middle Name:</label>
                                    <input value="{{ old('middle_name', $student_enrollment->middle_name) }}" type="text" name="middle_name" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <h6 class="font-weight-semibold">2. DATE OF BIRTH (MM/DD/YYYY)</h6>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <input name="dob" value="{{ old('dob', $student_enrollment->dob ? \Carbon\Carbon::parse($student_enrollment->dob)->format('m/d/Y') : '') }}" type="text" class="form-control date-pick" placeholder="MM/DD/YYYY" autocomplete="off" data-date-format="mm/dd/yyyy">
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <h6 class="font-weight-semibold">3. PLACE OF BIRTH</h6>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <input value="{{ old('place_of_birth_town', $student_enrollment->place_of_birth_town) }}" type="text" name="place_of_birth_town" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <h6 class="font-weight-semibold">4. GENDER</h6>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group d-flex align-items-center">
                                    <div class="form-check form-check-inline">
                                        <label class="form-check-label">
                                            <input class="form-check-input" type="radio" name="gender" value="Male" {{ old('gender', $student_enrollment->gender) == 'Male' ? 'checked' : '' }}> MALE
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <label class="form-check-label">
                                            <input class="form-check-input" type="radio" name="gender" value="Female" {{ old('gender', $student_enrollment->gender) == 'Female' ? 'checked' : '' }}> FEMALE
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <h6 class="font-weight-semibold">5. RELIGION</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <input value="{{ old('religion', $student_enrollment->religion) }}" type="text" name="religion" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <h6 class="font-weight-semibold">6. COMPLETE HOME ADDRESS</h6>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <input value="{{ old('home_town', $student_enrollment->home_town) }}" type="text" name="home_town" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <h6 class="font-weight-semibold">7. FATHER'S NAME & OCCUPATION</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Father's Name:</label>
                                    <input value="{{ old('father_name', $student_enrollment->father_name) }}" type="text" name="father_name" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Father's Occupation:</label>
                                    <input value="{{ old('father_occupation', $student_enrollment->father_occupation) }}" type="text" name="father_occupation" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <h6 class="font-weight-semibold">8. MOTHER'S NAME & OCCUPATION</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Mother's Name:</label>
                                    <input value="{{ old('mother_name', $student_enrollment->mother_name) }}" type="text" name="mother_name" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Mother's Occupation:</label>
                                    <input value="{{ old('mother_occupation', $student_enrollment->mother_occupation) }}" type="text" name="mother_occupation" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <h6 class="font-weight-semibold">9. GUARDIAN'S NAME & OCCUPATION</h6>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Guardian Name:</label>
                                    <input value="{{ old('guardian_name', $student_enrollment->guardian_name) }}" type="text" name="guardian_name" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Guardian Occupation:</label>
                                    <input value="{{ old('guardian_occupation', $student_enrollment->guardian_occupation) }}" type="text" name="guardian_occupation" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Relation to Student:</label>
                                    <input value="{{ old('guardian_relation', $student_enrollment->guardian_relation) }}" type="text" name="guardian_relation" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <h6 class="font-weight-semibold">10. CONTACT NUMBER</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <input value="{{ old('contact_number', $student_enrollment->contact_number) }}" type="text" name="contact_number" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <h6 class="font-weight-semibold">11. NAME OF LAST SCHOOL ATTENDED</h6>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Name of Last School Attended:</label>
                                    <input value="{{ old('last_school_name', $student_enrollment->last_school_name) }}" type="text" name="last_school_name" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>School Type:</label>
                                    <div class="d-flex align-items-center">
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                <input class="form-check-input" type="radio" name="last_school_type" value="PRIVATE" {{ old('last_school_type', $student_enrollment->last_school_type) == 'PRIVATE' ? 'checked' : '' }}> PRIVATE
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                <input class="form-check-input" type="radio" name="last_school_type" value="PUBLIC" {{ old('last_school_type', $student_enrollment->last_school_type) == 'PUBLIC' ? 'checked' : '' }}> PUBLIC
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <h6 class="font-weight-semibold">12. ADDRESS OF LAST SCHOOL ATTENDED</h6>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <input value="{{ old('last_school_address', $student_enrollment->last_school_address) }}" type="text" name="last_school_address" class="form-control">
                                </div>
                            </div>
                        </div>
                    @endif

                </fieldset>

                <fieldset>
                    @if($user->user_type === 'student')
                        <div class="row mb-3">
                            <div class="col-12">
                                <h6 class="font-weight-semibold">Student Data</h6>
                            </div>
                        </div>

                        @php
                            $gradeOptions = [
                                'Kinder',
                                'Grade 1',
                                'Grade 2',
                                'Grade 3',
                                'Grade 4',
                                'Grade 5',
                                'Grade 6',
                                'Grade 7',
                                'Grade 8',
                                'Grade 9',
                                'Grade 10',
                                'Grade 11',
                                'Grade 12',
                            ];
                            $currentGrade = old('student_grade', optional($student_enrollment)->student_grade ?? optional($student_enrollment)->grade_enrolling);
                            $currentClassId = old('my_class_id', optional($student_record)->my_class_id ?? optional($student_enrollment)->my_class_id);
                        @endphp

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="student_grade">Grade: <span class="text-danger">*</span></label>
                                    <select onchange="togglePathwayField();" required class="form-control select" name="student_grade" id="student_grade" data-placeholder="Choose..">
                                        <option value=""></option>
                                        @foreach($gradeOptions as $gradeName)
                                            <option {{ ($currentGrade == $gradeName) ? 'selected' : '' }} value="{{ $gradeName }}">{{ $gradeName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="my_class_id">Section: <span class="text-danger">*</span></label>
                                    <select required class="form-control select" name="my_class_id" id="my_class_id" data-placeholder="Choose..">
                                        <option value=""></option>
                                        @foreach($my_classes as $c)
                                            <option {{ ($currentClassId == $c->id) ? 'selected' : '' }} value="{{ $c->id }}">{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4" id="pathway_wrap" style="display: none;">
                                <div class="form-group">
                                    <label for="pathway">Pathway:</label>
                                    <select class="select-search form-control" id="pathway" name="pathway" data-placeholder="Choose..">
                                        <option value=""></option>
                                        @foreach(['Arts and Social Sciences', 'Accountancy and Entrepreneurship', 'Architecture and Engineering', 'Healthcare and Safety'] as $pathway)
                                            <option {{ old('pathway', optional($student_record)->pathway ?? optional($student_enrollment)->pathway) === $pathway ? 'selected' : '' }} value="{{ $pathway }}">{{ $pathway }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="student_id">Student ID:</label>
                                    <input value="{{ old('student_id', optional($student_enrollment)->student_id) }}" type="text" name="student_id" id="student_id" class="form-control">
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="row mb-3 mt-2">
                        <div class="col-12">
                            <h6 class="font-weight-semibold">Account Data</h6>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="username">Username:</label>
                                <input value="{{ old('username', $user->username) }}" {{ $isAutoUsername ? 'disabled' : '' }} type="text" name="username" id="username" class="form-control">
                                @if($isAutoUsername)
                                    <span class="form-text text-muted">Automatically generated from the employment date.</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="password">Password:</label>
                                <input value="{{ old('password', $user->user_type === 'student' ? (optional($student_enrollment)->password ?: '') : $display_password) }}" type="text" name="password" id="password" class="form-control" placeholder="Leave blank when unavailable to keep unchanged">
                            </div>
                        </div>
                    </div>
                </fieldset>

            </form>
        </div>

    </div>

    @if($user->user_type === 'parent')
        <script>
            (function () {
                const wrapper = document.getElementById('parent-children-wrapper');
                const addButton = document.getElementById('add-parent-child');
                if (!wrapper || !addButton) {
                    return;
                }

                const classStudentEndpoint = '{{ route('get_class_students', '__CLASS__') }}';

                const buildRow = function (index) {
                    return '<div class="row parent-child-item mb-2" data-index="' + index + '">'
                        + '<div class="col-md-5">'
                        + '<select name="children[' + index + '][class_id]" class="form-control parent-child-class select-search" data-placeholder="Choose class..." data-student-select="#parent-child-student-' + index + '">'
                        + '<option value=""></option>'
                        + '@foreach($my_classes as $class)'
                        + '<option value="{{ $class->id }}">{{ addslashes($class->name) }}</option>'
                        + '@endforeach'
                        + '</select>'
                        + '</div>'
                        + '<div class="col-md-5">'
                        + '<select id="parent-child-student-' + index + '" name="children[' + index + '][student_id]" class="form-control parent-child-student select-search" data-placeholder="Select student">'
                        + '<option value="">Select student</option>'
                        + '</select>'
                        + '</div>'
                        + '<div class="col-md-2"><button type="button" class="btn btn-danger remove-parent-child">Remove</button></div>'
                        + '</div>';
                };

                const resetStudentSelect = function (select, placeholder) {
                    select.innerHTML = '';
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = placeholder;
                    select.appendChild(option);
                };

                const loadStudents = function (classSelect, selectedStudent) {
                    const selector = classSelect.getAttribute('data-student-select');
                    const studentSelect = selector ? document.querySelector(selector) : null;
                    if (!studentSelect) {
                        return;
                    }

                    if (!classSelect.value) {
                        resetStudentSelect(studentSelect, 'Select student');
                        $(studentSelect).trigger('change');
                        return;
                    }

                    resetStudentSelect(studentSelect, 'Loading students...');
                    $(studentSelect).trigger('change');
                    fetch(classStudentEndpoint.replace('__CLASS__', classSelect.value))
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
                                option.selected = selectedStudent && selectedStudent === student.id;
                                studentSelect.appendChild(option);
                            });
                            $(studentSelect).trigger('change');
                        })
                        .catch(function () {
                            resetStudentSelect(studentSelect, 'Unable to load students');
                            $(studentSelect).trigger('change');
                        });
                };

                addButton.addEventListener('click', function () {
                    const index = parseInt(wrapper.getAttribute('data-next-index'), 10) || 0;
                    wrapper.insertAdjacentHTML('beforeend', buildRow(index));
                    wrapper.setAttribute('data-next-index', String(index + 1));
                    $(wrapper.querySelectorAll('.select-search')).select2();
                });

                $(wrapper).on('change', '.parent-child-class', function () {
                    loadStudents(this, null);
                });

                wrapper.addEventListener('click', function (event) {
                    if (!event.target.classList.contains('remove-parent-child')) {
                        return;
                    }

                    const rows = wrapper.querySelectorAll('.parent-child-item');
                    if (rows.length === 1) {
                        $(rows[0].querySelectorAll('.select-search')).each(function () {
                            if ($(this).hasClass('select2-hidden-accessible')) {
                                $(this).select2('destroy');
                            }
                        });
                        rows[0].remove();
                        return;
                    }

                    $(event.target.closest('.parent-child-item').querySelectorAll('.select-search')).each(function () {
                        if ($(this).hasClass('select2-hidden-accessible')) {
                            $(this).select2('destroy');
                        }
                    });
                    event.target.closest('.parent-child-item').remove();
                });

                wrapper.querySelectorAll('.parent-child-class').forEach(function (classSelect) {
                    const studentSelect = document.querySelector(classSelect.getAttribute('data-student-select'));
                    if (classSelect.value) {
                        loadStudents(classSelect, studentSelect ? studentSelect.getAttribute('data-selected-student') : null);
                    }
                });
            })();
        </script>
    @endif

    @if(in_array($user->user_type, ['administrator', 'teacher']))
        <script>
            (function () {
                const wrapper = document.getElementById('edit-roles-wrapper');
                const addButton = document.getElementById('add-edit-role');
                if (!wrapper || !addButton) {
                    return;
                }

                addButton.addEventListener('click', function () {
                    const row = document.createElement('div');
                    row.className = 'input-group mb-2 edit-role-item';
                    row.innerHTML = '<input type="text" name="roles[]" class="form-control" placeholder="Enter role">'
                        + '<div class="input-group-append"><button type="button" class="btn btn-danger remove-edit-role">Remove</button></div>';
                    wrapper.appendChild(row);
                });

                wrapper.addEventListener('click', function (event) {
                    if (!event.target.classList.contains('remove-edit-role')) {
                        return;
                    }
                    const rows = wrapper.querySelectorAll('.edit-role-item');
                    if (rows.length === 1) {
                        rows[0].querySelector('input[name="roles[]"]').value = '';
                        return;
                    }
                    event.target.closest('.edit-role-item').remove();
                });
            })();
        </script>
    @endif

    <script>
        function togglePathwayField(){
            var sel = document.getElementById('student_grade');
            var wrap = document.getElementById('pathway_wrap');
            if(!sel || !wrap) return;

            var val = sel.value;
            var shouldShow = val === 'Grade 11' || val === 'Grade 12';

            if(shouldShow){
                wrap.style.display = '';
            } else {
                wrap.style.display = 'none';
                var pathway = document.getElementById('pathway');
                if(pathway) { pathway.value = ''; }
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            togglePathwayField();
            var grade = document.getElementById('student_grade');
            if(grade) {
                $(grade).on('change', togglePathwayField);
            }
        });
    </script>
@endsection
