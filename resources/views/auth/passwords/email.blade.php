@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="title is-1 is-size-2-tablet">Forgot Password</h1>

        <form method="POST" action="{{ route('password.email') }}" class="form">
            @csrf

            <div class="field">
                <label class="label">Email Address</label>

                <div class="control">
                    <input type="email" name="email" class="input is-large@error('email') is-danger @enderror" required value="{{ old('email') }}" />
                </div>

                @error('email')
                    <p class="help is-danger">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="field">
                <button class="button is-primary is-fullwidth-mobile">
                    Send Password Reset Link
                </button>
            </div>
        </form>
    </div>
@endsection
