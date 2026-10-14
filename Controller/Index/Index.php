<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Brands\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\View\Result\PageFactory;
use Zero\Brands\ViewModel\Brands;

/**
 * /brands: every brand with products, A–Z, the featured ones first.
 */
class Index implements HttpGetActionInterface
{
    public function __construct(
        private readonly PageFactory $pageFactory,
        private readonly ForwardFactory $forwardFactory,
        private readonly Brands $brands
    ) {
    }

    public function execute()
    {
        if (!$this->brands->isEnabled()) {
            return $this->forwardFactory->create()->forward('noroute');
        }
        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->set(__('Brands'));
        $page->getConfig()->addRemotePageAsset($this->brands->indexUrl(), 'canonical', ['attributes' => ['rel' => 'canonical']]);

        return $page;
    }
}
