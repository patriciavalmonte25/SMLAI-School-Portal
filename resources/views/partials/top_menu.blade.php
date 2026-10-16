<div class="navbar navbar-expand-md navbar-dark" style="background:#0b3d91; color:#ffffff; padding:8px 20px; min-height:56px;">
    <div class="d-flex align-items-center mr-4" style="gap:10px; min-width:0;">
        <a href="#" class="navbar-nav-link sidebar-control sidebar-main-toggle d-none d-md-flex" style="margin:0; padding:0; width:34px; height:34px; align-items:center; justify-content:center; color:#ffffff; flex:0 0 auto;">
            <i class="icon-paragraph-justify3"></i>
        </a>

        <a href="{{ route('dashboard') }}" class="d-inline-block" style="min-width:0;">
        <h4 class="text-bold text-white" style="margin:0; line-height:1.1;">{{ Qs::getSystemName() }}</h4>
        </a>
    </div>
  {{--  <div class="navbar-brand">
        <a href="index.html" class="d-inline-block">
            <img src="{{ asset('global_assets/images/logo_light.png') }}" alt="">
        </a>
    </div>--}}

    <div class="d-md-none ml-auto">
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar-mobile">
            <i class="icon-tree5"></i>
        </button>
        <button class="navbar-toggler sidebar-mobile-main-toggle" type="button">
            <i class="icon-paragraph-justify3"></i>
        </button>
    </div>

    <div class="collapse navbar-collapse" id="navbar-mobile">
        <ul class="navbar-nav ml-md-auto">

        </ul>

			<span class="navbar-text ml-md-3 mr-md-auto"></span>

        <ul class="navbar-nav">

            <li class="nav-item dropdown dropdown-user">
                <a href="#" class="navbar-nav-link dropdown-toggle" data-toggle="dropdown">
                    <span>{{ Auth::user()->name }}</span>
                </a>

                <div class="dropdown-menu dropdown-menu-right">
                    @if(Auth::user()->user_type !== 'enroll')
                    <a href="{{ route('my_account') }}" class="dropdown-item"><i class="icon-user-plus"></i> My Profile</a>
                    <div class="dropdown-divider"></div>
                    @endif
                    <a href="{{ route('logout') }}" onclick="event.preventDefault();
          document.getElementById('logout-form').submit();" class="dropdown-item"><i class="icon-switch2"></i> Logout</a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </div>
            </li>
        </ul>
    </div>
</div>
