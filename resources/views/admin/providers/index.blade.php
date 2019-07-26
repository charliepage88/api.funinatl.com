@extends('layouts.admin')

@section('content')
    <div class="columns">
        <div class="column is-half has-text-left">
            <h1 class="title is-1">
                Providers
            </h1>
        </div>

        <div class="column is-half has-text-right">
            <a href="{{ route('admin.providers.create') }}" class="button is-success is-large">
                <span class="icon">
                    <i class="fas fa-plus"></i>
                </span>
                <span>Create Provider</span>
            </a>
        </div>
    </div>

    @if (!$providers->count())
        <p>No providers found.</p>
    @else
        <div class="responsive-table-container mb-1">
            <table class="table is-bordered is-fullwidth">
                <thead>
                    <th>Name</th>
                    <th>Location</th>
                    <th>Last Scraped</th>
                    <th>Active</th>
                    <th>Created</th>
                    <th></th>
                </thead>
                <tbody>
                    @foreach($providers as $provider)
                        <tr>
                            <td>
                                {{ $provider->name }}
                            </td>
                            <td>
                                @if(!empty($provider->location_id))
                                    <a href="{{ route('admin.locations.edit', [ 'location' => $provider->location_id ]) }}" class="tag is-warning has-no-underline">
                                        {{ $provider->location->name }}
                                    </a>
                                @else
                                    <span>N/A</span>
                                @endif
                            </td>
                            <td>
                                @if(!empty($provider->last_scraped))
                                    {{ $provider->last_scraped->format('F j, Y g:i A') }}
                                @else
                                    N/A
                                @endif
                            </td>
                            <td>
                                {{ $provider->active ? 'Yes' : 'No' }}
                            </td>
                            <td>
                                {{ $provider->created_at->format('F j, Y') }}
                            </td>
                            <td>
                                <a href="{{ route('admin.providers.edit', [ 'provider' => $provider->id ]) }}" class="button is-primary">
                                    <span class="icon">
                                        <i class="fas fa-pencil-alt"></i>
                                    </span>
                                    <span>Edit</span>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $providers->links('pagination::bulma') }}
    @endif
@endsection
