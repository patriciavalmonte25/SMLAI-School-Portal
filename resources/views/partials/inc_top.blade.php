<link rel="icon" href="{{ asset('global_assets/images/favicon.png') }}">

{{--<!-- Global stylesheets -->--}}
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,300,100,500,700,900" rel="stylesheet" type="text/css">
    <link href="{{ asset('global_assets/css/icons/icomoon/styles.css') }}" rel="stylesheet" type="text/css">
    <link href=" {{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css">
    <link href=" {{ asset('assets/css/bootstrap_limitless.min.css') }}" rel="stylesheet" type="text/css">
    <link href=" {{ asset('assets/css/layout.min.css') }}" rel="stylesheet" type="text/css">
    <link href=" {{ asset('assets/css/components.min.css') }}" rel="stylesheet" type="text/css">
    <link href=" {{ asset('assets/css/colors.min.css') }}" rel="stylesheet" type="text/css">
    <style>
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate,
        .dataTables_wrapper .dt-buttons {
            display: none !important;
        }

        .dataTable thead th.action-column {
            cursor: default !important;
            padding-right: 0.75rem !important;
        }

        .dataTable thead th.action-column::before,
        .dataTable thead th.action-column::after {
            display: none !important;
            content: none !important;
        }

        .sidebar-xs .sidebar-main {
            width: 0 !important;
            border: 0 !important;
            box-shadow: none !important;
            visibility: hidden !important;
        }

        .sidebar-xs .sidebar-main .sidebar-content {
            left: -16.875rem !important;
            width: 16.875rem !important;
            overflow: hidden !important;
            pointer-events: none;
        }

        .sidebar-xs .sidebar-main .nav-sidebar > .nav-item > .nav-link > i,
        .sidebar-xs .sidebar-main .nav-sidebar > .nav-item-header > i {
            display: none !important;
        }

        .sidebar-xs .sidebar-main .nav-sidebar > .nav-item > .nav-link > span,
        .sidebar-xs .sidebar-main .nav-sidebar > .nav-item-header > div {
            display: none !important;
        }

        .sidebar-xs .sidebar-main .nav-sidebar > .nav-item > .nav-link {
            justify-content: center;
            transition: none !important;
        }

        .sidebar-xs .sidebar-main .nav-sidebar > .nav-item > .nav-link.active,
        .sidebar-xs .sidebar-main .nav-sidebar > .nav-item-open > .nav-link:not(.disabled) {
            background: transparent !important;
            box-shadow: none !important;
            border: 0 !important;
            color: inherit !important;
            transition: none !important;
        }

        .sidebar-xs .sidebar-main .sidebar-user .media-body {
            display: none !important;
        }

        .sidebar-xs .sidebar-main .sidebar-user .media {
            justify-content: center;
        }
    </style>
    <!-- /global stylesheets -->

{{--DatePickers--}}
<link rel="stylesheet" href="{{ asset('assets/css/bootstrap-datepicker.min.css') }}" type="text/css">

{{-- Custom App CSS--}}
<link href=" {{ asset('assets/css/qs.css') }}" rel="stylesheet" type="text/css">

{{--   Core JS files --}}
    <script src="{{ asset('global_assets/js/main/jquery.min.js') }} "></script>
    <script src="{{ asset('global_assets/js/main/bootstrap.bundle.min.js') }} "></script>
    <script src="{{ asset('global_assets/js/plugins/loaders/blockui.min.js') }} "></script>
