@extends('layouts.admin')

@section('content')
    <div class="box">
        <div class="columns is-multiline">
            <div class="clearfix">
                <div class="float-left">
                    <h1 class="bg-brand font-bold text-center text-3xl md:text-5xl px-3">
                        Providers
                    </h1>
                </div>
                <div class="float-right">
                    <a href="{{ route('admin.providers.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 border border-blue-700 rounded">
                        <i class="fa fa-plus"></i>
                        <span>Create Provider</span>
                    </a>
                </div>
            </div>

            @if ($providers->count())
                <table style="width: 100%;" class="text-left" cellpadding="10">
                    <thead>
                        <th>Name</th>
                        <th>Location</th>
                        <th>Last Scraped</th>
                        <th>Active</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </thead>
                    <tbody>
                        @foreach($providers as $provider)
                            <tr>
                                <td>
                                    {{ $provider->name }}
                                </td>
                                <td>
                                    {{ $provider->location->name }}
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
                                    <a href="{{ route('admin.providers.edit', [ 'provider' => $provider->id ]) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 border border-blue-700 rounded">
                                        <i class="fa fa-pencil-alt"></i>
                                        <span>Edit</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{ $providers->links('pagination::tailwindcss') }}
            @endif
        </div>
    </div>
@endsection
