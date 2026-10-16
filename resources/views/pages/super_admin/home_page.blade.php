@extends('layouts.master')
@section('page_title', 'Edit Home Page')
@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('edit_home_page.update') }}" enctype="multipart/form-data" data-fouc>
                @csrf
                @method('PUT')

                <ul class="nav nav-tabs nav-tabs-highlight">
                    <li class="nav-item"><a href="#header" class="nav-link active" data-toggle="tab">Header & Branding</a></li>
                    <li class="nav-item"><a href="#introduction" class="nav-link" data-toggle="tab">Introduction</a></li>
                    <li class="nav-item"><a href="#resources" class="nav-link" data-toggle="tab">School Resources</a></li>
                    <li class="nav-item"><a href="#services" class="nav-link" data-toggle="tab">Services Offered</a></li>
                    <li class="nav-item"><a href="#pathways" class="nav-link" data-toggle="tab">Senior High Pathways</a></li>
                    <li class="nav-item"><a href="#login-page" class="nav-link" data-toggle="tab">Login Page</a></li>
                </ul>

                <div class="tab-content mt-3">
                    <div class="tab-pane fade show active" id="header">
                        <div class="form-group">
                            <label>School Name</label>
                            <input type="text" name="home_page_school_name" class="form-control" value="{{ $home_page_school_name }}">
                        </div>
                        <div class="form-group">
                            <label>School Logo</label>
                            @if($home_page_logo)
                                <div class="mb-2"><img src="{{ $home_page_logo }}" alt="Current school logo" style="max-width: 120px; max-height: 80px; object-fit: contain;"></div>
                            @endif
                            <input type="file" name="home_page_logo" class="form-control-file">
                        </div>
                        <div class="form-group">
                            <label>Hero Background Image</label>
                            @if($home_page_background)
                                <div class="mb-2"><img src="{{ $home_page_background }}" alt="Current hero background" style="max-width: 240px; max-height: 100px; object-fit: cover;"></div>
                            @endif
                            <input type="file" name="home_page_background" class="form-control-file">
                        </div>
                    </div>

                    <div class="tab-pane fade" id="introduction">
                        <div class="form-group">
                            <label>Summary</label>
                            <textarea name="home_page_summary" rows="4" class="form-control">{{ $home_page_summary }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Vision</label>
                            <textarea name="home_page_vision" rows="4" class="form-control">{{ $home_page_vision }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Mission</label>
                            <textarea name="home_page_mission" rows="4" class="form-control">{{ $home_page_mission }}</textarea>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="resources">
                        <div class="form-group">
                            <label>Facebook Page</label>
                            <input type="text" name="home_page_resource_facebook" class="form-control" value="{{ $home_page_resource_facebook }}">
                        </div>
                        <div class="form-group">
                            <label>Genyo E-Learning</label>
                            <input type="text" name="home_page_resource_genyo" class="form-control" value="{{ $home_page_resource_genyo }}">
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="home_page_resource_phone" class="form-control" value="{{ $home_page_resource_phone }}">
                        </div>
                        <div class="form-group">
                            <label>Telephone</label>
                            <input type="text" name="home_page_resource_telephone" class="form-control" value="{{ $home_page_resource_telephone }}">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="text" name="home_page_resource_email" class="form-control" value="{{ $home_page_resource_email }}">
                        </div>
                    </div>

                    <div class="tab-pane fade" id="services">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Pre-Elementary</h5>
                                <div class="form-group"><label>Title</label><input type="text" name="home_page_service_pre_elementary_title" class="form-control" value="{{ $home_page_service_pre_elementary_title }}"></div>
                                <div class="form-group"><label>Grade Level</label><input type="text" name="home_page_service_pre_elementary_grade" class="form-control" value="{{ $home_page_service_pre_elementary_grade }}"></div>
                                <div class="form-group"><label>Summary</label><textarea name="home_page_service_pre_elementary_summary" rows="3" class="form-control">{{ $home_page_service_pre_elementary_summary }}</textarea></div>
                                <div class="form-group"><label>Image</label>@if($home_page_service_pre_elementary_image)<div class="mb-2"><img src="{{ $home_page_service_pre_elementary_image }}" alt="Current pre-elementary image" style="max-width: 180px; max-height: 90px; object-fit: cover;"></div>@endif<input type="file" name="home_page_service_pre_elementary_image" class="form-control-file"></div>
                            </div>
                            <div class="col-md-6">
                                <h5>Elementary</h5>
                                <div class="form-group"><label>Title</label><input type="text" name="home_page_service_elementary_title" class="form-control" value="{{ $home_page_service_elementary_title }}"></div>
                                <div class="form-group"><label>Grade Level</label><input type="text" name="home_page_service_elementary_grade" class="form-control" value="{{ $home_page_service_elementary_grade }}"></div>
                                <div class="form-group"><label>Summary</label><textarea name="home_page_service_elementary_summary" rows="3" class="form-control">{{ $home_page_service_elementary_summary }}</textarea></div>
                                <div class="form-group"><label>Image</label>@if($home_page_service_elementary_image)<div class="mb-2"><img src="{{ $home_page_service_elementary_image }}" alt="Current elementary image" style="max-width: 180px; max-height: 90px; object-fit: cover;"></div>@endif<input type="file" name="home_page_service_elementary_image" class="form-control-file"></div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h5>Junior High School</h5>
                                <div class="form-group"><label>Title</label><input type="text" name="home_page_service_junior_high_title" class="form-control" value="{{ $home_page_service_junior_high_title }}"></div>
                                <div class="form-group"><label>Grade Level</label><input type="text" name="home_page_service_junior_high_grade" class="form-control" value="{{ $home_page_service_junior_high_grade }}"></div>
                                <div class="form-group"><label>Summary</label><textarea name="home_page_service_junior_high_summary" rows="3" class="form-control">{{ $home_page_service_junior_high_summary }}</textarea></div>
                                <div class="form-group"><label>Image</label>@if($home_page_service_junior_high_image)<div class="mb-2"><img src="{{ $home_page_service_junior_high_image }}" alt="Current junior high image" style="max-width: 180px; max-height: 90px; object-fit: cover;"></div>@endif<input type="file" name="home_page_service_junior_high_image" class="form-control-file"></div>
                            </div>
                            <div class="col-md-6">
                                <h5>Senior High School</h5>
                                <div class="form-group"><label>Title</label><input type="text" name="home_page_service_senior_high_title" class="form-control" value="{{ $home_page_service_senior_high_title }}"></div>
                                <div class="form-group"><label>Grade Level</label><input type="text" name="home_page_service_senior_high_grade" class="form-control" value="{{ $home_page_service_senior_high_grade }}"></div>
                                <div class="form-group"><label>Summary</label><textarea name="home_page_service_senior_high_summary" rows="3" class="form-control">{{ $home_page_service_senior_high_summary }}</textarea></div>
                                <div class="form-group"><label>Image</label>@if($home_page_service_senior_high_image)<div class="mb-2"><img src="{{ $home_page_service_senior_high_image }}" alt="Current senior high image" style="max-width: 180px; max-height: 90px; object-fit: cover;"></div>@endif<input type="file" name="home_page_service_senior_high_image" class="form-control-file"></div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="pathways">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Accountancy and Entrepreneurship</h5>
                                <div class="form-group"><label>Title</label><input type="text" name="home_page_pathway_accountancy_title" class="form-control" value="{{ $home_page_pathway_accountancy_title }}"></div>
                                <div class="form-group"><label>Summary</label><textarea name="home_page_pathway_accountancy_summary" rows="3" class="form-control">{{ $home_page_pathway_accountancy_summary }}</textarea></div>
                                <div class="form-group"><label>Image</label>@if($home_page_pathway_accountancy_image)<div class="mb-2"><img src="{{ $home_page_pathway_accountancy_image }}" alt="Current accountancy pathway image" style="max-width: 180px; max-height: 90px; object-fit: cover;"></div>@endif<input type="file" name="home_page_pathway_accountancy_image" class="form-control-file"></div>
                            </div>
                            <div class="col-md-6">
                                <h5>Architecture and Engineering</h5>
                                <div class="form-group"><label>Title</label><input type="text" name="home_page_pathway_architecture_title" class="form-control" value="{{ $home_page_pathway_architecture_title }}"></div>
                                <div class="form-group"><label>Summary</label><textarea name="home_page_pathway_architecture_summary" rows="3" class="form-control">{{ $home_page_pathway_architecture_summary }}</textarea></div>
                                <div class="form-group"><label>Image</label>@if($home_page_pathway_architecture_image)<div class="mb-2"><img src="{{ $home_page_pathway_architecture_image }}" alt="Current architecture pathway image" style="max-width: 180px; max-height: 90px; object-fit: cover;"></div>@endif<input type="file" name="home_page_pathway_architecture_image" class="form-control-file"></div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h5>Arts and Social Sciences</h5>
                                <div class="form-group"><label>Title</label><input type="text" name="home_page_pathway_arts_title" class="form-control" value="{{ $home_page_pathway_arts_title }}"></div>
                                <div class="form-group"><label>Summary</label><textarea name="home_page_pathway_arts_summary" rows="3" class="form-control">{{ $home_page_pathway_arts_summary }}</textarea></div>
                                <div class="form-group"><label>Image</label>@if($home_page_pathway_arts_image)<div class="mb-2"><img src="{{ $home_page_pathway_arts_image }}" alt="Current arts pathway image" style="max-width: 180px; max-height: 90px; object-fit: cover;"></div>@endif<input type="file" name="home_page_pathway_arts_image" class="form-control-file"></div>
                            </div>
                            <div class="col-md-6">
                                <h5>Healthcare and Safety</h5>
                                <div class="form-group"><label>Title</label><input type="text" name="home_page_pathway_healthcare_title" class="form-control" value="{{ $home_page_pathway_healthcare_title }}"></div>
                                <div class="form-group"><label>Summary</label><textarea name="home_page_pathway_healthcare_summary" rows="3" class="form-control">{{ $home_page_pathway_healthcare_summary }}</textarea></div>
                                <div class="form-group"><label>Image</label>@if($home_page_pathway_healthcare_image)<div class="mb-2"><img src="{{ $home_page_pathway_healthcare_image }}" alt="Current healthcare pathway image" style="max-width: 180px; max-height: 90px; object-fit: cover;"></div>@endif<input type="file" name="home_page_pathway_healthcare_image" class="form-control-file"></div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="login-page">
                        <div class="form-group">
                            <label>Login Page Background Image</label>
                            @if($home_page_login_background)
                                <div class="mb-2"><img src="{{ $home_page_login_background }}" alt="Current login page background" style="max-width: 240px; max-height: 120px; object-fit: contain;"></div>
                            @endif
                            <input type="file" name="home_page_login_background" class="form-control-file">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mt-4"><i class="icon-checkmark3"></i> Save Home Page</button>
            </form>
        </div>
    </div>
@endsection
