@extends('layouts.admin')

@section('content')
    <form class="form" method="POST" action="{{ route('admin.tags.edit', [ 'tag' => $tag->id ]) }}">
        @csrf

        <h1 class="title is-1">
            Edit Tag
        </h1>

        <div class="columns">
            <div class="column is-half">
                <div class="field">
                    <label class="label">
                        Name 
                        <span class="has-text-danger is-italic">*</span>
                    </label>
                    
                    <div class="control">
                        <input class="input is-medium" name="name" type="text" value="{{ old('name') ?? $tag->name }}">
                    </div>

                    @if ($errors->has('name'))
                        <p class="help is-danger">
                            {{ $errors->first('name') }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="column is-half has-text-right pt-2">
                <button type="submit" class="button is-primary is-large is-fullwidth-mobile">
                    Save
                </button>
            </div>
        </div>
    </form>
@endsection
