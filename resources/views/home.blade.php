@extends('layouts.app')

@section('content')
    <div class="content">
        <h1 class="title is-1 is-size-2-tablet">Dashboard</h1>

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

        <p class="has-text-grey-light mb-1">
            Welcome!
        </p>
    </div>
@endsection
