<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 *
 * Zero_Brands: shop by brand. A brand page for each brand (its products with
 * the filters), an A–Z of all brands, the brand on product pages; brands
 * edited in Catalog > Brands. See README.md.
 */
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Zero_Brands', __DIR__);
