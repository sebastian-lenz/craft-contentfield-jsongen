<?php

namespace lenz\contentfield\jsongen\generators;

use Craft;
use craft\base\ElementInterface;
use craft\models\Site;
use craft\web\Request;
use lenz\contentfield\json\helpers\AbstractManager;
use lenz\contentfield\jsongen\helpers\UrlRuleHelper;
use yii\base\InvalidConfigException;
use yii\web\UrlManager;

/**
 * Interface ElementGenerator
 */
abstract class ElementGenerator implements GeneratorInterface
{
  /**
   * @var array|null
   */
  public $matchedParams = null;


  /**
   * @param UrlManager $manager
   * @param Request $request
   * @return array|false
   * @throws InvalidConfigException
   */
  public function parseRequest(UrlManager $manager, Request $request) {
    $path = $request->getPathInfo();
    $asJson = AbstractManager::stripSuffix($path, '/index.json');
    if (
      !preg_match($this->getUrlPattern(), $path, $params) ||
      !array_key_exists('elementUri', $params)
    ) {
      return false;
    }

    $element = UrlRuleHelper::matchElement($params['elementUri']);
    if (!$element || !$this->isAffectedElement($element)) {
      return false;
    }

    $params = array_filter($params, 'is_string', ARRAY_FILTER_USE_KEY);
    $params['asJson'] = $asJson;
    $params['element'] = $element;

    if (!$this->validateParams($params)) {
      return false;
    }

    Craft::$app->urlManager->setMatchedElement($element);
    $this->matchedParams = $params;

    if (!$asJson) {
      return $element->getRoute();
    } else {
      return ['contentfield-jsongen/json/index', ['element' => $element]];
    }
  }


  // Abstract methods
  // ----------------

  /**
   * @param Site $site
   * @return ElementInterface[]
   */
  abstract protected function getAffectedElements(Site $site): array;

  /**
   * @return mixed
   */
  abstract protected function getUrlPattern(): string;

  /**
   * @param ElementInterface $element
   * @return bool
   */
  abstract protected function isAffectedElement(ElementInterface $element): bool;


  // Protected methods
  // -----------------

  /**
   * @param array $params
   * @return bool
   */
  protected function validateParams(array $params): bool {
    return true;
  }
}
