@extends('layouts.admin')

@section('content')
    <form class="form" method="POST" action="{{ route('admin.events.edit', [ 'event' => $event->id ]) }}" enctype="multipart/form-data">
        @csrf

        <h1 class="title is-1">
            Edit Event
        </h1>

        <div class="columns is-multiline">
            <div class="column is-half">
                <div class="field">
                    <label class="label">
                        Name
                        <span class="has-text-danger is-italic">*</span>
                    </label>

                    <div class="control">
                        <input class="input is-medium" name="name" type="text" value="{{ old('name') ?? $event->name }}">
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
                                        {{ (old('category_id') ?? $event->category_id) === $id ? 'selected' : '' }}
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

            <div class="column is-one-quarter">
                <div class="field">
                    <label class="label">
                        Location
                        <span class="has-text-danger is-italic">*</span>
                    </label>

                    <div class="control">
                        <div class="select is-medium is-fullwidth">
                            <select name="location_id">
                                <option value="">Choose Location</option>
                                @foreach($locations as $id => $name)
                                    <option
                                        value="{{ $id }}"
                                        {{ (old('location_id') ?? $event->location_id) === $id ? 'selected' : '' }}
                                    >
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if ($errors->has('location_id'))
                        <p class="help is-danger">
                            {{ $errors->first('location_id') }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="column is-one-quarter">
                <div class="field">
                    <label class="label">
                        Start Date
                        <span class="has-text-danger is-italic">*</span>
                    </label>

                    <admin-form-date-picker
                        name="start_date"
                        value="{{ old('start_date') ?? $event->start_date }}"
                    />

                    @if ($errors->has('start_date'))
                        <p class="help is-danger">
                            {{ $errors->first('start_date') }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="column is-one-quarter">
                <div class="field">
                    <label class="label">
                        End Date
                    </label>

                    <admin-form-date-picker
                        name="end_date"
                        value="{{ old('end_date') ?? $event->end_date }}"
                    />

                    @if ($errors->has('end_date'))
                        <p class="help is-danger">
                            {{ $errors->first('end_date') }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="column is-one-quarter">
                <div class="field">
                    <label class="label">
                        Start Time
                        <span class="has-text-danger is-italic">*</span>
                    </label>

                    <admin-form-time-picker
                        name="start_time"
                        value="{{ old('start_time') ?? $event->start_time_formatted }}"
                    />

                    @if ($errors->has('start_time'))
                        <p class="help is-danger">
                            {{ $errors->first('start_time') }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="column is-one-quarter">
                <div class="field">
                    <label class="label">
                        End Time
                    </label>

                    <admin-form-time-picker
                        name="end_time"
                        value="{{ old('end_time') ?? $event->end_time_formatted }}"
                    />

                    @if ($errors->has('end_time'))
                        <p class="help is-danger">
                            {{ $errors->first('end_time') }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="column is-one-quarter">
                <div class="field">
                    <label class="label">
                        Photo
                    </label>

                    @if(!empty($event->photo_url))
                        <figure class="image is-128x128 mb-1">
                            <img src="{{ $event->photo_url }}">
                        </figure>
                    @endif

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

            <div class="column is-one-quarter">
                <div class="field">
                    <label class="label">
                        Price
                        <span class="has-text-danger is-italic">*</span>
                    </label>

                    <div class="control">
                        <input class="input is-medium" type="text" name="price" value="{{ old('price') ?? $event->price }}">
                    </div>

                    @if ($errors->has('price'))
                        <p class="help is-danger">
                            {{ $errors->first('price') }}
                        </p>
                    @endif
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
                                {{ (old('is_family_friendly') ?? $event->is_family_friendly) ? 'checked' : '' }}
                            >
                        </label>
                    </div>
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
                                {{ (old('active') ?? $event->active) ? 'checked' : '' }}
                            >
                        </label>
                    </div>
                </div>
            </div>

            <div class="column is-narrow">
                <div class="field">
                    <label class="label">
                        Featured
                    </label>

                    <div class="control">
                        <label class="checkbox">
                            <input
                                type="checkbox"
                                name="featured"
                                value="1"
                                {{ (old('featured') ?? $event->featured) ? 'checked' : '' }}
                            >
                        </label>
                    </div>
                </div>
            </div>

            <div class="column">
                <div class="field">
                    <label class="label">
                        Website
                        <span class="has-text-danger is-italic">*</span>
                    </label>

                    <div class="control">
                        <input class="input is-medium" name="website" type="text" value="{{ old('website') ?? $event->website }}">
                    </div>

                    @if ($errors->has('website'))
                        <p class="help is-danger">
                            {{ $errors->first('website') }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="column is-half">
                <div class="field">
                    <label class="label">
                        Short Description
                    </label>

                    <div class="control">
                        <textarea class="textarea is-medium" name="short_description">{{ old('short_description') ?? $event->short_description }}</textarea>
                    </div>

                    @if ($errors->has('short_description'))
                        <p class="help is-danger">
                            {{ $errors->first('short_description') }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="column is-half">
                <div class="field">
                    <label class="label">
                        Description
                    </label>

                    <div class="control">
                        <textarea class="textarea is-medium" name="description">{{ old('description') ?? $event->description }}</textarea>
                    </div>

                    @if ($errors->has('description'))
                        <p class="help is-danger">
                            {{ $errors->first('description') }}
                        </p>
                    @endif
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
