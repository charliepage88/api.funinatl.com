@extends('layouts.admin')

@section('content')
    <form class="w-full" method="POST" action="{{ route('admin.contact_submissions.review', [ 'submission' => $submission->id ]) }}">
        @csrf

        <h1 class="bg-brand font-bold text-center text-3xl md:text-5xl px-3">
            Review Contact Form Submission
        </h1>

        <div class="flex flex-wrap -mx-3 mb-6">
            <div class="w-full px-3 pb-8 md:mb-0">
                <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2">
                    Sender Name
                </label>
                
                <p class="text-gray-700">{{ $submission->name }}</p>
            </div>

            <div class="w-full px-3 pb-8 md:mb-0">
                <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2">
                    Sender Email Address
                </label>
                
                <p class="text-gray-700">
                    <a href="mailto:{{ $submission->email }}" class="text-blue-500" target="_blank">
                        {{ $submission->email }}
                    </a>
                </p>
            </div>

            <div class="w-full px-3 pb-16 md:mb-0">
                <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2">
                    Sender Message
                </label>
                
                <p class="text-gray-700">{!! nl2br($submission->body) !!}</p>
            </div>
        </div>

        <div class="flex flex-wrap -mx-3 mb-6">
            <div class="w-full px-3 mb-6 md:mb-0">
                <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="reply">
                    Message Reply
                </label>

                <textarea class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500" id="reply" name="reply" rows="12">{{ old('reply') }}</textarea>
            </div>
        </div>

        <div class="flex flex-wrap -mx-3 mb-6">
            <div class="w-full px-3">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 border border-blue-700 rounded">
                    Submit
                </button>
            </div>
        </div>
    </form>
@endsection
