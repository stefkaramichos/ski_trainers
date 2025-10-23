<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TicketController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth'); // you already have auth scaffolding
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Ticket::class);

        if ($request->user()->isSuperAdmin()) {
            $tickets = Ticket::with('instructor')->latest()->paginate(20);
        } else {
            $tickets = Ticket::with('instructor')
                ->where('instructor_id', $request->user()->id)
                ->latest()->paginate(20);
        }

        return view('tickets.index', compact('tickets'));
    }

    public function create()
    {
        $this->authorize('create', Ticket::class);
        return view('tickets.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Ticket::class);

        $data = $request->validate([
            'subject'  => ['required','string','max:255'],
            'priority' => ['nullable','in:low,normal,high'],
            'message'  => ['required','string'],
        ]);

        $ticket = Ticket::create([
            'instructor_id' => $request->user()->id,
            'subject'       => $data['subject'],
            'priority'      => $data['priority'] ?? 'normal',
        ]);

        $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'body'    => $data['message'],
        ]);

        return redirect()->route('tickets.show', $ticket)->with('success','Ticket created.');
    }

    public function show(Ticket $ticket)
    {
        $this->authorize('view', $ticket);
        $ticket->load(['messages.author','instructor']);
        return view('tickets.show', compact('ticket'));
    }

    public function update(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $data = $request->validate([
            'status'   => ['nullable','in:open,pending,resolved,closed'],
            'priority' => ['nullable','in:low,normal,high'],
        ]);

        if (isset($data['status'])) {
            $ticket->status = $data['status'];
            $ticket->resolved_at = in_array($ticket->status, ['resolved','closed'])
                ? now() : null;
        }
        if (isset($data['priority'])) {
            $ticket->priority = $data['priority'];
        }
        $ticket->save();

        return back()->with('success','Ticket updated.');
    }
}

