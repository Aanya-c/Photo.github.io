<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('meta_description', config('app.name'))">
    <meta name="author" content="@yield('meta_author', config('app.name'))">
    @yield('meta')
    <link rel="shortcut icon" href="{{ asset('assets/front/images/Favicon.jpg') }}">
    <title>@yield('title', config('app.name'))</title>
    @stack('before-styles')
    <!-- Bootstrap Css -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" id="bootstrap-style" rel="stylesheet" type="text/css">
    <!-- Icons Css -->
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css">
    <!-- App Css-->
    <link href="{{ asset('assets/css/app.min.css') }}" id="app-style" rel="stylesheet" type="text/css">
    <!-- Custom Style -->
    <style type="text/css">
        .error{
            color: #ee2558;
        }
        .invalid-feedback{
            display: block;
            color: #ee2558;
        }
        .swal2-popup{
            font-size: 16px !important;
        }
        .fr-button{
           float: right !important;
        }
        .logo-text{
            color: white;
            font-size: 30px;
            font-weight: bold;
        }
    </style>
    @stack('after-styles')
    @if (trim($__env->yieldContent('page-style')))
        @yield('page-style')
    @endif
    <script type="text/javascript">
        window.Laravel =  {!! json_encode(['csrfToken' => csrf_token()]) !!};
        var BASE_URL = "{{ url('/') }}";
    </script>
</head>
    <body data-sidebar="dark">
        <!-- Loader -->
        <div id="preloader">
            <div id="status">
                <div class="spinner-chase">
                    <div class="chase-dot"></div>
                    <div class="chase-dot"></div>
                    <div class="chase-dot"></div>
                    <div class="chase-dot"></div>
                    <div class="chase-dot"></div>
                    <div class="chase-dot"></div>
                </div>
            </div>
        </div>
        <!-- Begin page -->
        <div id="layout-wrapper">
            @include('layouts.includes.topheader')
            <!-- ========== Left Sidebar Start ========== -->
            @include('layouts.includes.sidebar')
            <!-- Left Sidebar End -->
            <!-- ============================================================== -->
            <!-- Start right Content here -->
            <!-- ============================================================== -->
            <div class="main-content">
                <div class="page-content">
                    <div class="container-fluid">
                        <!-- start page title -->
                        @yield('breadcrumb')
                        @yield('content')
                        <!-- end page title -->
                    </div>
                </div>
                <!-- End Page-content -->
                <!-- Modal -->
                @yield('modal')
                <!-- end modal -->
                @include('layouts.includes.footer')
            </div>
            <!-- end main content-->
        </div>
        <!-- END layout-wrapper -->
        <!-- JAVASCRIPT -->
        @stack('before-scripts')
        <script src="{{ asset('assets/libs/jquery/jquery.min.js') }}"></script>
        <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
        <script src="{{ asset('assets/libs/metismenu/metisMenu.min.js') }}"></script>
        <script src="{{ asset('assets/libs/simplebar/simplebar.min.js') }}"></script>
        <script src="{{ asset('assets/libs/node-waves/waves.min.js') }}"></script>
        <script src="{{ asset('assets/js/app.js') }}"></script>
        <script src="{{ asset('assets/libs/validate/jquery.validate.min.js') }}"></script>
        <script src="{{ asset('assets/libs/validate/additional-methods.js') }}"></script>
        <script src="{{ asset('assets/libs/sweetalert/sweetalert.min.js') }}"></script>
        <script type="text/javascript">
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 7000
            });
        </script>
        @if (Session::has('alert-message'))
            <script type="text/javascript">
                Toast.fire({
                    type: "{{ Session::get('alert-class', 'info') }}",
                    title: "{{ Session::get('alert-message') }}"
                });
            </script>
        @endif
        @stack('after-scripts')
        @if (trim($__env->yieldContent('page-script')))
            @yield('page-script')
        @endif
    </body>
</html>
