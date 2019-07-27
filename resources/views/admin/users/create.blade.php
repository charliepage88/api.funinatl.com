@extends('layouts.admin')

@section('content')
    <form class="form" method="POST" action="{{ route('admin.users.create') }}">
        @csrf

        <h1 class="title is-1">
            Create User
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
                        Email Address
                        <span class="has-text-danger is-italic">*</span>
                    </label>

                    <div class="control">
                        <input class="input is-medium" name="email" type="email" value="{{ old('email') }}">
                    </div>

                    @if ($errors->has('email'))
                        <p class="help is-danger">
                            {{ $errors->first('email') }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="column is-half">
                <div class="field">
                    <label class="label">
                        Password 
                        <span class="has-text-danger is-italic">*</span>
                    </label>

                    <div class="control">
                        <input class="input is-medium" name="password" type="password" autocomplete="off" value="">
                    </div>

                    @if ($errors->has('password'))
                        <p class="help is-danger">
                            {{ $errors->first('password') }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="column is-half">
                <div class="field">
                    <label class="label">
                        Role 
                        <span class="has-text-danger is-italic">*</span>
                    </label>

                    <div class="control">
                        <div class="select is-medium is-fullwidth">
                            <select name="role">
                                <option value="">Choose Role</option>
                                @foreach($roles as $value => $label)
                                    <option
                                        value="{{ $value }}"
                                        {{ old('role') === $value ? 'selected' : '' }}
                                    >
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    @if ($errors->has('role'))
                        <p class="help is-danger">
                            {{ $errors->first('role') }}
                        </p>
                    @endif
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
