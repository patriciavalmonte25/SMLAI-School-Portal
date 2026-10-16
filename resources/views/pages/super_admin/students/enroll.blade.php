@extends('layouts.master')
@section('page_title', 'Enroll a Student')
@section('content')
    <style>
        #ajax-reg .actions li:has(> a[href$="#previous"]) {
            display: none;
        }
    </style>

    @php($prefill = isset($source_enrollment) ? $source_enrollment : null)
    @php($prefillGrade = old('student_grade', optional($prefill)->student_grade ?: optional($prefill)->grade_enrolling))
        <div class="card">
            <div class="card-header bg-white header-elements-inline">
                <h6 class="card-title">Enroll a Student</h6>
                {!! Qs::getPanelOptions() !!}
            </div>

            <form id="ajax-reg" method="post" enctype="multipart/form-data" class="wizard-form steps-validation" action="{{ route('registrar.students.enroll.store') }}" data-fouc novalidate data-confirm-submit="true" data-confirm-title="Create Student Account?" data-confirm-message="Create this student account with the entered details?" data-confirm-label="Create Account" data-success-popup="true" data-success-title="Student Account Created">
               @csrf
                <input type="hidden" name="source_enrollment_id" value="{{ old('source_enrollment_id', optional($prefill)->id) }}">
                <h6>Personal Information</h6>
                <fieldset>
                    <div class="row mb-2">
                        <div class="col-12">
                            <small class="text-muted">Ensure that all information is printed in CAPITAL LETTERS.</small>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="font-weight-semibold">1. FULL NAME</h6>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>SURNAME:</label>
                                <input value="{{ old('surname', optional($prefill)->surname) }}" type="text" name="surname" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>FIRST NAME/S:</label>
                                <input value="{{ old('first_name', optional($prefill)->first_name) }}" type="text" name="first_name" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>MIDDLE NAME:</label>
                                <input value="{{ old('middle_name', optional($prefill)->middle_name) }}" type="text" name="middle_name" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="font-weight-semibold">2. DATE OF BIRTH (MM/DD/YYYY)</h6>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <input name="dob" value="{{ old('dob', optional($prefill)->dob ? \Carbon\Carbon::parse($prefill->dob)->format('m/d/Y') : '') }}" type="text" class="form-control date-pick" placeholder="MM/DD/YYYY" autocomplete="off" data-date-format="mm/dd/yyyy">
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="font-weight-semibold">3. PLACE OF BIRTH</h6>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <input value="{{ old('place_of_birth_town', optional($prefill)->place_of_birth_town) }}" type="text" name="place_of_birth_town" class="form-control">
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
                                        <input class="form-check-input" type="radio" name="gender" value="Male" {{ old('gender', optional($prefill)->gender) == 'Male' ? 'checked' : '' }}> MALE
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <label class="form-check-label">
                                        <input class="form-check-input" type="radio" name="gender" value="Female" {{ old('gender', optional($prefill)->gender) == 'Female' ? 'checked' : '' }}> FEMALE
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
                                <input value="{{ old('religion', optional($prefill)->religion) }}" type="text" name="religion" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="font-weight-semibold">6. COMPLETE HOME ADDRESS</h6>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <input value="{{ old('home_town', optional($prefill)->home_town) }}" type="text" name="home_town" class="form-control">
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
                                <input value="{{ old('father_name', optional($prefill)->father_name) }}" type="text" name="father_name" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Father's Occupation:</label>
                                <input value="{{ old('father_occupation', optional($prefill)->father_occupation) }}" type="text" name="father_occupation" class="form-control">
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
                                <input value="{{ old('mother_name', optional($prefill)->mother_name) }}" type="text" name="mother_name" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Mother's Occupation:</label>
                                <input value="{{ old('mother_occupation', optional($prefill)->mother_occupation) }}" type="text" name="mother_occupation" class="form-control">
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
                                <input value="{{ old('guardian_name', optional($prefill)->guardian_name) }}" type="text" name="guardian_name" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Guardian Occupation:</label>
                                <input value="{{ old('guardian_occupation', optional($prefill)->guardian_occupation) }}" type="text" name="guardian_occupation" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Relation to Student:</label>
                                <input value="{{ old('guardian_relation', optional($prefill)->guardian_relation) }}" type="text" name="guardian_relation" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="font-weight-semibold">10. CONTACT NUMBER</h6>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <input value="{{ old('contact_number', optional($prefill)->contact_number) }}" type="text" name="contact_number" class="form-control">
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
                                <input value="{{ old('last_school_name', optional($prefill)->last_school_name) }}" type="text" name="last_school_name" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>School Type:</label>
                                <div class="d-flex align-items-center">
                                    <div class="form-check form-check-inline">
                                        <label class="form-check-label">
                                            <input class="form-check-input" type="radio" name="last_school_type" value="PRIVATE" {{ old('last_school_type', optional($prefill)->last_school_type) == 'PRIVATE' ? 'checked' : '' }}> PRIVATE
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <label class="form-check-label">
                                            <input class="form-check-input" type="radio" name="last_school_type" value="PUBLIC" {{ old('last_school_type', optional($prefill)->last_school_type) == 'PUBLIC' ? 'checked' : '' }}> PUBLIC
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
                                <input value="{{ old('last_school_address', optional($prefill)->last_school_address) }}" type="text" name="last_school_address" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="font-weight-semibold">13. GRADE/YEAR ENROLLING IN STA. MARIA (LAGUNA) ACADEMY, INC.</h6>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <input value="{{ old('grade_enrolling', optional($prefill)->grade_enrolling) }}" type="text" name="grade_enrolling" class="form-control">
                            </div>
                        </div>
                    </div>
                </fieldset>

                <h6>Student Data & Account</h6>
                <fieldset>
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="font-weight-semibold">Student Data</h6>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>1. GRADE:</label>
                                <select class="select-search form-control" id="student_grade" name="student_grade" data-placeholder="Choose..">
                                    <option value=""></option>
                                    @foreach($student_grades as $grade)
                                        <option {{ ($prefillGrade == $grade ? 'selected' : '') }} value="{{ $grade }}">{{ $grade }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>2. SECTION:</label>
                                <select class="select-search form-control" id="my_class_id" name="my_class_id" data-placeholder="Choose..">
                                    <option value=""></option>
                                    @foreach($my_classes as $c)
                                        <option {{ (old('my_class_id') == $c->id ? 'selected' : '') }} value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3" id="pathway_wrap" style="display: none;">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>3. PATHWAY:</label>
                                <select class="select-search form-control" id="pathway" name="pathway" data-placeholder="Choose..">
                                    <option value=""></option>
                                    <option value="Arts and Social Sciences" {{ old('pathway') === 'Arts and Social Sciences' ? 'selected' : '' }}>Arts and Social Sciences</option>
                                    <option value="Accountancy and Entrepreneurship" {{ old('pathway') === 'Accountancy and Entrepreneurship' ? 'selected' : '' }}>Accountancy and Entrepreneurship</option>
                                    <option value="Architecture and Engineering" {{ old('pathway') === 'Architecture and Engineering' ? 'selected' : '' }}>Architecture and Engineering</option>
                                    <option value="Healthcare and Safety" {{ old('pathway') === 'Healthcare and Safety' ? 'selected' : '' }}>Healthcare and Safety</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3" id="student_id_row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>4. STUDENT ID:</label>
                                <input value="{{ old('student_id') }}" type="text" name="student_id" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3 mt-4">
                        <div class="col-12">
                            <h6 class="font-weight-semibold">Account Data</h6>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>1. USERNAME:</label>
                                <input value="{{ old('username') }}" type="text" name="username" class="form-control">
                            </div>
                        </div>
                    </div>
                </fieldset>
            </form>
        </div>

        <script>
            function togglePathwayField() {
                var grade = $('#student_grade').val();
                var shouldShowPathway = grade === 'Grade 11' || grade === 'Grade 12';

                if (shouldShowPathway) {
                    $('#pathway_wrap').show();
                } else {
                    $('#pathway_wrap').hide();
                    $('#pathway').val('');
                }
            }

            $(document).ready(function () {
                togglePathwayField();
                $('#student_grade').on('change', togglePathwayField);
            });
        </script>

        <script>
            $('#ajax-reg').on('submit', function (event) {
                if ($(this).steps('getCurrentIndex') < 1) {
                    event.preventDefault();
                    return;
                }

            });
        </script>

    @endsection
