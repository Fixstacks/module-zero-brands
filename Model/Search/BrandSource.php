<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Brands\Model\Search;

use Magento\Framework\UrlInterface;
use Zero\Brands\Model\Brands;
use Zero\Brands\Model\Config;
use Zero\Search\Model\SourceInterface;

/**
 * For Zero_Search: brands with products whose name has every word of the
 * query, each linking to its page.
 */
class BrandSource implements SourceInterface
{
    public function __construct(
        private readonly Brands $brands,
        private readonly Config $config,
        private readonly UrlInterface $url
    ) {
    }

    public function title(): string
    {
        return (string) __('Brands');
    }

    public function find(string $query, int $storeId, int $limit): array
    {
        if (!$this->config->isEnabled($storeId)) {
            return [];
        }
        $words = preg_split('/\s+/u', mb_strtolower(trim($query))) ?: [];
        $items = [];
        foreach ($this->brands->all($storeId) as $brand) {
            if (!$brand['products']) {
                continue;
            }
            $name = mb_strtolower($brand['name']);
            foreach ($words as $word) {
                if ($word !== '' && !str_contains($name, $word)) {
                    continue 2;
                }
            }
            $items[] = [
                'name' => $brand['name'],
                'url' => $this->url->getUrl('', ['_direct' => $this->config->route($storeId) . '/' . $brand['url_key']]),
            ];
            if (count($items) >= $limit) {
                break;
            }
        }

        return $items;
    }
}
