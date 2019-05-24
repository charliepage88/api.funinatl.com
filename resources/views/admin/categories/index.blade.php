@extends('layouts.admin')

@section('content')
    <div class="box">
        <div class="columns is-multiline">
            <div class="clearfix">
                <div class="float-left">
                    <h1 class="bg-brand font-bold text-center text-3xl md:text-5xl px-3">
                        Categories
                    </h1>
                </div>
                <div class="float-right">
                    <a href="{{ route('admin.categories.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 border border-blue-700 rounded">
                        <i class="fa fa-plus"></i>
                        <span>Create Category</span>
                    </a>
                </div>
            </div>

            @if ($categories->count())
                <table style="width: 100%;" class="text-left" cellpadding="10">
                    <thead>
                        <th>Name</th>
                        <th>Active</th>
                        <th>Is Default</th>
                        <th>Created</th>
                        <th>Actions</th>
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
                                    <a href="{{ route('admin.categories.edit', [ 'category' => $category->id ]) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 border border-blue-700 rounded">
                                        <i class="fa fa-pencil-alt"></i>
                                        <span>Edit</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{ $categories->links('pagination::tailwindcss') }}
            @endif
        </div>
    </div>
@endsection
