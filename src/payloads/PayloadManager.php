<?php

namespace lenz\contentfield\jsongen\payloads;

use lenz\contentfield\json\helpers\AbstractManager;
use lenz\contentfield\jsongen\exporter\ExportJob;
use lenz\contentfield\jsongen\exporter\ExportSourceInterface;
use lenz\contentfield\jsongen\helpers\PathHelper;
use lenz\contentfield\jsongen\Plugin;

/**
 * Class PayloadManager
 *
 * @method PayloadInterface|null getInstance(string $name)
 * @method PayloadInterface[] getInstances()
 */
class PayloadManager extends AbstractManager implements ExportSourceInterface
{
  /**
   * @inheritDoc
   */
  CONST SEGMENT = 'payloads';

  /**
   * @inheritDoc
   */
  const SUFFIX = 'payload';

  /**
   * @inheritDoc
   */
  const ITEM_CLASS = PayloadInterface::class;


  /**
   * @inheritDoc
   */
  public function getExportTasks(ExportJob $job): \Generator {
    $this->reset();
    $settings = Plugin::getInstance()->settings;
    $suffix = $settings->payloadSuffix;
    $basePath = $settings->payloadPath;
    $baseUrl = $job->getSite()->getBaseUrl(false);

    foreach ($this->getInstances() as $name => $payload) {
      $fileName = $name . $suffix;
      $fullPath = $job->toOutName(PathHelper::join($baseUrl, $basePath, $fileName));
      yield new PayloadExportTask($fullPath, $payload);
    }
  }
}
