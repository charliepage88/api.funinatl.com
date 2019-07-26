@extends('layouts.admin')

@section('content')
    <form class="form" method="POST" action="{{ route('admin.users.edit', [ 'user' => $user->id ]) }}">
        @csrf

        <h1 class="title is-1">
            Edit User
        </h1>

        <div class="columns is-multiline">
            <div class="w-full md:w-1/2 px-3 mb-6 md:mb-0">
                <label class="label" for="grid-name">
                    Name 
                    <span class="has-text-danger is-italic">*</span>
                </label>
                
                <input class="appearance-none block w-full bg-gray-200 text-gray-700 rounded py-3 px-4 mb-3 leading-tight" name="name" id="grid-name" type="text" value="{{ $user->name }}">

                @if ($errors->has('name'))
                    <p class="help is-danger">
                        {{ $errors->first('name') }}
                    </p>
                @endif
            </div>

            <div class="w-full md:w-1/2 px-3 mb-6 md:mb-0">
                <label class="label" for="grid-email">
                    Email Address 
                    <span class="has-text-danger is-italic">*</span>
                </label>
                
                <input class="appearance-none block w-full bg-gray-200 text-gray-700 rounded py-3 px-4 mb-3 leading-tight" name="email" id="grid-email" type="email" value="{{ $user->email }}" autocomplete="off">

                @if ($errors->has('email'))
                    <p class="help is-danger">
                        {{ $errors->first('email') }}
                    </p>
                @endif
            </div>
        </div>

        <div class="columns is-multiline">
            <div class="w-full md:w-1/2 px-3 mb-6 md:mb-0">
                <label class="label" for="grid-password">
                    New Password
                </label>
                
                <input class="appearance-none block w-full bg-gray-200 text-gray-700 rounded py-3 px-4 mb-3 leading-tight" name="password" id="grid-password" type="password" autocomplete="off" value="">

                @if ($errors->has('password'))
                    <p class="help is-danger">
                        {{ $errors->first('password') }}
                    </p>
                @endif
            </div>

            <div class="w-full md:w-1/2 px-3 mb-6 md:mb-0">
                <label class="label" for="grid-role">
                    Role 
                    <span class="has-text-danger is-italic">*</span>
                </label>
                
                <div class="relative">
                    <select class="block appearance-none w-full bg-gray-200 border border-gray-200 text-gray-700 py-3 px-4 pr-8 rounded leading-tight focus:outline-none focus:bg-white focus:border-gray-500" id="grid-role" name="role">
                        <option value="">Choose Role</option>
                        @foreach($roles as $value => $title)
                            <option
                                value="{{ $value }}"
                                <?php echo \Bouncer::is($user)->an($value) ? ' selected' : '' ?>
                            >
                                {{ $title }}
                            </option>
                        @endforeach
                    </select>

                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                    </div>
                </div>

                @if ($errors->has('role'))
                    <p class="help is-danger">
                        {{ $errors->first('role') }}
                    </p>
                @endif
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
