<?php

namespace lenz\contentfield\jsongen\generators;

use lenz\contentfield\json\scope\State;
use lenz\contentfield\jsongen\exporter\ExportJob;
use lenz\contentfield\jsongen\exporter\ExportTask;

/**
 * Interface GeneratorExportTask
 */
class GeneratorExportTask extends ExportTask
{
  /**
   * @var callable
   */
  private $_callback;


  /**
   * @param string $fileName
   * @param callable $callback
   */
  public function __construct(string $fileName, callable $callback) {
    parent::__construct($fileName);
    $this->_callback = $callback;
  }


  // Protected methods
  // -----------------

  /**
   * @inheritDoc
   */
  protected function export(ExportJob $job, State $state) {
    return ($this->_callback)($job, $state);
  }
}
