<?php
namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketMessageController extends Controller
{
    public function store(Request $request, Ticket $ticket)
    {
        $this->authorize('reply', $ticket);

        $validated = $request->validate([
            'body' => ['required','string']
        ]);

        $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'body'    => $validated['body'],
        ]);

        $message = $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'body'    => $validated['body'],
        ]);

        // Optional: put ticket to pending when someone replies
        if ($request->user()->isSuperAdmin()) {
            $ticket->update(['status' => 'pending']);
            $ticket->instructor->notify(new \App\Notifications\TicketReplied($ticket, $message));
        } else {
            $ticket->update(['status' => 'open']);
            \App\Models\User::where('super_admin','Y')->get()
        ->each->notify(new \App\Notifications\TicketReplied($ticket, $message));
        }

        return back()->with('success','Reply posted.');
    }
}
