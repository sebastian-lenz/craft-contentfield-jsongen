<?php

namespace lenz\contentfield\jsongen\helpers;

use lenz\contentfield\json\Plugin as JsonPlugin;

/**
 * Class PathHelper
 */
class PathHelper
{
  /**
   * @return string
   */
  static public function join(): string {
    $segments = [];
    foreach (func_get_args() as $index => $segment) {
      $segments[] = self::normalize($segment, $index === 0);
    }

    return implode(DIRECTORY_SEPARATOR, $segments);
  }

  /**
   * @param string $path
   * @param bool $allowRoot
   * @return string
   */
  static public function normalize(string $path, bool $allowRoot = false): string {
    $path = str_replace('/', DIRECTORY_SEPARATOR, $path);

    return $allowRoot
      ? rtrim($path, DIRECTORY_SEPARATOR)
      : trim($path, DIRECTORY_SEPARATOR);
  }

  /**
   * @param string $url
   * @return string
   */
  static public function toPath(string $url): string {
    return str_replace('/', DIRECTORY_SEPARATOR, JsonPlugin::stripAlias($url));
  }
}
