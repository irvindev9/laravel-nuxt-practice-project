<?php

namespace App\Http\Controllers;

use App\Events\ProductUpdatedEvent;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Product::all();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $product = Product::create($request->only('title', 'description', 'image', 'price'));

        event(new ProductUpdatedEvent($product));

        return response($product, Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        return $product;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        $product->update($request->only('title', 'description', 'image', 'price'));

        event(new ProductUpdatedEvent($product));

        return response($product, Response::HTTP_ACCEPTED);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        $product->delete();

        event(new ProductUpdatedEvent($product));

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function frontend(){
        if ($products = \Cache::get('products_frontend')) {
            return $products;
        }

        $products = Product::all();

        \Cache::set('products_frontend', $products, 60 * 30); // 30 minutes

        return Product::all();
    }

    public function backend(Request $request){

        $page = $request->input('page', 1);

        $products = \Cache::remember('products_backend', 60 * 30, function () {
            return Product::all();
        });

        if ($s = $request->input('s')){
            $products = $products->filter(fn(Product $product) => Str::contains($product->title, $s) || Str::contains($product->description, $s));
        }

        if ($sort = $request->input('sort')){
            if ($sort == 'asc'){
                $products = $products->sortBy([
                    fn($a, $b) => $a['price'] <=> $b['price']
                ]);
            } else {
                $products = $products->sortBy([
                    fn($a, $b) => $b['price'] <=> $a['price']
                ]);
            }
        }

        return [
            'data' => $products->forPage($page, 10),
            'meta' => [
                'total' => $products->count(),
                'page' => $page,
                'last_page' => ceil($products->count() / 10),
            ]
        ];
    }
}
