<?php

namespace lenz\contentfield\jsongen\exporter;

use DateTime;
use Exception;
use lenz\contentfield\jsongen\helpers\PathHelper;

/**
 * Class GitManager
 */
class GitManager
{
  /**
   * @var string
   */
  public $path;


  /**
   * @param string $path
   */
  public function __construct(string $path) {
    $this->path = $path;
  }

  /**
   * @return string
   */
  public function getGitDir(): string {
    return PathHelper::join($this->path, '.git');
  }

  /**
   * @return bool
   * @throws Exception
   */
  public function hasPendingChanges(): bool {
    $result = $this->exec('status', '--porcelain');
    return !empty($result);
  }

  /**
   * @return bool
   */
  public function isRepository(): bool {
    return is_dir($this->getGitDir());
  }

  /**
   * @throws Exception
   */
  public function tryCommit() {
    if (!$this->isRepository() || !$this->hasPendingChanges()) {
      return;
    }

    $this->exec('add', '-A');
    $this->exec('commit', '-m', escapeshellarg('Content update, '. date(DateTime::ATOM)));
    $this->exec('push');
  }

  /**
   * @throws Exception
   */
  public function tryPull() {
    if (!$this->isRepository() || $this->hasPendingChanges()) {
      return;
    }

    $this->exec('pull');
  }


  // Private methods
  // ---------------

  /**
   * @return array
   * @throws Exception
   */
  private function exec(): array {
    $args = func_get_args();
    array_unshift($args,
      'git',
      '--git-dir=' . escapeshellarg($this->getGitDir()),
      '--work-tree=' . escapeshellarg($this->path)
    );

    $cmd = escapeshellcmd(implode(' ', $args));
    exec($cmd, $output, $resultCode);

    if ($resultCode) {
      throw new Exception('Execution of git failed: '. implode("\n", $output) . ' The command was: ' . $cmd);
    }

    return $output;
  }
}
