@extends('layouts.admin')

@section('content')
    <div class="columns is-multiline">
        <div class="column is-half has-text-left">
            <h1 class="title is-1">
                Users
            </h1>
        </div>

        <div class="column is-half has-text-right">
            <a href="{{ route('admin.users.create') }}" class="button is-success is-large">
                <span class="icon">
                    <i class="fas fa-plus"></i>
                </span>
                <span>Create User</span>
            </a>
        </div>
    </div>

    @if (!$users->count())
        <p>No users found.</p>
    @else
        <div class="responsive-table-container mb-1">
            <table class="table is-bordered is-fullwidth">
                <thead>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role(s)</th>
                    <th>Created</th>
                    <th></th>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td>
                                {{ $user->name }}
                            </td>
                            <td>
                                <a href="mailto:{{ $user->email }}" target="_blank">
                                    {{ $user->email }}
                                </a>
                            </td>
                            <td>
                                <div class="tags">
                                    @foreach($user->getRoles() as $role)
                                        <span class="tag is-success is-rounded">
                                            {{ $role }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                {{ $user->created_at->format('F j, Y') }}
                            </td>
                            <td>
                                <a href="{{ route('admin.users.edit', [ 'user' => $user->id ]) }}" class="button is-primary">
                                    <span class="icon">
                                        <i class="fa fa-pencil-alt"></i>
                                    </span>
                                    <span>Edit</span>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $users->links('pagination::bulma') }}
    @endif
@endsection
