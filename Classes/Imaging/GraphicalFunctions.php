<?php

namespace WapplerSystems\ZabbixClient\Imaging;

use TYPO3\CMS\Core\Imaging\ImageMagickFile;
use TYPO3\CMS\Core\Utility\CommandUtility;

class GraphicalFunctions extends \TYPO3\CMS\Core\Imaging\GraphicalFunctions
{


    public function imageMagickMetadata($imagefile): ?array
    {
        if (!$this->processorEnabled) {
            return null;
        }

        $result = $this->executeFullIdentifyCommandForImageFile($imagefile);
        if ($result) {
            return $result;
        }
        return null;
    }


    protected function executeFullIdentifyCommandForImageFile(string $imageFile): ?array
    {
        $frame = $this->addFrameSelection ? 0 : null;
        $cmd = CommandUtility::imageMagickCommand(
            'identify',
            '-verbose ' . ImageMagickFile::fromFilePath($imageFile, $frame)
        );
        $returnVal = [];
        CommandUtility::exec($cmd, $returnVal);
        $this->IM_commands[] = ['identify', $cmd, implode(',', $returnVal)];
        return $returnVal;
    }

}
