<?php

namespace lenz\contentfield\jsongen\helpers;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\errors\SiteNotFoundException;

/**
 * Class UrlRuleHelper
 */
class UrlRuleHelper
{
  /**
   * @param string $path
   * @return ElementInterface|null
   */
  static public function matchElement(string $path): ?ElementInterface {
    $path = rtrim($path, '/');
    if ($path === Element::HOMEPAGE_URI) {
      return null;
    }

    try {
      $siteId = Craft::$app->getSites()->getCurrentSite()->id;
    } catch (SiteNotFoundException $e) {
      return null;
    }

    return Craft::$app->getElements()->getElementByUri($path, $siteId, true);
  }
}
