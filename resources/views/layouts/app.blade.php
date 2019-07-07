<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css" integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body class="bg-gray-200 leading-normal tracking-normal">
    <nav id="header" class="bg-white fixed w-full z-10 pin-t shadow">
        <div class="w-full container mx-auto flex flex-wrap items-center px-8 mt-0 pt-3 pb-3 md:pb-0">
            
            <div class="w-1/2 pl-2 md:pl-0">
                <a class="text-black text-base xl:text-xl no-underline hover:no-underline font-bold"  href="{{ route('admin.dashboard') }}"> 
                    <i class="fas fa-calendar-week text-orange-700 pr-3"></i>
                    <span>FunInATL</span>
                </a>
            </div>

            <div class="w-1/2 pr-0">
                <div class="flex relative inline-block float-right">
                    @if(Auth::check())
                        <div class="relative text-sm">
                            <button id="userButton" class="flex items-center focus:outline-none mr-3">
                                {{-- <img class="w-8 h-8 rounded-full mr-4" src="http://i.pravatar.cc/300" alt="Avatar of User"> --}}
                                <span class="hidden md:inline-block">Hi, {{ Auth::user()->name }}</span>
                                <i class="fas fa-angle-down fa-fw mr-3 text-black"></i>
                            </button>
                            
                            <div id="userMenu" class="bg-white rounded shadow-md mt-2 absolute mt-12 pin-t pin-r min-w-full overflow-auto z-30 invisible">
                                <ul class="list-reset">
                                    <li>
                                        <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 block text-black hover:bg-gray-300 no-underline hover:no-underline">
                                            Dashboard
                                        </a>
                                    </li>
                                    <li><hr class="border-t mx-2 border-gray-300"></li>
                                    <li>
                                        <a href="{{ route('logout') }}" class="px-4 py-2 block text-black hover:bg-gray-300 no-underline hover:no-underline">
                                            Logout
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    @else
                        <div class="relative text-sm">
                            <button id="userButton" class="flex items-center focus:outline-none mr-3">
                                <span class="hidden md:inline-block">Hi Guest,</span>
                                <i class="fas fa-angle-down fa-fw mr-3 text-black"></i>
                            </button>
                            
                            <div id="userMenu" class="bg-white rounded shadow-md mt-2 absolute mt-12 pin-t pin-r min-w-full overflow-auto z-30 invisible">
                                <ul class="list-reset">
                                    <li>
                                        <a href="{{ route('login') }}" class="px-4 py-2 block text-black hover:bg-gray-300 no-underline hover:no-underline">
                                            Login
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    @endif

                    <div class="block lg:hidden pr-4">
                        <button id="nav-toggle" class="flex items-center px-3 py-2 border rounded text-gray border-gray-600 hover:text-black hover:border-teal appearance-none focus:outline-none">
                            <svg class="fill-current h-3 w-3" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><title>Menu</title><path d="M0 3h20v2H0V3zm0 6h20v2H0V9zm0 6h20v2H0v-2z"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container w-full mx-auto px-16 pt-20">
        <div class="w-full px-4 md:px-0 md:mt-8 mb-16 text-gray-700 leading-normal">
            @yield('content')
        </div>
    </div>

    <footer class="bg-white border-t border-gray-300 shadow"> 
        <div class="container mx-auto flex py-8 mb-4">
            <p class="w-full text-gray-600 text-sm text-center">
              Copyright &copy; {{ date('Y') }} Charles Page. 
            </p>
        </div>
    </footer>
</body>
</html>
