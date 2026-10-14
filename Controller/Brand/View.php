<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Brands\Controller\Brand;

use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\View\Result\PageFactory;
use Zero\Brands\Model\Config;
use Zero\Brands\ViewModel\Brands;

/**
 * /brands/{url key}: the brand's products, with the category page's
 * filters, sorting and pages (layer "zero_brand", its brand a fixed filter).
 */
class View implements HttpGetActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly PageFactory $pageFactory,
        private readonly ForwardFactory $forwardFactory,
        private readonly Resolver $layerResolver,
        private readonly Brands $brands,
        private readonly Config $config
    ) {
    }

    public function execute()
    {
        $brand = $this->brands->isEnabled() ? $this->brands->current() : null;
        if (!$brand) {
            return $this->forwardFactory->create()->forward('noroute');
        }
        $this->request->setParam('zero_fixed', [$this->config->attributeCode() => (string) $brand['option_id']]);
        $this->layerResolver->create('zero_brand');

        $page = $this->pageFactory->create();
        $config = $page->getConfig();
        $config->getTitle()->set($brand['meta_title'] ?: $brand['name']);
        $config->setDescription($brand['meta_description'] ?: strip_tags((string) $brand['description']) ?: (string) __('%1: all products', $brand['name']));
        $config->addRemotePageAsset($this->brands->url($brand), 'canonical', ['attributes' => ['rel' => 'canonical']]);
        $title = $page->getLayout()->getBlock('page.main.title');
        if ($title) {
            $title->setPageTitle($brand['name']);
        }

        return $page;
    }
}
