<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Brands\Controller\Adminhtml\Brand;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * A brand's form (id: an existing brand's option id; none: a new brand).
 */
class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Zero_Brands::brands';

    public function execute()
    {
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->setActiveMenu('Zero_Brands::brands');
        $page->getConfig()->getTitle()->prepend($this->getRequest()->getParam('id') ? __('Edit Brand') : __('New Brand'));

        return $page;
    }
}
