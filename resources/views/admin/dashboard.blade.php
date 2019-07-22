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
                            <div class="column is-narrow">
                                <div class="rounded p-px-10 has-background-info">
                                    <span class="icon is-6x is-centered">
                                        <i class="fas fa-calendar-alt fa-5x fa-inverse"></i>
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
                            <div class="column is-narrow">
                                <div class="rounded p-px-10 has-background-info">
                                    <span class="icon is-6x is-centered">
                                        <i class="fas fa-users fa-5x fa-inverse"></i>
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
                            <div class="column is-narrow">
                                <div class="rounded p-px-10 has-background-info">
                                    <span class="icon is-6x is-centered">
                                        <i class="fas fa-user-plus fa-5x fa-inverse"></i>
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
                            <div class="column is-narrow">
                                <div class="rounded p-px-10 has-background-info">
                                    <span class="icon is-6x is-centered">
                                        <i class="fas fa-calendar-week fa-5x fa-inverse"></i>
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
                            <div class="column is-narrow">
                                <div class="rounded p-px-10 has-background-info">
                                    <span class="icon is-6x is-centered">
                                        <i class="fas fa-map fa-5x fa-inverse"></i>
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
                            <div class="column is-narrow">
                                <div class="rounded p-px-10 has-background-info">
                                    <span class="icon is-6x is-centered">
                                        <i class="fas fa-envelope-open-text fa-5x fa-inverse"></i>
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
                <h5 class="subtitle is-5">Graph</h5>

                {{-- <canvas id="chartjs-0" class="chartjs" width="undefined" height="undefined"></canvas>
                <script>
                    new Chart(document.getElementById("chartjs-0"), {
                        "type": "line",
                        "data": {
                            "labels": ["January", "February", "March", "April", "May", "June", "July"],
                            "datasets": [{
                                "label": "Views",
                                "data": [65, 59, 80, 81, 56, 55, 40],
                                "fill": false,
                                "borderColor": "rgb(75, 192, 192)",
                                "lineTension": 0.1
                            }]
                        },
                        "options": {}
                    });
                </script> --}}
            </div>
            <!--/Graph Card-->
        </div>

        <div class="column is-half">
            <!--Graph Card-->
            <div class="box">
                <h5 class="subtitle is-5">Graph</h5>

                {{-- <canvas id="chartjs-1" class="chartjs" width="undefined" height="undefined"></canvas>
                <script>
                    new Chart(document.getElementById("chartjs-1"), {
                        "type": "bar",
                        "data": {
                            "labels": ["January", "February", "March", "April", "May", "June", "July"],
                            "datasets": [{
                                "label": "Likes",
                                "data": [65, 59, 80, 81, 56, 55, 40],
                                "fill": false,
                                "backgroundColor": ["rgba(255, 99, 132, 0.2)", "rgba(255, 159, 64, 0.2)", "rgba(255, 205, 86, 0.2)", "rgba(75, 192, 192, 0.2)", "rgba(54, 162, 235, 0.2)", "rgba(153, 102, 255, 0.2)", "rgba(201, 203, 207, 0.2)"],
                                "borderColor": ["rgb(255, 99, 132)", "rgb(255, 159, 64)", "rgb(255, 205, 86)", "rgb(75, 192, 192)", "rgb(54, 162, 235)", "rgb(153, 102, 255)", "rgb(201, 203, 207)"],
                                "borderWidth": 1
                            }]
                        },
                        "options": {
                            "scales": {
                                "yAxes": [{
                                    "ticks": {
                                        "beginAtZero": true
                                    }
                                }]
                            }
                        }
                    });
                </script> --}}
            </div>
            <!--/Graph Card-->
        </div>

        <div class="column is-half">
            <!--Graph Card-->
            <div class="box">
                <h5 class="subtitle is-5">Graph</h5>

                {{-- <canvas id="chartjs-4" class="chartjs" width="undefined" height="undefined"></canvas>
                <script>
                    new Chart(document.getElementById("chartjs-4"), {
                        "type": "doughnut",
                        "data": {
                            "labels": ["P1", "P2", "P3"],
                            "datasets": [{
                                "label": "Issues",
                                "data": [300, 50, 100],
                                "backgroundColor": ["rgb(255, 99, 132)", "rgb(54, 162, 235)", "rgb(255, 205, 86)"]
                            }]
                        }
                    });
                </script> --}}
            </div>
            <!--/Graph Card-->
        </div>
    </div>
@endsection
