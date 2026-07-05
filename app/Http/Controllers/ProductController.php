<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Repositories\ProductRepository;
use App\Services\ProductInventoryService;
use App\Services\ProductReportService;
use App\Services\ProductService;
use App\Services\ProductSpecService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function __construct(
        private ProductRepository $productRepository,
        private ProductService $productService,
        private ProductInventoryService $productInventoryService,
        private ProductReportService $productReportService,
        private ProductSpecService $productSpecService
    ) {}

    public function index(Request $request)
    {
        $categories = Cache::remember('products:filter:categories', now()->addMinutes(30), fn () =>
            Category::query()->select('id', 'name')->orderBy('name')->get()
        );

        $suppliers = Cache::remember('products:filter:suppliers', now()->addMinutes(30), fn () =>
            Supplier::query()->select('id', 'nama_supplier')->orderBy('nama_supplier')->get()
        );

        $users = Cache::remember('products:filter:users', now()->addMinutes(30), fn () =>
            User::query()->select('id', 'name')->orderBy('name')->get()
        );

        $query = $this->productRepository->getForIndex($request);
        $summary = $this->productRepository->getIndexSummary($request);
        $products = $query->paginate(10)->withQueryString();

        return view('products.index', [
            'products' => $products,
            'summary' => $summary,
            'categories' => $categories,
            'suppliers' => $suppliers,
            'users' => $users,
            'productRows' => $this->productInventoryService->resolveProductRowsForIndex($products->getCollection()),
            'specTemplates' => $this->buildAllSpecTemplates($categories),
        ]);
    }

    public function store(ProductRequest $request)
    {
        $product = $this->productService->createProduct($request->validated(), $request->file('image'));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Produk berhasil ditambah.',
                'product_id' => $product->id,
                'redirect' => route('products.index'),
            ]);
        }

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil ditambah.');
    }

    public function update(ProductRequest $request, Product $product)
    {
        $product = $this->productService->updateProduct($product, $request->validated(), $request->file('image'));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Produk berhasil diupdate.',
                'product_id' => $product->id,
                'redirect' => route('products.index'),
            ]);
        }

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil diupdate.');
    }

    public function destroy(Product $product)
    {
        $this->productService->deleteProduct($product);

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil dihapus.');
    }

    public function reportProduct(Request $request)
    {
        $data = $this->productReportService->getReportData($request);

        return view('laporan-product.index', $data);
    }

    public function downloadProductReport(Request $request)
    {
        return $this->productReportService->downloadReport($request);
    }

    public function specOptions(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
        ]);

        $category = Category::query()->findOrFail($request->integer('category_id'));
        return response()->json($this->buildSpecTemplatePayload($category));
    }

    private function buildAllSpecTemplates(Collection $categories): array
    {
        $allSpecifications = $this->productSpecService->loadAllSpecifications();

        return $categories
            ->mapWithKeys(fn(Category $category) => [
                $category->id => $this->buildSpecTemplatePayload($category, $allSpecifications),
            ])
            ->all();
    }

    private function buildSpecTemplatePayload(?Category $category, ?Collection $allSpecifications = null): array
    {
        $definition = $this->productSpecService->specDefinitionForCategory($category?->name);

        if ($definition === null) {
            return [
                'category_key' => null,
                'fields' => [],
                'options' => [],
            ];
        }

        $allSpecifications ??= $this->productSpecService->loadAllSpecifications();

        $fields = collect($definition['fields'])
            ->map(function (array $field) {
                return [
                    'key' => $field['key'],
                    'label' => $field['label'],
                    'placeholder' => $field['placeholder'] ?? '',
                    'hint' => $field['hint'] ?? null,
                    'required' => in_array($field['key'], config('product_specs.strict_keys', []), true),
                ];
            })
            ->values()
            ->all();

        $options = [];

        foreach ($definition['fields'] as $field) {
            $lookupKeys = collect([$field['key']])
                ->merge($field['lookup_keys'] ?? [])
                ->merge(config('product_specs.compatibility_aliases.' . $field['key'], []))
                ->map(fn($key) => $this->productSpecService->normalizeIdentifier($key))
                ->filter()
                ->unique()
                ->values();

            $options[$field['key']] = $allSpecifications
                ->filter(function ($item) use ($lookupKeys) {
                    return $lookupKeys->contains($this->productSpecService->normalizeIdentifier($item->spec_key))
                        && $this->productSpecService->nullableTrim($item->spec_value) !== null;
                })
                ->map(fn($item) => $this->productSpecService->normalizeSpecValue(
                    $field['key'],
                    $item->spec_value
                ))
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all();
        }

        return [
            'category_key' => $definition['key'],
            'fields' => $fields,
            'options' => $options,
        ];
    }
}
