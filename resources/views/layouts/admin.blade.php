<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script defer src="//use.fontawesome.com/releases/v5.8.1/js/all.js" integrity="sha384-g5uSoOSBd7KkhAMlnQILrecXvzst9TdC09/VM+pjDTCM+1il8RHz5fKANTFFb+gQ" crossorigin="anonymous"></script>

    <!-- Scripts -->
    <script src="{{ \SiteHelper::cdn_asset('js/app.js') }}" defer></script>

    <!-- Styles -->
    <link href="{{ \SiteHelper::cdn_asset('css/app.css') }}" rel="stylesheet">
</head>

<body class="has-navbar-fixed-top">
    <?php $routeName = \Request::route()->getName() ?>

    <nav class="navbar is-fixed-top is-primary" role="navigation" aria-label="main navigation">
        <div class="navbar-brand">
            <a href="{{ route('admin.dashboard') }}" class="navbar-item {{ ($routeName === 'admin.dashboard' ? 'is-active' : '') }}">
                <img src="https://funinatl.nyc3.digitaloceanspaces.com/site/funinatl-logo.jpg" alt="FunInATL Admin" />
            </a>

            <a role="button" class="navbar-burger burger" aria-label="menu" aria-expanded="false" data-target="appNavMenu">
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
            </a>
        </div>

        <div id="appNavMenu" class="navbar-menu">
            <div class="navbar-start">
                <a href="{{ env('PUBLIC_URL') }}" class="navbar-item is-hidden-desktop">
                    <span class="icon mr-px-3">
                        <i class="fas fa-home fa-fw"></i>
                    </span>
                    <span>Public Site</span>
                </a>

                <a href="{{ route('admin.events.index') }}" class="navbar-item {{ (strstr($routeName, 'admin.events') ? 'is-active' : '') }}">
                    <span class="icon mr-px-3">
                        <i class="fas fa-calendar-alt fa-fw"></i>
                    </span>
                    <span>Events</span>
                </a>

                <a href="{{ route('admin.locations.index') }}" class="navbar-item {{ (strstr($routeName, 'admin.locations') ? 'is-active' : '') }}">
                    <span class="icon mr-px-3">
                        <i class="fas fa-map fa-fw"></i>
                    </span>
                    <span>Locations</span>
                </a>

                <a href="{{ route('admin.providers.index') }}" class="navbar-item {{ (strstr($routeName, 'admin.providers') ? 'is-active' : '') }}">
                    <span class="icon mr-px-3">
                        <i class="fas fa-cogs fa-fw"></i>
                    </span>
                    <span>Providers</span>
                </a>

                <a href="{{ route('admin.categories.index') }}" class="navbar-item {{ (strstr($routeName, 'admin.categories') ? 'is-active' : '') }}">
                    <span class="icon mr-px-3">
                        <i class="fas fa-tasks fa-fw"></i>
                    </span>
                    <span>Categories</span>
                </a>

                <a href="{{ route('admin.users.index') }}" class="navbar-item {{ (strstr($routeName, 'admin.users') ? 'is-active' : '') }}">
                    <span class="icon mr-px-3">
                        <i class="fas fa-users fa-fw"></i>
                    </span>
                    <span>Users</span>
                </a>

                <a href="{{ route('admin.bands.index') }}" class="navbar-item {{ (strstr($routeName, 'admin.bands') ? 'is-active' : '') }}">
                    <span class="icon mr-px-3">
                        <i class="fas fa-music fa-fw"></i>
                    </span>
                    <span>Bands</span>
                </a>

                <a href="{{ route('admin.tags.index') }}" class="navbar-item {{ (strstr($routeName, 'admin.tags') ? 'is-active' : '') }}">
                    <span class="icon mr-px-3">
                        <i class="fas fa-tags fa-fw"></i>
                    </span>
                    <span>Tags</span>
                </a>

                <div class="navbar-item has-dropdown is-hoverable">
                    <div class="navbar-link {{ (strstr($routeName, 'admin.reports') ? 'is-active' : '') }}">
                        <span class="icon mr-px-3">
                            <i class="fas fa-chart-line fa-fw"></i>
                        </span>
                        <span>Reports</span>
                    </div>
                    <div class="navbar-dropdown is-boxed">
                        <a class="navbar-item {{ $routeName === 'admin.reports.daily_tweets' ? 'is-active' : '' }}" href="{{ route('admin.reports.daily_tweets') }}">
                            Daily Tweets
                        </a>
                    </div>
                </div>
            </div>

            <div class="navbar-end">
                <div class="navbar-item is-hidden-desktop">
                    <div class="field has-addons">
                        <div class="control">
                            <input class="input" type="text" placeholder="Search here">
                        </div>
                        <div class="control">
                            <a class="button is-white">
                                <i class="fas fa-search"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <a href="{{ route('admin.logout') }}" class="navbar-item is-hidden-touch">
                    Logout
                </a>

                <div class="navbar-item has-dropdown is-hoverable is-hidden-desktop">
                    <a class="navbar-link ">
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
    </section>

    <footer class="footer">
        <div class="container">
            <p class="has-text-centered">
                Copyright &copy; {{ date('Y') }} Charles Page.
            </p>
        </div>
    </footer>
</body>
</html>
