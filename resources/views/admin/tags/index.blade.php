@extends('layouts.admin')

@section('content')
    <div class="box">
        <div class="columns is-multiline">
            <div class="clearfix">
                <div class="float-left">
                    <h1 class="title is-1">
                        Tags
                    </h1>
                </div>
                <div class="float-right">
                    <a href="{{ route('admin.tags.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 border border-blue-700 rounded">
                        <i class="fa fa-plus mr-1"></i>
                        <span>Create Tag</span>
                    </a>
                </div>
            </div>

            @if ($tags->count())
                <div class="w-full mx-auto">
                    <div class="bg-white shadow-md rounded my-6">
                        <table class="text-left w-full border-collapse">
                            <thead>
                                <th class="py-4 px-6 bg-gray-200 font-bold uppercase text-sm text-gray-700 border-b border-gray-100">
                                    Name
                                </th>
                                <th class="py-4 px-6 bg-gray-200 font-bold uppercase text-sm text-gray-700 border-b border-gray-100">
                                    Used By Count
                                </th>
                                <th class="py-4 px-6 bg-gray-200 font-bold uppercase text-sm text-gray-700 border-b border-gray-100">
                                    Created
                                </th>
                                <th class="py-4 px-6 bg-gray-200 font-bold uppercase text-sm text-gray-700 border-b border-gray-100">
                                    Actions
                                </th>
                            </thead>
                            <tbody>
                                @foreach($tags as $tag)
                                    <tr class="hover:bg-gray-100">
                                        <td class="py-4 px-6 border-b border-gray-100">
                                            {{ $tag->name }}
                                        </td>
                                        <td class="py-4 px-6 border-b border-gray-100">
                                            {{ number_format($tag->relatedCount()) }}
                                        </td>
                                        <td class="py-4 px-6 border-b border-gray-100">
                                            {{ $tag->created_at->format('F j, Y') }}
                                        </td>
                                        <td class="py-4 px-6 border-b border-gray-100">
                                            <a href="{{ route('admin.tags.edit', [ 'tag' => $tag->id ]) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-3 border border-black rounded">
                                                <i class="fa fa-pencil-alt mr-1"></i>
                                                <span>Edit</span>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{ $tags->links('pagination::bulma') }}
            @endif
        </div>
    </div>
@endsection
