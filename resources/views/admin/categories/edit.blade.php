@extends('layouts.admin')

@section('content')
    <form class="form" method="POST" action="{{ route('admin.categories.edit', [ 'category' => $category->id ]) }}">
        @csrf

        <h1 class="title is-1">
            Edit Category
        </h1>

        <div class="columns is-multiline">
            <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                <label class="label" for="grid-name">
                    Name 
                    <span class="has-text-danger is-italic">*</span>
                </label>
                
                <input class="appearance-none block w-full bg-gray-200 text-gray-700 rounded py-3 px-4 mb-3 leading-tight" name="name" id="grid-name" type="text" value="{{ $category->name }}">

                @if ($errors->has('name'))
                    <p class="help is-danger">
                        {{ $errors->first('name') }}
                    </p>
                @endif
            </div>

            <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                <label class="label" for="grid-active">
                    Active 
                    <span class="has-text-danger is-italic">*</span>
                </label>

                <div class="mb-2">                
                    <div class="form-switch inline-block align-middle">
                        <input
                            type="checkbox"
                            name="active"
                            id="active"
                            class="form-switch-checkbox"
                            value="1"
                            <?php echo $category->active ? 'checked' : '' ?>
                        >
                        <label class="form-switch-label" for="active"></label>
                    </div>
                </div>
            </div>

            <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                <label class="label" for="grid-is-default">
                    Is Default
                </label>

                <div class="mb-2">                
                    <div class="form-switch inline-block align-middle">
                        <input
                            type="checkbox"
                            name="is_default"
                            id="is_default"
                            class="form-switch-checkbox"
                            value="1"
                            <?php echo $category->is_default ? 'checked' : '' ?>
                        >
                        <label class="form-switch-label" for="is_default"></label>
                    </div>
                </div>
            </div>
        </div>

        <div class="columns is-multiline">
            <div class="w-full md:w-1/3 px-3">
                <label class="label" for="grid-photo">
                    Photo
                </label>

                @if(!empty($category->photo_url))
                    <img class="h-128 w-128 mx-auto mb-2" src="{{ $category->photo_url }}">
                @endif
                
                <input class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 mb-3 leading-tight focus:border-gray-500" name="photo" id="grid-photo" type="file">
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
