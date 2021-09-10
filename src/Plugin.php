<?php

namespace lenz\contentfield\jsongen;

use Craft;
use craft\base\Element;
use craft\base\Plugin as BasePlugin;
use craft\elements\Entry;
use craft\events\ElementEvent;
use craft\services\Elements;
use craft\services\Structures;
use craft\web\Application;
use craft\web\UrlRule;
use lenz\contentfield\json\events\ProjectEvent;
use lenz\contentfield\json\scope\Project;
use lenz\contentfield\jsongen\exporter\QueueManager;
use lenz\contentfield\jsongen\generators\GeneratorUrlRule;
use lenz\contentfield\jsongen\helpers\JsonUrlRule;
use Throwable;
use yii\base\Event;

/**
 * Class Plugin
 *
 * @property generators\GeneratorManager $generators
 * @property payloads\PayloadManager $payloads
 * @property Settings $settings
 */
class Plugin extends BasePlugin
{
  /**
   * List of element states we consider to be published.
   */
  const PUBLISHED_STATES = [Element::STATUS_ENABLED, Entry::STATUS_LIVE];


  /**
   * @inheritDoc
   */
  public function init() {
    parent::init();

    $this->setComponents([
      'generators' => generators\GeneratorManager::class,
      'payloads' => payloads\PayloadManager::class,
    ]);

    Event::on(Project::class, Project::EVENT_CREATE_PROJECT, function(ProjectEvent $event) {
      $this->generators->onCreateProject($event);
    });

    Event::on(Application::class, Application::EVENT_INIT, function() {
      Craft::$app->urlManager->addRules([
        new JsonUrlRule([
          'route' => 'contentfield-jsongen/json/index',
          'suffix' => $this->settings->jsonFileName,
        ]),
        new GeneratorUrlRule(),
        new UrlRule([
          'pattern' => '/api/payloads/<name>',
          'route' => 'contentfield-jsongen/json/payload',
          'suffix' => '.json',
        ]),
        new UrlRule([
          'pattern' => '/api/directory',
          'route' => 'contentfield-jsongen/json/directory',
        ]),
      ]);
    });

    $listener = [$this, 'onElementChanged'];
    Event::on(Elements::class, Elements::EVENT_AFTER_SAVE_ELEMENT, $listener);
    Event::on(Elements::class, Elements::EVENT_AFTER_DELETE_ELEMENT, $listener);
    Event::on(Elements::class, Elements::EVENT_AFTER_MERGE_ELEMENTS, $listener);
    Event::on(Structures::class, Structures::EVENT_AFTER_MOVE_ELEMENT, $listener);
  }

  /**
   * @throws Throwable
   */
  public function onElementChanged(ElementEvent $event) {
    if (
      in_array($event->element->getStatus(), self::PUBLISHED_STATES) &&
      !QueueManager::hasPendingJob()
    ) {
      QueueManager::enqueueAllSites(60);
    }
  }


  // Protected methods
  // -----------------

  /**
   * @inheritDoc
   */
  protected function createSettingsModel(): Settings {
    return new Settings();
  }
}
