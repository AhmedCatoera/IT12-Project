<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $category = $this->textFilter($request, 'category');
        $category = array_key_exists((string) $category, Product::CATEGORIES) ? $category : null;

        $products = Product::query()
            ->withCount('orderItems')
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderByDesc('is_active')
            ->orderBy('category')
            ->orderBy('name')
            ->paginateOrLast(15)
            ->withQueryString();

        return view('products.index', compact('products', 'category'));
    }

    public function store(Request $request)
    {
        $data = $request->validateWithBag('createProduct', [
            'category' => ['required', Rule::in(array_keys(Product::CATEGORIES))],
            'name' => ['required', 'string', 'max:100', 'unique:products,name'],
            'base_price' => ['required', 'numeric', 'min:0', 'max:999999'],
        ]);

        Product::create($data + ['is_active' => true]);

        return back()->with('success', "{$data['name']} was added to the price list.");
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validateWithBag('editProduct', [
            'category' => ['required', Rule::in(array_keys(Product::CATEGORIES))],
            'name' => ['required', 'string', 'max:100', Rule::unique('products', 'name')->ignore($product->id)],
            'base_price' => ['required', 'numeric', 'min:0', 'max:999999'],
            'is_active' => ['required', 'boolean'],
        ]);

        $product->update($data);

        return back()->with('success', "{$product->name} was updated.");
    }
}
