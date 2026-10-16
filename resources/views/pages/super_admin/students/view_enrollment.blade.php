@extends('layouts.master')
@section('page_title', 'View Enrollment Form')
@section('content')
    <style>
        .enrollment-details-table th:first-child {
            width: 28%;
        }
    </style>
    <div class="card">
        <div class="card-header bg-white header-elements-inline">
            <h6 class="card-title">Enrollment Form: {{ $enrollment->formatted_name }}</h6>
            {!! Qs::getPanelOptions() !!}
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped enrollment-details-table">
                    <tbody>
                    <tr><th>Full Name</th><td>{{ $enrollment->formatted_name }}</td></tr>
                    <tr><th>Date of Birth</th><td>{{ $enrollment->dob }}</td></tr>
                    <tr><th>Gender</th><td>{{ $enrollment->gender }}</td></tr>
                    <tr><th>Religion</th><td>{{ $enrollment->religion }}</td></tr>
                    <tr><th>Place of Birth</th><td>{{ implode(', ', array_filter([$enrollment->place_of_birth_house, $enrollment->place_of_birth_street, $enrollment->place_of_birth_barangay, $enrollment->place_of_birth_town, $enrollment->place_of_birth_province])) }}</td></tr>
                    <tr><th>Home Address</th><td>{{ implode(', ', array_filter([$enrollment->home_house, $enrollment->home_street, $enrollment->home_barangay, $enrollment->home_town, $enrollment->home_province])) }}</td></tr>
                    <tr><th>Father's Name</th><td>{{ $enrollment->father_name }}</td></tr>
                    <tr><th>Father's Occupation</th><td>{{ $enrollment->father_occupation }}</td></tr>
                    <tr><th>Mother's Name</th><td>{{ $enrollment->mother_name }}</td></tr>
                    <tr><th>Mother's Occupation</th><td>{{ $enrollment->mother_occupation }}</td></tr>
                    <tr><th>Guardian's Name</th><td>{{ $enrollment->guardian_name }}</td></tr>
                    <tr><th>Guardian's Occupation</th><td>{{ $enrollment->guardian_occupation }}</td></tr>
                    <tr><th>Guardian's Relation</th><td>{{ $enrollment->guardian_relation }}</td></tr>
                    <tr><th>Contact Number</th><td>{{ $enrollment->contact_number }}</td></tr>
                    <tr><th>Last School Attended</th><td>{{ $enrollment->last_school_name }}</td></tr>
                    <tr><th>School Type</th><td>{{ $enrollment->last_school_type }}</td></tr>
                    <tr><th>School Address</th><td>{{ $enrollment->last_school_address }}</td></tr>
                    <tr><th>Grade Enrolling</th><td>{{ $enrollment->grade_enrolling }}</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between flex-wrap mt-4" style="gap:8px;">
                <a href="{{ route('registrar.students.inbox') }}" class="btn btn-light">Back to Inbox</a>
                @if($enrollment->status === 'pending')
                    <div class="d-flex flex-wrap" style="gap:8px;">
                        <form method="POST" action="{{ route('registrar.students.approve', ['enrollment_id' => $enrollment->id]) }}">
                            @csrf
                            <button type="submit" class="btn btn-success">Approve</button>
                        </form>
                        <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#denyEnrollmentModal">Deny</button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($enrollment->status === 'pending')
        <div class="modal fade" id="denyEnrollmentModal" tabindex="-1" role="dialog" aria-labelledby="denyEnrollmentModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form method="POST" action="{{ route('registrar.students.deny', ['enrollment_id' => $enrollment->id]) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="denyEnrollmentModalLabel">Deny Enrollment</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group mb-0">
                                <label for="denial-reason">Reason for denying this enrollment <span class="text-danger">*</span></label>
                                <textarea id="denial-reason" name="reason" class="form-control" rows="4" required minlength="5"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Confirm Denial</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection