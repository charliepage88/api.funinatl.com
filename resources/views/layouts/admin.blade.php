<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>{{ config('app.name', 'Laravel') }}</title>
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script src="https://kit.fontawesome.com/ed3def2da2.js"></script>
    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.2/Chart.bundle.min.js" integrity="sha256-XF29CBwU1MWLaGEnsELogU6Y6rcc5nCkhhx89nFMIDQ=" crossorigin="anonymous"></script> --}}

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>

<body class="bg-gray-200 leading-normal tracking-normal">
    <nav id="header" class="bg-white fixed w-full z-10 pin-t shadow">
        <div class="w-full container mx-auto flex flex-wrap items-center mt-0 pt-3 pb-3 md:pb-0">
            
            <div class="w-1/2 pl-2 md:pl-0">
                <a class="text-black text-base xl:text-xl no-underline hover:no-underline font-bold"  href="{{ route('admin.dashboard') }}"> 
                    <i class="fas fa-calendar-week text-orange-700 pr-3"></i>
                    <span>FunInATL Admin Dashboard</span>
                </a>
            </div>

            <div class="w-1/2 pr-0">
                <div class="flex relative inline-block float-right">
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

                    <div class="block lg:hidden pr-4">
                        <button id="nav-toggle" class="flex items-center px-3 py-2 border rounded text-gray border-gray-600 hover:text-black hover:border-teal appearance-none focus:outline-none">
                            <svg class="fill-current h-3 w-3" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <title>Menu</title>
                                <path d="M0 3h20v2H0V3zm0 6h20v2H0V9zm0 6h20v2H0v-2z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="w-full flex-grow lg:flex lg:items-center lg:w-auto hidden lg:block mt-2 lg:mt-0 bg-white z-20" id="nav-content">
                <?php $routeName = \Request::route()->getName() ?>

                <ul class="list-reset lg:flex flex-1 items-center px-4 md:px-0">
                    <li class="mr-6 my-2 md:my-0">
                        <a href="{{ route('admin.dashboard') }}" class="block py-1 md:py-3 pl-1 align-middle no-underline hover:text-black border-b-2 <?php echo ($routeName === 'admin.dashboard' ? 'text-orange-600 border-orange-600 hover:border-orange-600' : 'text-gray border-white hover:border-purple') ?>">
                            <i class="fas fa-home fa-fw mr-1"></i>
                            <span class="pb-1 md:pb-0 text-sm">Dashboard</span>
                        </a>
                    </li>

                    <li class="mr-6 my-2 md:my-0">
                        <a href="{{ route('admin.events.index') }}" class="block py-1 md:py-3 pl-1 align-middle no-underline hover:text-black border-b-2 <?php echo (strstr($routeName, 'admin.events') ? 'text-orange-600 border-orange-600 hover:border-orange-600' : 'text-gray border-white hover:border-purple') ?>">
                            <i class="fa fa-calendar-alt fa-fw mr-1"></i>
                            <span class="pb-1 md:pb-0 text-sm">Events</span>
                        </a>
                    </li>

                    <li class="mr-6 my-2 md:my-0">
                        <a href="{{ route('admin.locations.index') }}" class="block py-1 md:py-3 pl-1 align-middle no-underline hover:text-black border-b-2 <?php echo (strstr($routeName, 'admin.locations') ? 'text-orange-600 border-orange-600 hover:border-orange-600' : 'text-gray border-white hover:border-purple') ?>">
                            <i class="fas fa-map fa-fw mr-1"></i>
                            <span class="pb-1 md:pb-0 text-sm">Locations</span>
                        </a>
                    </li>

                    <li class="mr-6 my-2 md:my-0">
                        <a href="{{ route('admin.providers.index') }}" class="block py-1 md:py-3 pl-1 align-middle no-underline hover:text-black border-b-2 <?php echo (strstr($routeName, 'admin.providers') ? 'text-orange-600 border-orange-600 hover:border-orange-600' : 'text-gray border-white hover:border-purple') ?>">
                            <i class="fa fa-cogs fa-fw mr-1"></i>
                            <span class="pb-1 md:pb-0 text-sm">Providers</span>
                        </a>
                    </li>

                    <li class="mr-6 my-2 md:my-0">
                        <a href="{{ route('admin.categories.index') }}" class="block py-1 md:py-3 pl-1 align-middle no-underline hover:text-black border-b-2 <?php echo (strstr($routeName, 'admin.categories') ? 'text-orange-600 border-orange-600 hover:border-orange-600' : 'text-gray border-white hover:border-purple') ?>">
                            <i class="fas fa-tasks fa-fw mr-1"></i>
                            <span class="pb-1 md:pb-0 text-sm">Categories</span>
                        </a>
                    </li>

                    <li class="mr-6 my-2 md:my-0">
                        <a href="{{ route('admin.users.index') }}" class="block py-1 md:py-3 pl-1 align-middle no-underline hover:text-black border-b-2 <?php echo (strstr($routeName, 'admin.users') ? 'text-orange-600 border-orange-600 hover:border-orange-600' : 'text-gray border-white hover:border-purple') ?>">
                            <i class="fa fa-users fa-fw mr-1"></i>
                            <span class="pb-1 md:pb-0 text-sm">Users</span>
                        </a>
                    </li>

                    <li class="mr-6 my-2 md:my-0">
                        <a href="{{ route('admin.tags.index') }}" class="block py-1 md:py-3 pl-1 align-middle no-underline hover:text-black border-b-2 <?php echo (strstr($routeName, 'admin.tags') ? 'text-orange-600 border-orange-600 hover:border-orange-600' : 'text-gray border-white hover:border-purple') ?>">
                            <i class="fa fa-tags fa-fw mr-1"></i>
                            <span class="pb-1 md:pb-0 text-sm">Tags</span>
                        </a>
                    </li>
                </ul>
                
                <div class="relative pull-right pl-4 pr-4 md:pr-0">
                    <input type="search" placeholder="Search" class="w-full bg-gray-300 text-sm text-gray-700 transition border focus:outline-none focus:border-gray-600er rounded py-1 px-2 pl-10 appearance-none leading-normal">
                    <div class="absolute search-icon" style="top: 0.375rem;left: 1.75rem;">
                        <svg class="fill-current pointer-events-none text-gray-700 w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                            <path d="M12.9 14.32a8 8 0 1 1 1.41-1.41l5.35 5.33-1.42 1.42-5.33-5.34zM8 14A6 6 0 1 0 8 2a6 6 0 0 0 0 12z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!--Container-->
    <div class="container w-full mx-auto pt-20">
        <div id="app" class="w-full px-4 md:px-0 md:mt-8 mb-16 text-gray-700 leading-normal">
            @if(session()->has('is-error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-8" role="alert">
                  <strong class="font-bold">Error</strong>
                  <span class="block sm:inline">{{ session()->get('is-error') }}</span>
                  <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                    <i class="fas fa-times"></i>
                  </span>
                </div>
            @endif

            @if(session()->has('is-success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-8" role="alert">
                  <strong class="font-bold">Success</strong>
                  <span class="block sm:inline">{{ session()->get('is-success') }}</span>
                  <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                    <i class="fas fa-times"></i>
                  </span>
                </div>
            @endif

            @yield('content')
        </div>
    </div> 
    <!--/container-->
  
    <footer class="bg-white border-t border-gray-300 shadow"> 
        <div class="container mx-auto flex py-8 mb-4">
            <p class="w-full text-gray-600 text-sm text-center">
              Copyright &copy; {{ date('Y') }} Charles Page. 
            </p>
        </div>
    </footer>
</body>
</html>