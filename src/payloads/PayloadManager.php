<?php

namespace lenz\contentfield\jsongen\payloads;

use lenz\contentfield\json\helpers\AbstractManager;
use lenz\contentfield\jsongen\exporter\ExportJob;
use lenz\contentfield\jsongen\exporter\ExportSourceInterface;

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
    $baseUrl = $job->getSite()->getBaseUrl(false);

    foreach ($this->getInstances() as $name => $payload) {
      $fileName = $job->toOutName($baseUrl . '/api/payloads/' . $name . '.json');
      yield new PayloadExportTask($fileName, $payload);
    }
  }
}
