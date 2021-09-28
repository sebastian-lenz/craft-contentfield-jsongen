<?php

namespace lenz\contentfield\jsongen\exporter\elements;

use Craft;
use craft\base\ElementInterface;
use Exception;
use lenz\contentfield\json\Plugin;
use lenz\contentfield\json\scope\State;
use lenz\contentfield\jsongen\exporter\ExportJob;
use lenz\contentfield\jsongen\exporter\ExportTask;

/**
 * Class ElementExportTask
 */
class ElementExportTask extends ExportTask
{
  /**
   * @var ElementInterface|null
   */
  public $element;

  /**
   * @var string
   */
  public $elementClass;

  /**
   * @var int|null
   */
  public $elementId;


  /**
   * @param string $fileName
   * @param ElementInterface $element
   */
  public function __construct(string $fileName, ElementInterface $element) {
    parent::__construct($fileName);

    $this->element = $element;
    $this->elementId = $element->id;
    $this->elementClass = get_class($element);
  }

  /**
   * @param ExportJob $job
   * @return int
   */
  public function beforeExport(ExportJob $job): int {
    $result = parent::beforeExport($job);
    if ($result === ExportTask::ACTION_STASH) {
      $this->element = null;
    }

    return $result;
  }


  // Protected methods
  // -----------------

  /**
   * @inheritDoc
   */
  protected function export(ExportJob $job, State $state) {
    $element = $this->element;
    if (is_null($element)) {
      $element = Craft::$app->elements->getElementById($this->elementId, $this->elementClass, $job->getSite()->id);
    }

    return Plugin::toJson($element, Plugin::MODE_DEFAULT, $state);
  }

  /**
   * @inheritDoc
   */
  protected function getType(): string {
    return 'element';
  }
}
