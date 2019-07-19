@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="title is-1 is-size-2-tablet">Reset Password</h1>

        <form method="POST" action="{{ route('password.update') }}" class="form">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <div class="field">
                <label class="label">Email Address</label>

                <div class="control">
                    <input type="email" name="email" class="input is-large@error('email') is-danger @enderror" required value="{{ $email ?? old('email') }}" />
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
                    <input type="password" name="password" class="input is-large@error('email') is-danger @enderror" required autofocus autocomplete="new-password" />
                </div>

                @error('password')
                    <p class="help is-danger">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="field">
                <label class="label">Confirm Password</label>

                <div class="control">
                    <input type="password" name="password_confirmation" class="input is-large" required autofocus autocomplete="new-password" />
                </div>
            </div>

            <div class="field">
                <button class="button is-primary is-fullwidth-mobile">
                    Reset Password
                </button>
            </div>
        </form>
    </div>
@endsection
