@extends('layouts.admin')

@section('content')
    <div class="columns is-multiline">
        <div class="column is-half has-text-left">
            <h1 class="title is-1">
                Bands
            </h1>
        </div>

        <div class="column is-half has-text-right">
            <a href="{{ route('admin.bands.create') }}" class="button is-success is-large">
                <span class="icon">
                    <i class="fas fa-plus"></i>
                </span>
                <span>Create Band</span>
            </a>
        </div>
    </div>

    @if (!$bands->count())
        <p>No bands found.</p>
    @else
        <div class="responsive-table-container mb-1">
            <table class="table is-bordered is-fullwidth">
                <thead>
                    <th>Name</th>
                    <th>Events</th>
                    <th>Created</th>
                    <th></th>
                </thead>
                <tbody>
                    @foreach($bands as $band)
                        <tr>
                            <td>
                                {{ $band->name }}
                            </td>
                            <td>
                                {{ $band->events->count() }}
                            </td>
                            <td>
                                {{ $band->created_at->format('F j, Y') }}
                            </td>
                            <td>
                                <a href="{{ route('admin.bands.edit', [ 'band' => $band->id ]) }}" class="button is-primary">
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

        {{ $bands->links('pagination::bulma') }}
    @endif
@endsection
