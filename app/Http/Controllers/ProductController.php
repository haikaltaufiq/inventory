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
        $categories = Cache::remember(
            'products:filter:categories',
            now()->addMinutes(30),
            fn() =>
            Category::query()->select('id', 'name')->orderBy('name')->get()
        );

        $suppliers = Cache::remember(
            'products:filter:suppliers',
            now()->addMinutes(30),
            fn() =>
            Supplier::query()->select('id', 'nama_supplier')->orderBy('nama_supplier')->get()
        );

        $users = Cache::remember(
            'products:filter:users',
            now()->addMinutes(30),
            fn() =>
            User::query()->select('id', 'name')->orderBy('name')->get()
        );

        $summary = $this->productRepository->getIndexSummary($request);

        $products = $this->productRepository->getForIndex($request)
            ->paginate(15)
            ->withQueryString();

        // Kirim row yang sudah dinormalisasi agar pivot supplier terbaca tabel.
        $productRows = $this->productInventoryService->resolveProductRowsForIndex($products->getCollection());

        return view('products.index', [
            'categories' => $categories,
            'suppliers' => $suppliers,
            'users' => $users,
            'summary' => $summary,
            'productRows' => $productRows, // Pass variabel ke view
            'products' => $products,
            'specTemplates' => $this->buildSpecTemplateDefinitions($categories),
        ]);
    }

    /** Lightweight background feed for the inventory table. */
    public function list(Request $request)
    {
        $products = $this->productRepository->getForIndex($request)
            ->paginate(15)
            ->withQueryString();

        return response()->json([
            'data' => $this->productInventoryService->resolveProductRowsForIndex($products->getCollection()),
            'summary' => $this->productRepository->getIndexSummary($request),
            'meta' => [
                'current_page' => $products->currentPage(),
                'has_more' => $products->hasMorePages(),
            ],
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
        $optionKeys = collect(config('product_specs.categories', []))
            ->flatMap(fn(array $definition) => collect($definition['fields'] ?? [])
                ->flatMap(fn(array $field) => collect([$field['key']])
                    ->merge($field['lookup_keys'] ?? [])
                    ->merge(config('product_specs.compatibility_aliases.' . $field['key'], []))))
            ->all();
        $allSpecifications = $this->productSpecService->loadAllSpecifications($optionKeys);

        return $categories
            ->mapWithKeys(fn(Category $category) => [
                $category->id => $this->buildSpecTemplatePayload($category, $allSpecifications),
            ])
            ->all();
    }

    private function buildSpecTemplateDefinitions(Collection $categories): array
    {
        return $categories
            ->mapWithKeys(fn(Category $category) => [
                $category->id => $this->buildSpecTemplatePayload($category, collect()),
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
