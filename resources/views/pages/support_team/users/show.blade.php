@extends('layouts.master')
@section('page_title', 'User Profile - '.$user->name)
@section('content')
    <div class="row">
        <div class="col-md-3 text-center">
            <div class="card">
                @if(Qs::userIsRegistrar())
                    <div class="card-header text-center justify-content-center">
                        <a href="{{ route('users.edit', Qs::hash($user->id)) }}" class="btn btn-primary btn-sm"><i class="icon-pencil mr-2"></i>Edit</a>
                    </div>
                @endif
                <div class="card-body">
                    <h3 class="mt-3" style="overflow-wrap: break-word; word-break: normal;">{{ $user->name }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card">
                <div class="card-body">
                    <div>
                        @if($user->user_type === 'student')
                            <table class="table table-bordered">
                                <tbody>
                                @if($student_enrollment)
                                    <tr><td class="font-weight-bold">Surname</td><td>{{ $student_enrollment->surname }}</td></tr>
                                    <tr><td class="font-weight-bold">First Name</td><td>{{ $student_enrollment->first_name }}</td></tr>
                                    <tr><td class="font-weight-bold">Middle Name</td><td>{{ $student_enrollment->middle_name }}</td></tr>
                                @endif
                                <tr><td class="font-weight-bold">Date of Birth</td><td>{{ optional($student_enrollment)->dob ?: $user->dob }}</td></tr>
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
                                <tr><td class="font-weight-bold">Section</td><td>{{ optional(optional($student_record)->my_class)->name ?: optional(optional($student_enrollment)->my_class)->name }}</td></tr>
                                @if($student_enrollment)
                                    <tr><td class="font-weight-bold">Grade Level</td><td>{{ $student_enrollment->student_grade ?: $student_enrollment->grade_enrolling }}</td></tr>
                                    @if(in_array($student_enrollment->student_grade ?: $student_enrollment->grade_enrolling, ['Grade 11', 'Grade 12'], true))
                                        <tr><td class="font-weight-bold">Pathway</td><td>{{ $student_enrollment->pathway }}</td></tr>
                                    @endif
                                    <tr><td class="font-weight-bold">Student ID</td><td>{{ $student_enrollment->student_id }}</td></tr>
                                @endif
                                @if($can_view_parent_links)
                                    <tr>
                                        <td class="font-weight-bold">Parents/Guardians</td>
                                        <td>
                                            @if($parents_guardians->isNotEmpty())
                                                <ul class="mb-0 pl-3">
                                                    @foreach($parents_guardians as $guardian)
                                                        <li><a class="font-weight-semibold text-primary" href="{{ route('users.show', Qs::hash($guardian->id)) }}">{{ $guardian->name }}</a></li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <span class="text-muted">No parents/guardians linked</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                                <tr><td class="font-weight-bold">Username</td><td>{{ $user->username }}</td></tr>
                                @unless(Qs::userIsParent())
                                <tr><td class="font-weight-bold">Password</td><td>{{ optional($student_enrollment)->password ?: '••••••••' }}</td></tr>
                                @endunless

                                </tbody>
                            </table>
                        @elseif($user->user_type === 'parent')
                            <table class="table table-bordered">
                                <tbody>
                                <tr><td class="font-weight-bold">Name</td><td>{{ $user->name }}</td></tr>
                                <tr><td class="font-weight-bold">Date of Birth</td><td>{{ $user->dob }}</td></tr>
                                <tr><td class="font-weight-bold">Gender</td><td>{{ $user->gender }}</td></tr>
                                <tr><td class="font-weight-bold">Email</td><td>{{ $user->email }}</td></tr>
                                <tr><td class="font-weight-bold">Phone</td><td>{{ $user->phone }}</td></tr>
                                <tr><td class="font-weight-bold">Address</td><td>{{ $user->address }}</td></tr>
                                <tr><td class="font-weight-bold">Occupation</td><td>{{ $user->occupation }}</td></tr>
                                <tr><td class="font-weight-bold">Relationship to Children</td><td>{{ $user->relationship_to_children }}</td></tr>
                                <tr>
                                    <td class="font-weight-bold">Children</td>
                                    <td>
                                        @if($parent_children->isNotEmpty())
                                            <ul class="mb-0 pl-3">
                                                @foreach($parent_children as $child)
                                                <li>
                                                    @if($child->student_record)
                                                        <a class="font-weight-semibold text-primary" href="{{ route('students.show', Qs::hash($child->student_record->id)) }}">{{ $child->name }}</a>
                                                    @else
                                                        <a class="font-weight-semibold text-primary" href="{{ route('users.show', Qs::hash($child->id)) }}">{{ $child->name }}</a>
                                                    @endif
                                                    - {{ optional(optional($child->student_record)->my_class)->name ?: (optional(optional($child->student_enrollment)->my_class)->name ?: 'No class') }} / {{ optional(optional($child->student_record)->section)->name ?: (optional(optional($child->student_enrollment)->section)->name ?: 'No section') }}
                                                </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="text-muted">No children linked</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr><td class="font-weight-bold">Username</td><td>{{ $user->username }}</td></tr>
                                <tr><td class="font-weight-bold">Password</td><td>{{ $display_password ?: 'Unavailable - reset password to set a displayable password' }}</td></tr>
                                </tbody>
                            </table>
                        @else
                            <table class="table table-bordered">
                                <tbody>
                                <tr><td class="font-weight-bold">Name</td><td>{{ $user->name }}</td></tr>
                                <tr><td class="font-weight-bold">Date of Birth</td><td>{{ $user->dob }}</td></tr>
                                <tr><td class="font-weight-bold">Gender</td><td>{{ $user->gender }}</td></tr>
                                <tr><td class="font-weight-bold">Address</td><td>{{ $user->address }}</td></tr>
                                @if($user->user_type === 'student' && $can_view_parent_links)
                                    <tr>
                                        <td class="font-weight-bold">Parents/Guardians</td>
                                        <td>
                                            @if($parents_guardians->isNotEmpty())
                                                <ul class="mb-0 pl-3">
                                                    @foreach($parents_guardians as $guardian)
                                                        <li><a class="font-weight-semibold text-primary" href="{{ route('users.show', Qs::hash($guardian->id)) }}">{{ $guardian->name }}</a></li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <span class="text-muted">No parents/guardians linked</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                                @if($user->user_type !== 'teacher' && $user->occupation)<tr><td class="font-weight-bold">Occupation</td><td>{{ $user->occupation }}</td></tr>@endif
                                @if($user->email)<tr><td class="font-weight-bold">Email</td><td>{{ $user->email }}</td></tr>@endif
                                @if($user->username)<tr><td class="font-weight-bold">Username</td><td>{{ $user->username }}</td></tr>@endif
                                @if($user->phone)<tr><td class="font-weight-bold">Phone</td><td>{{ $user->phone.' '.$user->phone2 }}</td></tr>@endif
                                @if($user->user_type !== 'teacher' && is_array($user->roles) && count($user->roles))
                                    <tr>
                                        <td class="font-weight-bold">Roles</td>
                                        <td><ul class="mb-0">@foreach($user->roles as $role)<li>{{ $role }}</li>@endforeach</ul></td>
                                    </tr>
                                @endif
                                <tr><td class="font-weight-bold">Password</td><td>{{ $display_password ?: 'Unavailable - reset password to set a displayable password' }}</td></tr>
                                @if($user->bg_id)<tr><td class="font-weight-bold">Blood Group</td><td>{{ $user->blood_group->name }}</td></tr>@endif
                                @if($user->nal_id)<tr><td class="font-weight-bold">Nationality</td><td>{{ $user->nationality->name }}</td></tr>@endif
                                @if($user->state_id)<tr><td class="font-weight-bold">State</td><td>{{ $user->state->name }}</td></tr>@endif
                                @if($user->lga_id)<tr><td class="font-weight-bold">LGA</td><td>{{ $user->lga->name }}</td></tr>@endif
                                @if($user->user_type == 'teacher')
                                    <tr><td class="font-weight-bold">My Subjects</td><td>@foreach(Qs::findTeacherSubjects($user->id) as $sub)<span> - {{ $sub->name.' ('.$sub->my_class->name.')' }}</span><br>@endforeach</td></tr>
                                @endif
                    </div>
                </div>
                        @endif
        </div>
    </div>


    {{--User Profile Ends--}}

@endsection
