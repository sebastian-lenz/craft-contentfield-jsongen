<?php

namespace lenz\contentfield\jsongen\exporter;

use Generator;

/**
 * Interface ExportSourceInterface
 */
interface ExportSourceInterface
{
  /**
   * @param ExportJob $job
   * @return Generator
   */
  public function getExportTasks(ExportJob $job): Generator;
}
