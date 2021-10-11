<?php

namespace lenz\contentfield\jsongen\exporter;

use Craft;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\db\Table;
use craft\elements\MatrixBlock;
use craft\elements\User;
use craft\helpers\Db;
use yii\db\Expression;

/**
 * Class TagIndex
 */
class TagIndex
{
  /**
   * @var array
   */
  private $_maps = [];

  /**
   * @var int
   */
  private $_now = 0;

  /**
   * Known Tags:
   * - *
   * - element:{uid}
   * - section:{sectionHandle}
   * - type:{typeHandle}
   * - group:{typeHandle}
   * @var array
   */
  private $_tags = [];


  /**
   * @param ExportJob $job
   */
  public function __construct(ExportJob $job) {
    $this->_now = $job->getTimestamp();
    $this->loadTags($job);
  }

  /**
   * @param string $value
   * @return int|null
   */
  public function getTagValue(string $value): ?int {
    return array_key_exists($value, $this->_tags)
      ? $this->_tags[$value]
      : null;
  }


  // Private methods
  // ---------------

  /**
   * @param ExportJob $job
   * @return Query
   */
  private function createQuery(ExportJob $job): Query {
    $elements = Table::ELEMENTS;
    $elementsSites = Table::ELEMENTS_SITES;
    $entries = Table::ENTRIES;
    $categories = Table::CATEGORIES;
    $query = new Query();

    return $query
      ->select([
        "$elements.id", "$elements.uid", "$elements.type", "$elements.dateUpdated",
        "$entries.sectionId", "$entries.typeId", "$entries.postDate", "$entries.expiryDate",
        "$categories.groupId"
      ])
      ->from($elements)
      ->innerJoin($elementsSites, "$elementsSites.elementId = $elements.id")
      ->leftJoin($entries, "$entries.id = $elements.id")
      ->leftJoin($categories, "$categories.id = $elements.id")
      ->where([
        "$elements.draftId" => null,
        "$elements.revisionId" => null,
        "$elements.dateDeleted" => null,
        "$elements.enabled" => true,
        "$elements.archived" => false,
        "$elementsSites.enabled" => true,
        "$elementsSites.siteId" => $job->getSite()->id,
      ])
      ->andWhere([
        'NOT IN', "$elements.type", [MatrixBlock::class, User::class],
      ])
      /*
      ->andWhere([
        'or',
        ["$entries.postDate" => null],
        [
          'and',
          ['<=', "$entries.postDate", $currentTimeDb],
          [
            'or',
            ["$entries.expiryDate" => null],
            ['>', "$entries.expiryDate", $currentTimeDb],
          ],
        ]
      ])
      */;
  }

  /**
   * @param string $type
   * @param int $id
   * @return string
   */
  private function getMapValue(string $type, int $id): ?string {
    if (!array_key_exists($type, $this->_maps)) {
      $this->_maps[$type] = $this->loadMap($type);
    }

    return array_key_exists($id, $this->_maps[$type])
      ? $this->_maps[$type][$id]
      : null;
  }

  /**
   * @param string $type
   * @return array
   */
  private function loadMap(string $type): array {
    switch ($type) {
      case 'section':
        return self::createMap(Craft::$app->sections->getAllSections());
      case 'type':
        return self::createMap(Craft::$app->sections->getAllEntryTypes());
      case 'group':
        return self::createMap(Craft::$app->categories->getAllGroups());
      default:
        return [];
    }
  }

  /**
   * @param ExportJob $job
   */
  private function loadTags(ExportJob $job) {
    $now = $this->_now;

    foreach ($this->createQuery($job)->all() as $row) {
      $updated = strtotime($row['dateUpdated'] . ' UTC');
      foreach (['postDate', 'expiryDate'] as $dateField) {
        $dateValue = $row[$dateField];
        $dateValue = empty($dateValue) ? false : strtotime($dateValue . ' UTC');

        if ($dateValue && $dateValue > $updated && $dateValue < $now) {
          $updated = $dateValue;
        }
      }

      $this->setUpdated('*', $updated);
      $this->setUpdated('element:' . $row['uid'], $updated);
      $this->setUpdatedByGroup('section', $row['sectionId'], $updated);
      $this->setUpdatedByGroup('type', $row['typeId'], $updated);
      $this->setUpdatedByGroup('group', $row['groupId'], $updated);
      $this->setUpdatedByType($row['type'], $updated);
    }
  }

  /**
   * @param string $key
   * @param int $value
   */
  private function setUpdated(string $key, int $value) {
    $this->_tags[$key] = array_key_exists($key, $this->_tags)
      ? max($value, $this->_tags[$key])
      : $value;
  }

  /**
   * @param string $type
   * @param int|null $id
   * @param int $value
   */
  private function setUpdatedByGroup(string $type, ?int $id, int $value) {
    $handle = is_null($id)
      ? null
      : self::getMapValue($type, $id);

    if (!is_null($handle)) {
      $this->setUpdated($type . ':' . $handle, $value);
    }
  }

  /**
   * @param string $type
   * @param int $value
   */
  private function setUpdatedByType(string $type, int $value) {
    static $types;
    if (!isset($types)) {
      $types = self::createRefHandleMap();
    }

    if (array_key_exists($type, $types)) {
      $this->setUpdated($types[$type], $value);
    }
  }


  // Static methods
  // --------------

  /**
   * @param array $list
   * @param string $key
   * @param string $value
   * @return array
   */
  static public function createMap(array $list, string $key = 'id', string $value = 'handle'): array {
    $result = [];

    foreach ($list as $item) {
      $result[intval($item->$key)] = $item->$value;
    }

    return $result;
  }

  /**
   * @return array
   */
  static public function createRefHandleMap(): array {
    $result = [];

    /* @var string|ElementInterface $elementClass */
    foreach (Craft::$app->elements->getAllElementTypes() as $elementClass) {
      $refHandle = $elementClass::refHandle();
      if (!is_null($refHandle)) {
        $result[$elementClass] = $refHandle;
      }
    }

    return $result;
  }
}
