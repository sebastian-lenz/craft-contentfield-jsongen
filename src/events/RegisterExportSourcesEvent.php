<?php

namespace lenz\contentfield\jsongen\events;

use lenz\contentfield\jsongen\exporter\elements\ElementSource;
use lenz\contentfield\jsongen\exporter\ExportSourceInterface;
use lenz\contentfield\jsongen\Plugin;
use yii\base\Event;

/**
 * Class RegisterExportSourcesEvent
 */
class RegisterExportSourcesEvent extends Event
{
  /**
   * @var ExportSourceInterface[]
   */
  public $sources;


  /**
   * @param array $config
   */
  public function __construct($config = []) {
    parent::__construct(array_merge($config, [
      'sources' => [
        Plugin::getInstance()->payloads,
        Plugin::getInstance()->generators,
        new ElementSource(),
      ],
    ]));
  }
}
