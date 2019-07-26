@extends('layouts.admin')

@section('content')
    <form class="form" method="POST" action="{{ route('admin.tags.create') }}">
        @csrf

        <h1 class="title is-1">
            Create Tag
        </h1>

        <div class="columns is-multiline">
            <div class="w-full md:w-1/2 px-3 mb-6 md:mb-0">
                <label class="label" for="grid-name">
                    Name 
                    <span class="has-text-danger is-italic">*</span>
                </label>
                
                <input class="appearance-none block w-full bg-gray-200 text-gray-700 rounded py-3 px-4 mb-3 leading-tight" name="name" id="grid-name" type="text">

                @if ($errors->has('name'))
                    <p class="help is-danger">
                        {{ $errors->first('name') }}
                    </p>
                @endif
            </div>
        </div>

        <div class="columns is-multiline">
            <div class="w-full px-3">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 border border-blue-700 rounded">
                    Save
                </button>
            </div>
        </div>
    </form>
@endsection
