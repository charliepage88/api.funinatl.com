@extends('layouts.admin')

@section('content')
    <div class="box">
        <div class="columns is-multiline">
            <div class="clearfix">
                <div class="float-left">
                    <h1 class="title is-1">
                        Users
                    </h1>
                </div>
                <div class="float-right">
                    <a href="{{ route('admin.users.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 border border-blue-700 rounded">
                        <i class="fa fa-plus"></i>
                        <span>Create User</span>
                    </a>
                </div>
            </div>

            @if ($users->count())
                <table style="width: 100%;" class="text-left" cellpadding="10">
                    <thead>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role(s)</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr>
                                <td>
                                    {{ $user->name }}
                                </td>
                                <td>
                                    {{ $user->email }}
                                </td>
                                <td>
                                    @foreach($user->getRoles() as $role)
                                        <span class="text-white py-1 px-3 rounded text-xs bg-green-600">
                                            {{ $role }}
                                        </span>
                                    @endforeach
                                </td>
                                <td>
                                    {{ $user->created_at->format('F j, Y') }}
                                </td>
                                <td>
                                    <a href="{{ route('admin.users.edit', [ 'user' => $user->id ]) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 border border-blue-700 rounded">
                                        <i class="fa fa-pencil-alt"></i>
                                        <span>Edit</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{ $users->links('pagination::bulma') }}
            @endif
        </div>
    </div>
@endsection
