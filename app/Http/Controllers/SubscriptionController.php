<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Braintree\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{

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
                $subscription = Subscription::where('user_id', $user->id)->first();
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
