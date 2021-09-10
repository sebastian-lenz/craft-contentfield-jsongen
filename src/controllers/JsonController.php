<?php

namespace lenz\contentfield\jsongen\controllers;

use craft\base\ElementInterface;
use craft\web\Controller;
use lenz\contentfield\json\Plugin as JsonPlugin;
use lenz\contentfield\json\scope\State;
use lenz\contentfield\jsongen\Plugin;
use lenz\contentfield\jsongen\directory\DirectoryGenerator;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Class JsonController
 */
class JsonController extends Controller
{
  /**
   * @inheritDoc
   */
  protected $allowAnonymous = true;


  /**
   * @return Response
   */
  public function actionDefinitions(): Response {
    return $this->asRaw(JsonPlugin::getInstance()->project->toDefinitions());
  }

  /**
   * @param ElementInterface $element
   * @return Response
   */
  public function actionIndex(ElementInterface $element): Response {
    $state = new State();
    $data = JsonPlugin::getInstance()->project->toJson($element, JsonPlugin::MODE_DEFAULT, $state);
    $data->dependencies = $state->getDependencies();

    return $this->asJson($data);
  }

  /**
   * @param string $name
   * @return Response
   * @throws NotFoundHttpException
   */
  public function actionPayload(string $name): Response {
    $payload = Plugin::getInstance()->payloads->getInstance($name);
    if (is_null($payload)) {
      throw new NotFoundHttpException();
    }

    return $this->asJson($payload);
  }

  /**
   * @return Response
   */
  public function actionDirectory(): Response {
    $response = $this->response;
    $response->headers->set('Content-Type', 'application/json');
    $response->stream = new DirectoryGenerator();
    $response->format = Response::FORMAT_RAW;

    return $response;
  }
}
