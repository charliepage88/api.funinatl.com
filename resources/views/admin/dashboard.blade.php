@extends('layouts.admin')

@section('content')
    <div class="columns is-multiline">
        <div class="column is-6-tablet is-3-desktop">
            <a href="{{ route('admin.events.index') }}">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-header-title is-inline">Upcoming Events 
                            {{-- <span class="tag is-success is-pulled-right">Monthly</span> --}}
                        </h3>
                    </div>
                    <div class="card-content">
                        <div class="columns">
                            <div class="column is-narrow pl-0">
                                <div class="rounded p-px-10 has-background-info is-hidden-desktop">
                                    <span class="icon is-6x is-centered">
                                        <i class="fas fa-calendar-alt fa-5x fa-inverse"></i>
                                    </span>
                                </div>

                                <div class="rounded p-px-5 has-background-info is-hidden-touch">
                                    <span class="icon is-3x">
                                        <i class="fas fa-calendar-alt fa-2x fa-inverse"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="column is-two-thirds has-text-centered">
                                <h3 class="title">
                                    {{ number_format($stats['events']['upcoming']) }} 
                                    @if($stats['events']['upcoming_increase'] > 0)
                                        <span class="has-text-info">
                                            @if ($stats['events']['upcoming_increase'] === 2)
                                                <i class="fas fa-caret-up"></i>
                                            @else
                                                <i class="fas fa-caret-down"></i>
                                            @endif
                                        </span>
                                    @endif
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="column is-6-tablet is-3-desktop">
            <a href="{{ route('admin.users.index') }}">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-header-title is-inline">Total Users 
                            {{-- <span class="tag is-success is-pulled-right">Monthly</span> --}}
                        </h3>
                    </div>
                    <div class="card-content">
                        <div class="columns">
                            <div class="column is-narrow pl-0">
                                <div class="rounded p-px-10 has-background-info is-hidden-desktop">
                                    <span class="icon is-6x is-centered">
                                        <i class="fas fa-users fa-5x fa-inverse"></i>
                                    </span>
                                </div>

                                <div class="rounded p-px-5 has-background-info is-hidden-touch">
                                    <span class="icon is-3x">
                                        <i class="fas fa-users fa-2x fa-inverse"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="column is-two-thirds has-text-centered">
                                <h3 class="title">
                                    {{ number_format($stats['users']['total']) }} 
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="column is-6-tablet is-3-desktop">
            <a href="{{ route('admin.users.index') }}">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-header-title is-inline">New Users 
                            {{-- <span class="tag is-success is-pulled-right">Monthly</span> --}}
                        </h3>
                    </div>
                    <div class="card-content">
                        <div class="columns">
                            <div class="column is-narrow pl-0">
                                <div class="rounded p-px-10 has-background-info is-hidden-desktop">
                                    <span class="icon is-6x is-centered">
                                        <i class="fas fa-user-plus fa-5x fa-inverse"></i>
                                    </span>
                                </div>

                                <div class="rounded p-px-5 has-background-info is-hidden-touch">
                                    <span class="icon is-3x">
                                        <i class="fas fa-user-plus fa-2x fa-inverse"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="column is-two-thirds has-text-centered">
                                <h3 class="title">
                                    {{ number_format($stats['users']['new']) }} 
                                    @if($stats['users']['new_increase'] > 0)
                                        <span class="has-text-info">
                                            @if ($stats['users']['new_increase'] === 2)
                                                <i class="fas fa-caret-up"></i>
                                            @else
                                                <i class="fas fa-caret-down"></i>
                                            @endif
                                        </span>
                                    @endif
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="column is-6-tablet is-3-desktop">
            <a href="{{ route('admin.events.index', [ 'source' => 'submission' ]) }}">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-header-title is-inline">Event Submissions 
                            {{-- <span class="tag is-success is-pulled-right">Monthly</span> --}}
                        </h3>
                    </div>
                    <div class="card-content">
                        <div class="columns">
                            <div class="column is-narrow pl-0">
                                <div class="rounded p-px-10 has-background-info is-hidden-desktop">
                                    <span class="icon is-6x is-centered">
                                        <i class="fas fa-calendar-week fa-5x fa-inverse"></i>
                                    </span>
                                </div>

                                <div class="rounded p-px-5 has-background-info is-hidden-touch">
                                    <span class="icon is-3x">
                                        <i class="fas fa-calendar-week fa-2x fa-inverse"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="column is-two-thirds has-text-centered">
                                <h3 class="title">
                                    {{ number_format($stats['events']['submissions_pending']) }} 
                                    @if($stats['events']['submissions_pending_increase'] > 0)
                                        <span class="has-text-info">
                                            @if ($stats['events']['submissions_pending_increase'] === 2)
                                                <i class="fas fa-caret-up"></i>
                                            @else
                                                <i class="fas fa-caret-down"></i>
                                            @endif
                                        </span>
                                    @endif
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="column is-6-tablet is-3-desktop">
            <a href="{{ route('admin.locations.index', [ 'source' => 'submission' ]) }}">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-header-title is-inline">Location Submissions 
                            {{-- <span class="tag is-success is-pulled-right">Monthly</span> --}}
                        </h3>
                    </div>
                    <div class="card-content">
                        <div class="columns">
                            <div class="column is-narrow pl-0">
                                <div class="rounded p-px-10 has-background-info is-hidden-desktop">
                                    <span class="icon is-6x is-centered">
                                        <i class="fas fa-map fa-5x fa-inverse"></i>
                                    </span>
                                </div>

                                <div class="rounded p-px-5 has-background-info is-hidden-touch">
                                    <span class="icon is-3x">
                                        <i class="fas fa-map fa-2x fa-inverse"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="column is-two-thirds has-text-centered">
                                <h3 class="title">
                                    {{ number_format($stats['locations']['submissions_pending']) }} 
                                    @if($stats['locations']['submissions_pending_increase'] > 0)
                                        <span class="has-text-info">
                                            @if ($stats['locations']['submissions_pending_increase'] === 2)
                                                <i class="fas fa-caret-up"></i>
                                            @else
                                                <i class="fas fa-caret-down"></i>
                                            @endif
                                        </span>
                                    @endif
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="column is-6-tablet is-3-desktop">
            <a href="{{ route('admin.contact_submissions.index') }}">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-header-title is-inline">Contact Submissions 
                            {{-- <span class="tag is-success is-pulled-right">Monthly</span> --}}
                        </h3>
                    </div>
                    <div class="card-content">
                        <div class="columns">
                            <div class="column is-narrow pl-0">
                                <div class="rounded p-px-10 has-background-info is-hidden-desktop">
                                    <span class="icon is-6x is-centered">
                                        <i class="fas fa-envelope-open-text fa-5x fa-inverse"></i>
                                    </span>
                                </div>

                                <div class="rounded p-px-5 has-background-info is-hidden-touch">
                                    <span class="icon is-3x">
                                        <i class="fas fa-envelope-open-text fa-2x fa-inverse"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="column is-two-thirds has-text-centered">
                                <h3 class="title">
                                    {{ number_format($stats['site']['contact_pending']) }} 
                                    @if($stats['site']['contact_pending_increase'] > 0)
                                        <span class="has-text-info">
                                            @if ($stats['site']['contact_pending_increase'] === 2)
                                                <i class="fas fa-caret-up"></i>
                                            @else
                                                <i class="fas fa-caret-down"></i>
                                            @endif
                                        </span>
                                    @endif
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="columns is-multiline">
        <div class="column is-half">
            <!--Graph Card-->
            <div class="box">
                <h5 class="subtitle is-5">Events Timeline</h5>

                <chart-events-timeline
                    options-json="{{ json_encode($charts['events_timeline']['options']) }}"
                    data-json="{{ json_encode($charts['events_timeline']['data']) }}"
                />
            </div>
            <!--/Graph Card-->
        </div>

        <div class="column is-half">
            <!--Graph Card-->
            <div class="box">
                <h5 class="subtitle is-5">Upcoming Events Slow Days</h5>

                <chart-upcoming-events-slow-days
                    options-json="{{ json_encode($charts['events_upcoming_slow']['options']) }}"
                    data-json="{{ json_encode($charts['events_upcoming_slow']['data']) }}"
                />
            </div>
            <!--/Graph Card-->
        </div>
    </div>
@endsection
