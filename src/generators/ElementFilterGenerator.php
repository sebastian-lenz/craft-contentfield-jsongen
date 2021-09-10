<?php

namespace lenz\contentfield\jsongen\generators;

use Craft;
use craft\base\ElementInterface;
use craft\models\Site;
use craft\web\Request;
use Generator;
use lenz\contentfield\json\events\ProjectEvent;
use lenz\contentfield\json\helpers\AbstractManager;
use lenz\contentfield\json\Plugin;
use lenz\contentfield\json\scope\element\Structure;
use lenz\contentfield\json\scope\PropertyCollection;
use lenz\contentfield\json\scope\State;
use lenz\contentfield\jsongen\exporter\ExportJob;
use lenz\contentfield\jsongen\helpers\UrlRuleHelper;
use yii\base\InvalidConfigException;
use yii\web\UrlManager;

/**
 * Interface ElementFilterGenerator
 */
abstract class ElementFilterGenerator extends ElementGenerator
{
  /**
   * @var Structure[]
   */
  private $_structures;


  /**
   * @inheritDoc
   * @noinspection PhpExpressionResultUnusedInspection
   */
  public function getExportTasks(ExportJob $job): Generator {
    foreach ($this->getAffectedElements($job->getSite()) as $element)
    foreach ($this->getUrlsForElement($element) as $url => $params) {
      $fileName = $job->toOutName($url);

      yield new GeneratorExportTask($fileName, function(ExportJob $job, State $state) use ($element, $params) {
        $this->matchedParams = $params;
        $this->validateParams($params);

        $state->dependsOnElement($element);
        $state->useCache = false;
        $result = Plugin::toJson($element, Plugin::MODE_DEFAULT, $state);

        $this->matchedParams = null;
        return $result;
      });
    }
  }

  /**
   * @param ProjectEvent $event
   */
  public function onCreateProject(ProjectEvent $event) {
    $filters = $this->getElementFilters();
    $filters['mode'] = 'default';

    $event->modifyElement($filters,
      function(PropertyCollection $collection) {
        $this->modifyProperties($collection);
      }
    );
  }


  // Abstract methods
  // ----------------

  /**
   * @return array
   */
  abstract protected function getElementFilters(): array;

  /**
   * @param ElementInterface $element
   * @return array
   */
  abstract protected function getUrlsForElement(ElementInterface $element): array;


  // Protected methods
  // -----------------

  /**
   * @inheritDoc
   */
  protected function getAffectedElements(Site $site): array {
    $result = [];

    foreach ($this->getAffectedStructures() as $structure)
    foreach ($structure->getQuery()->site($site)->all() as $element) {
      $result[$element->id] = $element;
    }

    return array_values($result);
  }

  /**
   * @return Structure[]
   */
  protected function getAffectedStructures(): array {
    if (isset($this->_structures)) {
      return $this->_structures;
    }

    $filters = $this->getElementFilters();
    $filters['mode'] = 'default';
    $result = [];

    foreach (Plugin::getInstance()->project->getElementStructures() as $structure) {
      if ($structure->matchesFilters($filters)) {
        $result[] = $structure;
      }
    }

    $this->_structures = $result;
    return $result;
  }

  /**
   * @param ElementInterface $element
   * @return bool
   */
  protected function isAffectedElement(ElementInterface $element): bool {
    foreach ($this->getAffectedStructures() as $structure) {
      if ($structure->canExport($element)) {
        return true;
      }
    }

    return false;
  }

  /**
   * @param PropertyCollection $collection
   */
  protected function modifyProperties(PropertyCollection $collection) { }
}
