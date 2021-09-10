<?php

namespace lenz\contentfield\jsongen\generators;

use craft\models\Site;
use craft\web\Request;
use lenz\contentfield\json\events\ProjectEvent;
use lenz\contentfield\jsongen\exporter\ExportSourceInterface;
use yii\web\UrlManager;

/**
 * Interface GeneratorInterface
 */
interface GeneratorInterface extends ExportSourceInterface
{
  /**
   * @param UrlManager $manager
   * @param Request $request
   * @return array|false
   */
  public function parseRequest(UrlManager $manager, Request $request);

  /**
   * @param ProjectEvent $event
   * @return mixed
   */
  public function onCreateProject(ProjectEvent $event);
}
