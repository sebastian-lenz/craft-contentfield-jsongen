<?php

namespace lenz\contentfield\jsongen\exporter;

use craft\helpers\StringHelper;
use lenz\contentfield\jsongen\helpers\PathHelper;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Class FileIndex
 */
class FileIndex
{
  /**
   * @var string
   */
  private $_basePath;

  /**
   * @var string[]
   */
  private $_files;

  /**
   * @var string
   */
  private $_rootPath;


  /**
   * @param string $rootPath
   * @param string $basePath
   */
  public function __construct(string $rootPath, string $basePath) {
    $this->_rootPath = PathHelper::normalize($rootPath, true);
    $this->_basePath = PathHelper::join($rootPath, $basePath);

    $this->_files = $this->loadFileIndex();
  }

  /**
   * @return void
   */
  public function cleanUp() {
    while (count($this->_files)) {
      unlink(PathHelper::join($this->_rootPath, array_pop($this->_files)));
    }

    $this->removeEmptyDir($this->_basePath);
  }

  /**
   * @param string $fileName
   * @return bool
   */
  public function fileExists(string $fileName): bool {
    return in_array($this->normalize($fileName), $this->_files);
  }

  /**
   * @param string $path
   * @return string
   */
  public function normalize(string $path): string {
    $path = PathHelper::normalize($path, true);
    if (StringHelper::startsWith($path, $this->_rootPath)) {
      $path = substr($path, strlen($this->_rootPath));
    }

    return trim($path, DIRECTORY_SEPARATOR);
  }

  /**
   * @param string $fileName
   */
  public function touch(string $fileName) {
    $fileName = $this->normalize($fileName);
    $count = count($this->_files);

    for ($index = 0; $index < $count; $index++) {
      if ($this->_files[$index] === $fileName) {
        array_splice($this->_files, $index, 1);
        return;
      }
    }
  }


  // Private methods
  // ---------------

  /**
   * @return array
   * @noinspection PhpParamsInspection
   */
  private function loadFileIndex(): array {
    $result = [];
    if (!file_exists($this->_basePath)) {
      mkdir($this->_basePath, 0777, true);
    }

    $directory = new RecursiveDirectoryIterator($this->_basePath);
    $filter = new RecursiveCallbackFilterIterator($directory, function ($current) {
      return $current->getFilename()[0] !== '.';
    });

    foreach (new RecursiveIteratorIterator($filter) as $info) {
      $result[] = $this->normalize($info->getPathname());
    }

    return $result;
  }

  /**
   * @param string $basePath
   * @return bool
   */
  private function removeEmptyDir(string $basePath): bool {
    $isEmpty = true;
    foreach (scandir($basePath) as $name) {
      if ($name === '.' || $name === '..') {
        continue;
      }

      $path = PathHelper::join($basePath, $name);
      if (is_dir($path) && !$this->removeEmptyDir($path)) {
        $isEmpty = false;
      } elseif (is_file($path)) {
        $isEmpty = false;
      }
    }

    return $isEmpty && rmdir($basePath);
  }
}
