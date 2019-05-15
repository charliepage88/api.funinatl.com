@extends('layouts.admin')

@section('content')
    <form class="w-full" method="POST" action="{{ route('admin.locations.edit', [ 'location' => $location->id ]) }}" enctype="multipart/form-data">
        @csrf

        <h1 class="bg-brand font-bold text-center text-3xl md:text-5xl px-3">
            Edit Location
        </h1>

        <div class="flex flex-wrap -mx-3 mb-6">
            <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-name">
                    Name 
                    <span class="text-red-500 text-xs italic">*</span>
                </label>
                
                <input class="appearance-none block w-full bg-gray-200 text-gray-700 rounded py-3 px-4 mb-3 leading-tight" name="name" id="grid-name" type="text" value="{{ $location->name }}">

                @if ($errors->has('name'))
                    <span class="text-red-500 text-sm" role="alert">
                        {{ $errors->first('name') }}
                    </span>
                @endif
            </div>
            <div class="w-full md:w-1/3 px-3">
                <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-website">
                    Website 
                    <span class="text-red-500 text-xs italic">*</span>
                </label>
                
                <input class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight" name="website" id="grid-website" type="text" value="{{ $location->website }}">

                @if ($errors->has('website'))
                    <span class="text-red-500 text-sm" role="alert">
                        {{ $errors->first('website') }}
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
                        <option value="">Choose category</option>
                        @foreach($categories as $id => $name)
                            <option value="{{ $id }}" <?php echo $location->category_id === $id ? ' selected' : '' ?>>
                                {{ $name }}
                            </option>
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
        </div>

        <div class="flex flex-wrap -mx-3 mb-6">
            <div class="w-full md:w-1/3 px-3">
                <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-photo">
                    Photo
                </label>

                @if(!empty($location->photo_url))
                    <img class="h-128 w-128 mx-auto mb-2" src="{{ $location->photo_url }}">
                @endif
                
                <input class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 mb-3 leading-tight focus:border-gray-500" name="photo" id="grid-photo" type="file">
            </div>

            <div class="w-full md:w-2/3 px-3">
                <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-address">
                    Address 
                    <span class="text-red-500 text-xs italic">*</span>
                </label>
                
                <input class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 mb-3 leading-tight focus:border-gray-500" name="address" id="grid-address" type="text" value="{{ $location->address }}">

                @if ($errors->has('address'))
                    <span class="text-red-500 text-sm" role="alert">
                        {{ $errors->first('address') }}
                    </span>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap -mx-3 mb-2">
            <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-city">
                    City 
                    <span class="text-red-500 text-xs italic">*</span>
                </label>
                
                <input class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500" id="grid-city" type="text" name="city" value="{{ $location->city }}">

                @if ($errors->has('city'))
                    <span class="text-red-500 text-sm" role="alert">
                        {{ $errors->first('city') }}
                    </span>
                @endif
            </div>
            
            <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-state">
                    State 
                    <span class="text-red-500 text-xs italic">*</span>
                </label>
                
                <div class="relative">
                    <select class="block appearance-none w-full bg-gray-200 border border-gray-200 text-gray-700 py-3 px-4 pr-8 rounded leading-tight focus:outline-none focus:bg-white focus:border-gray-500" id="grid-state" name="state">
                        <option value="GA" selected>Georgia</option>
                    </select>

                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                    </div>
                </div>

                @if ($errors->has('state'))
                    <span class="text-red-500 text-sm" role="alert">
                        {{ $errors->first('state') }}
                    </span>
                @endif
            </div>
            
            <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-zip">
                    Zip 
                    <span class="text-red-500 text-xs italic">*</span>
                </label>
                
                <input class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500" id="grid-zip" type="text" name="zip" value="{{ $location->zip }}">

                @if ($errors->has('zip'))
                    <span class="text-red-500 text-sm" role="alert">
                        {{ $errors->first('zip') }}
                    </span>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap -mx-3 mb-6">
            <div class="w-full px-3">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 border border-blue-700 rounded">
                    Save
                </button>
            </div>
        </div>
    </form>
@endsection
