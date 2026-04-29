<?php

namespace App\Http\Controllers;

use App\Models\SpecValuePreset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SpecValuePresetController extends Controller
{
    private const SPEC_OPTIONS_CACHE_KEY = 'products.spec_options';

    public function index()
    {
        $config    = config('product_specs.categories', []);
        $allFields = $this->collectAllFields($config);

        // Semua preset yang tersimpan
        $presets = SpecValuePreset::query()
            ->orderBy('spec_key')
            ->orderBy('spec_value')
            ->get()
            ->groupBy('spec_key');

        // Di arsitektur baru, product_values (nilai di produk yang belum jadi preset)
        // tidak relevan lagi — semua spec produk ADALAH preset via pivot.
        // Kita cukup tampilkan jumlah produk yang pakai tiap preset.
        $sections = collect($allFields)->map(function (array $field) use ($presets) {
            $key = $field['key'];

            return [
                'key'      => $key,
                'label'    => $field['label'],
                'category' => $field['category'],
                'hint'     => $field['hint'] ?? null,
                'required' => in_array($key, config('product_specs.strict_keys', []), true),
                'presets'  => $presets->get($key, collect())->map(fn($p) => [
                    'id'            => $p->id,
                    'value'         => $p->spec_value,
                    'products_count'=> $p->products()->count(), // berapa produk pakai preset ini
                ])->values()->all(),
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

    public function destroy(SpecValuePreset $specValuePreset)
    {
        // Pivot product_spec_value terhapus otomatis karena onDelete('cascade')
        $specValuePreset->delete();
        $this->forgetCaches();

        return back()->with('success', 'Preset nilai berhasil dihapus.');
    }

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
                    $seen[$fieldKey]['category'] .= ', ' . $categoryLabel;
                    continue;
                }

                $seen[$fieldKey]  = true;
                $fields[]         = [
                    'key'      => $fieldKey,
                    'label'    => $field['label'],
                    'hint'     => $field['hint'] ?? null,
                    'category' => $categoryLabel,
                ];
                $seen[$fieldKey]  = &$fields[array_key_last($fields)];
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
