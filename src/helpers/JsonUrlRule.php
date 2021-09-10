<?php

namespace lenz\contentfield\jsongen\helpers;

use Craft;
use craft\web\Request;
use lenz\contentfield\json\helpers\AbstractManager;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use yii\web\UrlRuleInterface;

/**
 * Class JsonUrlRule
 */
class JsonUrlRule extends BaseObject implements UrlRuleInterface
{
  /**
   * @var string
   */
  public $route;

  /**
   * @var string
   */
  public $suffix;


  /**
   * @inheritDoc
   * @throws InvalidConfigException
   */
  public function parseRequest($manager, $request) {
    if (!($request instanceof Request) || !$request->isSiteRequest) {
      return false;
    }

    $path = $request->getPathInfo();

    return AbstractManager::stripSuffix($path, $this->suffix)
      ? $this->parseJsonRequest($path)
      : false;
  }

  /**
   * @inheritDoc
   */
  public function createUrl($manager, $route, $params) {
    return false;
  }


  // Private methods
  // ---------------

  /**
   * @return array|bool
   */
  private function parseJsonRequest(string $path) {
    $element = UrlRuleHelper::matchElement($path);
    if (is_null($element)) {
      return false;
    }

    Craft::$app->getUrlManager()->setMatchedElement($element);
    return [$this->route, [
      'element' => $element,
    ]];
  }
}
