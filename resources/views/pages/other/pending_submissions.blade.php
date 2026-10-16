@extends('layouts.master')
@section('page_title', 'Pending Submissions')
@section('content')
    @php($approvedEnrollments = $enrollments->where('status', 'approved'))
    @php($deniedEnrollments = $enrollments->where('status', 'denied'))
    <div class="card">
        <div class="card-header bg-white header-elements-inline">
            <h6 class="card-title">Pending Submissions</h6>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            <ul class="nav nav-tabs nav-tabs-highlight">
                <li class="nav-item"><a href="#outgoing-submissions" class="nav-link active" data-toggle="tab">Outgoing Submissions</a></li>
                <li class="nav-item"><a href="#inbox" class="nav-link" data-toggle="tab">Inbox</a></li>
            </ul>

            <div class="tab-content" style="padding-top:20px;">
                <div class="tab-pane fade show active" id="outgoing-submissions">
                    @forelse($enrollments as $enrollment)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <tbody>
                                <tr><th style="width:220px;">Applicant</th><td>{{ $enrollment->formatted_name }}</td></tr>
                                <tr><th>Date of Birth</th><td>{{ $enrollment->dob }}</td></tr>
                                <tr><th>Gender</th><td>{{ $enrollment->gender }}</td></tr>
                                <tr><th>Religion</th><td>{{ $enrollment->religion }}</td></tr>
                                <tr><th>Place of Birth</th><td>{{ $enrollment->place_of_birth_town }}</td></tr>
                                <tr><th>Home Address</th><td>{{ $enrollment->home_town }}</td></tr>
                                <tr><th>Contact Number</th><td>{{ $enrollment->contact_number }}</td></tr>
                                <tr><th>Last School Attended</th><td>{{ $enrollment->last_school_name }}</td></tr>
                                <tr><th>School Type</th><td>{{ $enrollment->last_school_type }}</td></tr>
                                <tr><th>School Address</th><td>{{ $enrollment->last_school_address }}</td></tr>
                                <tr><th>Grade Enrolling</th><td>{{ $enrollment->grade_enrolling }}</td></tr>
                                <tr><th>Temporary Username</th><td>{{ $enrollment->account_username }}</td></tr>
                                <tr><th>Temporary Password</th><td>{{ $enrollment->password }}</td></tr>
                                <tr><th>Status</th><td>{{ ucfirst($enrollment->status) }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    @empty
                        <div class="alert alert-info">No outgoing submissions found.</div>
                    @endforelse
                </div>

                <div class="tab-pane fade" id="inbox">
                    @foreach($accountCreationNotices as $notice)
                        <div class="card border-primary mb-3">
                            <div class="card-header bg-primary text-white">Account Creation Notice</div>
                            <div class="card-body">
                                <p>The following accounts have been created.</p>
                                @foreach($notice->accounts as $account)
                                    <p>Account Type: {{ $account['account_type'] }}. Username: {{ $account['username'] }}. Password: "{{ $account['password'] ?? 'N/A' }}".</p>
                                @endforeach

                                @if($notice->acknowledged_at)
                                    <div class="alert alert-info mb-0">
                                        @if(Auth::user()->user_type === 'parent')
                                            Notice acknowledged. This notice will be deleted on {{ optional($notice->temporary_account_delete_at)->format('m/d/Y h:i A') }}.
                                        @else
                                            Notice acknowledged. Your temporary account is scheduled for deletion on {{ optional($notice->temporary_account_delete_at)->format('m/d/Y h:i A') }}.
                                        @endif
                                    </div>
                                @else
                                    <form method="POST" action="{{ route('pending_submissions.account_notice_acknowledge', ['notice_id' => $notice->id]) }}">
                                        @csrf
                                        <div class="form-group form-check mt-4">
                                            <input type="checkbox" class="form-check-input" id="accountNoticeAck{{ $notice->id }}" name="acknowledged" value="1" required>
                                            <label class="form-check-label" for="accountNoticeAck{{ $notice->id }}">
                                                @if(Auth::user()->user_type === 'parent')
                                                    By checking this box, you acknowledge that you have read this notice. This notice will be deleted THREE DAYS after acknowledgment.
                                                @else
                                                    By checking this box, you acknowledge that you have read this notice. Your temporary account will be deleted THREE DAYS after submission.
                                                @endif
                                            </label>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Submit Acknowledgment</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    @foreach($accountCreatedEnrollments as $enrollment)
                        <div class="card border-primary mb-3">
                            <div class="card-header bg-primary text-white">Account Created Notice</div>
                            <div class="card-body">
                                <p>
                                    Good day. The student account of
                                    {{ $enrollment->formatted_name }}
                                    has been created. Username: {{ $enrollment->created_student_username }}. Password: password. Please change your password upon logging in. Do not forget these details.
                                </p>

                                @if($enrollment->account_created_acknowledged_at)
                                    <div class="alert alert-info mb-0">
                                        @if(Auth::user()->user_type === 'parent')
                                            Notice acknowledged. This notice will be deleted on {{ optional($enrollment->temporary_account_delete_at)->format('m/d/Y h:i A') }}.
                                        @else
                                            Notice acknowledged. Your temporary account is scheduled for deletion on {{ optional($enrollment->temporary_account_delete_at)->format('m/d/Y h:i A') }}.
                                        @endif
                                    </div>
                                @else
                                    <form method="POST" action="{{ route('pending_submissions.account_created_acknowledge', ['enrollment_id' => $enrollment->id]) }}">
                                        @csrf
                                        <div class="form-group form-check mt-4">
                                            <input type="checkbox" class="form-check-input" id="accountCreatedAck{{ $enrollment->id }}" name="acknowledged" value="1" required>
                                            <label class="form-check-label" for="accountCreatedAck{{ $enrollment->id }}">
                                                @if(Auth::user()->user_type === 'parent')
                                                    By checking this box, you acknowledge that you have read this notice. This notice will be deleted THREE DAYS after acknowledgment.
                                                @else
                                                    By checking this box, you acknowledge that you have read this notice. Your temporary account will be deleted THREE DAYS after submission.
                                                @endif
                                            </label>
                                        </div>

                                        <button type="submit" class="btn btn-primary">Submit Acknowledgment</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    @foreach($approvedEnrollments as $enrollment)
                        <div class="card border-success mb-3">
                            <div class="card-header bg-success text-white">Approval Notice</div>
                            <div class="card-body">
                                <p>Good day. This notice is to confirm that the enrollment application of {{ $enrollment->formatted_name }} has been APPROVED. To complete the enrollment process, you must physically present the following documents upon arriving at the school:</p>
                                <ol type="a" class="pl-3">
                                    <li>PSA Birth Certificate</li>
                                    <li>Baptismal Certificate</li>
                                    <li>Proof of Income</li>
                                    <li>SF9 Report Card</li>
                                    <li>SF10 Transcript of Record</li>
                                    <li>2x2 Picture</li>
                                </ol>
                                <p class="mb-0">Following this visit, student and parent accounts for the school portal will be created and the details (username and password) will be sent to you. Remember your current login details.</p>
                            </div>
                        </div>
                    @endforeach

                    @forelse($deniedEnrollments as $enrollment)
                        <div class="card border-danger mb-3">
                            <div class="card-header bg-danger text-white">Denial Notice</div>
                            <div class="card-body">
                                <p>
                                    Good day. This notice is to confirm that the enrollment application of
                                    {{ $enrollment->formatted_name }}
                                    has been REJECTED. Reason: {{ $enrollment->denied_reason }}. Please resubmit your application
                                    (take note that the application process is different for resubmissions).
                                </p>

                                <form method="POST" action="{{ route('pending_submissions.acknowledge', ['enrollment_id' => $enrollment->id]) }}">
                                    @csrf
                                    <div class="form-group form-check mt-4">
                                        <input type="checkbox" class="form-check-input" id="acknowledged{{ $enrollment->id }}" name="acknowledged" value="1" required>
                                        <label class="form-check-label" for="acknowledged{{ $enrollment->id }}">
                                            By checking this box, you acknowledge that you have read this notice. Your temporary account will be deleted IMMEDIATELY upon submission.
                                        </label>
                                    </div>

                                    <button type="submit" class="btn btn-danger">Submit Acknowledgment</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        @if($approvedEnrollments->isEmpty() && $accountCreatedEnrollments->isEmpty() && $accountCreationNotices->isEmpty())
                            <div class="alert alert-info">
                                There are no incoming notices at the moment.
                            </div>
                        @endif
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
