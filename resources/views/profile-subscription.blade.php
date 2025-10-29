@extends('layouts.app')

@section('content')
<div class="main-form">
    <div class="container">
        <div class="row">
            @include('includes.profile-header')
            @include('includes.admin-edit-menu')

            <div class="mb-3 w-100">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Συνδρομή</span>

                        @if ($hasActiveSubscription)
                            @if ($isCancelScheduled)
                                <span class="badge bg-warning text-dark">Λήγει σύντομα</span>
                            @else
                                <span class="badge bg-success">Ενεργή</span>
                            @endif
                        @else
                            <span class="badge bg-secondary">Δεν είναι ενεργή</span>
                        @endif
                    </div>

                    <div class="card-body edit-profile">
                        @include('includes.success-error-message')

                        {{-- CASE 1: No active sub at all -> offer to subscribe --}}
                        @if (!$hasActiveSubscription)
                            <p class=" mb-3">
                                Δεν έχετε ενεργή συνδρομή.
                            </p>

                            <form method="POST" action="{{ route('subscription.start', $user->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary">
                                    Έναρξη Συνδρομής
                                </button>
                            </form>

                        {{-- CASE 2: active and NOT yet canceled --}}
                        @elseif ($hasActiveSubscription && !$isCancelScheduled)
                            @if ($nextBillingDate)
                                <p class="mb-2">
                                    Η συνδρομή σας είναι ενεργή μέχρι
                                    {{ $nextBillingDate->format('d/m/Y H:i') }}.
                                    Μπορείτε να την ακυρώσετε και θα σταματήσουν οι επόμενες χρεώσεις.
                                </p>
                            @else
                                <p class="mb-2">
                                    Η συνδρομή σας είναι ενεργή. Μπορείτε να την ακυρώσετε και θα σταματήσουν οι επόμενες χρεώσεις.
                                </p>
                            @endif

                            <form
                                method="POST"
                                action="{{ route('subscription.cancel', $user->id) }}"
                                onsubmit="return confirm('Είστε σίγουρος/η ότι θέλετε να ακυρώσετε τη συνδρομή;');"
                            >
                                @csrf
                                <input type="hidden" name="subscription_id" value="{{ $stripeSubId }}">
                                <button type="submit" class="btn btn-danger">
                                    Ακύρωση Συνδρομής
                                </button>
                            </form>

                        {{-- CASE 3: cancel scheduled for period end --}}
                        @elseif ($hasActiveSubscription && $isCancelScheduled)
                            <p class="mb-2">
                                Η συνδρομή σας έχει προγραμματιστεί να λήξει.
                                Δεν θα υπάρξουν νέες χρεώσεις.
                                @if ($cancelAtDate)
                                    Πρόσβαση μέχρι {{ $cancelAtDate->format('d/m/Y H:i') }}.
                                @endif
                            </p>

                            <p class="text-muted mb-0">
                                Δεν χρειάζεται άλλη ενέργεια από εσάς.
                            </p>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
