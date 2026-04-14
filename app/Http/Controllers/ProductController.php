<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\ProductSpecification;
use App\Models\User;
use App\Repositories\ProductRepository;
use App\Services\ProductGridService;
use App\Services\ProductReportService;
use App\Services\ProductService;
use App\Services\ProductSpecService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ProductController extends Controller
{
    public function __construct(
        private ProductRepository $productRepository,
        private ProductService $productService,
        private ProductGridService $productGridService,
        private ProductReportService $productReportService,
        private ProductSpecService $productSpecService
    ) {}

    public function index(Request $request)
    {
        $categories = Category::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
            
        $suppliers = Supplier::query()
            ->select('id', 'nama_supplier')
            ->orderBy('nama_supplier')
            ->get();
            
        $users = User::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $query = $this->productRepository->getForIndex($request);
        $summary = $this->productRepository->getIndexSummary($request);
        $products = $query->paginate(10)->withQueryString();

        return view('products.index', [
            'products' => $products,
            'summary' => $summary,
            'categories' => $categories,
            'suppliers' => $suppliers,
            'users' => $users,
            'gridRows' => $this->productGridService->resolveGridRowsForIndex($products->getCollection(), $categories),
            'specTemplates' => $this->buildAllSpecTemplates($categories),
        ]);
    }

    public function create()
    {
        return redirect()->route('products.index');
    }

    public function store(ProductRequest $request)
    {
        $this->productService->createProduct($request->validated(), $request->file('image'));

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil ditambah.');
    }

    public function edit(Product $product)
    {
        return redirect()->route('products.index');
    }

    public function update(ProductRequest $request, Product $product)
    {
        $this->productService->updateProduct($product, $request->validated(), $request->file('image'));

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil diupdate.');
    }

    public function gridSave(Request $request)
    {
        $result = $this->productGridService->processGridSave($request);

        return $this->redirectAfterGridSave($request, $result['message']);
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

    public function specOptions(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
        ]);

        $category = Category::query()->findOrFail($request->integer('category_id'));
        return response()->json($this->buildSpecTemplatePayload($category));
    }

    private function redirectAfterGridSave(Request $request, string $message)
    {
        $redirectTo = $request->string('redirect_to')->toString();

        if (
            $redirectTo !== ''
            && (str_starts_with($redirectTo, url('/')) || str_starts_with($redirectTo, '/'))
        ) {
            return redirect()->to($redirectTo)->with('success', $message);
        }

        return redirect()->route('products.index')->with('success', $message);
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
                ->filter(function (ProductSpecification $specification) use ($lookupKeys) {
                    return $lookupKeys->contains($this->productSpecService->normalizeIdentifier($specification->spec_key))
                        && $this->productSpecService->nullableTrim($specification->spec_value) !== null;
                })
                ->map(fn(ProductSpecification $specification) => $this->productSpecService->normalizeSpecValue(
                    $field['key'],
                    $specification->spec_value
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
