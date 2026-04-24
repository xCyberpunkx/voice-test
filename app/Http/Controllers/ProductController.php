<?php
namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        return Product::all();
    }

    public function store(Request $request)
    {
        return Product::create($request->only(['name','price','quantity']));
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $product->update($request->only(['name','price','quantity']));

        return $product;
    }

    public function destroy($id)
    {
        Product::findOrFail($id)->delete();

        return response()->json(['message' => 'deleted']);
    }
}