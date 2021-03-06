<?php
/**
 * @author Alexey Tatarinov <tatarinov@shogo.ru>
 * @link https://github.com/shogodev/argilla/
 * @copyright Copyright &copy; 2003-2016 Shogo
 * @license http://argilla.ru/LICENSE
 */
require_once "phing/Task.php";

class FrameworkConfigTask extends Task
{
  public $defaultFrameworkPath = '../yii/framework';

  protected $file;

  public function setFile($file)
  {
    $this->file = $file;
  }

  public function main()
  {
    if( !$this->newMethodSetVersion() )
      $this->oldMethodSetVersion();
  }

  protected function newMethodSetVersion()
  {
    if( !file_exists($this->file) )
      return false;

    $frameworkConfig = require($this->file);

    $this->project->setProperty('framework.path', realpath(__DIR__.'/../../'.$frameworkConfig['frameworkPath']));
    $this->project->setProperty('framework.version', $frameworkConfig['version']);

    return true;
  }

  protected function oldMethodSetVersion()
  {
    $path = dirname($this->file);
    $versionFile = $path.DIRECTORY_SEPARATOR.'version.php';
    $version = require($versionFile);

    $this->project->setProperty('framework.path', realpath(__DIR__.'/../../'.$this->defaultFrameworkPath));
    $this->project->setProperty('framework.version', $version);
  }
}