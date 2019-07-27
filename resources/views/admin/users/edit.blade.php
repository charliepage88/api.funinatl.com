@extends('layouts.admin')

@section('content')
    <form class="form" method="POST" action="{{ route('admin.users.edit', [ 'user' => $user->id ]) }}">
        @csrf

        <h1 class="title is-1">
            Edit User
        </h1>

        <div class="columns is-multiline">
            <div class="column is-half">
                <div class="field">
                    <label class="label">
                        Name 
                        <span class="has-text-danger is-italic">*</span>
                    </label>
                    
                    <div class="control">
                        <input class="input is-medium" name="name" type="text" value="{{ old('name') ?? $user->name }}">
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
                        Email Address 
                        <span class="has-text-danger is-italic">*</span>
                    </label>
                    
                    <div class="control">
                        <input class="input is-medium" name="email" type="email" value="{{ old('email') ?? $user->email }}">
                    </div>

                    @if ($errors->has('email'))
                        <p class="help is-danger">
                            {{ $errors->first('email') }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="column is-half">
                <div class="field">
                    <label class="label">
                        New Password
                    </label>
                    
                    <div class="control">
                        <input class="input is-medium" name="password" type="password" autocomplete="off" value="">
                    </div>

                    @if ($errors->has('password'))
                        <p class="help is-danger">
                            {{ $errors->first('password') }}
                        </span>
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
                                        {{ (old('role') === $value || \Bouncer::is($user)->an($value)) ? 'selected' : '' }}
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
