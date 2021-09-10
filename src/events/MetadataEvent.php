<?php

namespace lenz\contentfield\jsongen\events;

use lenz\contentfield\json\scope\State;
use lenz\contentfield\jsongen\exporter\ExportTask;
use yii\base\Event;

/**
 * Class MetadataEvent
 */
class MetadataEvent extends Event
{
  /**
   * @var object
   */
  public $content;

  /**
   * @var object
   */
  public $metadata;

  /**
   * @var State
   */
  public $state;

  /**
   * @var ExportTask
   */
  public $task;
}
