<?php
/**
 * @author Sergey Glagolev <glagolev@shogo.ru>
 * @link https://github.com/shogodev/argilla/
 * @copyright Copyright &copy; 2003-2014 Shogo
 * @license http://argilla.ru/LICENSE
 * @package backend.components
 *
 * Класс для работы с модулями бэкенда.
 * Методы используются для построения меню групп, для построения меню отдельных групп
 */
Yii::import('backend.modules.brac.components.AccessHelper');
Yii::import('backend.modules.brac.models');

class BMenu extends CComponent
{
  public $groupNames = array(
    'content'  => 'Контент',
    'seo'      => 'SEO',
    'settings' => 'Настройки',
    'help'     => 'Помощь',
  );

  private $groups = array();

  private $modules;

  private $submodules = array();

  /**
   * @var BController
   */
  private $controller;

  /**
   * @var BModule
   */
  private $currentModule;

  /**
   * @var string
   */
  private $currentGroup;

  public function init()
  {
    $this->controller = Yii::app()->controller;
    $this->currentModule = $this->controller->module;
    if( $this->currentModule && !empty($this->currentModule->group) )
      $this->currentGroup = $this->currentModule->group;

    $this->buildStructure(Yii::app()->getModules());

    $this->sort();
  }

  public function getGroups()
  {
    return $this->groups;
  }

  public function getModules($hideOneModule = true)
  {
    $modules = Arr::get($this->modules, $this->currentGroup, array());
    return count($modules) < 2 && $hideOneModule ? array() : $modules;
  }

  /**
   * @param bool $hideOneController - не строим подменю, если контроллер единственный
   *
   * @return array
   */
  public function getSubmodules($hideOneController = true)
  {
    return count($this->submodules) < 2 && $hideOneController ? array() : $this->submodules;
  }

  public function getDefaultRout($default = '')
  {
    if( $groups = $this->getGroups() )
    {
      return Arr::reset($groups)['route'];
    }

    return $default;
  }

  /**
   * @param array $modules
   * @param BModule $parent|null
   * @throws CException
   */
  private function buildStructure(array $modules, $parent = null)
  {
    $filteredModules = AccessHelper::filterModulesByAccess($modules);

    foreach($filteredModules as $moduleId => $moduleConfig)
    {
      if( empty($moduleConfig['autoloaded']) || $moduleConfig['autoloaded'] == false )
        continue;

      Yii::import($moduleConfig['class']);
      $moduleClassName = ucfirst($moduleId).'Module';
      /**
       * @var BModule $module
       */
      $module = new $moduleClassName($moduleId, $parent);
      if( !$this->allowedModule($module) )
        continue;

      if( $this->isModuleActive($module) )
      {
        $this->createSubmodulesMenu($module);
      }

      if( $this->needCreateModulesMenu($module) )
      {
        if( $this->createGroupsMenu($module) )
        {
          if( !$this->createFakeModulesMenu($module) )
            $this->createModulesMenu($module);
        }
      }

      if( !empty($moduleConfig['modules']) )
      {
        $module->setModules($moduleConfig['modules']);
        $this->buildStructure($module->getModules(), $module);
      }
    }
  }

  /**
   * @param BModule $module
   *
   * @return bool
   */
  private function isModuleActive(BModule $module)
  {
    if( !$this->currentModule )
      return false;

    if( $this->currentModule->getName() == $module->getName() )
      return true;

    if( $currentModuleParents = $this->currentModule->getParents() )
    {
      if( isset($currentModuleParents[$module->getName()]) )
        return true;
    }

    if( $parents = $module->getParents() )
    {
      if( isset($parents[$this->currentModule->getName()]) )
        return true;
    }

    if( array_intersect(array_keys($currentModuleParents), array_keys($parents)))
      return true;

    return false;
  }

  /**
   * @param BModule $module
   *
   * @return bool
   */
  private function needCreateModulesMenu(BModule $module)
  {
    return !$module->getParentModule();
  }

  /**
   * @param BModule $module
   *
   * @return bool
   */
  private function createGroupsMenu($module)
  {
    if( !isset($this->groups[$module->group]) )
    {
      if( !$mappedControllerId = $this->getAllowedControllerId($module) )
        return false;

      $this->groups[$module->group] = array(
        'label' => Arr::get($this->groupNames, $module->group, $module->group),
        'url' => $module->createUrl($mappedControllerId),
        'route' => implode('/', array($module->getName(),  $mappedControllerId)),
        'active' => $this->currentGroup == $module->group,
        'itemOptions' => array('class' => $module->group)
      );
    }

    return true;
  }

  /**
   * @param BModule $module
   */
  private function createModulesMenu($module)
  {
    $this->modules[$module->group][$module->getName()] = array(
      'label' => $module->name,
      'url' => $module->createUrl('/'),
      'active' => $this->isModuleActive($module),
      'itemOptions' => array('class' => $module->id),
      'position' => $module->position,
      'module' => $module
    );
  }

  /**
   * Добавляет в меню виртуальные контроллеры модуля
   *
   * @param BModule $module
   *
   * @return bool
   * @throws CHttpException
   */
  private function createFakeModulesMenu($module)
  {
    if( $fakeMenu = $module->getMenuControllers() )
    {
      foreach($fakeMenu as $key => $menuItem)
      {
        $subMenu = Arr::cut($menuItem, 'menu', array());

        foreach($subMenu as $controllerName)
        {
          if( !$controllerMappedId = AccessHelper::getControllerTaskName($module, $controllerName.'Controller') )
            throw new CHttpException(500, $controllerName.'Controller не найден в controllerMap модуля '.$module->getName() );

          if(  AccessHelper::init($module->getName(), $controllerMappedId)->checkAccess() )
          {
            if( !isset($this->modules[$module->group][$key]) )
            {
              $this->modules[$module->group][$key] = $menuItem;
              $this->modules[$module->group][$key]['url'] = $module->createUrl($controllerMappedId);
            }

            $this->modules[$module->group][$key]['menu'][$controllerMappedId] = $controllerName;
          }
        }
      }

      if( isset($this->controller->moduleMenu) )
        $this->modules[$module->group][$this->controller->moduleMenu]['active'] = true;

      return true;
    }
  }

  /**
   * @param BModule $module
   *
   * @return array
   */
  private function createSubmodulesMenu($module)
  {
    foreach($module->controllerMap as $mappedId => $controllerClass)
    {
      $id = $mappedId ? : BApplication::cutClassPrefix($controllerClass);

      if( !AccessHelper::init($module->id, $id)->checkAccess() )
        continue;

      /**
       * @var BController $controller
       */
      $controller = new $controllerClass($id, null);

      // Убираем ненужные виртуальные контроллеры, которые уже отобразились в меню
      if( $fakeControllers = $module->getMenuControllers() )
      {
        if( !(isset($controller->moduleMenu, $this->controller->moduleMenu) && in_array(BApplication::CLASS_PREFIX.ucfirst($controller->id), $fakeControllers[$this->controller->moduleMenu]['menu'])) )
          continue;
      }

      if( !isset($controller->enabled) || $controller->enabled === true )
      {
        $this->submodules[$id] = array(
          'label' => $controller->name,
          'url' => $module->createUrl($controller->id),
          'active' => $this->controller->id === $id || ucfirst($this->controller->id) === BApplication::CLASS_PREFIX.ucfirst($id),
          'position' => $controller->position,
          'itemOptions' => array('class' => $id),
        );
      }
    }
  }

  private function getAllowedControllerId(BModule $module)
  {
    $controllerMappedId = AccessHelper::getControllerTaskName($module, $module->defaultController.'Controller');

    if( $controllerMappedId && AccessHelper::init($module->getName(), $controllerMappedId)->checkAccess() )
      return $controllerMappedId;

    foreach($module->controllerMap as $controllerMappedId => $controller)
    {
      if( AccessHelper::init($module->getName(), $controllerMappedId)->checkAccess() )
        return $controllerMappedId;
    }

    return null;
  }

  private function sort()
  {
    $this->sortGroups();
    $this->sortModules();
    $this->sortSubmodiles();
  }

  private function sortGroups()
  {
  }

  private function sortModules()
  {
    if( empty($this->modules) )
      return;
    foreach($this->modules as $key => $data)
    {
      uasort($this->modules[$key], function($a, $b) {
        return $a['label'] > $b['label'];
      });
    }
  }

  private function sortSubmodiles()
  {
    uasort($this->submodules, function($a, $b) {
      return $a['position'] > $b['position'];
    });
  }

  /**
   * @param BModule $module
   *
   * @return bool
   */
  private function allowedModule($module)
  {
    return $module instanceof BModule && !empty($module->group) && $module->enabled;
  }
}