@extends('layouts.admin')

@section('content')
    <form class="form" method="POST" action="{{ route('admin.contact_submissions.review', [ 'submission' => $submission->id ]) }}">
        @csrf

        <h1 class="title is-1">
            Review Contact Form Submission
        </h1>

        <div class="columns is-multiline">
            <div class="column is-half">
                <label class="label">
                    Sender Name
                </label>
                
                <p class="text-gray-700">{{ $submission->name }}</p>
            </div>

            <div class="column is-half">
                <label class="label">
                    Sender Email Address
                </label>
                
                <p class="text-gray-700">
                    <a href="mailto:{{ $submission->email }}" target="_blank">
                        {{ $submission->email }}
                    </a>
                </p>
            </div>

            <div class="column is-half">
                <label class="label">
                    Sender Message
                </label>
                
                <p class="text-gray-700">{!! nl2br($submission->body) !!}</p>
            </div>

            @if(!$submission->reviewed)
                <div class="column is-half">
                    <label class="label">
                        Message Reply
                    </label>

                    <textarea class="textarea is-large" name="reply" rows="6">{{ old('reply') }}</textarea>
                </div>

                <div class="column is-full has-text-right">
                    <button type="submit" class="button is-primary is-large">
                        Submit
                    </button>
                </div>
            @endif
        </div>
    </form>
@endsection
