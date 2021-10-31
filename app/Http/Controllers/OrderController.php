<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Http\Resources\OrderResource;

class OrderController extends Controller
{
    public function index()
    {
        return OrderResource::collection(Order::with('orderItems')->get());
    }
}
