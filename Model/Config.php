<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Brands\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    public function __construct(private readonly ScopeConfigInterface $scopeConfig)
    {
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag('zero_brands/general/enabled', ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function attributeCode(): string
    {
        return trim((string) $this->scopeConfig->getValue('zero_brands/general/attribute')) ?: 'manufacturer';
    }

    public function route(?int $storeId = null): string
    {
        $route = trim((string) $this->scopeConfig->getValue('zero_brands/general/route', ScopeInterface::SCOPE_STORE, $storeId), '/');

        return $route !== '' ? $route : 'brands';
    }
}
