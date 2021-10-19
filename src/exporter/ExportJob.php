<?php

namespace lenz\contentfield\jsongen\exporter;

use Craft;
use craft\models\Site;
use craft\queue\JobInterface;
use DateTime;
use Exception;
use lenz\contentfield\json\Plugin as JsonPlugin;
use lenz\contentfield\jsongen\events\RegisterExportSourcesEvent;
use lenz\contentfield\jsongen\helpers\PathHelper;
use lenz\contentfield\jsongen\Plugin;
use Serializable;
use Throwable;
use yii\base\Component;

/**
 * Class ExportJob
 */
class ExportJob extends Component implements JobInterface, Serializable
{
  /**
   * @var string
   */
  public $outputPath;

  /**
   * @var int
   */
  public $siteId;

  /**
   * @var FileIndex
   */
  private $_fileIndex;

  /**
   * @var GitManager
   */
  private $_git;

  /**
   * @var DateTime
   */
  private $_now;

  /**
   * @var Site
   */
  private $_site;

  /**
   * @var ExportSourceInterface[]
   */
  private $_sources;

  /**
   * @var TagIndex
   */
  private $_tagIndex;

  /**
   * @var ExportTask[]
   */
  private $_tasks = [];

  /**
   * Triggered when the exporter collects the metadata of an output file.
   */
  const EVENT_METADATA = 'metadata';

  /**
   * Triggered when the exporter collects the available export sources.
   */
  const EVENT_REGISTER_EXPORT_SOURCES = 'registerExportSources';


  /**
   * @inheritDoc
   */
  public function init() {
    parent::init();

    $this->_now = new DateTime('now', new \DateTimeZone('UTC'));
  }

  /**
   * @return string
   */
  public function getDescription(): string {
    return 'Export site `' . $this->getSite()->name . '`';
  }

  /**
   * @return FileIndex
   */
  public function getFileIndex(): FileIndex {
    if (!isset($this->_fileIndex)) {
      $baseUrl = $this->getSite()->getBaseUrl(false);
      $this->_fileIndex = new FileIndex($this->outputPath, PathHelper::toPath($baseUrl));
    }

    return $this->_fileIndex;
  }

  /**
   * @return GitManager
   */
  public function getGit(): GitManager {
    if (!isset($this->_git)) {
      $path = array_key_exists('CONTENT_JSONGEN_GIT_PATH', $_ENV)
        ? $_ENV['CONTENT_JSONGEN_GIT_PATH']
        : $this->outputPath;

      $this->_git = new GitManager($path);
    }

    return $this->_git;
  }

  /**
   * @return Site
   */
  public function getSite(): Site {
    if (!isset($this->_site)) {
      $this->_site = Craft::$app->sites->getSiteById($this->siteId);
    }

    return $this->_site;
  }

  /**
   * @return ExportSourceInterface[]
   */
  public function getSources(): array {
    if (!isset($this->_sources)) {
      $event = new RegisterExportSourcesEvent();
      $this->trigger(self::EVENT_REGISTER_EXPORT_SOURCES, $event);
      $this->_sources = $event->sources;
    }

    return $this->_sources;
  }

  /**
   * @return TagIndex
   */
  public function getTagIndex(): TagIndex {
    if (!isset($this->_tagIndex)) {
      $this->_tagIndex = new TagIndex($this);
    }

    return $this->_tagIndex;
  }

  /**
   * @return int
   */
  public function getTimestamp(): int {
    return $this->_now->getTimestamp();
  }

  /**
   * @inheritDoc
   * @throws Throwable
   */
  public function execute($queue) {
    $this->beforeExecute();

    foreach ($this->getSources() as $source) {
      foreach ($source->getExportTasks($this) as $task) {
        $this->addTask($task);
      }
    }

    foreach ($this->_tasks as $task) {
      $task->process($this);
    }

    $this->afterExecute();
  }

  /**
   * @return string
   */
  public function serialize(): string {
    return serialize([
      'outputPath' => $this->outputPath,
      'siteId' => $this->siteId,
    ]);
  }

  /**
   * @param string $url
   * @return string
   */
  public function toOutName(string $url): string {
    return PathHelper::join($this->outputPath, PathHelper::toPath($url));
  }

  /**
   * @param string $data
   */
  public function unserialize($data) {
    $data = unserialize($data);
    $this->outputPath = $data['outputPath'];
    $this->siteId = $data['siteId'];
    $this->_now = new DateTime();
  }


  // Private methods
  // ---------------

  /**
   * @param ExportTask $task
   */
  private function addTask(ExportTask $task) {
    $this->getFileIndex()->touch($task->getFileName());
    $action = $task->beforeExport($this);

    if ($action === ExportTask::ACTION_IMMEDIATE) {
      $task->process($this);
    } else if ($action === ExportTask::ACTION_STASH) {
      $this->_tasks[] = $task;
    }
  }

  /**
   * @return void
   * @throws Throwable
   */
  private function afterExecute() {
    $this->getFileIndex()->cleanUp();

    if (!QueueManager::hasPendingJob()) {
      $this->getGit()->tryCommit();
    }
  }

  /**
   * @throws Throwable
   */
  private function beforeExecute() {
    $this->getGit()->tryPull();

    QueueManager::removeJobsBySite($this->siteId);
    JsonPlugin::$ensureAssetTransforms = true;
    $this->ensureOutDir();
    $this->ensureSite();
  }

  /**
   * @throws Exception
   */
  private function ensureOutDir() {
    if (empty($this->outputPath)) {
      $this->outputPath = Plugin::getInstance()->settings->outputPath;
    }

    $this->outputPath = Craft::parseEnv($this->outputPath);
    if (!file_exists($this->outputPath)) {
      mkdir($this->outputPath, 0777, true);
    }

    if (!is_dir($this->outputPath)) {
      throw new Exception('The output directory does not exist or cannot be created.');
    }
  }

  /**
   * @throws Exception
   */
  private function ensureSite() {
    $sites = Craft::$app->sites;
    if (empty($this->siteId)) {
      $this->siteId = $sites->currentSite->id;
    }

    try {
      $site = $this->getSite();
      Craft::$app->getSites()->setCurrentSite($site);
      Craft::$app->language = $site->language;
    } catch (Exception $error) {
      throw new Exception('No site to export selected.');
    }
  }
}
