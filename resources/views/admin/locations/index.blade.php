@extends('layouts.admin')

@section('content')
    <admin-filter-locations
        categories-json="{{ json_encode($categories) }}"
        create-location-url="{{ route('admin.locations.create') }}"
    ></admin-filter-locations>

    @if (!$locations->count())
        <p>No locations found.</p>
    @else
        <div class="responsive-table-container mb-1">
            <table class="table is-bordered is-fullwidth">
                <thead>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Created</th>
                    <th></th>
                </thead>
                <tbody>
                    @foreach($locations as $location)
                        <tr>
                            <td>
                                {{ $location->name }}
                            </td>
                            <td>
                                @if(!empty($location->category_id))
                                    <a href="{{ route('admin.categories.edit', [ 'category' => $location->category_id ]) }}" class="tag is-warning has-no-underline">
                                        {{ $location->category->name }}
                                    </a>
                                @else
                                    <span>N/A</span>
                                @endif
                            </td>
                            <td>
                                {{ $location->created_at->format('F j, Y') }}
                            </td>
                            <td>
                                <a href="{{ route('admin.locations.edit', [ 'location' => $location->id ]) }}" class="button is-primary">
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

        {{ $locations->links('pagination::bulma') }}
    @endif
@endsection
