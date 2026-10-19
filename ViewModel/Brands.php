<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Brands\ViewModel;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Zero\Brands\Model\Brands as BrandList;
use Zero\Brands\Model\Config;

/**
 * Brands for the storefront templates: the A–Z, the current brand's page,
 * a product's brand.
 */
class Brands implements ArgumentInterface
{
    /** @var array<int, array>|null */
    private ?array $all = null;

    public function __construct(
        private readonly BrandList $brands,
        private readonly Config $config,
        private readonly StoreManagerInterface $storeManager,
        private readonly UrlInterface $url,
        private readonly RequestInterface $request,
        private readonly Registry $registry
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->config->isEnabled($this->storeId());
    }

    /**
     * Brands with products, featured first, then by name; grouped by first letter.
     *
     * @return array{featured: array[], letters: array<string, array[]>}
     */
    public function index(): array
    {
        $featured = [];
        $letters = [];
        foreach ($this->all() as $brand) {
            if (!$brand['products']) {
                continue;
            }
            if ($brand['is_featured']) {
                $featured[] = $brand;
            }
            $letter = mb_strtoupper(mb_substr($brand['name'], 0, 1));
            $letters[preg_match('/\p{L}/u', $letter) ? $letter : '#'][] = $brand;
        }
        usort($featured, static fn ($a, $b) => [$a['sort_order'], $a['name']] <=> [$b['sort_order'], $b['name']]);
        ksort($letters, SORT_LOCALE_STRING);

        return ['featured' => $featured, 'letters' => $letters];
    }

    /**
     * The brand page's brand.
     */
    public function current(): ?array
    {
        return $this->all()[(int) $this->request->getParam('brand_id')] ?? null;
    }

    /**
     * The product page's brand, when it has a page.
     */
    public function ofProduct(?Product $product = null): ?array
    {
        $product ??= $this->registry->registry('current_product');
        if (!$product) {
            return null;
        }
        $value = $product->getData($this->config->attributeCode());
        $id = (int) (is_string($value) ? explode(',', $value)[0] : $value);

        return $id ? ($this->all()[$id] ?? null) : null;
    }

    public function url(array $brand): string
    {
        return $this->url->getUrl('', ['_direct' => $this->config->route($this->storeId()) . '/' . $brand['url_key']]);
    }

    public function indexUrl(): string
    {
        return $this->url->getUrl('', ['_direct' => $this->config->route($this->storeId())]);
    }

    public function logoUrl(array $brand): ?string
    {
        return $brand['logo'] !== ''
            ? $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . ltrim($brand['logo'], '/')
            : null;
    }

    private function all(): array
    {
        return $this->all ??= $this->brands->all($this->storeId(), (int) $this->storeManager->getStore()->getWebsiteId());
    }

    private function storeId(): int
    {
        return (int) $this->storeManager->getStore()->getId();
    }
}
