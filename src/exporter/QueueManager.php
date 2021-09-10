<?php

namespace lenz\contentfield\jsongen\exporter;

use Craft;
use craft\models\Site;
use Exception;
use Throwable;
use yii\db\Query;

/**
 * Class QueueManager
 */
class QueueManager
{
  /**
   * @param int $delay
   * @throws Exception
   */
  static public function enqueueAllSites(int $delay = -1) {
    foreach (Craft::$app->getSites()->getAllSites() as $site) {
      self::enqueueSite($site, $delay);
    }
  }

  /**
   * @param Site $site
   * @param int $delay
   * @throws Exception
   */
  static public function enqueueSite(Site $site, int $delay = -1) {
    static $enqueuedSites = [];
    if (in_array($site->id, $enqueuedSites)) {
      return;
    }

    $enqueuedSites[] = $site->id;
    $queue = Craft::$app->queue;
    $job = new ExportJob([
      'siteId' => $site->id,
    ]);

    if ($delay < 0) {
      $job->execute($queue);
    } else {
      $queue->delay($delay)->push($job);
    }
  }

  /**
   * @return ExportJob[]
   * @throws Throwable
   */
  static public function getPendingJobs(): array {
    $result = [];
    $queue = Craft::$app->queue;
    $query = (new Query())
      ->from($queue->tableName)
      ->select(['id', 'job'])
      ->where([
        'channel' => $queue->channel,
        'fail' => false,
        'timeUpdated' => null
      ]);

    $records = $queue->db->usePrimary(function() use ($queue, $query) {
      return $query->all($queue->db);
    });

    foreach ($records as $record) {
      $job = unserialize($record['job']);
      if ($job instanceof ExportJob) {
        $result[$record['id']] = $job;
      }
    }

    return $result;
  }

  /**
   * @return bool
   * @throws Throwable
   */
  static public function hasPendingJob(): bool {
    return count(self::getPendingJobs()) > 0;
  }

  /**
   * @param int $siteId
   * @throws Throwable
   */
  static public function removeJobsBySite(int $siteId) {
    foreach (self::getPendingJobs() as $jobId => $job) {
      if ($job->siteId == $siteId) {
        Craft::$app->queue->release($jobId);
      }
    }
  }
}
