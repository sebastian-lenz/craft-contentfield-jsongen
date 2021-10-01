<?php

namespace lenz\contentfield\jsongen\generators;

use craft\base\ElementInterface;
use craft\models\Site;
use Exception;
use Generator;
use lenz\contentfield\json\events\ProjectEvent;
use lenz\contentfield\json\Plugin;
use lenz\contentfield\json\scope\element\Structure;
use lenz\contentfield\json\scope\PropertyCollection;
use lenz\contentfield\json\scope\State;
use lenz\contentfield\jsongen\exporter\ExportJob;
use lenz\contentfield\jsongen\exporter\ExportTask;
use lenz\contentfield\jsongen\helpers\PathHelper;

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
   */
  public function getExportTasks(ExportJob $job): Generator {
    $jsonFileName = \lenz\contentfield\jsongen\Plugin::getInstance()->settings->jsonFileName;

    foreach ($this->getAffectedElements($job->getSite()) as $element)
    foreach ($this->getUrlsForElement($element) as $url => $params) {
      $fileName = $job->toOutName(PathHelper::join($url, $jsonFileName));

      yield new GeneratorExportTask($fileName, function(ExportJob $job, State $state) use ($url, $element, $params) {
        if (!$this->validateParams($params)) {
          throw new Exception('Invalid params');
        }

        $this->matchedParams = $params;

        $uid = self::generateUid($element->uid, $params);
        $state->dependsOnElement($element);
        $state->useCache = false;
        $state->metaData['generatorParams'] = $params;
        $state->metaData['generatorUid'] = $uid;
        $state->metaData['generatorUrl'] = Plugin::toAlias($url);

        $result = Plugin::toJson($element, Plugin::MODE_DEFAULT, $state);
        if (is_object($result)) {
          $this->injectMetadata($result, $uid);
        }

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
   * @param object $result
   * @param string $uid
   */
  protected function injectMetadata(object $result, string $uid) {
    if (!isset($result->{ExportTask::META_ATTRIBUTE})) {
      $result->{ExportTask::META_ATTRIBUTE} = (object)[];
    }

    $result->{ExportTask::META_ATTRIBUTE}->generator = (object)[
      'params' => $this->matchedParams,
      'uid' => $uid,
    ];
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


  // Static methods
  // --------------

  /**
   * @param string $uid
   * @param array $params
   * @return string
   */
  static public function generateUid(string $uid, array $params): string {
    $hash = md5(implode(';', [$uid, json_encode($params)]));
    return implode('-', [
      substr($hash, 0, 8),
      substr($hash, 8, 4),
      substr($hash, 12, 4),
      substr($hash, 16, 4),
      substr($hash, 20),
    ]);
  }
}
