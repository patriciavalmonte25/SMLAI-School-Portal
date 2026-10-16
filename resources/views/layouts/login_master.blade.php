<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'CJ INSPIRED ACADEMY') }}</title>

    @include('partials.login.inc_top')
</head>

@php($loginBackground = Qs::getSetting('home_page_login_background'))
<body style="{{ $loginBackground ? "background-image: linear-gradient(rgba(15, 23, 42, 0.45), rgba(15, 23, 42, 0.45)), url('" . e($loginBackground) . "'); background-size: cover; background-position: center; background-attachment: fixed;" : '' }}">
@include('partials.login.header')
@yield('content')
@include('partials.login.footer')

</body>

</html>
