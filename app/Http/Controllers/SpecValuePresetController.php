<?php

namespace App\Http\Controllers;

use App\Models\SpecValuePreset;
use App\Models\ProductSpecification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SpecValuePresetController extends Controller
{
    /**
     * Cache key yang sama dengan ProductController
     * supaya saat preset diubah, dropdown di grid ikut refresh.
     */
    private const SPEC_OPTIONS_CACHE_KEY = 'products.spec_options';

    public function index()
    {
        $config   = config('product_specs.categories', []);
        $allFields = $this->collectAllFields($config);

        // Ambil semua preset tersimpan
        $presets = SpecValuePreset::query()
            ->orderBy('spec_key')
            ->orderBy('spec_value')
            ->get()
            ->groupBy('spec_key');

        // Ambil nilai yang sudah ada di product_specifications
        // (nilai dari produk yang sudah diinput sebelumnya)
        $existingFromProducts = ProductSpecification::query()
            ->select('spec_key', 'spec_value')
            ->whereNotNull('spec_value')
            ->where('spec_value', '!=', '')
            ->get()
            ->groupBy('spec_key')
            ->map(fn($rows) => $rows->pluck('spec_value')->unique()->sort()->values());

        // Gabungkan info field dengan preset + nilai existing
        $sections = collect($allFields)->map(function (array $field) use ($presets, $existingFromProducts) {
            $key         = $field['key'];
            $presetValues = $presets->get($key, collect())->pluck('spec_value')->all();

            // Nilai dari produk yang belum jadi preset
            $productValues = $existingFromProducts->get($key, collect())
                ->filter(fn($v) => !in_array($v, $presetValues, true))
                ->values()
                ->all();

            return [
                'key'            => $key,
                'label'          => $field['label'],
                'category'       => $field['category'],
                'hint'           => $field['hint'] ?? null,
                'required'       => in_array($key, config('product_specs.strict_keys', []), true),
                'presets'        => $presets->get($key, collect())->map(fn($p) => [
                    'id'    => $p->id,
                    'value' => $p->spec_value,
                ])->values()->all(),
                'product_values' => $productValues,
            ];
        })->values()->all();

        return view('spec.index', [
            'sections' => $sections,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'spec_key'   => 'required|string|max:100',
            'spec_value' => 'required|string|max:255',
        ]);

        $key   = $this->normalizeIdentifier($request->string('spec_key')->toString());
        $value = trim($request->string('spec_value')->toString());

        if ($key === '' || $value === '') {
            return back()->with('error', 'Key atau value tidak boleh kosong.');
        }

        SpecValuePreset::firstOrCreate(
            ['spec_key' => $key, 'spec_value' => Str::upper($value)],
        );

        $this->forgetCaches();

        return back()->with('success', "Nilai {$value} berhasil ditambahkan ke {$key}.");
    }

    /**
     * Import nilai-nilai dari product_specifications ke preset
     * supaya semua data lama langsung terdaftar.
     */
    public function importFromProducts(Request $request)
    {
        $request->validate([
            'spec_key' => 'required|string|max:100',
        ]);

        $key = $this->normalizeIdentifier($request->string('spec_key')->toString());

        $values = ProductSpecification::query()
            ->where('spec_key', $key)
            ->whereNotNull('spec_value')
            ->where('spec_value', '!=', '')
            ->pluck('spec_value')
            ->unique()
            ->all();

        $imported = 0;

        foreach ($values as $value) {
            $result = SpecValuePreset::firstOrCreate([
                'spec_key'   => $key,
                'spec_value' => Str::upper(trim($value)),
            ]);

            if ($result->wasRecentlyCreated) {
                $imported++;
            }
        }

        $this->forgetCaches();

        return back()->with('success', "{$imported} nilai berhasil diimpor ke preset {$key}.");
    }

    public function destroy(SpecValuePreset $specValuePreset)
    {
        $specValuePreset->delete();
        $this->forgetCaches();

        return back()->with('success', 'Preset nilai berhasil dihapus.');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function collectAllFields(array $config): array
    {
        $fields = [];
        $seen   = [];

        foreach ($config as $categoryKey => $definition) {
            $categoryLabel = $definition['labels'][0] ?? ucfirst($categoryKey);

            foreach ($definition['fields'] as $field) {
                $fieldKey = $field['key'];

                if (isset($seen[$fieldKey])) {
                    // Kalau key sama muncul di beberapa kategori (mis. form_factor),
                    // tambahkan nama kategori saja ke daftar yang sudah ada
                    $seen[$fieldKey]['category'] .= ', ' . $categoryLabel;
                    continue;
                }

                $seen[$fieldKey] = true;

                $fields[]         = [
                    'key'      => $fieldKey,
                    'label'    => $field['label'],
                    'hint'     => $field['hint'] ?? null,
                    'category' => $categoryLabel,
                ];

                $seen[$fieldKey] = &$fields[array_key_last($fields)];
            }
        }

        return $fields;
    }

    private function normalizeIdentifier(string $value): string
    {
        return (string) Str::of($value)
            ->trim()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_');
    }

    private function forgetCaches(): void
    {
        Cache::forget(self::SPEC_OPTIONS_CACHE_KEY);
    }
}
