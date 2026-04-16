<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductSpecification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ProductSpecService
{
    private const SPEC_OPTIONS_CACHE_KEY = 'products.spec_options';

    public function buildSpecificationPayload(array $validated, string $categoryName): array
    {
        $definition = $this->specDefinitionForCategory($categoryName);
        $baseSpecs = collect();

        if ($definition !== null) {
            foreach ($definition['fields'] as $field) {
                $value = $this->normalizeSpecValue(
                    $field['key'],
                    data_get($validated['specs'] ?? [], $field['key'] . '.value')
                );

                if ($value === null) {
                    continue;
                }

                $baseSpecs->put($field['key'], $value);
            }
        }

        $extraSpecs = collect($validated['extra_specs'] ?? [])
            ->mapWithKeys(function (array $spec) {
                $key = $this->normalizeCustomSpecKey($spec['key'] ?? null);
                $value = $this->normalizeSpecValue($key ?? 'custom', $spec['value'] ?? null);

                if ($key === null || $value === null) {
                    return [];
                }

                return [$key => $value];
            });

        $technicalSpecs = $baseSpecs
            ->merge($extraSpecs)
            ->all();

        $compatibilityAliases = collect(config('product_specs.compatibility_aliases', []))
            ->flatMap(function (array $aliases, string $sourceKey) use ($baseSpecs) {
                if (!$baseSpecs->has($sourceKey)) {
                    return [];
                }

                return collect($aliases)->mapWithKeys(function (string $alias) use ($baseSpecs, $sourceKey) {
                    return [$alias => $baseSpecs->get($sourceKey)];
                });
            });

        $specifications = collect($technicalSpecs)
            ->merge($compatibilityAliases)
            ->map(function ($value, $key) {
                return [
                    'spec_key' => $key,
                    'spec_value' => $value,
                ];
            })
            ->values()
            ->all();

        return [
            'technical_specs' => $technicalSpecs,
            'specifications' => $specifications,
        ];
    }

    public function extractSpecFormData(Product $product): array
    {
        $definition = $this->specDefinitionForCategory($product->category?->name);
        $rawSpecs = collect($product->technical_specs ?? []);

        if ($rawSpecs->isEmpty()) {
            $rawSpecs = $product->specifications
                ->mapWithKeys(function (ProductSpecification $specification) {
                    return [$specification->spec_key => $specification->spec_value];
                });
        }

        $formSpecs = [];
        $additionalSpecs = [];

        foreach ($rawSpecs as $key => $value) {
            $normalizedValue = $this->nullableTrim($value);

            if ($normalizedValue === null) {
                continue;
            }

            $canonicalKey = $this->canonicalSpecKey($key, $definition);

            if ($canonicalKey !== null) {
                $formSpecs[$canonicalKey] = $normalizedValue;
                continue;
            }

            if ($this->isCompatibilityAliasKey($key)) {
                continue;
            }

            $additionalSpecs[] = [
                'key' => $this->displaySpecKey($key),
                'value' => $normalizedValue,
            ];
        }

        return [$formSpecs, $additionalSpecs];
    }

    public function specDefinitionForCategory(?string $categoryName): ?array
    {
        if ($categoryName === null) {
            return null;
        }

        $normalizedCategory = $this->normalizeIdentifier($categoryName);

        foreach (config('product_specs.categories', []) as $configKey => $definition) {
            $labels = collect($definition['labels'] ?? [])
                ->prepend($configKey)
                ->map(fn($label) => $this->normalizeIdentifier($label));

            if ($labels->contains($normalizedCategory)) {
                return [
                    'key' => $configKey,
                    'fields' => $definition['fields'] ?? [],
                ];
            }
        }

        return null;
    }

    private function canonicalSpecKey(string $key, ?array $definition = null): ?string
    {
        $normalizedKey = $this->normalizeIdentifier($key);
        $definitions = $definition !== null
            ? [$definition]
            : array_map(function ($configKey, $configDefinition) {
                return [
                    'key' => $configKey,
                    'fields' => $configDefinition['fields'] ?? [],
                ];
            }, array_keys(config('product_specs.categories', [])), config('product_specs.categories', []));

        foreach ($definitions as $currentDefinition) {
            foreach ($currentDefinition['fields'] ?? [] as $field) {
                $lookupKeys = collect([$field['key']])
                    ->merge($field['lookup_keys'] ?? [])
                    ->map(fn($item) => $this->normalizeIdentifier($item))
                    ->filter()
                    ->unique();

                if ($lookupKeys->contains($normalizedKey)) {
                    return $field['key'];
                }
            }
        }

        return null;
    }

    private function isCompatibilityAliasKey(string $key): bool
    {
        $normalizedKey = $this->normalizeIdentifier($key);

        return collect(config('product_specs.compatibility_aliases', []))
            ->flatten()
            ->map(fn($alias) => $this->normalizeIdentifier($alias))
            ->contains($normalizedKey);
    }

    public function normalizeSpecValue(string $key, mixed $value): ?string
    {
        $trimmed = $this->nullableTrim($value);

        if ($trimmed === null) {
            return null;
        }

        $matchedExisting = $this->matchExistingSpecValue($key, $trimmed);

        if ($matchedExisting !== null) {
            return $matchedExisting;
        }

        return Str::upper($trimmed);
    }

    private function normalizeCustomSpecKey(?string $key): ?string
    {
        $trimmed = $this->nullableTrim($key);

        if ($trimmed === null) {
            return null;
        }

        $normalized = $this->normalizeIdentifier($trimmed);

        return $normalized !== '' ? $normalized : null;
    }

    public function normalizeIdentifier(string $value): string
    {
        return (string) Str::of($value)
            ->trim()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_');
    }

    private function normalizeComparableValue(string $value): string
    {
        return (string) Str::of($value)
            ->trim()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->toString();
    }

    private function matchExistingSpecValue(string $key, string $value): ?string
    {
        static $allSpecifications;

        $allSpecifications ??= $this->loadAllSpecifications();

        $normalizedLookupKeys = $this->lookupKeysForSpecField($key)
            ->map(fn($item) => $this->normalizeIdentifier((string) $item))
            ->filter()
            ->unique()
            ->values();

        $comparableValue = $this->normalizeComparableValue($value);

        if ($comparableValue === '') {
            return null;
        }

        return $allSpecifications
            ->first(function ($item) use ($normalizedLookupKeys, $comparableValue) {
                return $normalizedLookupKeys->contains($this->normalizeIdentifier($item->spec_key))
                    && $this->normalizeComparableValue((string) $item->spec_value) === $comparableValue;
            })
            ?->spec_value;
    }

    private function lookupKeysForSpecField(string $key): Collection
    {
        $lookupKeys = collect([$key])
            ->merge(config('product_specs.compatibility_aliases.' . $key, []));

        foreach (config('product_specs.categories', []) as $definition) {
            foreach (($definition['fields'] ?? []) as $field) {
                if (($field['key'] ?? null) !== $key) {
                    continue;
                }

                $lookupKeys = $lookupKeys
                    ->merge($field['lookup_keys'] ?? [])
                    ->merge(config('product_specs.compatibility_aliases.' . $field['key'], []));
            }
        }

        return $lookupKeys;
    }

    private function displaySpecKey(string $key): string
    {
        return Str::of($key)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    public function nullableTrim(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim(preg_replace('/\s+/', ' ', (string) $value));

        return $trimmed === '' ? null : $trimmed;
    }

    public function loadAllSpecifications(): Collection
    {
        return Cache::remember(
            self::SPEC_OPTIONS_CACHE_KEY,
            now()->addMinutes(10),
            function () {
                $fromProducts = ProductSpecification::query()
                    ->get(['id', 'spec_key', 'spec_value']);

                $fromPresets = \App\Models\SpecValuePreset::query()
                    ->get(['id', 'spec_key', 'spec_value'])
                    ->map(fn($preset) => (object) [
                        'id'         => 'preset_' . $preset->id,
                        'spec_key'   => $preset->spec_key,
                        'spec_value' => $preset->spec_value,
                    ]);

                return $fromProducts->concat($fromPresets);
            }
        );
    }
}
