<?php
/**
 * @author Alexey Tatarivov <tatarinov@shogo.ru>
 * @link https://github.com/shogodev/argilla/
 * @copyright Copyright &copy; 2003-2014 Shogo
 * @license http://argilla.ru/LICENSE
 * @package frontend.components.auth
 */
class FWebUser extends CWebUser
{
  private $data = null;

  /**
   * @return User|null
   */
  public function getData()
  {
    if( !$this->isGuest && is_null($this->data) )
    {
      $this->data = User::model()->findByPk($this->getId());
    }

    return $this->data;
  }
}