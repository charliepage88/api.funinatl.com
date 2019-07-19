<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>

    <script defer src="//use.fontawesome.com/releases/v5.8.1/js/all.js" integrity="sha384-g5uSoOSBd7KkhAMlnQILrecXvzst9TdC09/VM+pjDTCM+1il8RHz5fKANTFFb+gQ" crossorigin="anonymous"></script>

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body class="has-navbar-fixed-top">
    <nav class="navbar is-fixed-top is-primary" role="navigation" aria-label="main navigation">
        <div class="navbar-brand">
            <a class="navbar-item" href="{{ env('PUBLIC_URL') }}">
              <img src="https://funinatl.nyc3.digitaloceanspaces.com/site/funinatl-logo.jpg" width="112" height="28">
            </a>

            <a role="button" class="navbar-burger burger" aria-label="menu" aria-expanded="false" data-target="appNavMenu">
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
            </a>
        </div>

        <div id="appNavMenu" class="navbar-menu">
            <div class="navbar-start">
                <a href="{{ env('PUBLIC_URL') }}" class="navbar-item">
                    Public Site
                </a>

                @if(Auth::check())
                    <a href="{{ route('admin.dashboard') }}" class="navbar-item">
                        Admin
                    </a>
                @endif
            </div>

            <div class="navbar-end">
                @if(Auth::check())
                    <div class="navbar-item has-dropdown is-hoverable">
                        <a class="navbar-link">
                            Hi, {{ Auth::user()->name }}
                        </a>

                        <div class="navbar-dropdown">
                            <a href="{{ route('admin.dashboard') }}" class="navbar-item">
                                Admin
                            </a>

                            <a href="{{ route('admin.logout') }}" class="navbar-item">
                                Logout
                            </a>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="navbar-item">
                        Login
                    </a>
                @endif
            </div>
        </div>
    </nav>

    <section class="section is-content has-background-white-bis pr-0 pl-0" id="app">
        <div class="container is-fluid pl-4 pr-4">
            <div class="centered-container">
                <div class="box shadow-md rounded">
                    @if (session('is-danger'))
                        <article class="message is-danger">
                            <div class="message-header">
                                <p>Error</p>
                            </div>

                            <div class="message-body">
                                {{ session('is-danger') }}
                            </div>
                        </article>
                    @endif

                    @if (session('is-success'))
                        <article class="message is-success">
                            <div class="message-header">
                                <p>Success</p>
                            </div>

                            <div class="message-body">
                                {{ session('is-success') }}
                            </div>
                        </article>
                    @endif

                    @if (session('status'))
                        <article class="message is-success">
                            <div class="message-header">
                                <p>Success</p>
                            </div>

                            <div class="message-body">
                                {{ session('status') }}
                            </div>
                        </article>
                    @endif

                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p class="has-text-centered">
                Copyright &copy; {{ date('Y') }} Charles Page. 
            </p>
        </div>
    </footer>
</body>
</html>
