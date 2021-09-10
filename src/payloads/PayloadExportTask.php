<?php

namespace lenz\contentfield\jsongen\payloads;

use lenz\contentfield\json\scope\State;
use lenz\contentfield\jsongen\exporter\ExportJob;
use lenz\contentfield\jsongen\exporter\ExportTask;

/**
 * Class PayloadExportTask
 */
class PayloadExportTask extends ExportTask
{
  /**
   * @var PayloadInterface
   */
  private $_payload;


  /**
   * @param string $fileName
   * @param PayloadInterface $payload
   */
  public function __construct(string $fileName, PayloadInterface $payload) {
    parent::__construct($fileName);
    $this->_payload = $payload;
  }


  // Protected methods
  // -----------------

  /**
   * @inheritDoc
   */
  protected function export(ExportJob $job, State $state) {
    return $this->_payload;
  }
}
