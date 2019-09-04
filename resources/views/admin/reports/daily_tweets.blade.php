@extends('layouts.admin')

@section('content')
    <report-daily-tweets :report-json="{{ json_encode($report) }}" />
@endsection
