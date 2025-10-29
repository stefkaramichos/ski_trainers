<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Mountain;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use App\Mail\NewUserRegisteredMail;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeCheckoutSession;

class RegisterController extends Controller
{
    use RegistersUsers {
        register as traitRegister;
    }

    protected $redirectTo = '/home';

    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showRegistrationForm()
    {
        $mountains = Mountain::all();
        return view('auth.register', compact('mountains'));
    }

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'    => ['required', 'string', 'min:8', 'confirmed'],
            'description' => ['nullable', 'string'],
            'image'       => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'mountains'   => ['nullable', 'array'],
            'mountains.*' => ['exists:mountains,id'],
        ]);
    }

    /**
     * STEP 1:
     * Validate + stash user data in session,
     * create Stripe Checkout Session for a SUBSCRIPTION with a TRIAL (0€ now),
     * redirect user to Stripe.
     */
    public function register(Request $request)
    {
        // 1. validate input
        $this->validator($request->all())->validate();

        // 2. save uploaded image to temp
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('profiles-temp', 'public');
        }

        // 3. stash everything we need to actually create the user later
        $request->session()->put('pending_registration', [
            'name'        => $request->input('name'),
            'email'       => $request->input('email'),
            'password'    => $request->input('password'), // we'll hash it later
            'description' => $request->input('description'),
            'image'       => $imagePath,
            'mountains'   => $request->input('mountains', []),
        ]);

        // 4. Stripe calls
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        // 4a. Create (or reuse) the Stripe Customer now
        $customer = \Stripe\Customer::create([
            'email' => $request->input('email'),
            'name'  => $request->input('name'),
        ]);

        // 4b. Create the Checkout Session for a SUBSCRIPTION
        //     We're telling Stripe:
        //       - this is subscription mode
        //       - attach it to THIS customer
        //       - use this price (recurring price in Stripe dashboard)
        //     Optionally you can uncomment trial_period_days to start as free trial.
        $checkoutSession = \Stripe\Checkout\Session::create([
            'mode' => 'subscription',
            'customer' => $customer->id, // 👈 this forces Stripe to link the sub to THIS customer
            'line_items' => [[
                'price' => 'price_1Qs6WRBHbjRGq0TFPtQHWJYR',
                'quantity' => 1,
            ]],
            // 'subscription_data' => [
            //     'trial_period_days' => 7, // or 0 if no trial
            // ],

            'success_url' => route('register.payment.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('register.payment.cancel'),
        ]);

        return redirect($checkoutSession->url);
    }


    /**
     * STEP 2:
     * Stripe redirected here after the user completed Checkout.
     * We confirm it, then we create and log in the user.
     */
    public function paymentSuccess(Request $request)
    {
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        $sessionId = $request->query('session_id');
        if (!$sessionId) {
            return redirect()->route('register')->with('error', 'Missing payment session.');
        }

        // Ask Stripe for a "fuller" session so we are sure we get subscription + customer
        $session = \Stripe\Checkout\Session::retrieve([
            'id' => $sessionId,
            'expand' => ['subscription', 'customer'],
        ]);

        // Make sure checkout actually finished
        if ($session->status !== 'complete') {
            return redirect()->route('register.payment.cancel')
                ->with('error', 'Subscription not completed.');
        }

        // Get Stripe data
        // After expand, we can safely read both:
        $subscriptionId = is_string($session->subscription)
            ? $session->subscription           // sometimes it's just "sub_abc.."
            : ($session->subscription->id ?? null); // or full object

        $customerId = is_string($session->customer)
            ? $session->customer               // "cus_abc.."
            : ($session->customer->id ?? null);

        // Get the info we stored before redirecting to Stripe
        $data = $request->session()->get('pending_registration');
        if (!$data) {
            return redirect()->route('register')
                ->with('error', 'Registration data expired. Please try again.');
        }

        $finalImagePath = $data['image'] ?? null;

        // Create the user in DB NOW with Stripe info
        $user = User::create([
            'name'                   => $data['name'],
            'email'                  => $data['email'],
            'password'               => \Hash::make($data['password']),
            'description'            => $data['description'] ?? null,
            'image'                  => $finalImagePath,
            'stripe_customer_id'     => $customerId,      // 👈 should now be "cus_..."
            'stripe_subscription_id' => $subscriptionId,  // 👈 should now be "sub_..."
            'status'                 => 'A',              // mark active
        ]);

        // Attach selected mountains
        if (!empty($data['mountains'])) {
            $user->mountains()->attach($data['mountains']);
        }

        // Notify admin
        \Mail::to('stephanoskaramichos@gmail.com')->send(new \App\Mail\NewUserRegisteredMail($user));

        // Log user in
        $this->guard()->login($user);

        // Cleanup temp session data
        $request->session()->forget('pending_registration');

        return redirect($this->redirectPath())
            ->with('success', 'Your account is active. Trial started!');
    }


    /**
     * User canceled at Stripe -> no account created.
     */
    public function paymentCancel()
    {
        return redirect()->route('register')
            ->with('error', 'You cancelled before completing your subscription. No account was created.');
    }
}
