@extends('layouts.master')
@section('page_title', 'Enrollment')
@section('content')
    @php($activeTab = in_array(request('tab'), ['form', 'pending', 'inbox'], true) ? request('tab') : 'form')

    <div class="card">
        <div class="card-header bg-white header-elements-inline">
            <h6 class="card-title">Enrollment</h6>
            {!! Qs::getPanelOptions() !!}
        </div>
        <div class="card-body">
            @if (isset($errors) && $errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0 pl-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <ul class="nav nav-tabs nav-tabs-highlight">
                <li class="nav-item"><a href="#parent-enrollment-form" class="nav-link {{ $activeTab === 'form' ? 'active' : '' }}" data-toggle="tab">New Enrollment</a></li>
                <li class="nav-item"><a href="#parent-enrollment-pending" class="nav-link {{ $activeTab === 'pending' ? 'active' : '' }}" data-toggle="tab">Pending Submissions</a></li>
                <li class="nav-item"><a href="#parent-enrollment-inbox" class="nav-link {{ $activeTab === 'inbox' ? 'active' : '' }}" data-toggle="tab">Inbox</a></li>
            </ul>

            <div class="tab-content pt-3">
                <div class="tab-pane fade {{ $activeTab === 'form' ? 'show active' : '' }}" id="parent-enrollment-form">
                    <p class="text-muted">Please complete the form below to begin the enrollment process.</p>
                    <form method="POST" action="{{ route('enrollment_form.store') }}">
                        @csrf
                        <div class="row">
                                    <div class="col-md-4 form-group"><label for="surname">Surname</label><input type="text" name="surname" id="surname" value="{{ old('surname') }}" class="form-control" required></div>
                                    <div class="col-md-4 form-group"><label for="first_name">First Name</label><input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" class="form-control" required></div>
                                    <div class="col-md-4 form-group"><label for="middle_name">Middle Name</label><input type="text" name="middle_name" id="middle_name" value="{{ old('middle_name') }}" class="form-control"></div>
                        </div>
                        <div class="row">
                                    <div class="col-md-4 form-group"><label for="dob">Date of Birth</label><input type="text" name="dob" id="dob" value="{{ old('dob') }}" class="form-control date-pick" placeholder="MM/DD/YYYY" autocomplete="off" data-date-format="mm/dd/yyyy" required></div>
                                    <div class="col-md-4 form-group"><label for="place_of_birth_town">Place of Birth</label><input type="text" name="place_of_birth_town" id="place_of_birth_town" value="{{ old('place_of_birth_town') }}" class="form-control" required></div>
                                    <div class="col-md-4 form-group"><label>Gender</label><div class="pt-2"><label class="mr-3"><input type="radio" name="gender" value="Male" {{ old('gender') == 'Male' ? 'checked' : '' }} required> Male</label><label><input type="radio" name="gender" value="Female" {{ old('gender') == 'Female' ? 'checked' : '' }} required> Female</label></div></div>
                            </div>
                            <div class="row">
                                    <div class="col-md-4 form-group"><label for="religion">Religion</label><input type="text" name="religion" id="religion" value="{{ old('religion') }}" class="form-control" required></div>
                                    <div class="col-md-8 form-group"><label for="home_town">Home Address</label><input type="text" name="home_town" id="home_town" value="{{ old('home_town') }}" class="form-control" required></div>
                            </div>
                            <div class="row">
                                    <div class="col-md-6 form-group"><label for="father_name">Father's Name</label><input type="text" name="father_name" id="father_name" value="{{ old('father_name') }}" class="form-control"></div>
                                    <div class="col-md-6 form-group"><label for="father_occupation">Father's Occupation</label><input type="text" name="father_occupation" id="father_occupation" value="{{ old('father_occupation') }}" class="form-control"></div>
                            </div>
                            <div class="row">
                                    <div class="col-md-6 form-group"><label for="mother_name">Mother's Name</label><input type="text" name="mother_name" id="mother_name" value="{{ old('mother_name') }}" class="form-control"></div>
                                    <div class="col-md-6 form-group"><label for="mother_occupation">Mother's Occupation</label><input type="text" name="mother_occupation" id="mother_occupation" value="{{ old('mother_occupation') }}" class="form-control"></div>
                            </div>
                            <div class="row">
                                    <div class="col-md-4 form-group"><label for="guardian_name">Guardian Name</label><input type="text" name="guardian_name" id="guardian_name" value="{{ old('guardian_name') }}" class="form-control"></div>
                                    <div class="col-md-4 form-group"><label for="guardian_occupation">Guardian Occupation</label><input type="text" name="guardian_occupation" id="guardian_occupation" value="{{ old('guardian_occupation') }}" class="form-control"></div>
                                    <div class="col-md-4 form-group"><label for="guardian_relation">Relation to Student</label><input type="text" name="guardian_relation" id="guardian_relation" value="{{ old('guardian_relation') }}" class="form-control"></div>
                            </div>
                            <div class="row">
                                    <div class="col-md-4 form-group"><label for="contact_number">Contact Number</label><input type="text" name="contact_number" id="contact_number" value="{{ old('contact_number') }}" class="form-control" required></div>
                                    <div class="col-md-8 form-group"><label for="last_school_name">Name of Last School Attended</label><input type="text" name="last_school_name" id="last_school_name" value="{{ old('last_school_name') }}" class="form-control"></div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 form-group"><label>School Type</label><div class="pt-2"><label class="mr-3"><input type="radio" name="last_school_type" value="PRIVATE" {{ old('last_school_type') == 'PRIVATE' ? 'checked' : '' }}> PRIVATE</label><label><input type="radio" name="last_school_type" value="PUBLIC" {{ old('last_school_type') == 'PUBLIC' ? 'checked' : '' }}> PUBLIC</label></div></div>
                                    <div class="col-md-6 form-group"><label for="grade_enrolling">Grade Enrolling</label><input type="text" name="grade_enrolling" id="grade_enrolling" value="{{ old('grade_enrolling') }}" class="form-control" required></div>
                        </div>
                        <div class="form-group mb-0"><label for="last_school_address">Address of Last School Attended</label><input type="text" name="last_school_address" id="last_school_address" value="{{ old('last_school_address') }}" class="form-control"></div>
                        <div class="text-right mt-3"><button type="submit" class="btn btn-primary">Submit Enrollment</button></div>
                    </form>
                </div>

                <div class="tab-pane fade {{ $activeTab === 'pending' ? 'show active' : '' }}" id="parent-enrollment-pending">
                    @forelse($enrollments as $enrollment)
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered table-striped mb-0">
                                <tbody>
                                <tr><th style="width:220px;">Applicant</th><td>{{ $enrollment->formatted_name }}</td></tr>
                                <tr><th>Date of Birth</th><td>{{ \Carbon\Carbon::parse($enrollment->dob)->format('m/d/Y') }}</td></tr>
                                <tr><th>Gender</th><td>{{ $enrollment->gender }}</td></tr>
                                <tr><th>Religion</th><td>{{ $enrollment->religion }}</td></tr>
                                <tr><th>Place of Birth</th><td>{{ $enrollment->place_of_birth_town }}</td></tr>
                                <tr><th>Home Address</th><td>{{ $enrollment->home_town }}</td></tr>
                                <tr><th>Contact Number</th><td>{{ $enrollment->contact_number }}</td></tr>
                                <tr><th>Last School Attended</th><td>{{ $enrollment->last_school_name }}</td></tr>
                                <tr><th>School Type</th><td>{{ $enrollment->last_school_type }}</td></tr>
                                <tr><th>School Address</th><td>{{ $enrollment->last_school_address }}</td></tr>
                                <tr><th>Grade Enrolling</th><td>{{ $enrollment->grade_enrolling }}</td></tr>
                                <tr><th>Status</th><td>{{ ucfirst($enrollment->status) }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    @empty
                        <div class="alert alert-info">No enrollment submissions found.</div>
                    @endforelse
                </div>

                <div class="tab-pane fade {{ $activeTab === 'inbox' ? 'show active' : '' }}" id="parent-enrollment-inbox">
                    @foreach($billingNotices as $delivery)
                        <div class="card {{ $delivery->notice->paid_at ? 'border-success' : 'border-danger' }} mb-3">
                            <div class="card-header {{ $delivery->notice->paid_at ? 'bg-success text-white' : 'bg-danger text-white' }}">Payment Notice</div>
                            <div class="card-body">
                                <p>Good day {{ $delivery->parent_name }}.</p>
                                <p>Student: {{ $delivery->notice->student_name }}, {{ $delivery->notice->student_class }}{{ $delivery->notice->student_section ? ' - '.$delivery->notice->student_section : '' }}</p>
                                <div class="table-responsive mb-3"><table class="table table-bordered" style="table-layout:fixed;"><thead><tr>@foreach($delivery->notice->layout['columns'] ?? [] as $column)<th style="padding:8px 12px; white-space:normal; overflow-wrap:anywhere;">{{ $column['title'] ?? '' }}</th>@endforeach</tr></thead><tbody>@foreach($delivery->notice->layout['rows'] ?? [] as $row)<tr>@foreach($delivery->notice->layout['columns'] ?? [] as $column)<td style="padding:8px 12px; white-space:normal; overflow-wrap:anywhere;">{{ $row['cells'][$column['key']] ?? '' }}</td>@endforeach</tr>@endforeach</tbody></table></div>
                                <p class="mb-0">Please go to the school cashier to pay the presented amount.</p>
                                @if($delivery->notice->paid_at)<p class="font-weight-bold mb-0 mt-2">Paid on {{ $delivery->notice->paid_at->format('m/d/Y h:i A') }}.</p>@endif
                            </div>
                        </div>
                    @endforeach
                    @forelse($notices as $delivery)
                        @php($accountDetails = collect($delivery->notice->accounts)->firstWhere('username', $delivery->username))
                        <div class="card border-primary mb-3">
                            <div class="card-header bg-primary text-white">Account Created Notice</div>
                            <div class="card-body">
                                <p>Good day. The account details for {{ $delivery->notice->studentEnrollment->formatted_name }} are as follows:</p>
                                <ul><li>Account Type: {{ $delivery->account_type }}. Username: {{ $delivery->username }}.</li></ul>
                                <p class="mb-0">Password: {{ $accountDetails['password'] ?? 'N/A' }}. Please change your password upon logging in. Do not forget these details.</p>
                                <form method="POST" action="{{ route('account_inbox.account_creation_notices.acknowledge', ['recipient_id' => $delivery->id]) }}">
                                    @csrf
                                    <div class="form-group form-check mt-4">
                                        <input type="checkbox" class="form-check-input" id="parentEnrollmentAccountNoticeAck{{ $delivery->id }}" name="acknowledged" value="1">
                                        <label class="form-check-label" for="parentEnrollmentAccountNoticeAck{{ $delivery->id }}">By checking this, you acknowledge that this account has been created successfully, and the pending submission and this notice will be removed.</label>
                                    </div>
                                    <div class="text-right">
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @empty
                        @if($billingNotices->isEmpty())<div class="alert alert-info mb-0">No notices found.</div>@endif
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
