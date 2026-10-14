<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Brands\Model\Layer;

use Magento\Catalog\Model\Layer\ItemCollectionProviderInterface;
use Magento\Framework\Search\EngineResolverInterface;

/**
 * The search engine's product collection, under no category: the brand page
 * shows the brand's products wherever they are in the catalog. One
 * collection factory per engine, as Magento's own category provider.
 */
class ItemCollectionProvider implements ItemCollectionProviderInterface
{
    /**
     * @param array<string, object> $factories By search engine (etc/di.xml)
     */
    public function __construct(
        private readonly EngineResolverInterface $engineResolver,
        private readonly array $factories = []
    ) {
    }

    public function getCollection(\Magento\Catalog\Model\Category $category)
    {
        $engine = $this->engineResolver->getCurrentSearchEngine();
        if (!isset($this->factories[$engine])) {
            throw new \DomainException('Undefined factory ' . $engine);
        }

        return $this->factories[$engine]->create();
    }
}
