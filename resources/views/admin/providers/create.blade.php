@extends('layouts.admin')

@section('content')
    <form class="form" method="POST" action="{{ route('admin.providers.create') }}">
        @csrf

        <h1 class="title is-1">
            Create Provider
        </h1>

        <div class="columns is-multiline">
            <div class="column is-half">
                <div class="field">
                    <label class="label">
                        Name 
                        <span class="has-text-danger is-italic">*</span>
                    </label>
                    
                    <div class="control">
                        <input class="input is-medium" name="name" type="text" value="{{ old('name') }}">
                    </div>

                    @if ($errors->has('name'))
                        <p class="help is-danger">
                            {{ $errors->first('name') }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="column is-half">
                <div class="field">
                    <label class="label">
                        Scrape URL 
                        <span class="has-text-danger is-italic">*</span>
                    </label>
                    
                    <div class="control">
                        <input class="input is-medium" name="scrape_url" type="text" value="{{ old('scrape_url') }}">
                    </div>

                    @if ($errors->has('scrape_url'))
                        <p class="help is-danger">
                            {{ $errors->first('scrape_url') }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="column is-half">
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
                                        {{ old('location_id') === $id ? 'selected' : '' }}
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

            <div class="column is-half">
                <div class="field">
                    <label class="label">
                        Active
                        <span class="has-text-danger is-italic">*</span>
                    </label>

                    <div class="control">
                        <label class="checkbox">
                            <input type="checkbox" name="active" value="1" {{ old('active') !== false ? 'checked' : '' }}>
                        </label>
                    </div>
                </div>
            </div>

            <div class="column is-full has-text-right">
                <button type="submit" class="button is-primary is-large is-fullwidth-mobile">
                    Save
                </button>
            </div>
        </div>
    </form>
@endsection
