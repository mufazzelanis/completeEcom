<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Concerns\SyncsProductVariants;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\ImageOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    use SyncsProductVariants;

    public function index(Request $request)
    {
        $vendorId = $request->user()->vendor->id;

        $query = Product::where('seller_id', $vendorId)->with(['category', 'brand']);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query->latest()->paginate(15)->withQueryString();

        return view('seller.products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $brands = Brand::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('seller.products.create', compact('categories', 'brands'));
    }

    public function store(Request $request)
    {
        $vendor = $request->user()->vendor;

        $data = $this->validated($request);
        $data['seller_id'] = $vendor->id;
        $data['slug'] = $this->uniqueSlug(Str::slug($request->name));
        $data['approval_status'] = 'pending';
        $data['is_active'] = false;
        $data['download_expiry_days'] = $request->filled('download_expiry_days') ? (int) $request->download_expiry_days : null;

        if ($request->hasFile('image')) {
            $data['image'] = ImageOptimizer::store($request->file('image'), 'products', 'public', 1200);
        }
        if ($request->hasFile('download_file')) {
            $data['download_file'] = $request->file('download_file')->store('downloads', 'private');
        }

        $product = Product::create($data);

        $colorIdsByIndex = $this->syncProductColors($product, $request->input('colors', []), $request);
        $sizeIdsByIndex = $this->syncProductSizes($product, $request->input('sizes', []));
        $this->syncProductCombinations($product, $request->input('combinations', []), $colorIdsByIndex, $sizeIdsByIndex);

        return redirect()->route('seller.products.index')
            ->with('success', 'Product submitted for admin approval. It will go live once approved.');
    }

    public function edit(Request $request, Product $product)
    {
        $this->authorizeOwnership($request, $product);

        $categories = Category::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $brands = Brand::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $variantMatrix = $this->variantMatrixJsData($product);

        return view('seller.products.edit', compact('product', 'categories', 'brands', 'variantMatrix'));
    }

    public function update(Request $request, Product $product)
    {
        $this->authorizeOwnership($request, $product);

        $data = $this->validated($request, $product->id, $product);
        // Any edit is re-reviewed before it goes live again — avoids a partial
        // re-approval state machine (e.g. "price changed but still shown live").
        $data['approval_status'] = 'pending';
        $data['is_active'] = false;
        $data['rejection_reason'] = null;
        $data['download_expiry_days'] = $request->filled('download_expiry_days') ? (int) $request->download_expiry_days : null;

        if ($request->hasFile('image')) {
            $data['image'] = ImageOptimizer::store($request->file('image'), 'products', 'public', 1200);
        }
        if ($request->hasFile('download_file')) {
            $data['download_file'] = $request->file('download_file')->store('downloads', 'private');
        }

        $product->update($data);

        $colorIdsByIndex = $this->syncProductColors($product, $request->input('colors', []), $request);
        $sizeIdsByIndex = $this->syncProductSizes($product, $request->input('sizes', []));
        $this->syncProductCombinations($product, $request->input('combinations', []), $colorIdsByIndex, $sizeIdsByIndex);

        return redirect()->route('seller.products.index')
            ->with('success', 'Product updated and re-submitted for admin approval.');
    }

    public function destroy(Request $request, Product $product)
    {
        $this->authorizeOwnership($request, $product);

        if (OrderItem::where('product_id', $product->id)->exists()) {
            return back()->with('error', 'Cannot delete a product that has already been ordered.');
        }

        $product->delete();

        return back()->with('success', 'Product deleted.');
    }

    private function authorizeOwnership(Request $request, Product $product): void
    {
        abort_unless($product->seller_id === $request->user()->vendor->id, 403);
    }

    private function validated(Request $request, ?int $exceptId = null, ?Product $product = null): array
    {
        $request->validate([
            'name'              => 'required|string|max:255',
            'type'              => 'required|in:simple,variable,digital',
            'category_id'       => 'required|exists:categories,id',
            'brand_id'          => 'nullable|exists:brands,id',
            'sku'               => 'nullable|string|max:100|unique:products,sku,' . ($exceptId ?? 'NULL') . ',id',
            'price'             => 'required|numeric|min:0',
            'sale_price'        => 'nullable|numeric|min:0|lt:price',
            'stock'             => 'nullable|integer|min:0',
            'weight'            => 'nullable|numeric|min:0',
            'short_description' => 'nullable|string|max:500',
            'description'       => 'nullable|string',
            'image'             => 'nullable|image|max:4096',
            // Only required if there isn't already a file saved — editing other
            // fields shouldn't force a re-upload.
            'download_file'     => ($request->input('type') === 'digital' && ! ($product && $product->download_file))
                ? 'required|file|max:102400' : 'nullable|file|max:102400',
        ]);

        return $request->only([
            'type', 'name', 'category_id', 'brand_id', 'sku', 'price', 'sale_price',
            'stock', 'weight', 'short_description', 'description',
        ]);
    }

    private function uniqueSlug(string $slug): string
    {
        $original = $slug;
        $i = 1;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $original . '-' . $i++;
        }

        return $slug;
    }
}
