<?php
/**
 * @author Sergey Glagolev <glagolev@shogo.ru>
 * @link https://github.com/shogodev/argilla/
 * @copyright Copyright &copy; 2003-2014 Shogo
 * @license http://argilla.ru/LICENSE
 */
class SingleImageGrid extends ImageGrid
{
  protected function initColumns()
  {
    $this->imageColumn();
    $this->buttonColumn();
  }
}