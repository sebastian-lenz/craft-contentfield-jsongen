<?php

namespace lenz\contentfield\jsongen\console\controllers;

use craft\console\Controller;
use Exception;
use lenz\contentfield\jsongen\exporter\QueueManager;

/**
 * Class ExportController
 */
class ExportController extends Controller
{
  /**
   * Export the json data of all sites.
   * @throws Exception
   */
  public function actionIndex(int $delay = -1) {
    QueueManager::enqueueAllSites($delay);
  }
}
