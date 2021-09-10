<?php

namespace lenz\contentfield\jsongen;

use craft\base\Model;

/**
 * Class Settings
 */
class Settings extends Model
{
  /**
   * @var string
   */
  public $jsonFileName = 'index.json';

  /**
   * @var string
   */
  public $outputPath = '@storage/json';
}
