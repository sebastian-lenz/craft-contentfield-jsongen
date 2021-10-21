<?php

namespace lenz\contentfield\jsongen\console\controllers;

use craft\console\Controller;
use lenz\contentfield\jsongen\exporter\QueueManager;
use Throwable;

/**
 * Class ExportController
 */
class ExportController extends Controller
{
  /**
   * @var int
   */
  public $delay = -1;

  /**
   * @var bool
   */
  public $fullRebuild = false;

  /**
   * @var bool
   */
  public $useGit = true;


  /**
   * Export the json data of all sites.
   * @throws Throwable
   */
  public function actionIndex() {
    QueueManager::enqueueAllSites($this->delay, [
      'isFullRebuild' => $this->fullRebuild,
      'useGit' => $this->useGit,
    ]);
  }

  public function options($actionID) {
    $options = parent::options($actionID);
    if ($actionID == 'index') {
      array_push($options, 'delay', 'fullRebuild', 'useGit');
    }

    return $options;
  }
}
