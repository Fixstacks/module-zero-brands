<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Brands\Model;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\ResourceModel\Entity\Attribute as AttributeResource;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filter\TranslitUrl;

/**
 * The brands: the brand attribute's options (name = the option's label, the
 * store view's own if it has one) with their zero_brand row (address, logo,
 * texts). A brand without a row yet gets its address from its name.
 */
class Brands
{
    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly EavConfig $eavConfig,
        private readonly AttributeResource $attributeResource,
        private readonly Config $config,
        private readonly TranslitUrl $translitUrl
    ) {
    }

    public function attribute(): \Magento\Eav\Model\Entity\Attribute\AbstractAttribute
    {
        $attribute = $this->eavConfig->getAttribute(Product::ENTITY, $this->config->attributeCode());
        if (!$attribute->getId() || !in_array($attribute->getFrontendInput(), ['select', 'multiselect'], true)) {
            throw new LocalizedException(__('The brand attribute "%1" is not a dropdown product attribute.', $this->config->attributeCode()));
        }

        return $attribute;
    }

    /**
     * Every brand, by name; with how many products each has (all, enabled or
     * not), on one website's catalog when $websiteId is given.
     *
     * @return array<int, array> By option id
     */
    public function all(int $storeId = 0, ?int $websiteId = null): array
    {
        $connection = $this->resource->getConnection();
        $attribute = $this->attribute();
        $select = $connection->select()
            ->from(['o' => $this->resource->getTableName('eav_attribute_option')], ['option_id', 'sort_order'])
            ->joinLeft(
                ['admin' => $this->resource->getTableName('eav_attribute_option_value')],
                'admin.option_id = o.option_id AND admin.store_id = 0',
                ['admin_name' => 'value']
            )
            ->joinLeft(
                ['store' => $this->resource->getTableName('eav_attribute_option_value')],
                $connection->quoteInto('store.option_id = o.option_id AND store.store_id = ?', $storeId),
                ['store_name' => 'value']
            )
            ->joinLeft(
                ['b' => $this->resource->getTableName('zero_brand')],
                'b.option_id = o.option_id',
                ['url_key', 'logo', 'description', 'meta_title', 'meta_description', 'is_featured', 'brand_sort' => 'sort_order']
            )
            ->where('o.attribute_id = ?', (int) $attribute->getId());
        $counts = $this->productCounts((int) $attribute->getId(), (string) $attribute->getBackendTable(), $websiteId);
        $brands = [];
        foreach ($connection->fetchAll($select) as $row) {
            $id = (int) $row['option_id'];
            $name = (string) ($row['store_name'] ?: $row['admin_name']);
            $brands[$id] = [
                'option_id' => $id,
                'name' => $name,
                'admin_name' => (string) $row['admin_name'],
                'url_key' => (string) ($row['url_key'] ?: $this->urlKey($name, $id)),
                'logo' => (string) $row['logo'],
                'description' => (string) $row['description'],
                'meta_title' => (string) $row['meta_title'],
                'meta_description' => (string) $row['meta_description'],
                'is_featured' => (bool) $row['is_featured'],
                'sort_order' => (int) $row['brand_sort'],
                'products' => $counts[$id] ?? 0,
            ];
        }
        uasort($brands, static fn ($a, $b) => strnatcasecmp($a['name'], $b['name']));

        return $brands;
    }

    public function get(int $optionId, int $storeId = 0): ?array
    {
        return $this->all($storeId)[$optionId] ?? null;
    }

    public function byUrlKey(string $urlKey, int $storeId): ?array
    {
        foreach ($this->all($storeId) as $brand) {
            if ($brand['url_key'] === $urlKey) {
                return $brand;
            }
        }

        return null;
    }

    /**
     * Creates or changes a brand; its name is the option's admin label.
     *
     * @throws LocalizedException
     */
    public function save(?int $optionId, array $data): int
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new LocalizedException(__('A brand needs a name.'));
        }
        $connection = $this->resource->getConnection();
        $attribute = $this->attribute();
        if (!$optionId) {
            $connection->insert($this->resource->getTableName('eav_attribute_option'), [
                'attribute_id' => (int) $attribute->getId(),
                'sort_order' => 0,
            ]);
            $optionId = (int) $connection->lastInsertId($this->resource->getTableName('eav_attribute_option'));
        }
        $valueTable = $this->resource->getTableName('eav_attribute_option_value');
        $valueId = $connection->fetchOne($connection->select()
            ->from($valueTable, ['value_id'])->where('option_id = ?', $optionId)->where('store_id = 0'));
        if ($valueId) {
            $connection->update($valueTable, ['value' => $name], ['value_id = ?' => (int) $valueId]);
        } else {
            $connection->insert($valueTable, ['option_id' => $optionId, 'store_id' => 0, 'value' => $name]);
        }
        $urlKey = $this->urlKey((string) ($data['url_key'] ?? '') ?: $name, $optionId);
        $taken = $connection->fetchOne($connection->select()
            ->from($this->resource->getTableName('zero_brand'), ['option_id'])
            ->where('url_key = ?', $urlKey)->where('option_id <> ?', $optionId));
        if ($taken) {
            throw new LocalizedException(__('Another brand already has the address "%1".', $urlKey));
        }
        $row = [
            'option_id' => $optionId,
            'url_key' => $urlKey,
            'description' => (string) ($data['description'] ?? ''),
            'meta_title' => (string) ($data['meta_title'] ?? ''),
            'meta_description' => (string) ($data['meta_description'] ?? ''),
            'is_featured' => !empty($data['is_featured']) ? 1 : 0,
            'sort_order' => max(0, (int) ($data['sort_order'] ?? 0)),
        ];
        if (array_key_exists('logo', $data)) {
            $row['logo'] = $data['logo'] ?: null;
        }
        $connection->insertOnDuplicate($this->resource->getTableName('zero_brand'), $row, array_keys(array_diff_key($row, ['option_id' => 1])));
        $this->eavConfig->clear();

        return $optionId;
    }

    /**
     * Deletes the brand: its attribute option (products lose it) and its row.
     */
    public function delete(int $optionId): void
    {
        $this->resource->getConnection()->delete(
            $this->resource->getTableName('eav_attribute_option'),
            ['option_id = ?' => $optionId, 'attribute_id = ?' => (int) $this->attribute()->getId()]
        );
        $this->eavConfig->clear();
    }

    public function urlKey(string $text, int $optionId): string
    {
        return trim($this->translitUrl->filter($text), '-') ?: 'brand-' . $optionId;
    }

    /**
     * @return array<int, int>
     */
    private function productCounts(int $attributeId, string $table, ?int $websiteId = null): array
    {
        $connection = $this->resource->getConnection();
        if ($table === '' || !$connection->isTableExists($table)) {
            return [];
        }

        // Products shown in the catalog (not the hidden variants of configurable ones).
        $visibility = (int) $this->eavConfig->getAttribute(Product::ENTITY, 'visibility')->getId();

        $select = $connection->select()
            ->from(['b' => $table], ['value', 'COUNT(DISTINCT b.entity_id)'])
            ->join(
                ['v' => $this->resource->getTableName('catalog_product_entity_int')],
                'v.entity_id = b.entity_id AND v.store_id = 0 AND v.attribute_id = ' . $visibility,
                []
            )
            ->where('b.attribute_id = ?', $attributeId)
            ->where('b.store_id = 0')
            ->where('v.value IN (?)', [2, 4])
            ->group('b.value');
        if ($websiteId !== null) {
            // Only this website's products: each store lists its own brands.
            $select->join(
                ['w' => $this->resource->getTableName('catalog_product_website')],
                $connection->quoteInto('w.product_id = b.entity_id AND w.website_id = ?', $websiteId),
                []
            );
        }

        return array_map('intval', $connection->fetchPairs($select));
    }
}
