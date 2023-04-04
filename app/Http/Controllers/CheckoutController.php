<?php

namespace App\Http\Controllers;

use App\Mail\OrderShipped;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class CheckoutController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $stripe = new \Stripe\StripeClient('sk_test_51LutNKF5pBtNoeM0cLzxBI7lux9yarAP8ALWirwYJjCW6i99zhgJ44c53PXGOE3aREeH2TnLusxYwJKigGR9xRbS00tbAXZ7B4');

        $order = Order::where('user_id', '=', auth()->user()->id)
            ->where('payment_intent', null)
            ->first();

        // if (is_null($order)) {
        //     return redirect()->route('checkout_success.index');
        // }

        $intent = $stripe->paymentIntents->create([
            // 'amount' => (int) $order->total,
            'amount' => 1000,
            'currency' => 'usd',
            'payment_method_types' => ['card'],
        ]);

        return Inertia::render('Checkout', [
            'intent' => $intent,
            'order' => $order
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $res = Order::where('user_id', '=', auth()->user()->id)
            ->where('payment_intent', null)
            ->first();

        if (!is_null($res)) {
            $res->total = $request->total;
            $res->total_decimal = $request->total_decimal;
            $res->items = json_encode($request->items);
            $res->save();
        } else {
            $order = new Order();
            $order->user_id = auth()->user()->id;
            $order->total = $request->total;
            $order->total_decimal = $request->total_decimal;
            $order->items = json_encode($request->items);
            $order->save();
        }
        return redirect()->route('checkout.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        dd($request->all());
        $order = Order::where('user_id', '=', auth()->user()->id)
            ->where('payment_intent', null)
            ->first();
        $order->payment_intent = $request['payment_intent'];
        $order->save();

        Mail::to($request->user())->send(new OrderShipped($order));

        return redirect()->route('dashboard');
        // return redirect()->route('checkout_success.index');
    }
}