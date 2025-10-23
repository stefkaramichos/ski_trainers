@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Tickets</h1>

    @if(!auth()->user()->isSuperAdmin())
        <a class="btn btn-primary mb-3" href="{{ route('tickets.create') }}">Open Ticket</a>
    @endif

    <table class="table">
        <thead>
            <tr>
                <th>#</th><th>Subject</th><th>Instructor</th><th>Status</th><th>Priority</th><th>Updated</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tickets as $t)
                <tr>
                    <td>{{ $t->id }}</td>
                    <td><a href="{{ route('tickets.show', $t) }}">{{ $t->subject }}</a></td>
                    <td>{{ $t->instructor->name }}</td>
                    <td>{{ ucfirst($t->status) }}</td>
                    <td>{{ ucfirst($t->priority) }}</td>
                    <td>{{ $t->updated_at?->diffForHumans() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $tickets->links() }}
</div>
@endsection
