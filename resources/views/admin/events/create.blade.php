@extends('layouts.admin')

@section('content')
    <form class="w-full" method="POST" action="{{ route('admin.events.create') }}" enctype="multipart/form-data">
        @csrf

        <h1 class="bg-brand font-bold text-center text-3xl md:text-5xl px-3">
            Create Event
        </h1>

        <div class="flex flex-wrap -mx-3 mb-6">
            <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-name">
                    Name 
                    <span class="text-red-500 text-xs italic">*</span>
                </label>
                
                <input class="appearance-none block w-full bg-gray-200 text-gray-700 rounded py-3 px-4 mb-3 leading-tight" name="name" id="grid-name" type="text">

                @if ($errors->has('name'))
                    <span class="text-red-500 text-sm" role="alert">
                        {{ $errors->first('name') }}
                    </span>
                @endif
            </div>

            <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-category">
                    Category 
                    <span class="text-red-500 text-xs italic">*</span>
                </label>
                
                <div class="relative">
                    <select class="block appearance-none w-full bg-gray-200 border border-gray-200 text-gray-700 py-3 px-4 pr-8 rounded leading-tight focus:outline-none focus:bg-white focus:border-gray-500" id="grid-category" name="category_id">
                        <option value="">Choose Category</option>
                        @foreach($categories as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>

                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                    </div>
                </div>

                @if ($errors->has('category_id'))
                    <span class="text-red-500 text-sm" role="alert">
                        {{ $errors->first('category_id') }}
                    </span>
                @endif
            </div>

            <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-location">
                    Location 
                    <span class="text-red-500 text-xs italic">*</span>
                </label>
                
                <div class="relative">
                    <select class="block appearance-none w-full bg-gray-200 border border-gray-200 text-gray-700 py-3 px-4 pr-8 rounded leading-tight focus:outline-none focus:bg-white focus:border-gray-500" id="grid-location" name="location_id">
                        <option value="">Choose Location</option>
                        @foreach($locations as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>

                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                    </div>
                </div>

                @if ($errors->has('location_id'))
                    <span class="text-red-500 text-sm" role="alert">
                        {{ $errors->first('location_id') }}
                    </span>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap -mx-3 mb-6">
            <div class="w-full md:w-1/3 px-3">
                <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-photo">
                    Photo
                </label>
                
                <input class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 mb-3 leading-tight focus:border-gray-500" name="photo" id="grid-photo" type="file">
            </div>

            <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-start-date">
                    Start Date 
                    <span class="text-red-500 text-xs italic">*</span>
                </label>
                
                <date-picker name="start_date" />

                @if ($errors->has('start_date'))
                    <span class="text-red-500 text-sm" role="alert">
                        {{ $errors->first('start_date') }}
                    </span>
                @endif
            </div>

            <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-end-date">
                    End Date 
                </label>
                
                <date-picker name="end_date" />

                @if ($errors->has('end_date'))
                    <span class="text-red-500 text-sm" role="alert">
                        {{ $errors->first('end_date') }}
                    </span>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap -mx-3 mb-2">
            <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-price">
                    Price 
                    <span class="text-red-500 text-xs italic">*</span>
                </label>
                
                <input class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500" id="grid-price" type="text" name="price">

                @if ($errors->has('price'))
                    <span class="text-red-500 text-sm" role="alert">
                        {{ $errors->first('price') }}
                    </span>
                @endif
            </div>
            
            <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-start-time">
                    Start Time 
                    <span class="text-red-500 text-xs italic">*</span>
                </label>
                
                <input class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500" id="grid-start-time" type="text" name="start_time">

                @if ($errors->has('start_time'))
                    <span class="text-red-500 text-sm" role="alert">
                        {{ $errors->first('start_time') }}
                    </span>
                @endif
            </div>
            
            <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-end-time">
                    End Time
                </label>
                
                <input class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500" id="grid-end-time" type="text" name="end_time">

                @if ($errors->has('end_time'))
                    <span class="text-red-500 text-sm" role="alert">
                        {{ $errors->first('end_time') }}
                    </span>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap -mx-3 mb-2 pt-4">
            <div class="w-1/3 md:w-1/3 px-3 mb-6 md:mb-0">
                <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-is-family-friendly">
                    Family Friendly 
                </label>

                <div class="mb-2">                
                    <div class="form-switch inline-block align-middle">
                        <input
                            type="checkbox"
                            name="is_family_friendly"
                            id="is_family_friendly"
                            class="form-switch-checkbox"
                            value="1"
                        >
                        <label class="form-switch-label" for="is_family_friendly"></label>
                    </div>
                </div>
            </div>

            <div class="w-1/3 md:w-1/3 px-3 mb-6 md:mb-0">
                <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-active">
                    Active
                    <span class="text-red-500 text-xs italic">*</span>
                </label>

                <div class="mb-2">                
                    <div class="form-switch inline-block align-middle">
                        <input
                            type="checkbox"
                            name="active"
                            id="active"
                            class="form-switch-checkbox"
                            value="1"
                            checked
                        >
                        <label class="form-switch-label" for="active"></label>
                    </div>
                </div>
            </div>

            <div class="w-1/3 md:w-1/3 px-3 mb-6 md:mb-0">
                <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-featured">
                    Featured
                </label>

                <div class="mb-2">                
                    <div class="form-switch inline-block align-middle">
                        <input
                            type="checkbox"
                            name="featured"
                            id="featured"
                            class="form-switch-checkbox"
                            value="1"
                        >
                        <label class="form-switch-label" for="featured"></label>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap -mx-3 mb-2">
            <div class="w-1/2 md:w-1/2 px-3 mb-6 md:mb-0">
                <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-short-description">
                    Short Description
                </label>

                <textarea class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500" id="grid-short-description" name="short_description"></textarea>

                @if ($errors->has('short_description'))
                    <span class="text-red-500 text-sm" role="alert">
                        {{ $errors->first('short_description') }}
                    </span>
                @endif
            </div>

            <div class="w-1/2 md:w-1/2 px-3 mb-6 md:mb-0">
                <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-description">
                    Description
                </label>

                <textarea class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500" id="grid-description" name="description"></textarea>

                @if ($errors->has('description'))
                    <span class="text-red-500 text-sm" role="alert">
                        {{ $errors->first('description') }}
                    </span>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap -mx-3 pt-4">
            <div class="w-full px-3">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 border border-blue-700 rounded">
                    Save
                </button>
            </div>
        </div>
    </form>
@endsection
