@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Open Ticket</h1>
    <form method="POST" action="{{ route('tickets.store') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Subject</label>
            <input name="subject" class="form-control" value="{{ old('subject') }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Priority</label>
            <select name="priority" class="form-select">
                <option value="normal">Normal</option>
                <option value="low">Low</option>
                <option value="high">High</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Message</label>
            <textarea name="message" rows="5" class="form-control" required>{{ old('message') }}</textarea>
        </div>
        <button class="btn btn-primary">Create</button>
    </form>
</div>
@endsection
