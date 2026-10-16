@extends('layouts.login_master')
@section('content')
    <div style="min-height:100vh; background:#f6f8fb; padding:40px 20px;">
        <div style="max-width:1100px; margin:0 auto; background:#fff; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.08); padding:30px;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:16px; margin-bottom:24px; flex-wrap:wrap;">
                <div>
                    <h1 style="margin:0; color:#0b3d91; font-size:2.2rem;">Enrollment Form</h1>
                    <p style="margin:8px 0 0; color:#495057;">Please complete the form below to begin the enrollment process.</p>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger" style="margin-bottom:20px;">
                    <ul style="margin:0; padding-left:20px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('enrollment_form.store') }}">
                @csrf
                <div class="card" style="border:1px solid #e5e7eb; border-radius:10px; overflow:hidden;">
                    <div class="card-header" style="background:#f8fafc; padding:16px 20px; border-bottom:1px solid #e5e7eb;">
                        <h4 style="margin:0; color:#1f2937;">Student Information</h4>
                    </div>
                    <div class="card-body" style="padding:24px;">
                        <div class="row">
                            <div class="col-md-4 form-group" style="margin-bottom:20px;">
                                <label for="surname">Surname <span class="text-danger">*</span></label>
                                <input type="text" name="surname" id="surname" value="{{ old('surname') }}" class="form-control" required>
                            </div>
                            <div class="col-md-4 form-group" style="margin-bottom:20px;">
                                <label for="first_name">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" class="form-control" required>
                            </div>
                            <div class="col-md-4 form-group" style="margin-bottom:20px;">
                                <label for="middle_name">Middle Name</label>
                                <input type="text" name="middle_name" id="middle_name" value="{{ old('middle_name') }}" class="form-control">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 form-group" style="margin-bottom:20px;">
                                <label for="dob">Date of Birth (MM/DD/YYYY) <span class="text-danger">*</span></label>
                                <input type="text" name="dob" id="dob" value="{{ old('dob') }}" class="form-control" placeholder="MM/DD/YYYY" inputmode="numeric" autocomplete="off" required>
                            </div>
                            <div class="col-md-4 form-group" style="margin-bottom:20px;">
                                <label for="place_of_birth_town">Place of Birth <span class="text-danger">*</span></label>
                                <input type="text" name="place_of_birth_town" id="place_of_birth_town" value="{{ old('place_of_birth_town') }}" class="form-control" required>
                            </div>
                            <div class="col-md-4 form-group" style="margin-bottom:20px;">
                                <label>Gender <span class="text-danger">*</span></label>
                                <div style="display:flex; gap:16px; padding-top:10px;">
                                    <label style="margin:0;"><input type="radio" name="gender" value="Male" {{ old('gender') == 'Male' ? 'checked' : '' }} required> Male</label>
                                    <label style="margin:0;"><input type="radio" name="gender" value="Female" {{ old('gender') == 'Female' ? 'checked' : '' }} required> Female</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 form-group" style="margin-bottom:20px;">
                                <label for="religion">Religion <span class="text-danger">*</span></label>
                                <input type="text" name="religion" id="religion" value="{{ old('religion') }}" class="form-control" required>
                            </div>
                            <div class="col-md-8 form-group" style="margin-bottom:20px;">
                                <label for="home_town">Home Address <span class="text-danger">*</span></label>
                                <input type="text" name="home_town" id="home_town" value="{{ old('home_town') }}" class="form-control" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group" style="margin-bottom:20px;">
                                <label for="father_name">Father's Name</label>
                                <input type="text" name="father_name" id="father_name" value="{{ old('father_name') }}" class="form-control">
                            </div>
                            <div class="col-md-6 form-group" style="margin-bottom:20px;">
                                <label for="father_occupation">Father's Occupation</label>
                                <input type="text" name="father_occupation" id="father_occupation" value="{{ old('father_occupation') }}" class="form-control">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group" style="margin-bottom:20px;">
                                <label for="mother_name">Mother's Name</label>
                                <input type="text" name="mother_name" id="mother_name" value="{{ old('mother_name') }}" class="form-control">
                            </div>
                            <div class="col-md-6 form-group" style="margin-bottom:20px;">
                                <label for="mother_occupation">Mother's Occupation</label>
                                <input type="text" name="mother_occupation" id="mother_occupation" value="{{ old('mother_occupation') }}" class="form-control">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 form-group" style="margin-bottom:20px;">
                                <label for="guardian_name">Guardian Name</label>
                                <input type="text" name="guardian_name" id="guardian_name" value="{{ old('guardian_name') }}" class="form-control">
                            </div>
                            <div class="col-md-4 form-group" style="margin-bottom:20px;">
                                <label for="guardian_occupation">Guardian Occupation</label>
                                <input type="text" name="guardian_occupation" id="guardian_occupation" value="{{ old('guardian_occupation') }}" class="form-control">
                            </div>
                            <div class="col-md-4 form-group" style="margin-bottom:20px;">
                                <label for="guardian_relation">Relation to Student</label>
                                <input type="text" name="guardian_relation" id="guardian_relation" value="{{ old('guardian_relation') }}" class="form-control">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 form-group" style="margin-bottom:20px;">
                                <label for="contact_number">Contact Number <span class="text-danger">*</span></label>
                                <input type="text" name="contact_number" id="contact_number" value="{{ old('contact_number') }}" class="form-control" required>
                            </div>
                            <div class="col-md-8 form-group" style="margin-bottom:20px;">
                                <label for="last_school_name">Name of Last School Attended</label>
                                <input type="text" name="last_school_name" id="last_school_name" value="{{ old('last_school_name') }}" class="form-control">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group" style="margin-bottom:20px;">
                                <label>School Type</label>
                                <div style="display:flex; gap:20px; padding-top:10px;">
                                    <label style="margin:0;"><input type="radio" name="last_school_type" value="PRIVATE" {{ old('last_school_type') == 'PRIVATE' ? 'checked' : '' }}> PRIVATE</label>
                                    <label style="margin:0;"><input type="radio" name="last_school_type" value="PUBLIC" {{ old('last_school_type') == 'PUBLIC' ? 'checked' : '' }}> PUBLIC</label>
                                </div>
                            </div>
                            <div class="col-md-6 form-group" style="margin-bottom:20px;">
                                <label for="grade_enrolling">Grade Enrolling <span class="text-danger">*</span></label>
                                <input type="text" name="grade_enrolling" id="grade_enrolling" value="{{ old('grade_enrolling') }}" class="form-control" required>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom:10px;">
                            <label for="last_school_address">Address of Last School Attended</label>
                            <input type="text" name="last_school_address" id="last_school_address" value="{{ old('last_school_address') }}" class="form-control">
                        </div>
                    </div>
                </div>

                <div style="margin-top:24px; text-align:right;">
                    <button type="submit" class="btn btn-primary" style="padding:12px 26px; font-weight:600; border-radius:8px; background:#0b3d91; border:none;">
                        Submit Enrollment
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
