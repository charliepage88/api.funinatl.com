@extends('layouts.admin')

@section('content')
    <div class="columns is-multiline">
        <div class="column is-half has-text-left">
            <h1 class="title is-1">
                Tags
            </h1>
        </div>

        <div class="column is-half has-text-right">
            <a href="{{ route('admin.tags.create') }}" class="button is-success is-large">
                <span class="icon">
                    <i class="fas fa-plus"></i>
                </span>
                <span>Create Tag</span>
            </a>
        </div>
    </div>

    @if (!$tags->count())
        <p>No tags found.</p>
    @else
        <div class="responsive-table-container mb-1">
            <table class="table is-bordered is-fullwidth">
                <thead>
                    <th>Name</th>
                    <th>Used By Count</th>
                    <th>Created</th>
                    <th></th>
                </thead>
                <tbody>
                    @foreach($tags as $tag)
                        <tr>
                            <td>
                                {{ $tag->name }}
                            </td>
                            <td>
                                {{ number_format($tag->relatedCount()) }}
                            </td>
                            <td>
                                {{ $tag->created_at->format('F j, Y') }}
                            </td>
                            <td>
                                <a href="{{ route('admin.tags.edit', [ 'tags' => $tag->id ]) }}" class="button is-primary">
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

        {{ $tags->links('pagination::bulma') }}
    @endif
@endsection
