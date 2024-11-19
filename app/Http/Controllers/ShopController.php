<?php

namespace App\Http\Controllers;

use App\Models\product;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index()
    {
        $products = product::orderBy('created_at', 'DESC')->paginate();
        return view('shop', compact('products'));
    }

    public function product_details($product_slug)
    {
        $product = product::where('slug', $product_slug)->first();
        $rproducts = product::where('slug', '<>', $product_slug)->get()->take(8);
        return view('details', compact('product', 'rproducts'));
    }
}
