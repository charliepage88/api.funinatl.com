@extends('layouts.admin')

@section('content')
    <div class="flex flex-wrap">
        <div class="w-full md:w-1/2 xl:w-1/3 p-3">
            <!--Metric Card-->
            <div class="bg-white border rounded shadow p-2">
                <div class="flex flex-row items-center">
                    <div class="w-1/5 pr-0">
                        <div class="rounded p-3 pl-4 bg-purple-700">
                            <i class="fa fa-calendar-alt fa-2x fa-fw fa-inverse"></i>
                        </div>
                    </div>
                    <div class="w-3/4 text-right md:text-center">
                        <h5 class="uppercase text-gray-500">Upcoming Events</h5>

                        <h3 class="text-3xl">
                            <span>{{ number_format($stats['events']['upcoming']) }}</span>

                            @if($stats['events']['upcoming_increase'] > 0)
                                <span class="text-blue-700">
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
            <!--/Metric Card-->
        </div>
        <div class="w-full md:w-1/2 xl:w-1/3 p-3">
            <!--Metric Card-->
            <div class="bg-white border rounded shadow p-2">
                <div class="flex flex-row items-center">
                    <div class="w-1/5 pr-0">
                        <div class="rounded p-3 pl-4 bg-purple-700">
                            <i class="fa fa-users fa-2x fa-fw fa-inverse"></i>
                        </div>
                    </div>
                    <div class="w-3/4 text-right md:text-center">
                        <h5 class="uppercase text-gray-500">Total Users</h5>

                        <h3 class="text-3xl">
                            <span>{{ number_format($stats['users']['total']) }}</span>
                        </h3>
                    </div>
                </div>
            </div>
            <!--/Metric Card-->
        </div>
        <div class="w-full md:w-1/2 xl:w-1/3 p-3">
            <!--Metric Card-->
            <div class="bg-white border rounded shadow p-2">
                <div class="flex flex-row items-center">
                    <div class="w-1/5 pr-0">
                        <div class="rounded p-3 pl-4 bg-purple-700">
                            <i class="fa fa-user-plus fa-2x fa-fw fa-inverse"></i>
                        </div>
                    </div>
                    <div class="w-3/4 text-right md:text-center">
                        <h5 class="uppercase text-gray-500">New Users</h5>

                        <h3 class="text-3xl">
                            <span>{{ number_format($stats['users']['new']) }}</span>

                            @if($stats['users']['new_increase'] > 0)
                                <span class="text-blue-700">
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
            <!--/Metric Card-->
        </div>
        <div class="w-full md:w-1/2 xl:w-1/3 p-3">
            <!--Metric Card-->
            <div class="bg-white border rounded shadow p-2">
                <div class="flex flex-row items-center">
                    <div class="flex-shrink pr-4">
                        <div class="rounded p-3 bg-blue-dark"><i class="fas fa-server fa-2x fa-fw fa-inverse"></i></div>
                    </div>
                    <div class="flex-1 text-right md:text-center">
                        <h5 class="uppercase text-grey">Server Uptime</h5>
                        <h3 class="text-3xl">152 days</h3>
                    </div>
                </div>
            </div>
            <!--/Metric Card-->
        </div>
        <div class="w-full md:w-1/2 xl:w-1/3 p-3">
            <!--Metric Card-->
            <div class="bg-white border rounded shadow p-2">
                <div class="flex flex-row items-center">
                    <div class="flex-shrink pr-4">
                        <div class="rounded p-3 bg-indigo-dark"><i class="fas fa-tasks fa-2x fa-fw fa-inverse"></i></div>
                    </div>
                    <div class="flex-1 text-right md:text-center">
                        <h5 class="uppercase text-grey">To Do List</h5>
                        <h3 class="text-3xl">7 tasks</h3>
                    </div>
                </div>
            </div>
            <!--/Metric Card-->
        </div>
        <div class="w-full md:w-1/2 xl:w-1/3 p-3">
            <!--Metric Card-->
            <div class="bg-white border rounded shadow p-2">
                <div class="flex flex-row items-center">
                    <div class="flex-shrink pr-4">
                        <div class="rounded p-3 bg-red-dark"><i class="fas fa-inbox fa-2x fa-fw fa-inverse"></i></div>
                    </div>
                    <div class="flex-1 text-right md:text-center">
                        <h5 class="uppercase text-grey">Issues</h5>
                        <h3 class="text-3xl">3 <span class="text-red"><i class="fas fa-caret-up"></i></span></h3>
                    </div>
                </div>
            </div>
            <!--/Metric Card-->
        </div>
    </div>

    <!--Divider-->
    <hr class="border-b-2 border-grey-light my-8 mx-4">

    <div class="flex flex-row flex-wrap flex-grow mt-2">

        <div class="w-full md:w-1/2 p-3">
            <!--Graph Card-->
            <div class="bg-white border rounded shadow">
                <div class="border-b p-3">
                    <h5 class="uppercase text-grey-800">Events Timeline</h5>
                </div>
                <div class="p-5">
                    <chart-events-timeline
                        options-json="{{ json_encode($charts['events_timeline']['options']) }}"
                        data-json="{{ json_encode($charts['events_timeline']['data']) }}"
                    />
                </div>
            </div>
            <!--/Graph Card-->
        </div>

        <div class="w-full md:w-1/2 p-3">
            <!--Graph Card-->
            <div class="bg-white border rounded shadow">
                <div class="border-b p-3">
                    <h5 class="uppercase text-grey-dark">Graph</h5>
                </div>
                <div class="p-5">
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
            </div>
            <!--/Graph Card-->
        </div>

        <div class="w-full md:w-1/2 xl:w-1/3 p-3">
            <!--Graph Card-->
            <div class="bg-white border rounded shadow">
                <div class="border-b p-3">
                    <h5 class="uppercase text-grey-dark">Graph</h5>
                </div>
                <div class="p-5">
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
            </div>
            <!--/Graph Card-->
        </div>

        <div class="w-full md:w-1/2 xl:w-1/3 p-3">
            <!--Graph Card-->
            <div class="bg-white border rounded shadow">
                <div class="border-b p-3">
                    <h5 class="uppercase text-grey-dark">Graph</h5>
                </div>
                <div class="p-5">
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
            </div>
            <!--/Graph Card-->
        </div>

        <div class="w-full md:w-1/2 xl:w-1/3 p-3">
            <!--Template Card-->
            <div class="bg-white border rounded shadow">
                <div class="border-b p-3">
                    <h5 class="uppercase text-grey-dark">Template</h5>
                </div>
                <div class="p-5">
     
                </div>
            </div>
            <!--/Template Card-->
        </div>

        <div class="w-full p-3">
            <!--Table Card-->
            <div class="bg-white border rounded shadow">
                <div class="border-b p-3">
                    <h5 class="uppercase text-grey-dark">Table</h5>
                </div>
                <div class="p-5">
                    <table class="w-full p-5 text-grey-darker">
                        <thead>
                            <tr>
                                <th class="text-left text-blue-darkest">Name</th>
                                <th class="text-left text-blue-darkest">Side</th>
                                <th class="text-left text-blue-darkest">Role</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td>Obi Wan Kenobi</td>
                                <td>Light</td>
                                <td>Jedi</td>
                            </tr>
                            <tr>
                                <td>Greedo</td>
                                <td>South</td>
                                <td>Scumbag</td>
                            </tr>
                            <tr>
                                <td>Darth Vader</td>
                                <td>Dark</td>
                                <td>Sith</td>
                            </tr>                                   
                        </tbody>
                    </table>

                    <p class="py-2"><a href="#">See More issues...</a></p>

                </div>
            </div>
            <!--/table Card-->
        </div>
    </div>
@endsection
