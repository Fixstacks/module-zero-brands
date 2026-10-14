<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Brands\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;

/**
 * A brand's logo upload into pub/media/zero/brand: JPEG, PNG, WebP or SVG.
 */
class Logo
{
    private const DIR = 'zero/brand';

    public function __construct(
        private readonly UploaderFactory $uploaderFactory,
        private readonly Filesystem $filesystem
    ) {
    }

    /**
     * @return string The logo's path under pub/media
     * @throws LocalizedException
     */
    public function save(array $upload): string
    {
        if (($upload['error'] ?? 0) !== UPLOAD_ERR_OK) {
            throw new LocalizedException(__('The logo didn\'t upload (too large?).'));
        }
        try {
            $uploader = $this->uploaderFactory->create(['fileId' => $upload]);
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'webp', 'svg']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);
            $result = $uploader->save($this->filesystem->getDirectoryWrite(DirectoryList::MEDIA)->getAbsolutePath(self::DIR));
        } catch (\Throwable $e) {
            throw new LocalizedException(__('The logo couldn\'t be saved: %1', $e->getMessage()));
        }

        return self::DIR . '/' . $result['file'];
    }
}
