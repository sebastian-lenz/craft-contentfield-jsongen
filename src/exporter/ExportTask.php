<?php

namespace lenz\contentfield\jsongen\exporter;

use craft\base\ElementInterface;
use Exception;
use lenz\contentfield\json\scope\State;
use lenz\contentfield\jsongen\events\MetadataEvent;
use Throwable;

/**
 * Class ExportTask
 */
abstract class ExportTask
{
  /**
   * @var string
   */
  protected $_fileName;

  /**
   * @var string
   */
  protected $_existingChecksum;

  /**
   * Return values of ExportTask::beforeExport
   */
  const ACTION_IMMEDIATE = 0; // Task will be executed immediately
  const ACTION_SKIP = 1;      // Task will be skipped
  const ACTION_STASH = 2;     // Task will be processed after all tasks have been loaded

  /**
   * The attribute key we will use to store the metadata within the generated json files.
   */
  const META_ATTRIBUTE = '__metadata';

  /**
   * @param string $fileName
   */
  public function __construct(string $fileName) {
    $this->_fileName = $fileName;
  }

  /**
   * @param ExportJob $job
   * @return int
   */
  public function beforeExport(ExportJob $job): int {
    if (!file_exists($this->_fileName)) {
      return self::ACTION_IMMEDIATE;
    }

    return $this->hasPendingChanges($job)
      ? self::ACTION_IMMEDIATE
      : self::ACTION_SKIP;
  }

  /**
   * @return string
   */
  public function getFileName(): string {
    return $this->_fileName;
  }

  /**
   * @param string $checksum
   * @return bool
   */
  protected function hasChanged(string $checksum): bool {
    if (!file_exists($this->_fileName)) {
      return true;
    }

    if (!empty($this->_existingChecksum)) {
      return $this->_existingChecksum !== $checksum;
    }

    try {
      $data = $this->loadMetaData();
      return is_null($data) || $data->checksum !== $checksum;
    } catch (Exception $exception) {
      return true;
    }
  }

  /**
   * @param ExportJob $job
   */
  public function process(ExportJob $job) {
    $fileName = $this->_fileName;

    $path = dirname($fileName);
    if (!file_exists($path)) {
      mkdir(dirname($fileName), 0777, true);
    }

    $state = new State();
    $content = $this->export($job, $state);
    $checksum = md5(json_encode($content));

    if ($this->hasChanged($checksum)) {
      $this->createMetaData($content, $job, $state, $checksum);
      file_put_contents($fileName, json_encode($content));
    }
  }


  // Protected methods
  // -----------------

  /**
   * @param ExportJob $job
   * @param State $state
   * @return array|object
   */
  abstract protected function export(ExportJob $job, State $state);

  /**
   * @param object $content
   * @param ExportJob $job
   * @param State $state
   * @param string $checksum
   * @return object
   */
  protected function createMetaData(object $content, ExportJob $job, State $state, string $checksum): object {
    $event = new MetadataEvent([
      'content' => $content,
      'metadata' => (object)($content->{self::META_ATTRIBUTE} ?? []),
      'state' => $state,
      'task' => $this,
    ]);

    $job->trigger(ExportJob::EVENT_METADATA, $event);
    $metadata = $event->metadata;
    $content->{self::META_ATTRIBUTE} = $metadata;

    $metadata->dependencies = $state->getDependencies();
    $metadata->checksum = $checksum;
    $metadata->requirements = $state->getRequirements();
    $metadata->updated = $job->getTimestamp();

    return $metadata;
  }

  /**
   * @param ExportJob $job
   * @return bool
   */
  protected function hasPendingChanges(ExportJob $job): bool {
    $tags = $job->getTagIndex();
    $updated = 0;

    try {
      $data = $this->loadMetaData();
      if (isset($data->checksum)) {
        $this->_existingChecksum = $data->checksum;
      }

      if (!isset($data->updated) || !isset($data->dependencies)) {
        return true;
      }

      foreach ($data->dependencies as $dependency) {
        $tag = $tags->getTagValue($dependency);
        if (is_null($tag)) {
          echo 'Unknown: ' . $dependency . "\n";
          return true;
        }

        $updated = max($updated, $tag);
      }

      return $updated > $data->updated;
    } catch (Exception $exception) {
      return true;
    }
  }

  /**
   * @return object|null
   */
  protected function loadMetaData(): ?object {
    try {
      $data = json_decode(file_get_contents($this->_fileName));
      return $data->{self::META_ATTRIBUTE} ?? null;
    } catch (Throwable $error) {
      return null;
    }
  }
}
