@extends('layouts.login_master')
@section('content')
    <div style="background: #f6f8fb; min-height: 100vh;">
        <div style="display:flex; justify-content:center; padding: 35px 20px;">
            <div style="width:100%; max-width:1200px; background:#fff; border-radius:12px; box-shadow:0 8px 25px rgba(0,0,0,0.08); overflow:hidden;">
                <section style="position:relative; min-height:320px; background-image:linear-gradient(rgba(7,39,96,0.55), rgba(7,39,96,0.55)), url('{{ $home_page_background }}'); background-color:#0b3d91; background-size:cover; background-position:center; background-repeat:no-repeat; color:#fff; display:flex; align-items:center; justify-content:center; text-align:center; padding:30px 20px;">
                    <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; width:100%;">
                        @if($home_page_logo)
                            <img src="{{ $home_page_logo }}" alt="School logo" style="width:110px; height:110px; object-fit:cover; border-radius:50%; margin:0 auto 12px; display:block; background:#fff; padding:6px;">
                        @endif
                        <h1 style="margin:0; font-size:34px; line-height:1.2;">{{ $home_page_school_name }}</h1>
                    </div>
                </section>

                <section style="padding:30px 40px; border-bottom:1px solid #e5e7eb;">
                    <div style="display:grid; grid-template-columns:1.2fr 1fr; gap:30px; align-items:start;">
                        <div>
                            <h2 style="margin-top:0; color:#0b3d91; font-size:24px;">About Our School</h2>
                            <p style="line-height:1.7; color:#495057; margin:0;">{{ $home_page_summary }}</p>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                            <div style="background:#f8fafc; padding:16px; border-radius:8px;">
                                <h3 style="margin-top:0; font-size:18px; color:#0b3d91;">Vision</h3>
                                <p style="margin:0; line-height:1.6; color:#495057;">{{ $home_page_vision }}</p>
                            </div>
                            <div style="background:#f8fafc; padding:16px; border-radius:8px;">
                                <h3 style="margin-top:0; font-size:18px; color:#0b3d91;">Mission</h3>
                                <p style="margin:0; line-height:1.6; color:#495057;">{{ $home_page_mission }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section style="padding:30px 40px;">
                    <h2 style="margin-top:0; color:#0b3d91; font-size:24px; text-align:center;">Services Offered</h2>
                    <div style="display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:20px; margin-bottom:24px;">
                        @foreach([
                            ['title' => $home_page_service_pre_elementary_title, 'grade' => $home_page_service_pre_elementary_grade, 'summary' => $home_page_service_pre_elementary_summary, 'image' => $home_page_service_pre_elementary_image],
                            ['title' => $home_page_service_elementary_title, 'grade' => $home_page_service_elementary_grade, 'summary' => $home_page_service_elementary_summary, 'image' => $home_page_service_elementary_image],
                            ['title' => $home_page_service_junior_high_title, 'grade' => $home_page_service_junior_high_grade, 'summary' => $home_page_service_junior_high_summary, 'image' => $home_page_service_junior_high_image],
                            ['title' => $home_page_service_senior_high_title, 'grade' => $home_page_service_senior_high_grade, 'summary' => $home_page_service_senior_high_summary, 'image' => $home_page_service_senior_high_image],
                        ] as $service)
                            <div style="border:1px solid #e5e7eb; border-radius:10px; padding:18px; background:#fafbfc; text-align:center;">
                                <h3 style="margin:0 0 4px; color:#0b3d91;">{{ $service['title'] }}</h3>
                                <p style="margin:0 0 12px; color:#6b7280; font-weight:600;">{{ $service['grade'] }}</p>
                                @if($service['image'])
                                    <img src="{{ $service['image'] }}" alt="{{ $service['title'] }}" style="width:calc(100% + 20px); height:300px; margin-left:-10px; object-fit:contain; object-position:center; background:#fff; border-radius:8px; margin-bottom:10px;">
                                @endif
                                <p style="margin:0; line-height:1.6; color:#495057;">{{ $service['summary'] }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div style="border-top:1px solid #e5e7eb; padding-top:20px;">
                        <h3 style="color:#0b3d91; margin-bottom:14px; text-align:center;">Senior High School Pathways</h3>
                        <div style="display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:20px;">
                            @foreach([
                                ['title' => $home_page_pathway_accountancy_title, 'summary' => $home_page_pathway_accountancy_summary, 'image' => $home_page_pathway_accountancy_image],
                                ['title' => $home_page_pathway_architecture_title, 'summary' => $home_page_pathway_architecture_summary, 'image' => $home_page_pathway_architecture_image],
                                ['title' => $home_page_pathway_arts_title, 'summary' => $home_page_pathway_arts_summary, 'image' => $home_page_pathway_arts_image],
                                ['title' => $home_page_pathway_healthcare_title, 'summary' => $home_page_pathway_healthcare_summary, 'image' => $home_page_pathway_healthcare_image],
                            ] as $pathway)
                                <div style="border:1px solid #e5e7eb; border-radius:10px; padding:16px; background:#fff; text-align:center;">
                                    <h4 style="margin:0 0 8px; color:#0b3d91;">{{ $pathway['title'] }}</h4>
                                    @if($pathway['image'])
                                        <img src="{{ $pathway['image'] }}" alt="{{ $pathway['title'] }}" style="width:calc(100% + 20px); height:300px; margin-left:-10px; object-fit:contain; object-position:center; background:#fff; border-radius:8px; margin-bottom:10px;">
                                    @endif
                                    <p style="margin:0; line-height:1.6; color:#495057;">{{ $pathway['summary'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div style="margin-top:24px; text-align:center;">
                        <a href="{{ route('enrollment_form') }}" style="display:inline-block; background:#0b3d91; color:#fff; padding:12px 24px; border-radius:4px; text-decoration:none; font-weight:700;">ENROLL NOW</a>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
