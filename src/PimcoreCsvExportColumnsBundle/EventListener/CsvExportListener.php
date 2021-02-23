<?php

namespace Pipirima\PimcoreCsvExportColumnsBundle\EventListener;

use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Event\Model\ElementEventInterface;
use Pimcore\Model\Translation\Admin as TranslationAdmin;
use Pimcore\Model\WebsiteSetting;

/**
 * Class CsvExportListener
 * @package Pipirima\PimcoreCsvExportColumnsBundle\EventListener
 */
class CsvExportListener
{
    const CSV_PREFIX_WS_NAME = 'csv_export_columns_prefix';
    const DEFAULT_PREFIX = 'pimcore_';

    protected string $prefix;

    /**
     * CsvExportListener constructor.
     */
    public function __construct()
    {
        $websiteSetting = WebsiteSetting::getByName(self::CSV_PREFIX_WS_NAME);
        if (!$websiteSetting instanceof WebsiteSetting) {
            $websiteSetting = new WebsiteSetting();
            $websiteSetting->setType('text');
            $websiteSetting->setName(self::CSV_PREFIX_WS_NAME);
            $websiteSetting->setData(self::DEFAULT_PREFIX);
            $websiteSetting->save();
        }

        $this->prefix = strval($websiteSetting->getData());
    }

    /**
     * @param ElementEventInterface $event
     */
    public function onPostCsvItemExport(ElementEventInterface $event)
    {
        if (!$event instanceof DataObjectEvent) {
            return;
        }

        $obj = $event->getObject();
        /** @var string $requestedLanguage */
        $requestedLanguage = $event->getArgument('requestedLanguage');
        $origObjectData = $event->getArgument('objectData');
        $newObjectData = [];
        foreach ($origObjectData as $key => $value) {
            $key = $this->removeClassificationStorePrefixes($key);
            $key = $this->translate($key, $requestedLanguage);
            $key = $this->addPrefix($key);
            $newObjectData[$key] = $value;
        }

        $event->setArgument('objectData', $newObjectData);
    }

    /**
     * @param string $key
     * @return string
     */
    private function removeClassificationStorePrefixes(string $key): string
    {
        $pos = strrpos($key, '~');
        if (false === $pos) {
            return $key;
        }

        return substr($key, $pos + 1);
    }

    /**
     * @param string $key
     * @param string $lang
     * @return string
     */
    private function translate(string $key, string $lang): string
    {
        $translated = $key;
        try {
            $translation = TranslationAdmin::getByKey($key, false, true);
            if ($translation) {
                $translated = strval($translation->getTranslation($lang));
            }
        } catch (\Exception $e) {
            // ignore
        }

        return $translated;
    }

    /**
     * @param string $key
     * @return string
     */
    private function addPrefix(string $key): string
    {
        return $this->prefix . $key;
    }
}
