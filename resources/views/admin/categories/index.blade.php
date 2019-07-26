@extends('layouts.admin')

@section('content')
    <div class="columns is-multiline">
        <div class="column is-half has-text-left">
            <h1 class="title is-1">
                Categories
            </h1>
        </div>

        <div class="column is-half has-text-right">
            <a href="{{ route('admin.categories.create') }}" class="button is-success is-large">
                <span class="icon">
                    <i class="fas fa-plus"></i>
                </span>
                <span>Create Category</span>
            </a>
        </div>
    </div>

    @if (!$categories->count())
        <p>No categories found.</p>
    @else
        <div class="responsive-table-container mb-1">
            <table class="table is-bordered is-fullwidth">
                <thead>
                    <th>Name</th>
                    <th>Active</th>
                    <th>Is Default</th>
                    <th>Created</th>
                    <th></th>
                </thead>
                <tbody>
                    @foreach($categories as $category)
                        <tr>
                            <td>
                                {{ $category->name }}
                            </td>
                            <td>
                                {{ $category->active ? 'Yes' : 'No' }}
                            </td>
                            <td>
                                {{ $category->is_default ? 'Yes' : 'No' }}
                            </td>
                            <td>
                                {{ $category->created_at->format('F j, Y') }}
                            </td>
                            <td>
                                <a href="{{ route('admin.categories.edit', [ 'category' => $category->id ]) }}" class="button is-primary">
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

        {{ $categories->links('pagination::bulma') }}
    @endif
@endsection
