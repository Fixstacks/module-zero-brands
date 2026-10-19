<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Brands\Block\Widget;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Widget\Block\BlockInterface;
use Zero\Brands\Model\Config;
use Zero\Brands\ViewModel\Brands;

/**
 * "Zero Brands: carousel": the logos of the brands this store sells (those
 * with products on its website), featured ones first in their order, then by
 * name; each links to its brand page. A widget (Content > Widgets, or inside a
 * CMS page or block) and a Zero CMS component (etc/zero_cms/components.json).
 */
class Carousel extends Template implements BlockInterface, IdentityInterface
{
    public const CACHE_TAG = 'zero_brands';

    /** @var string */
    protected $_template = 'Zero_Brands::widget/carousel.phtml';

    public function __construct(
        Context $context,
        private readonly Brands $brands,
        private readonly Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return array<int, array{name: string, url: string, logo: ?string}>
     */
    public function getBrands(): array
    {
        if (!$this->config->isEnabled()) {
            return [];
        }
        $index = $this->brands->index();
        $featured = $index['featured'];
        $rest = array_merge(...array_values($index['letters']) ?: [[]]);
        $seen = [];
        $list = [];
        foreach ([...$featured, ...$rest] as $brand) {
            if (isset($seen[$brand['option_id']])) {
                continue;
            }
            $seen[$brand['option_id']] = true;
            $list[] = ['name' => $brand['name'], 'url' => $this->brands->url($brand), 'logo' => $this->brands->logoUrl($brand)];
        }

        return array_slice($list, 0, max(1, (int) ($this->getData('limit') ?: 12)));
    }

    public function getIndexUrl(): string
    {
        return $this->brands->indexUrl();
    }

    public function getHeading(): string
    {
        return (string) $this->getData('heading');
    }

    public function getEyebrow(): string
    {
        return (string) $this->getData('eyebrow');
    }

    /**
     * @return string[]
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG];
    }
}
