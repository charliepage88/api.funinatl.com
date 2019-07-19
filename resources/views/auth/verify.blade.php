@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="title is-1 is-size-2-tablet">
            Verify Your Email Address
        </h1>

        @if (session('resent'))
            <article class="message is-success">
                <div class="message-body">
                    A fresh verification link has been sent to your email address.
                </div>
            </article>
        @endif

        <p>
            Before proceeding, please check your email for a verification link. 
            If you did not receive the email, <a href="{{ route('verification.resend') }}">click here to request another</a>
        </p>
    </div>
@endsection
