<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    /**
     * Show the pricing page.
     */
    public function pricing(): View
    {
        return view('billing.pricing');
    }

    /**
     * Create a Stripe Checkout session for Pro subscription.
     */
    public function checkout(Request $request): RedirectResponse
    {
        $priceId = config('services.stripe.pro_price_id');

        if (! $priceId) {
            return back()->withErrors(['billing' => 'Stripe is not configured.']);
        }

        return $request->user()
            ->newSubscription('pro', $priceId)
            ->checkout([
                'success_url' => route('billing.success').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('billing.pricing'),
            ])
            ->redirect();
    }

    /**
     * Handle successful checkout.
     */
    public function success(Request $request): View
    {
        return view('billing.success');
    }

    /**
     * Redirect to Stripe Customer Portal for subscription management.
     */
    public function portal(Request $request): RedirectResponse
    {
        return $request->user()->redirectToBillingPortal(route('dashboard'));
    }
}
