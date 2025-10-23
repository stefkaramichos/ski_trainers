@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Ticket #{{ $ticket->id }} – {{ $ticket->subject }}</h1>
        <form method="POST" action="{{ route('tickets.update', $ticket) }}" class="d-flex gap-2">
            @csrf @method('PATCH')
            @can('update', $ticket)
            <select name="status" class="form-select">
                @foreach(['open','pending','resolved','closed'] as $st)
                    <option value="{{ $st }}" @selected($ticket->status===$st)>{{ ucfirst($st) }}</option>
                @endforeach
            </select>
            <select name="priority" class="form-select">
                @foreach(['low','normal','high'] as $pr)
                    <option value="{{ $pr }}" @selected($ticket->priority===$pr)>{{ ucfirst($pr) }}</option>
                @endforeach
            </select>
            <button class="btn btn-secondary">Update</button>
            @endcan
        </form>
    </div>

    <p class="text-muted mb-4">
        Instructor: {{ $ticket->instructor->name }} |
        Status: <strong>{{ ucfirst($ticket->status) }}</strong> |
        Priority: <strong>{{ ucfirst($ticket->priority) }}</strong>
    </p>

    <div class="card mb-4">
        <div class="card-body">
            @foreach($ticket->messages as $msg)
                <div class="mb-3">
                    <div>
                        <strong>{{ $msg->author->name }}</strong>
                        <small class="text-muted">• {{ $msg->created_at->diffForHumans() }}</small>
                    </div>
                    <div>{{ $msg->body }}</div>
                </div>
                <hr>
            @endforeach
        </div>
    </div>

    @can('reply', $ticket)
    <form method="POST" action="{{ route('tickets.messages.store', $ticket) }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Reply</label>
            <textarea name="body" rows="4" class="form-control" required></textarea>
        </div>
        <button class="btn btn-primary">Send Reply</button>
    </form>
    @endcan
</div>
@endsection
