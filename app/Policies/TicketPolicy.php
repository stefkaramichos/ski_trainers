<?php
namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || true; // instructors can see their own list (scoped in controller)
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->isSuperAdmin() || $ticket->instructor_id === $user->id;
    }

    public function create(User $user): bool
    {
        // any authenticated user (instructor) can open
        return true;
    }

    public function update(User $user, Ticket $ticket): bool
    {
        // allow status/subject edits by owner or super admin
        return $user->isSuperAdmin() || $ticket->instructor_id === $user->id;
    }

    public function reply(User $user, Ticket $ticket): bool
    {
        // both super admin and ticket owner can reply
        return $this->view($user, $ticket);
    }
}
