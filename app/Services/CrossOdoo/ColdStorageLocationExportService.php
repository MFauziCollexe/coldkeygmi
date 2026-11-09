<?php

namespace App\Services\CrossOdoo;

use App\Services\OdooXmlRpcService;
use RuntimeException;

class ColdStorageLocationExportService
{
    private const STORAGE_CATEGORIES = [
        'Damage',
        'Dummy',
        'In-Transit',
        'Pickslot',
        'Quarantine',
        'Receipt',
        'Reserve',
        'Return',
        'Spill',
        'Storage',
    ];

    public function __construct(private OdooXmlRpcService $odoo) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchRows(): array
    {
        $categories = $this->odoo->searchRead(
            'stock.storage.category',
            ['id', 'name'],
            null,
            [['name', 'in', self::STORAGE_CATEGORIES]],
            ['lang' => 'en_US'],
        );
        $categoryIds = [];
        $categoryNames = [];

        foreach ($categories as $category) {
            $categoryId = (int) ($category['id'] ?? 0);
            $categoryName = (string) ($category['name'] ?? '');
            if ($categoryId > 0 && in_array($categoryName, self::STORAGE_CATEGORIES, true)) {
                $categoryIds[] = $categoryId;
                $categoryNames[$categoryId] = $categoryName;
            }
        }

        $missingCategories = array_values(array_diff(self::STORAGE_CATEGORIES, $categoryNames));
        if ($missingCategories !== []) {
            throw new RuntimeException('Storage Category tidak ditemukan di Odoo: '.implode(', ', $missingCategories));
        }

        $locations = $this->odoo->searchRead(
            'stock.location',
            ['id', 'name', 'complete_name', 'storage_category_id'],
            null,
            [['storage_category_id', 'in', $categoryIds]],
            ['lang' => 'en_US'],
        );
        $locationsById = [];
        foreach ($locations as $location) {
            $locationId = (int) ($location['id'] ?? 0);
            $category = $location['storage_category_id'] ?? false;
            if ($locationId > 0 && is_array($category) && isset($category[0])) {
                $locationsById[$locationId] = [
                    'name' => (string) ($location['complete_name'] ?? $location['name'] ?? ''),
                    'storageCategory' => $categoryNames[(int) $category[0]] ?? (string) ($category[1] ?? ''),
                ];
            }
        }

        if ($locationsById === []) {
            return [];
        }

        $quantFieldsMetadata = $this->odoo->executeKw(
            'stock.quant',
            'fields_get',
            [],
            ['attributes' => ['string', 'type']],
        );
        $optionalListFields = [];
        foreach (is_array($quantFieldsMetadata) ? $quantFieldsMetadata : [] as $fieldName => $metadata) {
            $label = strtolower((string) ($metadata['string'] ?? ''));
            if ($label === 'reference') {
                $optionalListFields['Reference'] = (string) $fieldName;
            } elseif ($label === 'source document') {
                $optionalListFields['Source Document'] = (string) $fieldName;
            }
        }

        $quantFields = [
            'location_id',
            'product_id',
            'package_id',
            'lot_id',
            'quantity',
            'reserved_quantity',
            'product_uom_id',
            'owner_id',
            'ns_plate_number',
            'ns_weight',
            ...array_values($optionalListFields),
        ];
        $quants = $this->odoo->searchRead(
            'stock.quant',
            $quantFields,
            null,
            [
                ['location_id', 'in', array_keys($locationsById)],
                ['quantity', '>', 0],
            ],
            ['lang' => 'en_US'],
        );

        $rows = [];
        foreach ($quants as $quant) {
            $location = $quant['location_id'] ?? false;
            $locationId = is_array($location) && isset($location[0]) ? (int) $location[0] : 0;
            $locationData = $locationsById[$locationId] ?? null;
            if ($locationData === null) {
                continue;
            }

            $rows[] = [
                'Location' => $locationData['name'],
                'Product' => $this->relationName($quant['product_id'] ?? false),
                'Package' => $this->relationName($quant['package_id'] ?? false),
                'Lot/Serial Number' => $this->relationName($quant['lot_id'] ?? false),
                'Owner' => $this->relationName($quant['owner_id'] ?? false),
                'Plate Number' => $this->displayValue($quant['ns_plate_number'] ?? false),
                'Reference' => $this->displayValue($quant[$optionalListFields['Reference'] ?? ''] ?? false),
                'Source Document' => $this->displayValue($quant[$optionalListFields['Source Document'] ?? ''] ?? false),
                'On Hand Quantity' => (float) ($quant['quantity'] ?? 0),
                'Reserved Quantity' => (float) ($quant['reserved_quantity'] ?? 0),
                'Available Quantity' => (float) ($quant['quantity'] ?? 0) - (float) ($quant['reserved_quantity'] ?? 0),
                'Qty Kilogram' => (float) ($quant['ns_weight'] ?? 0),
                'Unit' => $this->relationName($quant['product_uom_id'] ?? false),
            ];
        }

        return $rows;
    }

    private function relationName(mixed $value): string
    {
        return is_array($value) && isset($value[1]) ? (string) $value[1] : '';
    }

    private function displayValue(mixed $value): string
    {
        if (is_array($value)) {
            return $this->relationName($value);
        }

        return is_scalar($value) && $value !== false ? (string) $value : '';
    }
}
