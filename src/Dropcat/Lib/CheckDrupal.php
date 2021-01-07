<?php
namespace Dropcat\Lib;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Class CheckDrupal
 *
 * Checking if it is Drupal, and which version.
 *
 * @package Dropcat\Lib
 */
class CheckDrupal
{
    public $dir;
    public Filesystem $fs;

    public function __construct()
    {
        $this->dir = getcwd();
        $this->fs = new Filesystem();
    }

    public function isDrupal(): bool
    {
        return $this->fs->exists($this->dir . '/web/core/core.api.php');
    }

    /**
     * The composer.json of Drupal core contains this:
     * '    "config": {
     * '         "preferred-install": "dist",
     * '         "autoloader-suffix": "Drupal9",
     *  With either Drupal8 or Drupal9 depending on the version.
     * @return string
     * @throws \Exception
     */
    public function version(): string
    {
        if ($this->fs->exists($this->dir . '/composer.json')) {
            $composerJson = file_get_contents($this->dir . '/composer.json');
            if (strpos($composerJson, 'Drupal9') !== false) {
                return '9';
            } elseif (strpos($composerJson, 'Drupal8') !== false) {
                return '8';
            }
        }

        throw new \Exception("Could not determine Drupal version.");
    }
}
