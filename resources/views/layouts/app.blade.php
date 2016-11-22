<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="57x57" href="/favicons/apple-touch-icon-57x57.png?v=PYY5xnPp8v">
    <link rel="apple-touch-icon" sizes="60x60" href="/favicons/apple-touch-icon-60x60.png?v=PYY5xnPp8v">
    <link rel="apple-touch-icon" sizes="72x72" href="/favicons/apple-touch-icon-72x72.png?v=PYY5xnPp8v">
    <link rel="apple-touch-icon" sizes="76x76" href="/favicons/apple-touch-icon-76x76.png?v=PYY5xnPp8v">
    <link rel="apple-touch-icon" sizes="114x114" href="/favicons/apple-touch-icon-114x114.png?v=PYY5xnPp8v">
    <link rel="apple-touch-icon" sizes="120x120" href="/favicons/apple-touch-icon-120x120.png?v=PYY5xnPp8v">
    <link rel="apple-touch-icon" sizes="144x144" href="/favicons/apple-touch-icon-144x144.png?v=PYY5xnPp8v">
    <link rel="apple-touch-icon" sizes="152x152" href="/favicons/apple-touch-icon-152x152.png?v=PYY5xnPp8v">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon-180x180.png?v=PYY5xnPp8v">
    <link rel="icon" type="image/png" href="/favicons/favicon-32x32.png?v=PYY5xnPp8v" sizes="32x32">
    <link rel="icon" type="image/png" href="/favicons/favicon-194x194.png?v=PYY5xnPp8v" sizes="194x194">
    <link rel="icon" type="image/png" href="/favicons/favicon-96x96.png?v=PYY5xnPp8v" sizes="96x96">
    <link rel="icon" type="image/png" href="/favicons/android-chrome-192x192.png?v=PYY5xnPp8v" sizes="192x192">
    <link rel="icon" type="image/png" href="/favicons/favicon-16x16.png?v=PYY5xnPp8v" sizes="16x16">
    <link rel="manifest" href="/favicons/manifest.json?v=PYY5xnPp8v">
    <link rel="mask-icon" href="/favicons/safari-pinned-tab.svg?v=PYY5xnPp8v" color="#8c001a">
    <link rel="shortcut icon" href="/favicons/favicon.ico?v=PYY5xnPp8v">
    <meta name="apple-mobile-web-app-title" content="Chronos">
    <meta name="application-name" content="Chronos">
    <meta name="msapplication-TileColor" content="#b91d47">
    <meta name="msapplication-TileImage" content="/favicons/mstile-144x144.png?v=PYY5xnPp8v">
    <meta name="msapplication-config" content="/favicons/browserconfig.xml?v=PYY5xnPp8v">
    <meta name="theme-color" content="#8c001a">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Styles -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.5/css/bootstrap.min.css" integrity="sha384-AysaV+vQoT3kOAXZkl02PThvDr8HYKPZhNT5h/CXfBThSRXQ6jW5DO2ekP5ViFdi" crossorigin="anonymous">
    <link href="/css/app.css" rel="stylesheet">

    <!-- Scripts -->
    <script>
        window.Laravel = <?php echo json_encode([
            'csrfToken' => csrf_token(),
        ]); ?>
    </script>

    <script src="https://use.fontawesome.com/9abe837e3b.js"></script>
</head>
<body>
    <nav class="navbar navbar-fixed-top navbar-light bg-faded">
        <div class="container">

            <button class="navbar-toggler hidden-lg-up" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation"></button>
            <div class="collapse navbar-toggleable-md" id="navbarResponsive">
                <!-- Branding Image -->
                <div class="navbar-brand">
                    <img class="mr-1" src="/img/chronos.png" height="26" alt="Chronos">
                </div>

                <!-- Left side of navbar -->
                <ul class="nav navbar-nav">
                    @if (Auth::check())
                        <li class="nav-item {{ active('calendar') }}"><a class="nav-link" href="{{ route('calendar') }}">Calendar</a></li>
                        <li class="nav-item {{ active('reservationList') }}"><a class="nav-link" href="{{ route('reservationList') }}">My reservations</a></li>
                    @endif
                </ul>

                <!-- Right side of navbar -->
                <ul class="nav navbar-nav float-xs-right">
                    <!-- Authentication Links -->
                    @if (Auth::guest())
                        <li class="nav-item"><a class="nav-link" href="{{ url('/login') }}">Login</a></li>
                    @else
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="supportedContentDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                {{ Auth::user()->name }} <span class="caret"></span>
                            </a>
                            <div class="dropdown-menu" aria-labelledby="supportedContentDropdown">
                                <a class="dropdown-item" href="{{ url('/logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    Logout
                                </a>

                                <form id="logout-form" action="{{ url('/logout') }}" method="POST" style="display: none;">
                                    {{ csrf_field() }}
                                </form>
                            </div>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        @if (Session::has('status'))
            <div class="alert alert-info" role="alert">
                <i class="fa fa-info-circle" aria-hidden="true"></i> {!! Session::get('status') !!}
            </div>
        @endif

        @if (Session::has('success'))
            <div class="alert alert-success" role="alert">
                <i class="fa fa-check-circle" aria-hidden="true"></i> {!! Session::get('success') !!}
            </div>
        @endif

        @if (Session::has('warning'))
            <div class="alert alert-warning" role="alert">
                <i class="fa fa-exclamation-triangle" aria-hidden="true"></i> {!! Session::get('warning') !!}
            </div>
        @endif

        @if (Session::has('error'))
            <div class="alert alert-danger" role="alert">
                <i class="fa fa-times-circle" aria-hidden="true"></i> {!! Session::get('error') !!}
            </div>
        @endif
    </div>

@yield('content')

    <!-- Scripts -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js" integrity="sha384-3ceskX3iaEnIogmQchP8opvBy3Mi7Ce34nWjpBIwVTHfGYWQS9jwHDVRnpKKHJg7" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tether/1.3.7/js/tether.min.js" integrity="sha384-XTs3FgkjiBgo8qjEjBk0tGmf3wPrWtA6coPfQDfFEY8AnYJwjalXCiosYRBIBZX8" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.5/js/bootstrap.min.js" integrity="sha384-BLiI7JTZm+JWlgKa0M0kGRpJbF2J8q+qreVrKBC47e3K6BW78kGLrCkeRX6I9RoK" crossorigin="anonymous"></script>
    {{--<script src="/js/app.js"></script>--}}

    @stack('scripts')
</body>
</html>
