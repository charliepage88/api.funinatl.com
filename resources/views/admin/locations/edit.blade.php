@extends('layouts.admin')

@section('content')
    <form class="form" method="POST" action="{{ route('admin.locations.edit', [ 'location' => $location->id ]) }}" enctype="multipart/form-data">
        @csrf

        <h1 class="title is-1">
            Edit Location
        </h1>

        <div class="columns is-multiline">
            <div class="column is-half">
                <div class="field">
                    <label class="label">
                        Name 
                        <span class="has-text-danger is-italic">*</span>
                    </label>
                    
                    <div class="control">
                        <input class="input is-medium" name="name" type="text" value="{{ old('name') ?? $location->name }}">
                    </div>

                    @if ($errors->has('name'))
                        <p class="help is-danger">
                            {{ $errors->first('name') }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="column is-half">
                <div class="field">
                    <label class="label">
                        Website 
                        <span class="has-text-danger is-italic">*</span>
                    </label>
                    
                    <div class="field">
                        <input class="input is-medium" name="website" type="text" value="{{ old('website') ?? $location->website }}">
                    </div>

                    @if ($errors->has('website'))
                        <p class="help is-danger">
                            {{ $errors->first('website') }}
                        </p>
                    @endif
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

            @if(!empty($location->photo_url))
                <div class="column is-one-quarter">
                    <figure class="image is-128x128">
                        <img src="{{ $location->photo_url }}">
                    </figure>
                </div>
            @endif

            <div class="column is-half">
                <div class="field">
                    <label class="label">
                        Category
                        <span class="has-text-danger is-italic">*</span>
                    </label>

                    <div class="control">
                        <div class="select is-medium is-fullwidth">
                            <select name="category_id">
                                <option value="">Choose Category</option>
                                @foreach($categories as $id => $name)
                                    <option
                                        value="{{ $id }}"
                                        {{ (old('category_id') ?? $location->category_id) === $id ? 'selected' : '' }}
                                    >
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if ($errors->has('category_id'))
                        <p class="help is-danger">
                            {{ $errors->first('category_id') }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="column is-full">
                <div class="field">
                    <label class="label">
                        Address 
                        <span class="has-text-danger is-italic">*</span>
                    </label>
                    
                    <div class="control">
                        <input class="input is-medium" type="text" name="address" value="{{ old('address') ?? $location->address }}">
                    </div>

                    @if ($errors->has('address'))
                        <p class="help is-danger">
                            {{ $errors->first('address') }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="column is-one-quarter">
                <div class="field">
                    <label class="label">
                        City 
                        <span class="has-text-danger is-italic">*</span>
                    </label>
                    
                    <div class="control">
                        <input class="input is-medium" type="text" name="city" value="{{ old('city') ?? $location->city }}">
                    </div>

                    @if ($errors->has('city'))
                        <p class="help is-danger">
                            {{ $errors->first('city') }}
                        </p>
                    @endif
                </div>
            </div>
            
            <div class="column is-one-quarter">
                <div class="field">
                    <label class="label">
                        State 
                        <span class="has-text-danger is-italic">*</span>
                    </label>
                    
                    <div class="control">
                        <div class="select is-medium is-fullwidth">
                            <select name="state">
                                <option value="GA">Georgia</option>
                            </select>
                        </div>
                    </div>

                    @if ($errors->has('state'))
                        <p class="help is-danger">
                            {{ $errors->first('state') }}
                        </p>
                    @endif
                </div>
            </div>
            
            <div class="column is-one-quarter">
                <div class="field">
                    <label class="label">
                        Zip 
                        <span class="has-text-danger is-italic">*</span>
                    </label>
                    
                    <div class="control">
                        <input class="input is-medium" type="text" name="zip" value="{{ old('zip') ?? $location->zip }}">
                    </div>

                    @if ($errors->has('zip'))
                        <p class="help is-danger">
                            {{ $errors->first('zip') }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="column is-narrow">
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
                                {{ (old('active') ?? $location->active) ? 'checked' : '' }}
                            >
                        </label>
                    </div>
                </div>
            </div>

            <div class="column is-narrow">
                <div class="field">
                    <label class="label">
                        Family Friendly
                    </label>

                    <div class="control">
                        <label class="checkbox">
                            <input
                                type="checkbox"
                                name="is_family_friendly"
                                value="1"
                                {{ (old('is_family_friendly') ?? $location->is_family_friendly) ? 'checked' : '' }}
                            >
                        </label>
                    </div>
                </div>
            </div>

            <div class="column is-full has-text-right">
                <button type="submit" class="button is-primary is-large">
                    Save
                </button>
            </div>
        </div>
    </form>
@endsection
