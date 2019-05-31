@extends('layouts.app')

@section('content')
    <div class="container">
        <form method="POST" action="{{ route('login') }}" class="bg-blue-500 text-center w-1/3 px-3 py-4 text-gray-600 mx-auto rounded">
            @csrf

            <input type="text" name="email" placeholder="E-Mail Address" class="block w-full mx-auto text-sm py-2 px-3 rounded" required autofocus autocomplete="email" />

            @error('email')
                <span class="block bg-red-400 mx-auto" role="alert">
                    <strong class="font-bold">{{ $message }}</strong>
                </span>
            @enderror

            <input type="password" name="password" placeholder="Password" class="block w-full mx-auto text-sm py-2 px-3 rounded my-3" />

            @error('password')
                <span class="block bg-red-400 mx-auto" role="alert">
                    <strong class="font-bold">{{ $message }}</strong>
                </span>
            @enderror

            <input type="hidden" name="remember" value="1">

            <button class="bg-blue-500 text-white font-bold py-2 px-4 rounded border block mx-auto w-full">
                Login
            </button>
        </form>
    </div>
@endsection
