@extends('layouts.master')
@section('page_title', 'Document Requests')
@section('content')
    <div class="card">
        <div class="card-header bg-white header-elements-inline">
            <h6 class="card-title">Document Requests</h6>
            {!! Qs::getPanelOptions() !!}
        </div>
        <div class="card-body">
            @if($errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                </div>
            @endif

            <style>
                .document-request-option {
                    display: flex;
                    align-items: center;
                    gap: 16px;
                    min-height: 44px;
                    padding: 4px 0;
                }

                .document-request-option-label {
                    display: flex;
                    align-items: center;
                    gap: 14px;
                    flex: 0 0 340px;
                    max-width: 100%;
                    margin: 0;
                }

                .document-request-option-label input[type="checkbox"] {
                    flex: 0 0 auto;
                    margin: 0;
                }

                .document-request-option-fields {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    flex: 0 0 190px;
                    min-height: 34px;
                }

                .document-request-option-fields input {
                    width: 90px;
                }

                @media (max-width: 575.98px) {
                    .document-request-option {
                        align-items: flex-start;
                        flex-direction: column;
                        gap: 4px;
                    }

                    .document-request-option-label,
                    .document-request-option-fields {
                        flex-basis: auto;
                    }

                    .document-request-option-fields {
                        margin-left: 32px;
                    }
                }
            </style>
            <ul class="nav nav-tabs nav-tabs-highlight">
                <li class="nav-item"><a href="#request-documents" class="nav-link active" data-toggle="tab">Request Documents</a></li>
                <li class="nav-item"><a href="#sent-requests" class="nav-link" data-toggle="tab">Sent Requests</a></li>
            </ul>

            <div class="tab-content pt-3">
                <div class="tab-pane fade show active" id="request-documents">
                    @php
                        $firstStudent = $actor->user_type === 'student' ? $actor : null;
                    @endphp
                    <form method="POST" action="{{ route('document_requests.store') }}" id="document-request-form">
                        @csrf
                        @if($actor->user_type === 'parent')
                            <div class="form-group">
                                <label for="request-student">Request for student</label>
                                <select id="request-student" name="student_user_id" class="form-control">
                                    <option value="">Choose a linked student...</option>
                                    @foreach($students as $student)
                                        @php
                                            $enrollment = $student->student_enrollment;
                                            $studentRecord = $student->student_record;
                                        @endphp
                                        <option value="{{ $student->id }}"
                                            data-name="{{ $student->name }}"
                                            data-dob="{{ $student->dob }}"
                                            data-place-of-birth="{{ optional($enrollment)->place_of_birth_town }}"
                                            data-contact="{{ optional($enrollment)->contact_number ?: $student->phone }}"
                                            data-father="{{ optional($enrollment)->father_name }}"
                                            data-father-occupation="{{ optional($enrollment)->father_occupation }}"
                                            data-mother="{{ optional($enrollment)->mother_name }}"
                                            data-mother-occupation="{{ optional($enrollment)->mother_occupation }}"
                                            data-address="{{ optional($enrollment)->home_town ?: $student->address }}"
                                            data-class="{{ optional(optional($studentRecord)->my_class)->name ?: optional(optional($enrollment)->my_class)->name }}"
                                            {{ (string) old('student_user_id') === (string) $student->id ? 'selected' : '' }}>{{ $student->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <input type="hidden" name="student_user_id" value="{{ $actor->id }}">
                        @endif

                        <div class="form-group">
                            <label for="student-name">NAME</label>
                            <input id="student-name" name="student_name" type="text" class="form-control" value="{{ old('student_name', optional($firstStudent)->name) }}" required maxlength="150">
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="school-year">School Year Last Attended/Graduated</label>
                                <input id="school-year" name="school_year_last_attended" type="text" class="form-control" value="{{ old('school_year_last_attended') }}" required maxlength="50">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="grade-section">Grade &amp; Section</label>
                                <input id="grade-section" name="grade_section" type="text" class="form-control" value="{{ old('grade_section', optional(optional($firstStudent)->student_record)->my_class->name ?? optional(optional($firstStudent)->student_enrollment)->my_class->name ?? '') }}" required maxlength="150">
                            </div>
                        </div>

                        <h6 class="font-weight-semibold mb-2">A. Requesting for: (please check)</h6>
                        <fieldset class="border rounded px-3 py-1 mb-3">
                            <div class="document-request-option">
                                <label class="document-request-option-label">
                                    <input class="request-item-check" type="checkbox" name="request_items[]" value="form_137" {{ in_array('form_137', old('request_items', [])) ? 'checked' : '' }}>
                                    <span>a. Form 137 / SF 10</span>
                                </label>
                                <div class="document-request-option-fields">
                                    <label for="form-137-grade" class="mb-0">Grade:</label>
                                    <input id="form-137-grade" name="form_137_grade" type="text" class="form-control form-control-sm request-grade" data-request="form_137" value="{{ old('form_137_grade') }}" maxlength="50">
                                </div>
                            </div>
                            <div class="document-request-option">
                                <label class="document-request-option-label">
                                    <input class="request-item-check" type="checkbox" name="request_items[]" value="form_138" {{ in_array('form_138', old('request_items', [])) ? 'checked' : '' }}>
                                    <span>b. Form 138 / Report Card / SF</span>
                                </label>
                                <div class="document-request-option-fields">
                                    <label for="form-138-grade" class="mb-0">Grade:</label>
                                    <input id="form-138-grade" name="form_138_grade" type="text" class="form-control form-control-sm request-grade" data-request="form_138" value="{{ old('form_138_grade') }}" maxlength="50">
                                </div>
                            </div>
                            @foreach(['moral' => 'c. Certification of Good Moral Character', 'transfer' => 'd. Certification of Transfer', 'graduate' => 'e. Certification of Graduate', 'other' => 'f. Others (specify)'] as $value => $label)
                                <div class="document-request-option">
                                    <label class="document-request-option-label">
                                        <input class="request-item-check" type="checkbox" name="request_items[]" value="{{ $value }}" {{ in_array($value, old('request_items', [])) ? 'checked' : '' }}>
                                        <span>{{ $label }}</span>
                                    </label>
                                    @if($value === 'other')
                                        <div class="document-request-option-fields">
                                            <input id="other-request" name="other_request" type="text" class="form-control" value="{{ old('other_request') }}" maxlength="250">
                                        </div>
                                    @else
                                        <div class="document-request-option-fields"></div>
                                    @endif
                                </div>
                            @endforeach
                        </fieldset>

                        <h6 class="font-weight-semibold mb-2">B. Purpose</h6>
                        <div class="form-group">
                            <label for="request-purpose" class="sr-only">Purpose</label>
                            <textarea id="request-purpose" name="purpose" class="form-control" rows="3" required maxlength="2000">{{ old('purpose') }}</textarea>
                        </div>

                        <h6 class="font-weight-semibold mb-2">C. Information</h6>
                        <fieldset class="border rounded p-3 mb-3">
                            <div class="form-group">
                                <label for="information-name">a. Name</label>
                                <input id="information-name" name="information_name" type="text" class="form-control" value="{{ old('information_name', optional($firstStudent)->name) }}" required maxlength="150">
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="request-dob">b. Date of Birth</label>
                                    <input id="request-dob" name="date_of_birth" type="text" class="form-control date-pick" data-date-format="mm/dd/yyyy" placeholder="MM/DD/YYYY" autocomplete="off" value="{{ old('date_of_birth', optional($firstStudent)->dob ? \Carbon\Carbon::parse($firstStudent->dob)->format('m/d/Y') : '') }}" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="place-of-birth">c. Place of Birth</label>
                                    <input id="place-of-birth" name="place_of_birth" type="text" class="form-control" value="{{ old('place_of_birth', optional(optional($firstStudent)->student_enrollment)->place_of_birth_town) }}" required maxlength="200">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="contact-messenger">d. CP Number / Messenger</label>
                                <input id="contact-messenger" name="contact_messenger" type="text" class="form-control" value="{{ old('contact_messenger', optional(optional($firstStudent)->student_enrollment)->contact_number ?: optional($firstStudent)->phone) }}" required maxlength="100">
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="father-name">e. Father</label>
                                    <input id="father-name" name="father_name" type="text" class="form-control mb-2" value="{{ old('father_name', optional(optional($firstStudent)->student_enrollment)->father_name) }}" required maxlength="150">
                                    <label for="father-occupation">Occupation</label>
                                    <input id="father-occupation" name="father_occupation" type="text" class="form-control" value="{{ old('father_occupation', optional(optional($firstStudent)->student_enrollment)->father_occupation) }}" required maxlength="150">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="mother-name">Mother</label>
                                    <input id="mother-name" name="mother_name" type="text" class="form-control mb-2" value="{{ old('mother_name', optional(optional($firstStudent)->student_enrollment)->mother_name) }}" required maxlength="150">
                                    <label for="mother-occupation">Occupation</label>
                                    <input id="mother-occupation" name="mother_occupation" type="text" class="form-control" value="{{ old('mother_occupation', optional(optional($firstStudent)->student_enrollment)->mother_occupation) }}" required maxlength="150">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="parents-address">f. Address of Parents</label>
                                <textarea id="parents-address" name="parents_address" class="form-control" rows="2" required maxlength="500">{{ old('parents_address', optional(optional($firstStudent)->student_enrollment)->home_town ?: optional($firstStudent)->address) }}</textarea>
                            </div>
                        </fieldset>

                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>

                <div class="tab-pane fade" id="sent-requests">
                    @forelse($requests as $documentRequest)
                        @php
                            $requestStyle = [
                                'pending' => ['danger', 'Request Sent'],
                                'approved' => ['primary', 'Approved'],
                                'rejected' => ['dark', 'Rejected'],
                                'collected' => ['success', 'Collected'],
                            ][$documentRequest->status] ?? ['secondary', ucfirst($documentRequest->status)];
                        @endphp
                        <div class="card border-{{ $requestStyle[0] }} mb-3">
                            <div class="card-header bg-{{ $requestStyle[0] }} {{ $documentRequest->status === 'rejected' || $documentRequest->status === 'collected' ? 'text-white' : '' }}">{{ $requestStyle[1] }}: {{ $documentRequest->student_name }}</div>
                            <div class="card-body">
                                @include('pages.document_requests._details', ['documentRequest' => $documentRequest])
                                @if($documentRequest->status === 'approved')
                                    <p class="font-weight-bold mb-0 mt-3">Approved. Request may be collected on {{ $documentRequest->release_date->format('m/d/Y') }}. Fee: {{ $documentRequest->fee }}.</p>
                                @elseif($documentRequest->status === 'rejected')
                                    <p class="font-weight-bold mb-0 mt-3">Rejected. Reason: {{ $documentRequest->rejection_reason }}</p>
                                @elseif($documentRequest->status === 'collected')
                                    <p class="font-weight-bold mb-0 mt-3">Approved. Request may be collected on {{ $documentRequest->release_date->format('m/d/Y') }}. Fee: {{ $documentRequest->fee }}.</p>
                                    <p class="font-weight-bold mb-0 mt-3">Collected on {{ $documentRequest->collection_date->format('m/d/Y h:i A') }}. Received by {{ $documentRequest->request_received_by }}; prepared by {{ $documentRequest->request_prepared_by }}; released by {{ $documentRequest->request_released_by }}.</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="alert alert-info mb-0">No document requests have been sent.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var studentSelect = document.getElementById('request-student');
            var form = document.getElementById('document-request-form');

            function pickerDate(value) {
                if (!value) { return ''; }
                var isoMatch = String(value).match(/^(\d{4})-(\d{2})-(\d{2})/);
                if (isoMatch) { return isoMatch[2] + '/' + isoMatch[3] + '/' + isoMatch[1]; }
                return value;
            }

            function applyStudentDetails(option) {
                if (!option || !option.value) { return; }
                document.getElementById('student-name').value = option.dataset.name || '';
                document.getElementById('information-name').value = option.dataset.name || '';
                document.getElementById('request-dob').value = pickerDate(option.dataset.dob || '');
                document.getElementById('place-of-birth').value = option.dataset.placeOfBirth || '';
                document.getElementById('contact-messenger').value = option.dataset.contact || '';
                document.getElementById('father-name').value = option.dataset.father || '';
                document.getElementById('father-occupation').value = option.dataset.fatherOccupation || '';
                document.getElementById('mother-name').value = option.dataset.mother || '';
                document.getElementById('mother-occupation').value = option.dataset.motherOccupation || '';
                document.getElementById('parents-address').value = option.dataset.address || '';
                document.getElementById('grade-section').value = option.dataset.class || '';
            }

            function updateConditionalFields() {
                document.querySelectorAll('.request-grade').forEach(function (field) {
                    var checkbox = document.querySelector('.request-item-check[value="' + field.dataset.request + '"]');
                    field.required = !!(checkbox && checkbox.checked);
                });
                var otherSelected = document.querySelector('.request-item-check[value="other"]').checked;
                document.getElementById('other-request').required = otherSelected;
            }

            if (studentSelect) {
                studentSelect.addEventListener('change', function () {
                    applyStudentDetails(studentSelect.options[studentSelect.selectedIndex]);
                });
                if (studentSelect.value) {
                    applyStudentDetails(studentSelect.options[studentSelect.selectedIndex]);
                }
            }
            document.querySelectorAll('.request-item-check').forEach(function (checkbox) {
                checkbox.addEventListener('change', updateConditionalFields);
            });
            updateConditionalFields();

            form.addEventListener('submit', function (event) {
                if (!window.confirm('Submit this document request?')) { event.preventDefault(); }
            });

            var activeTab = window.location.hash;
            if (activeTab) {
                var tab = document.querySelector('.nav-tabs a[href="' + activeTab + '"]');
                if (tab) { window.jQuery(tab).tab('show'); }
            }
        });
    </script>
@endsection