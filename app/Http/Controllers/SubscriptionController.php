<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{

    /**
     * First step of braintree payment cycle
     * Creates a customer entity at Braintree 
     * (Reference : https://developer.paypal.com/braintree/docs/reference/request/customer/create)
     * Saves the above customer information and associates it with the logged in user
     *
     * @author Ahsan Qureshi <ahsanqureshi1603@mim-essay.com>
     * @param  Request      $request
     * @redirect /payment
     */

    public function subscribe(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
            if (!$user->isSubscribed()) {
                $subscription = Subscription::where('user_id', $user->id)->where('status', '<>', Subscription::DEACTIVATED)->first();
                $plan = $request->input('plan');
                $planId = config('braintree.plan_id_' . $plan);
                if (empty($subscription)) {
                    DB::beginTransaction();
                    $gateway = new \Braintree\Gateway([
                        'environment' => config('braintree.environment'),
                        'merchantId' => config('braintree.merchant_id'),
                        'publicKey' => config('braintree.public_key'),
                        'privateKey' => config('braintree.private_key'),
                    ]);
                    $result = $gateway->customer()->create([
                        'firstName' => $user->fname,
                        'lastName' => $user->lname,
                        'email' => $user->email,
                    ]);

                    Subscription::create([
                        'user_id' => $user->id,
                        'bt_customer_id' => $result->customer->id,
                        'bt_plan_id' => $planId,
                        'bt_plan_type' => $plan,
                        'status' => Subscription::CUSTOMER_CREATED,
                    ]);
                    DB::commit();
                } else {
                    DB::beginTransaction();
                    $subscription->bt_plan_id = $planId;
                    $subscription->bt_plan_type = $plan;
                    $subscription->save();
                    DB::commit();
                }
                return redirect('payment');
            } else {
                return redirect('dashboard');
            }
        }
        return redirect("login")->withSuccess('Login details are not valid');
    }

    /**
     * Step 2, 3 and 4 of the payment cycle
     * Step 2:
     * On payment page we send '$clientToken' to the javascript (of the page)
     * (Reference : https://developer.paypal.com/braintree/docs/reference/request/client-token/generate/php)
     * And the payment options are shown
     * Once user enters right card to the system on submit we go to step 3
     * Step 3:
     * Function gets 'nonce' in the request of the above submit
     * The above 'nonce' is a on time javascript token used to create payment method for the 'customer'
     * (Reference : https://developer.paypal.com/braintree/docs/reference/request/payment-method/create)
     * Step 4:
     * We create the a selected subscription for the user using the 'payment method token'
     * (Reference : https://developer.paypal.com/braintree/docs/reference/request/subscription/create)
     * Once the a subscription is made at braintree. Rebilling is taken care of by Braintree
     * @author Ahsan Qureshi <ahsanqureshi1603@mim-essay.com>
     * @param  Request      $request
     * 
     * @redirect /dashboard
     */

    public function token(Request $request)
    {
        if (Auth::check()) {
            $gateway = new \Braintree\Gateway([
                'environment' => config('braintree.environment'),
                'merchantId' => config('braintree.merchant_id'),
                'publicKey' => config('braintree.public_key'),
                'privateKey' => config('braintree.private_key'),
            ]);
            if ($request->input('nonce') != null) {
                $user = Auth::user();
                $subscription = Subscription::where('user_id', $user->id)->where('status', '<>', Subscription::DEACTIVATED)->first();
                $nonceFromTheClient = $request->input('nonce');
                DB::beginTransaction();
                $paymentMethodresult = $gateway->paymentMethod()->create([
                    'customerId' => $subscription->bt_customer_id,
                    'paymentMethodNonce' => $nonceFromTheClient
                ]);

                $subscriptionResult = $gateway->subscription()->create([
                    'paymentMethodToken' => $paymentMethodresult->paymentMethod->token,
                    'planId' => $subscription->bt_plan_id
                ]);
                $subscription->bt_payment_method_token = $paymentMethodresult->paymentMethod->token;
                $subscription->bt_subscription_id = $subscriptionResult->subscription->id;
                $subscription->status = Subscription::ACTIVE;
                $subscription->save();
                DB::commit();
                return redirect('dashboard');
            } else {
                $clientToken = $gateway->clientToken()->generate();
                return view('braintree', ['token' => $clientToken]);
            }
        }
    }

    /**
     * Cancels the subscription created for the user
     * (Reference : https://developer.paypal.com/braintree/docs/reference/request/subscription/cancel)
     * @author Ahsan Qureshi <ahsanqureshi1603@mim-essay.com>
     * @param  Request      $request
     * 
     * @redirect /dashboard
     */

    public function unSubscribe(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->isSubscribed()) {
                DB::beginTransaction();
                $gateway = new \Braintree\Gateway([
                    'environment' => config('braintree.environment'),
                    'merchantId' => config('braintree.merchant_id'),
                    'publicKey' => config('braintree.public_key'),
                    'privateKey' => config('braintree.private_key'),
                ]);
                $subscription = Subscription::where('user_id', $user->id)->where('status', Subscription::ACTIVE)->first();
                $result = $gateway->subscription()->cancel($subscription->bt_subscription_id);
                if ($result->success) {
                    $subscription->status = Subscription::DEACTIVATED;
                    $subscription->save();
                }
                DB::commit();
                return redirect('dashboard');
            } else {
                return redirect('dashboard');
            }
        }
        return redirect("login")->withSuccess('Login details are not valid');
    }
}
