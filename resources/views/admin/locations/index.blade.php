@extends('layouts.admin')

@section('content')
    <div class="box">
        <admin-filter-locations
            categories-json="{{ json_encode($categories) }}"
            create-location-url="{{ route('admin.locations.create') }}"
        ></admin-filter-locations>

        @if (!$locations->count())
            <span>No locations found.</span>
        @else
            <div class="w-full mx-auto">
                <div class="bg-white shadow-md rounded my-6">
                    <table class="text-left w-full border-collapse">
                        <thead>
                            <th class="py-4 px-6 bg-gray-200 font-bold uppercase text-sm text-gray-700 border-b border-gray-100">
                                Name
                            </th>
                            <th class="py-4 px-6 bg-gray-200 font-bold uppercase text-sm text-gray-700 border-b border-gray-100">
                                Category
                            </th>
                            <th class="py-4 px-6 bg-gray-200 font-bold uppercase text-sm text-gray-700 border-b border-gray-100">
                                Created
                            </th>
                            <th class="py-4 px-6 bg-gray-200 font-bold uppercase text-sm text-gray-700 border-b border-gray-100">
                                Actions
                            </th>
                        </thead>
                        <tbody>
                            @foreach($locations as $location)
                                <tr class="hover:bg-gray-100">
                                    <td class="py-4 px-6 border-b border-gray-100">
                                        {{ $location->name }}
                                    </td>
                                    <td class="py-4 px-6 border-b border-gray-100">
                                        {{ $location->category_id ? $location->category->name : 'N/A' }}
                                    </td>
                                    <td class="py-4 px-6 border-b border-gray-100">
                                        {{ $location->created_at->format('F j, Y') }}
                                    </td>
                                    <td class="py-4 px-6 border-b border-gray-100">
                                        <a href="{{ route('admin.locations.edit', [ 'location' => $location->id ]) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 border border-blue-700 rounded">
                                            <i class="fa fa-pencil-alt"></i>
                                            <span>Edit</span>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $locations->links('pagination::tailwindcss') }}
        @endif
    </div>
@endsection
