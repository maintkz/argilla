<?php
/**
 * @author Sergey Glagolev <glagolev@shogo.ru>
 * @link https://github.com/shogodev/argilla/
 * @copyright Copyright &copy; 2003-2013 Shogo
 * @license http://argilla.ru/LICENSE
 * @package backend.components.actions
 */
Yii::import('backend.modules.product.components.*');
Yii::import('backend.modules.product.models.*');
Yii::import('backend.modules.info.models.*');

class BSaveAssociationAction extends CAction
{
  public function run($src, $srcId, $dst)
  {
    if( Yii::app()->request->isAjaxRequest )
    {
      $ids   = Yii::app()->request->getPost('ids');
      $value = Yii::app()->request->getPost('value');

      /**
       * @var BActiveRecord $class
       * @var BActiveRecord $model
       */
      $class = new $src;
      $model = $class->findByPk($srcId);

      if( $model )
      {
        if( !$value )
        {
          BAssociation::model()->deleteAssociations($model, $dst, $ids);
          return;
        }

        BAssociation::model()->updateAssociations($model, $dst, !is_array($ids) ? array($ids) : $ids, !$value);
      }
    }
    else
      throw new CHttpException(500, 'Некорректный запрос.');
  }
}