@extends('layouts.admin')

@section('content')
    <div class="box">
        <admin-filter-events
            locations-json="{{ json_encode($locations) }}"
            categories-json="{{ json_encode($categories) }}"
            create-event-url="{{ route('admin.events.create') }}"
        ></admin-filter-events>

        @if (!$events->count())
            <p>No events found.</p>
        @else
            <div class="responsive-table-container mb-1">
                <table class="table is-bordered is-fullwidth">
                    <thead>
                        <th>Event</th>
                        <th>Start Date</th>
                        <th>Location</th>
                        <th>Category</th>
                        <th></th>
                    </thead>
                    <tbody>
                        @foreach($events as $event)
                            <tr>
                                <td>
                                    {{ $event->name }}
                                </td>
                                <td>
                                    <h3 class="subtitle is-6 mb-px-5">
                                        {{ $event->start_date->format('D, M jS') }}
                                    </h3>

                                    <span class="tag is-info mb-px-5">
                                        @if(!empty($event->end_time))
                                            {{ $event->start_time }} - {{ $event->end_time }}
                                        @else
                                            {{ $event->start_time }}
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    {{ $event->location_id ? $event->location->name : 'N/A' }}
                                </td>
                                <td>
                                    {{ $event->category->name }}
                                </td>
                                <td>
                                    @if($event->source === 'submission')
                                        <a href="{{ route('admin.events.edit', [ 'event' => $event->id ]) }}" class="button is-warning">
                                            <span class="icon">
                                                <i class="fa fa-check-circle"></i>
                                            </span>
                                            <span>Review</span>
                                        </a>
                                    @else
                                        <a href="{{ route('admin.events.edit', [ 'event' => $event->id ]) }}" class="button is-primary">
                                            <span class="icon">
                                                <i class="fa fa-pencil-alt"></i>
                                            </span>
                                            <span>Edit</span>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $events->links('pagination::bulma') }}
        @endif
    </div>
@endsection
