@extends('layouts.admin')

@section('content')
    <div class="box">
        <div class="clearfix">
            <div class="float-left">
                <h1 class="bg-brand font-bold text-center text-3xl md:text-5xl px-3">
                    Contact Submissions
                </h1>
            </div>
        </div>

        @if (!$items->count())
            <span>No contact submissions found.</span>
        @else
            <div class="w-full mx-auto">
                <div class="bg-white shadow-md rounded my-6">
                    <table class="text-left w-full border-collapse">
                        <thead>
                            <th class="py-4 px-6 bg-gray-200 font-bold uppercase text-sm text-gray-700 border-b border-gray-100">
                                Name
                            </th>
                            <th class="py-4 px-6 bg-gray-200 font-bold uppercase text-sm text-gray-700 border-b border-gray-100">
                                Email
                            </th>
                            <th class="py-4 px-6 bg-gray-200 font-bold uppercase text-sm text-gray-700 border-b border-gray-100">
                                Reviewed
                            </th>
                            <th class="py-4 px-6 bg-gray-200 font-bold uppercase text-sm text-gray-700 border-b border-gray-100">
                                Created
                            </th>
                            <th class="py-4 px-6 bg-gray-200 font-bold uppercase text-sm text-gray-700 border-b border-gray-100">
                                Actions
                            </th>
                        </thead>
                        <tbody>
                            @foreach($items as $item)
                                <tr class="hover:bg-gray-100">
                                    <td class="py-4 px-6 border-b border-gray-100">
                                        {{ $item->name }}
                                    </td>
                                    <td class="py-4 px-6 border-b border-gray-100">
                                        {{ $item->email }}
                                    </td>
                                    <td class="py-4 px-6 border-b border-gray-100">
                                        {{ $item->reviewed ? 'Yes' : 'No' }}
                                    </td>
                                    <td class="py-4 px-6 border-b border-gray-100">
                                        {{ $item->created_at->format('F j, Y') }}
                                    </td>
                                    <td class="py-4 px-6 border-b border-gray-100">
                                        @if(!$item->reviewed)
                                            <a href="{{ route('admin.contact_submissions.review', [ 'submission' => $item->id ]) }}" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 border border-green-700 rounded mr-2">
                                                <i class="far fa-check-square mr-1"></i>
                                                <span>Review</span>
                                            </a>
                                        @endif

                                        <a
                                            href="{{ route('admin.contact_submissions.delete', [ 'submission' => $item->id ]) }}"
                                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 border border-blue-700 rounded"
                                            onclick="return confirm('Are you sure you want to delete this submission?');"
                                        >
                                            <i class="fa fa-trash mr-1"></i>
                                            <span>Delete</span>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $items->links('pagination::bulma') }}
        @endif
    </div>
@endsection
