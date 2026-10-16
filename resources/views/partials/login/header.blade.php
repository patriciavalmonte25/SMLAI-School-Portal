<!-- Main navbar -->
<div class="navbar navbar-expand-md navbar-dark" style="background:#0b3d91; color:#ffffff; padding:8px 20px; min-height:56px;">
    <div style="display:flex; align-items:center; gap:10px; flex:1; min-width:0;">
        <details style="position:relative;">
            <summary style="list-style:none; cursor:pointer; display:flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:50%; background:rgba(255,255,255,0.16); color:#ffffff; font-size:18px; flex:0 0 auto;">
                ☰
            </summary>
            <div style="position:absolute; top:40px; left:0; min-width:320px; background:#fff; color:#1f2937; border-radius:10px; box-shadow:0 10px 30px rgba(0,0,0,0.15); padding:16px; z-index:1000;">
                <div style="display:flex; gap:12px; margin-bottom:12px; border-bottom:1px solid #e5e7eb; padding-bottom:8px;">
                    <div style="font-weight:700; color:#0b3d91;">School Resources</div>
                    <div style="font-weight:700; color:#6b7280;">Attributions</div>
                </div>
                <div style="display:grid; gap:8px;">
                    <div>
                        <strong>Facebook Page</strong><br>
                        <a href="{{ $home_page_resource_facebook ?? '#' }}" target="_blank" rel="noopener" style="color:#0b3d91;">{{ $home_page_resource_facebook ?? 'Not provided yet' }}</a>
                    </div>
                    <div>
                        <strong>Genyo E-Learning</strong><br>
                        <a href="{{ $home_page_resource_genyo ?? '#' }}" target="_blank" rel="noopener" style="color:#0b3d91;">{{ $home_page_resource_genyo ?? 'Not provided yet' }}</a>
                    </div>
                    <div><strong>Phone</strong><br>{{ $home_page_resource_phone ?? 'Not provided yet' }}</div>
                    <div><strong>Telephone</strong><br>{{ $home_page_resource_telephone ?? 'Not provided yet' }}</div>
                    <div><strong>Email</strong><br>{{ $home_page_resource_email ?? 'Not provided yet' }}</div>
                </div>
            </div>
        </details>

        <a href="{{ route('public_home') }}" class="d-inline-block" style="min-width:0;">
            <h4 class="text-bold text-white" style="margin:0; line-height:1.1;">{{ Qs::getSystemName() ?: ($home_page_school_name ?? config('app.name')) }}</h4>
        </a>
    </div>

    <div class="ml-auto">
        <a href="{{ route('login') }}" style="background:#fff; color:#0b3d91; padding:8px 14px; border-radius:4px; font-weight:700; text-decoration:none; display:inline-block; line-height:1;">LOGIN</a>
    </div>
</div>
<!-- /main navbar -->
