@extends('layouts.admin')

@section('content')
    <div class="box">
        <div class="columns is-multiline">
            <div class="clearfix">
                <div class="float-left">
                    <h1 class="bg-brand font-bold text-center text-3xl md:text-5xl px-3">
                        Locations
                    </h1>
                </div>
                <div class="float-right">
                    <a href="{{ route('admin.locations.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 border border-blue-700 rounded">
                        <i class="fa fa-plus"></i>
                        <span>Create Location</span>
                    </a>
                </div>
            </div>

            @if ($locations->count())
                <table style="width: 100%;" class="text-left" cellpadding="10">
                    <thead>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Created</th>
                        <th>Updated</th>
                        <th>Actions</th>
                    </thead>
                    <tbody>
                        @foreach($locations as $location)
                            <tr>
                                <td>
                                    {{ $location->name }}
                                </td>
                                <td>
                                    {{ $location->category->name }}
                                </td>
                                <td>
                                    {{ $location->created_at->format('F j, Y') }}
                                </td>
                                <td>
                                    {{ $location->updated_at->format('F j, Y') }}
                                </td>
                                <td>
                                    <a href="{{ route('admin.locations.edit', [ 'location' => $location->id ]) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 border border-blue-700 rounded">
                                        <i class="fa fa-pencil-alt"></i>
                                        <span>Edit</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{ $locations->links('pagination::tailwindcss') }}
            @endif
        </div>
    </div>
@endsection
