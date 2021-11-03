<?php

namespace App\Http\Controllers;

use App\Events\OrderCompletedEvent;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Link;
use App\Http\Resources\OrderResource;
use App\Models\OrderItem;
use App\Models\Product;
use Cartalyst\Stripe\Stripe;

class OrderController extends Controller
{
    public function index()
    {
        return OrderResource::collection(Order::with('orderItems')->get());
    }

    public function store(Request $request){
        if(!$link = Link::where('code', $request->code)->first()){
            return response()->json(['message' => 'Code not found'], 400);
        }

        $source = [];

        try{
            \DB::beginTransaction();

            $order = new Order();
            $order->code = $link->code;
            $order->user_id = $link->user->id;
            $order->ambassador_email = $link->user->email;
            $order->first_name = $request->first_name;
            $order->last_name = $request->last_name;
            $order->email = $request->email;
            $order->address = $request->address;
            $order->country = $request->country;
            $order->city = $request->city;
            $order->zip = $request->zip;
            $order->save();

            $lineItems = [];

            foreach($request->products as $item){
                $product = Product::find($item['product_id']);

                $orderItem = new OrderItem();

                $orderItem->order_id = $order->id;
                $orderItem->product_title = $product->title;
                $orderItem->price = $product->price;
                $orderItem->quantity = $item['quantity'];
                $orderItem->ambassador_revenue = 0.1 * $product->price * $item['quantity'];
                $orderItem->admin_revenue = 0.9 * $product->price * $item['quantity'];

                $orderItem->save();

                $lineItems[] = [
                    'name' => $product->title,
                    'description' => $product->description,
                    'image' => [
                        $product->image
                    ],
                    'amount' => $product->price * 100,
                    'currency' => 'usd',
                    'quantity' => $item['quantity']
                ];
            }

            $stripe = Stripe::make(env('STRIPE_PRIVATE_KEY'));

            $source = $stripe->checkout()->sessions()->create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'success_url' => env('APP_URL') . '/success?source={CHECKOUT_SESSION_ID}',
                'cancel_url' => env('APP_URL') . '/cancel',
            ]);

            $order->transaction_id = $source->id;

            \DB::commit();
        }catch(\Throwable $e){
            \DB::rollback();
            return response()->json(['message' => $e->getMessage()], 500);
        }

        return $source;
    }

    public function confirm(Request $request){
        if(!$order = Order::where('transaction_id', $request->source)->first()){
            return response()->json(['message' => 'Order not found'], 404);
        }

        $order->complete = 1;
        $order->save();

        event(new OrderCompletedEvent());

        return response()->json(['message' => 'Order confirmed'], 200);

    }
}
