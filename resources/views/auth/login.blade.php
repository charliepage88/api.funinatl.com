@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="title is-1 is-size-2-tablet">Login</h1>

        <form method="POST" action="{{ route('login') }}" class="form">
            @csrf

            <div class="field">
                <label class="label">Email Address</label>

                <div class="control">
                    <input type="email" name="email" class="input is-large" required autofocus autocomplete="email" />
                </div>

                @error('email')
                    <p class="help is-danger">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="field">
                <label class="label">Password</label>

                <div class="control">
                    <input type="password" name="password" class="input is-large" required autofocus autocomplete="password" />
                </div>

                @error('password')
                    <p class="help is-danger">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <input type="hidden" name="remember" value="1">

            <div class="field">
                <button class="button is-primary is-fullwidth-mobile">
                    Login
                </button>
            </div>
        </form>
    </div>
@endsection
