@extends('layouts.admin')

@section('content')
    <div class="box">
        <admin-filter-events
            locations-json="{{ json_encode($locations) }}"
            categories-json="{{ json_encode($categories) }}"
            create-event-url="{{ route('admin.events.create') }}"
        ></admin-filter-events>

        @if (!$events->count())
            <span>No events found.</span>
        @else
            <div class="w-full mx-auto">
                <div class="bg-white shadow-md rounded my-6">
                    <table class="text-left w-full border-collapse">
                        <thead>
                            <th class="py-4 px-6 bg-gray-200 font-bold uppercase text-sm text-gray-700 border-b border-gray-100">
                                Event
                            </th>
                            <th class="py-4 px-6 bg-gray-200 font-bold uppercase text-sm text-gray-700 border-b border-gray-100">
                                Start Date
                            </th>
                            <th class="py-4 px-6 bg-gray-200 font-bold uppercase text-sm text-gray-700 border-b border-gray-100">
                                Location
                            </th>
                            <th class="py-4 px-6 bg-gray-200 font-bold uppercase text-sm text-gray-700 border-b border-gray-100">
                                Category
                            </th>
                            <th class="py-4 px-6 bg-gray-200 font-bold uppercase text-sm text-gray-700 border-b border-gray-100">
                                Actions
                            </th>
                        </thead>
                        <tbody>
                            @foreach($events as $event)
                                <tr class="hover:bg-gray-100">
                                    <td class="py-4 px-6 border-b border-gray-100">
                                        {{ $event->name }}
                                    </td>
                                    <td class="py-4 px-6 border-b border-gray-100">
                                        <span>{{ $event->start_date->format('D, M jS') }}</span>
                                        <br />
                                        <small class="text-xs">
                                            {{ $event->start_time }}
                                        </small>

                                        @if(!empty($event->end_time))
                                            <small class="text-xs ml-1">
                                                - {{ $event->end_time }}
                                            </small>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6 border-b border-gray-100">
                                        {{ $event->location_id ? $event->location->name : 'N/A' }}
                                    </td>
                                    <td class="py-4 px-6 border-b border-gray-100">
                                        {{ $event->category->name }}
                                    </td>
                                    <td class="py-4 px-6 border-b border-gray-100">
                                        <a href="{{ route('admin.events.edit', [ 'event' => $event->id ]) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-3 border border-black rounded">
                                            @if($event->source === 'submission')
                                                <i class="fa fa-check-circle mr-1"></i>
                                                <span>Review</span>
                                            @else
                                                <i class="fa fa-pencil-alt mr-1"></i>
                                                <span>Edit</span>
                                            @endif
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $events->links('pagination::bulma') }}
        @endif
    </div>
@endsection
