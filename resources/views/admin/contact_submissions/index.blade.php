@extends('layouts.admin')

@section('content')
    <h1 class="title is-1">
        Contact Submissions
    </h1>

    @if (!$items->count())
        <p>No contact submissions found.</p>
    @else
        <div class="responsive-table-container mb-1">
            <table class="table is-bordered is-fullwidth">
                <thead>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Reviewed?</th>
                    <th>Submitted</th>
                    <th></th>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td>
                                {{ $item->name }}
                            </td>
                            <td>
                                <a href="mailto:{{ $item->email }}" target="_blank">
                                    {{ $item->email }}
                                </a>
                            </td>
                            <td>
                                {{ $item->reviewed ? 'Yes' : 'No' }}
                            </td>
                            <td>
                                {{ $item->created_at->format('F j, Y') }}
                            </td>
                            <td>
                                <div class="buttons">
                                    @if(!$item->reviewed)
                                        <a href="{{ route('admin.contact_submissions.review', [ 'submission' => $item->id ]) }}" class="button is-warning">
                                            <span class="icon">
                                                <i class="fas fa-check-square"></i>
                                            </span>
                                            <span>Pending Review</span>
                                        </a>
                                    @else
                                        <a href="{{ route('admin.contact_submissions.review', [ 'submission' => $item->id ]) }}" class="button is-info">
                                            <span class="icon">
                                                <i class="fas fa-eye"></i>
                                            </span>
                                            <span>View</span>
                                        </a>
                                    @endif

                                    <admin-delete-button
                                        url="{{ route('admin.contact_submissions.delete', [ 'submission' => $item->id ]) }}"
                                    />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $items->links('pagination::bulma') }}
    @endif
@endsection
