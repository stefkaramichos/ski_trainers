<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    // =============
    // SHOW PAGE
    // =============
    public function show(Request $request, User $user)
    {
        // permission
        if ($request->user()->id !== $user->id && $request->user()->super_admin !== "Y") {
            abort(403, 'Δεν έχετε δικαίωμα σε αυτή την ενέργεια.');
        }

        $hasActiveSubscription = false;
        $isCancelScheduled     = false;
        $nextBillingDate       = null;
        $cancelAtDate          = null;
        $stripeSubId           = null;

        try {
            if (!empty($user->stripe_subscription_id) || !empty($user->stripe_customer_id)) {
                \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

                $sub = null;

                // try by stored subscription id first
                if (!empty($user->stripe_subscription_id)) {
                    $sub = \Stripe\Subscription::retrieve($user->stripe_subscription_id);
                }
                // fallback: find any active-ish sub for this customer
                elseif (!empty($user->stripe_customer_id)) {
                    $subs = \Stripe\Subscription::all([
                        'customer' => $user->stripe_customer_id,
                        'status'   => 'all',
                        'limit'    => 5,
                    ]);

                    foreach ($subs->data as $candidate) {
                        if (in_array($candidate->status, ['trialing','active','past_due','unpaid'])) {
                            $sub = $candidate;
                            break;
                        }
                    }
                }

                if ($sub) {
                    // still billable now?
                    if (in_array($sub->status, ['trialing','active','past_due','unpaid'])) {
                        $hasActiveSubscription = true;
                    }

                    // scheduled to end at period end?
                    if (!empty($sub->cancel_at_period_end) && $sub->cancel_at_period_end === true) {
                        $isCancelScheduled = true;
                    }

                    // future end date / next billing cycle boundary
                    if (!empty($sub->current_period_end)) {
                        $nextBillingDate = Carbon::createFromTimestamp($sub->current_period_end);
                        $cancelAtDate    = Carbon::createFromTimestamp($sub->current_period_end);
                    }

                    $stripeSubId = $sub->id;
                }
            }
        } catch (\Exception $e) {
            // \Log::error('Stripe fetch failed: '.$e->getMessage());
        }

        // >>> NEW PART: sync user's status with Stripe reality
        // Rule:
        // - hasActiveSubscription true  -> user->status = 'A'
        // - otherwise                   -> user->status = 'D'
        $newStatus = $hasActiveSubscription ? 'A' : 'D';

        if ($user->status !== $newStatus) {
            $user->status = $newStatus;
            $user->save();
        }
        // <<< END NEW PART

        return view('profile-subscription', [
            'user'                  => $user,
            'hasActiveSubscription' => $hasActiveSubscription,
            'isCancelScheduled'     => $isCancelScheduled,
            'nextBillingDate'       => $nextBillingDate,
            'cancelAtDate'          => $cancelAtDate,
            'stripeSubId'           => $stripeSubId,
        ]);
    }

    // =============
    // START (new or re-subscribe)
    // =============
    public function start(Request $request, User $user)
    {
        if ($request->user()->id !== $user->id && $request->user()->super_admin !== "Y") {
            abort(403, 'Δεν έχετε δικαίωμα σε αυτή την ενέργεια.');
        }

        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        // ensure customer exists / save it if new
        $customerId = $user->stripe_customer_id;
        if (empty($customerId)) {
            $customer = \Stripe\Customer::create([
                'email' => $user->email,
                'name'  => $user->name,
            ]);
            $customerId = $customer->id;
            $user->stripe_customer_id = $customerId;
            $user->save();
        }
         $priceId = config('services.stripe.price_id');

        $checkoutSession = \Stripe\Checkout\Session::create([
            'mode' => 'subscription',
            'customer' => $customerId,
            'line_items' => [[
                'price' => $priceId, // your plan price
                'quantity' => 1,
            ]],
            'subscription_data' => [
                'trial_period_days' => 1,
            ],
            'success_url' => route('subscription.resume.success', $user->id) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('subscription.show', $user->id),
        ]);

        return redirect($checkoutSession->url);
    }

    // =============
    // SUCCESS AFTER START/RESUBSCRIBE
    // =============
    public function resumeSuccess(Request $request, User $user)
    {
        if ($request->user()->id !== $user->id && $request->user()->super_admin !== "Y") {
            abort(403, 'Δεν έχετε δικαίωμα σε αυτή την ενέργεια.');
        }

        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        $sessionId = $request->query('session_id');
        if (!$sessionId) {
            return redirect()->route('subscription.show', $user->id)
                ->withErrors(['subscription' => 'Missing checkout session.']);
        }

        $session = \Stripe\Checkout\Session::retrieve([
            'id' => $sessionId,
            'expand' => ['subscription', 'customer'],
        ]);

        if ($session->status !== 'complete') {
            return redirect()->route('subscription.show', $user->id)
                ->withErrors(['subscription' => 'Η πληρωμή δεν ολοκληρώθηκε.']);
        }

        $subscriptionId = is_string($session->subscription)
            ? $session->subscription
            : ($session->subscription->id ?? null);

        $customerId = is_string($session->customer)
            ? $session->customer
            : ($session->customer->id ?? null);

        // save on user
        if ($customerId && !$user->stripe_customer_id) {
            $user->stripe_customer_id = $customerId;
        }
        if ($subscriptionId) {
            $user->stripe_subscription_id = $subscriptionId;
        }

        // subscription is now active => user becomes 'A'
        $user->status = 'A';
        $user->save();

        return redirect()->route('subscription.show', $user->id)
            ->with('success', 'Η συνδρομή σας ενεργοποιήθηκε!');
    }

    // =============
    // CANCEL
    // =============
    public function cancel(Request $request, User $user)
    {
        if ($request->user()->id !== $user->id && $request->user()->super_admin !== "Y") {
            abort(403, 'Δεν έχετε δικαίωμα σε αυτή την ενέργεια.');
        }

        $subscriptionId = $request->input('subscription_id');

        if (!$subscriptionId) {
            return back()->withErrors([
                'subscription' => 'Δεν βρέθηκε ενεργή συνδρομή.',
            ]);
        }

        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            \Stripe\Subscription::update(
                $subscriptionId,
                ['cancel_at_period_end' => true]
            );

            // IMPORTANT NOTE:
            // We are NOT immediately setting status='D' here.
            // Why?
            // - User still paid through end of period.
            // - We want them to keep access until that time.
            //
            // What will flip them to 'D'?
            // - After period end, Stripe marks sub as 'canceled'.
            // - Next time they load subscription.show(), we won't detect active -> we'll set 'D'.

            return back()->with('success', 'Η συνδρομή σας θα ακυρωθεί στο τέλος της τρέχουσας περιόδου.');
        } catch (\Exception $e) {
            return back()->withErrors([
                'subscription' => 'Σφάλμα κατά την ακύρωση: ' . $e->getMessage(),
            ]);
        }
    }
}
