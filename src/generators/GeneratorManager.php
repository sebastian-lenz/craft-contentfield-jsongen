<?php

namespace lenz\contentfield\jsongen\generators;

use craft\web\Application;
use craft\web\ErrorHandler;
use Generator;
use JsonSerializable;
use lenz\contentfield\json\events\ProjectEvent;
use lenz\contentfield\json\helpers\AbstractManager;
use lenz\contentfield\jsongen\exporter\ExportJob;
use lenz\contentfield\jsongen\exporter\ExportSourceInterface;
use yii\base\Event;

/**
 * Interface GeneratorManager
 *
 * @method GeneratorInterface|null getInstance(string $name)
 * @method GeneratorInterface[] getInstances()
 */
class GeneratorManager extends AbstractManager implements ExportSourceInterface
{
  /**
   * @inheritDoc
   */
  CONST SEGMENT = 'generators';

  /**
   * @inheritDoc
   */
  const SUFFIX = 'generator';

  /**
   * @inheritDoc
   */
  const ITEM_CLASS = GeneratorInterface::class;


  /**
   * @param ExportJob $job
   * @return Generator
   */
  public function getExportTasks(ExportJob $job): Generator {
    foreach ($this->getInstances() as $generator) {
      yield from $generator->getExportTasks($job);
    }
  }

  /**
   * @param ProjectEvent $event
   */
  public function onCreateProject(ProjectEvent $event) {
    foreach ($this->getInstances() as $instance) {
      $instance->onCreateProject($event);
    }
  }
}
