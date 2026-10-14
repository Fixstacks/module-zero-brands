<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Brands\ViewModel;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Zero\Brands\Model\Brands;
use Zero\Brands\Model\Config;

/**
 * Catalog > Brands: the list and the form.
 */
class Admin implements ArgumentInterface
{
    public function __construct(
        private readonly Brands $brands,
        private readonly Config $config,
        private readonly UrlInterface $url,
        private readonly RequestInterface $request,
        private readonly FormKey $formKey,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function all(): array
    {
        try {
            return $this->brands->all();
        } catch (\Throwable) {
            return [];
        }
    }

    public function error(): ?string
    {
        try {
            $this->brands->attribute();

            return null;
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    public function current(): ?array
    {
        $id = (int) $this->request->getParam('id');

        return $id ? $this->brands->get($id) : null;
    }

    public function url(string $route, array $params = []): string
    {
        return $this->url->getUrl($route, $params);
    }

    public function formKey(): string
    {
        return $this->formKey->getFormKey();
    }

    public function logoUrl(?string $logo): ?string
    {
        return $logo
            ? $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . ltrim($logo, '/')
            : null;
    }

    public function storefrontUrl(array $brand): string
    {
        $store = $this->storeManager->getDefaultStoreView();

        return $store ? $store->getBaseUrl() . $this->config->route((int) $store->getId()) . '/' . $brand['url_key'] : '';
    }
}
