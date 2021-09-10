<?php

namespace lenz\contentfield\jsongen\exporter\elements;

use Craft;
use craft\db\Paginator;
use craft\elements\Category;
use craft\elements\db\ElementQuery;
use craft\elements\Entry;
use craft\models\CategoryGroup_SiteSettings;
use craft\models\Section_SiteSettings;
use craft\models\Site;
use Generator;
use lenz\contentfield\jsongen\exporter\ExportJob;
use lenz\contentfield\jsongen\exporter\ExportSourceInterface;
use lenz\contentfield\jsongen\helpers\PathHelper;
use lenz\contentfield\jsongen\Plugin;

/**
 * Class EntrySource
 */
class ElementSource implements ExportSourceInterface
{
  /**
   * @param ExportJob $job
   * @return Generator
   */
  public function getExportTasks(ExportJob $job): Generator {
    yield from $this->getCategoryExportTasks($job);
    yield from $this->getEntryExportTasks($job);
  }


  // Private methods
  // ---------------

  /**
   * @param ExportJob $job
   * @return Generator
   */
  private function getCategoryExportTasks(ExportJob $job): Generator {
    $site = $job->getSite();
    foreach (Craft::$app->categories->getAllGroups() as $group) {
      if (!self::hasUriFormat($site, $group->siteSettings)) {
        continue;
      }

      $query = Category::find()->group($group)->site($site);
      yield from $this->getQueryExportTasks($job, $query);
    }
  }

  /**
   * @param ExportJob $job
   * @return Generator
   */
  private function getEntryExportTasks(ExportJob $job): Generator {
    $site = $job->getSite();
    foreach (Craft::$app->getSections()->getAllSections() as $section) {
      if (!self::hasUriFormat($site, $section->siteSettings)) {
        continue;
      }

      $query = Entry::find()->section($section)->site($site);
      yield from $this->getQueryExportTasks($job, $query);
    }
  }

  /**
   * @param ExportJob $job
   * @param ElementQuery $query
   * @return Generator
   */
  private function getQueryExportTasks(ExportJob $job, ElementQuery $query): Generator {
    $paginator = new Paginator($query);
    $jsonFileName = Plugin::getInstance()->settings->jsonFileName;

    for ($page = 1; $page <= $paginator->totalPages; $page++) {
      $paginator->setCurrentPage($page);
      $elements = $paginator->getPageResults();

      foreach ($elements as $element) {
        $url = $element->getUrl();
        if (is_null($url)) {
          continue;
        }

        $fileName = $job->toOutName(PathHelper::join($url, $jsonFileName));
        yield new ElementExportTask($fileName, $element);
      }
    }
  }

  /**
   * @param Site $site
   * @param CategoryGroup_SiteSettings[]|Section_SiteSettings[] $settings
   * @return bool
   */
  static public function hasUriFormat(Site $site, array $settings): bool {
    foreach ($settings as $setting) {
      if (
        $setting->siteId == $site->id &&
        $setting->hasUrls &&
        !empty($setting->uriFormat)
      ) {
        return true;
      }
    }

    return false;
  }
}
