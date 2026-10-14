<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Brands\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Magento's own "manufacturer" attribute as the brand: a filter on category
 * and search pages, searchable, shown on product pages and in listings, in
 * every product attribute set (its General group).
 */
class ShowBrandAttribute implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavSetupFactory $eavSetupFactory
    ) {
    }

    public function apply(): void
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        if (!$eavSetup->getAttributeId(Product::ENTITY, 'manufacturer')) {
            return;
        }
        foreach ([
            'is_filterable' => 1,
            'is_filterable_in_search' => 1,
            'is_searchable' => 1,
            'is_visible_on_front' => 1,
            'used_in_product_listing' => 1,
            'is_comparable' => 1,
            'frontend_label' => 'Brand',
        ] as $field => $value) {
            $eavSetup->updateAttribute(Product::ENTITY, 'manufacturer', $field, $value);
        }
        foreach ($eavSetup->getAllAttributeSetIds(Product::ENTITY) as $setId) {
            $group = $eavSetup->getAttributeGroup(Product::ENTITY, $setId, 'General')
                ?: $eavSetup->getAttributeGroup(Product::ENTITY, $setId, 'Product Details');
            if ($group) {
                $eavSetup->addAttributeToGroup(Product::ENTITY, $setId, (int) $group['attribute_group_id'], 'manufacturer', 30);
            }
        }
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
