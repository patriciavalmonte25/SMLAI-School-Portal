@extends('layouts.master')
@section('page_title', 'My Profile')
@section('content')

    @if(Auth::user()->user_type !== 'enroll')
    <div class="row">
        <div class="col-md-3 text-center">
            <div class="card">
                <div class="card-body">
                    <h3 class="mt-3">{{ $my->name }}</h3>
                    <div class="text-muted">{{ ucwords(str_replace('_', ' ', $my->user_type)) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card">
                <div class="card-header header-elements-inline">
                    <h6 class="card-title">My Profile</h6>
                    {!! Qs::getPanelOptions() !!}
                </div>

                <div class="card-body">
                    <ul class="nav nav-tabs nav-tabs-highlight">
                        <li class="nav-item"><a href="#profile-info" class="nav-link active" data-toggle="tab"><i class="icon-user-plus"></i> My Profile</a></li>
                        <li class="nav-item"><a href="#change-pass" class="nav-link" data-toggle="tab">Change Password</a></li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="profile-info">
                            <table class="table table-bordered">
                                <tbody>
                                @if($my->user_type === 'student')
                                    <tr><td class="font-weight-bold">Name</td><td>{{ $my->name }}</td></tr>
                                    @if($student_enrollment)
                                        <tr><td class="font-weight-bold">Surname</td><td>{{ $student_enrollment->surname }}</td></tr>
                                        <tr><td class="font-weight-bold">First Name</td><td>{{ $student_enrollment->first_name }}</td></tr>
                                        <tr><td class="font-weight-bold">Middle Name</td><td>{{ $student_enrollment->middle_name }}</td></tr>
                                    @endif
                                    <tr><td class="font-weight-bold">Date of Birth</td><td>{{ optional($student_enrollment)->dob ?: $my->dob }}</td></tr>
                                    @if($student_enrollment)
                                        <tr><td class="font-weight-bold">Place of Birth</td><td>{{ $student_enrollment->place_of_birth_town }}</td></tr>
                                        <tr><td class="font-weight-bold">Religion</td><td>{{ $student_enrollment->religion }}</td></tr>
                                        <tr><td class="font-weight-bold">Home Address</td><td>{{ $student_enrollment->home_town }}</td></tr>
                                        <tr><td class="font-weight-bold">Father's Name</td><td>{{ $student_enrollment->father_name }}</td></tr>
                                        <tr><td class="font-weight-bold">Father's Occupation</td><td>{{ $student_enrollment->father_occupation }}</td></tr>
                                        <tr><td class="font-weight-bold">Mother's Name</td><td>{{ $student_enrollment->mother_name }}</td></tr>
                                        <tr><td class="font-weight-bold">Mother's Occupation</td><td>{{ $student_enrollment->mother_occupation }}</td></tr>
                                        <tr><td class="font-weight-bold">Guardian's Name</td><td>{{ $student_enrollment->guardian_name }}</td></tr>
                                        <tr><td class="font-weight-bold">Guardian's Occupation</td><td>{{ $student_enrollment->guardian_occupation }}</td></tr>
                                        <tr><td class="font-weight-bold">Guardian's Relation</td><td>{{ $student_enrollment->guardian_relation }}</td></tr>
                                        <tr><td class="font-weight-bold">Contact Number</td><td>{{ $student_enrollment->contact_number }}</td></tr>
                                    @endif
                                    <tr><td class="font-weight-bold">Class</td><td>{{ optional(optional($student_record)->my_class)->name ?: optional(optional($student_enrollment)->my_class)->name }}</td></tr>
                                    <tr><td class="font-weight-bold">Section</td><td>{{ optional(optional($student_record)->section)->name ?: optional(optional($student_enrollment)->section)->name }}</td></tr>
                                    @if($student_enrollment)
                                        <tr><td class="font-weight-bold">Grade Level</td><td>{{ $student_enrollment->student_grade ?: $student_enrollment->grade_enrolling }}</td></tr>
                                        @if(in_array($student_enrollment->student_grade ?: $student_enrollment->grade_enrolling, ['Grade 11', 'Grade 12'], true))
                                            <tr><td class="font-weight-bold">Pathway</td><td>{{ $student_enrollment->pathway }}</td></tr>
                                        @endif
                                        <tr><td class="font-weight-bold">Student ID</td><td>{{ $student_enrollment->student_id }}</td></tr>
                                    @endif
                                    @if($student_record)
                                        <tr><td class="font-weight-bold">ADM_NO</td><td>{{ $student_record->adm_no }}</td></tr>
                                        <tr><td class="font-weight-bold">Year Admitted</td><td>{{ $student_record->year_admitted }}</td></tr>
                                    @endif
                                    <tr><td class="font-weight-bold">Gender</td><td>{{ $my->gender }}</td></tr>
                                    <tr><td class="font-weight-bold">Email</td><td>{{ $my->email }}</td></tr>
                                    <tr><td class="font-weight-bold">Phone</td><td>{{ $my->phone }}</td></tr>
                                    <tr><td class="font-weight-bold">Address</td><td>{{ $my->address }}</td></tr>
                                    <tr><td class="font-weight-bold">Username</td><td>{{ $my->username }}</td></tr>
                                @elseif($my->user_type === 'parent')
                                    <tr><td class="font-weight-bold">Name</td><td>{{ $my->name }}</td></tr>
                                    <tr><td class="font-weight-bold">Date of Birth</td><td>{{ $my->dob }}</td></tr>
                                    <tr><td class="font-weight-bold">Gender</td><td>{{ $my->gender }}</td></tr>
                                    <tr><td class="font-weight-bold">Email</td><td>{{ $my->email }}</td></tr>
                                    <tr><td class="font-weight-bold">Phone</td><td>{{ $my->phone }}</td></tr>
                                    <tr><td class="font-weight-bold">Address</td><td>{{ $my->address }}</td></tr>
                                    <tr><td class="font-weight-bold">Occupation</td><td>{{ $my->occupation }}</td></tr>
                                    <tr><td class="font-weight-bold">Relationship to Children</td><td>{{ $my->relationship_to_children }}</td></tr>
                                    <tr>
                                        <td class="font-weight-bold">Children</td>
                                        <td>
                                            @if($parent_children->isNotEmpty())
                                                <ul class="mb-0 pl-3">
                                                    @foreach($parent_children as $child)
                                                        <li>
                                                            @if($child->student_record)
                                                                <a href="{{ route('students.show', Qs::hash($child->student_record->id)) }}">{{ $child->name }}</a>
                                                            @else
                                                                <a href="{{ route('users.show', Qs::hash($child->id)) }}">{{ $child->name }}</a>
                                                            @endif
                                                            - {{ optional(optional($child->student_record)->my_class)->name ?: (optional(optional($child->student_enrollment)->my_class)->name ?: 'No class') }}
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <span class="text-muted">No children linked</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr><td class="font-weight-bold">Username</td><td>{{ $my->username }}</td></tr>
                                @else
                                    <tr><td class="font-weight-bold">Name</td><td>{{ $my->name }}</td></tr>
                                    <tr><td class="font-weight-bold">Date of Birth</td><td>{{ $my->dob }}</td></tr>
                                    <tr><td class="font-weight-bold">Gender</td><td>{{ $my->gender }}</td></tr>
                                    <tr><td class="font-weight-bold">Address</td><td>{{ $my->address }}</td></tr>
                                    @if($my->user_type !== 'teacher' && $my->occupation)<tr><td class="font-weight-bold">Occupation</td><td>{{ $my->occupation }}</td></tr>@endif
                                    @if($my->email)<tr><td class="font-weight-bold">Email</td><td>{{ $my->email }}</td></tr>@endif
                                    @if($my->username)<tr><td class="font-weight-bold">Username</td><td>{{ $my->username }}</td></tr>@endif
                                    @if($my->phone)<tr><td class="font-weight-bold">Phone</td><td>{{ $my->phone.' '.$my->phone2 }}</td></tr>@endif
                                    @if($my->user_type === 'teacher')
                                        <tr><td class="font-weight-bold">My Subjects</td><td>@foreach(Qs::findTeacherSubjects($my->id) as $subject)<span>{{ $subject->name.' ('.$subject->my_class->name.')' }}</span><br>@endforeach</td></tr>
                                    @endif
                                    @if($my->user_type !== 'teacher' && is_array($my->roles) && count($my->roles))
                                        <tr><td class="font-weight-bold">Roles</td><td><ul class="mb-0">@foreach($my->roles as $role)<li>{{ $role }}</li>@endforeach</ul></td></tr>
                                    @endif
                                @endif
                                @if($my->bg_id)
                                    <tr>
                                        <td class="font-weight-bold">Blood Group</td>
                                        <td>{{ optional($my->blood_group)->name }}</td>
                                    </tr>
                                @endif
                                @if($my->nal_id)
                                    <tr>
                                        <td class="font-weight-bold">Nationality</td>
                                        <td>{{ optional($my->nationality)->name }}</td>
                                    </tr>
                                @endif
                                @if($my->state_id)
                                    <tr>
                                        <td class="font-weight-bold">State</td>
                                        <td>{{ optional($my->state)->name }}</td>
                                    </tr>
                                @endif
                                @if($my->lga_id)
                                    <tr>
                                        <td class="font-weight-bold">LGA</td>
                                        <td>{{ optional($my->lga)->name }}</td>
                                    </tr>
                                @endif
                                </tbody>
                            </table>
                        </div>

                        <div class="tab-pane fade" id="change-pass">
                            <div class="row">
                                <div class="col-md-8">
                                    <form method="post" action="{{ route('my_account.change_pass') }}">
                                        @csrf @method('put')

                                        <div class="form-group row">
                                            <label for="current_password" class="col-lg-3 col-form-label font-weight-semibold">Current Password <span class="text-danger">*</span></label>
                                            <div class="col-lg-9">
                                                <input id="current_password" name="current_password"  required type="password" class="form-control" >
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="password" class="col-lg-3 col-form-label font-weight-semibold">New Password <span class="text-danger">*</span></label>
                                            <div class="col-lg-9">
                                                <input id="password" name="password"  required type="password" class="form-control" >
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="password_confirmation" class="col-lg-3 col-form-label font-weight-semibold">Confirm Password <span class="text-danger">*</span></label>
                                            <div class="col-lg-9">
                                                <input id="password_confirmation" name="password_confirmation"  required type="password" class="form-control" >
                                            </div>
                                        </div>

                                        <div class="text-right">
                                            <button type="submit" class="btn btn-danger">Submit form <i class="icon-paperplane ml-2"></i></button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{--My Profile Ends--}}

@endsection
