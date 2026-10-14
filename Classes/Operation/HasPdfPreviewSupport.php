<?php

namespace WapplerSystems\ZabbixClient\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use WapplerSystems\ZabbixClient\Attribute\MonitoringOperation;
use WapplerSystems\ZabbixClient\Imaging\GraphicalFunctions;
use WapplerSystems\ZabbixClient\OperationResult;


/**
 *
 * Check that the image processing renders a PDF page into an image, the way TYPO3 builds
 * previews and thumbnails of PDF files.
 *
 * When it cannot (no Ghostscript, or an ImageMagick security policy that forbids reading PDF,
 * the default of several distributions), TYPO3 does not report an error: the processed file
 * silently stays the PDF itself, and the frontend shows an empty or broken preview.
 *
 */
#[MonitoringOperation('HasPdfPreviewSupport')]
class HasPdfPreviewSupport implements IOperation, SingletonInterface
{

    /**
     *
     * @param array $parameter None
     * @return OperationResult true if a PNG was rendered from the test PDF
     */
    public function execute(array $parameter = []): OperationResult
    {
        $inputFile = ExtensionManagementUtility::extPath('zabbix_client') . 'Resources/Private/TestInput/TestPreview.pdf';
        $imageProcessor = $this->initializeImageProcessor();
        $imageProcessor->imageMagickConvert_forceFileNameBody = StringUtility::getUniqueId('pdfpreview');
        $imResult = $imageProcessor->imageMagickConvert($inputFile, 'png', '200', '', '', '0', [], true);
        $outputFile = $imResult[3] ?? '';
        if ($outputFile === '' || $outputFile === $inputFile || !file_exists($outputFile)) {
            return new OperationResult(true, false);
        }
        $rendered = $this->isRenderedTestPage($outputFile);
        unlink($outputFile);
        return new OperationResult(true, $rendered);
    }

    /**
     * The test page is a red block with a black border: the output must be a PNG of the
     * requested width with a red centre, not an empty or placeholder image.
     */
    protected function isRenderedTestPage(string $file): bool
    {
        $size = @getimagesize($file);
        if ($size === false || $size[2] !== IMAGETYPE_PNG || $size[0] !== 200) {
            return false;
        }
        if (!function_exists('imagecreatefrompng')) {
            return true;
        }
        $image = @imagecreatefrompng($file);
        if ($image === false) {
            return false;
        }
        $rgb = imagecolorsforindex($image, imagecolorat($image, intdiv($size[0], 2), intdiv($size[1], 2)));
        return $rgb['red'] > 200 && $rgb['green'] < 80 && $rgb['blue'] < 80;
    }


    /**
     * Initialize image processor
     *
     * @return GraphicalFunctions Initialized image processor
     */
    protected function initializeImageProcessor(): GraphicalFunctions
    {
        $imageProcessor = GeneralUtility::makeInstance(GraphicalFunctions::class);
        $imageProcessor->dontCheckForExistingTempFile = true;
        $imageProcessor->filenamePrefix = 'zabbixClient-';
        $imageProcessor->alternativeOutputKey = 'zabbixClientPdfTest';
        return $imageProcessor;
    }

}
