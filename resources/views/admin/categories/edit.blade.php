@extends('layouts.admin')

@section('content')
    <form class="form" method="POST" action="{{ route('admin.categories.edit', [ 'category' => $category->id ]) }}" enctype="multipart/form-data">
        @csrf

        <h1 class="title is-1">
            Edit Category
        </h1>

        <div class="columns is-multiline">
            <div class="column is-half">
                <div class="field">
                    <label class="label">
                        Name 
                        <span class="has-text-danger is-italic">*</span>
                    </label>
                    
                    <div class="control">
                        <input class="input is-medium" name="name" type="text" value="{{ old('name') ?? $category->name }}">
                    </div>

                    @if ($errors->has('name'))
                        <p class="help is-danger">
                            {{ $errors->first('name') }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="column is-one-quarter">
                <div class="field">
                    <label class="label">
                        Active
                        <span class="has-text-danger is-italic">*</span>
                    </label>

                    <div class="control">
                        <label class="checkbox">
                            <input
                                type="checkbox"
                                name="active"
                                value="1"
                                {{ (old('active') ?? $category->active) ? 'checked' : '' }}
                            >
                        </label>
                    </div>
                </div>
            </div>

            <div class="column is-one-quarter">
                <div class="field">
                    <label class="label">
                        Is Default
                    </label>

                    <div class="control">
                        <label class="checkbox">
                            <input
                                type="checkbox"
                                name="is_default"
                                value="1"
                                {{ (old('is_default') ?? $category->is_default) ? 'checked' : '' }}
                            >
                        </label>
                    </div>
                </div>
            </div>

            <div class="column is-one-quarter">
                <div class="field">
                    <label class="label">
                        Photo
                    </label>

                    <div class="file is-warning is-boxed">
                        <label class="file-label">
                            <input class="file-input" type="file" name="photo">
                            <span class="file-cta">
                                <span class="file-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </span>
                                <span class="file-label">
                                    Choose a photo
                                </span>
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            @if(!empty($category->photo_url))
                <div class="column is-one-quarter">
                    <figure class="image is-128x128">
                        <img src="{{ $category->photo_url }}">
                    </figure>
                </div>
            @endif

            <div class="column is-full has-text-right">
                <button type="submit" class="button is-primary is-large is-fullwidth-mobile">
                    Save
                </button>
            </div>
        </div>
    </form>
@endsection
