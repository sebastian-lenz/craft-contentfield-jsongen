<?php

namespace lenz\contentfield\jsongen\generators;

use craft\web\Request;
use lenz\contentfield\jsongen\Plugin;
use yii\base\BaseObject;
use yii\web\UrlRuleInterface;

/**
 * Class GeneratorUrlRule
 */
class GeneratorUrlRule extends BaseObject implements UrlRuleInterface
{
  /**
   * @inheritDoc
   */
  public function parseRequest($manager, $request) {
    if (!($request instanceof Request) || !$request->isSiteRequest) {
      return false;
    }

    $generators = Plugin::getInstance()->generators->getInstances();
    foreach ($generators as $generator) {
      $result = $generator->parseRequest($manager, $request);

      if ($result !== false) {
        return $result;
      }
    }

    return false;
  }

  /**
   * @inheritDoc
   */
  public function createUrl($manager, $route, $params) {
    return false;
  }
}
