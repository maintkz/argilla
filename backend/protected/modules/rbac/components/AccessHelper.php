<?php
/**
 * @author Nikita Melnikov <melnikov@shogo.ru>
 * @link https://github.com/shogodev/argilla/
 * @copyright Copyright &copy; 2003-2014 Shogo
 * @license http://argilla.ru/LICENSE
 * @package backend.modules.rbac.components
 *
 * @example
 * В контроллере, доступ на который необходимо проверить
 * <pre>
 *  $access = new AccessHelper();
 *  $access->checkAccess();
 * </pre>
 *
 * Будет происходить проверка по шаблону module:controller
 *
 * Для проверки на доступ к текущему экшену:
 * @example
 * <pre>
 *  $access = new AccessHelper();
 *  $access->checkAccess(true);
 * </pre>
 *
 *
 * Для того, чтобы проверить удаленный контроллер
 * @example
 * <pre>
 *  $access = new AccessHelper($module, $controller, $action)
 * </pre>
 *
 * Так же можно получить объект класса через статический метод init()
 *
 * @example
 * <pre>
 *  AccessHelper::init($module, $controller, $action)->checkAccess();
 * </pre>
 */
class AccessHelper
{
  /**
   * Массив со стандартными названием действий в контроллерах
   * и их человеко-понятным названием
   *
   * @var array
   */
  public $baseActionNames = array(
    'index'  => 'Разводная',
    'update' => 'Обновление',
    'create' => 'Создание',
    'delete' => 'Удаление',
    'view'   => 'Просмотр',
  );

  /**
   * Модуль
   *
   * @var string
   */
  private $module;

  /**
   * Контроллер
   *
   * @var string
   */
  private $controller;

  /**
   * Экшен для контроллера
   *
   * @var string
   */
  private $action;

  /**
   * Название задачи
   *
   * module:controller
   *
   * @var string
   */
  private $taskName = '';

  /**
   * Название операции
   *
   * module:controller:action
   *
   * @var string
   */
  private $operationName = '';

  /**
   * Массив с исключениями, по которым не проверяется доступ
   *
   * @var array
   */
  private $excludes = array('base:error', 'base', 'help:help');

  /**
   * Задача входа пользователя в систему
   *
   * @var string
   */
  private $loginOperation = 'base:login';

  private $taskHumanityName = '';

  private $operationHumanityName = '';

  private static $moduleList;

  private static $childList;

  private static $assignments;

  /**
   * @param null $module
   * @param null $controller
   * @param null $action
   */
  public function __construct($module = null, $controller = null, $action = null)
  {
    if( $this->initProperties($module, $controller, $action) )
    {
      $this->createTaskName();
      $this->createOperationName();
    }
  }

  /**
   * @return string
   */
  public function getTaskName()
  {
    return $this->taskName;
  }

  /**
   * @return string
   */
  public function getOperationName()
  {
    return $this->operationName;
  }

  /**
   * @param string|null $module
   * @param string|null $controller
   * @param string|null $action
   *
   * @return AccessHelper
   */
  public static function init($module = null, $controller = null, $action = null)
  {
    return new AccessHelper($module, $controller, $action);
  }

  public static function filterModulesByAccess($modules)
  {
    $allowedModules = array();
    $assignments = self::getAssignments(Yii::app()->user->id);
    $childList = self::getChildList();

    foreach($assignments as $name => $assignment)
    {
      if( !isset($childList[$name]) )
        continue;

      foreach($modules as $module => $moduleData )
      {
        if( isset($allowedModules[$module]) )
          continue;

        $tasks = self::moduleList($module);
        if( array_intersect($tasks, Yii::app()->authManager->defaultRoles) || array_intersect($tasks, $childList[$name]) )
        {
          $allowedModules[$module] = $moduleData;
        }
      }
    }

    return $allowedModules;
  }

  /**
   * Проверка на доступ к текущему указанному контроллеру
   * Для проверки доступа по action необходимо установить флаг $useOperations в TRUE
   *
   * @param boolean $useOperation
   *
   * @return boolean
   */
  public function checkAccess($useOperation = false)
  {
    if( $this->loginOperation === $this->operationName )
      return true;

    if( self::isServerDev() && !Yii::app()->user->isGuest )
      return true;

    if( !$useOperation )
      return $this->checkTaskAccess();
    else
    {
      $this->checkTaskAccess(); // create task
      return $this->checkOperationAccess();
    }
  }

  /**
   * Проверка на расположение сервера
   *
   * @return boolean
   */
  public static function isServerDev()
  {
    return BDevServerAuthConfig::getInstance()->isAvailable();
  }

  public static function moduleList($module = null)
  {
    if( is_null(self::$moduleList) )
    {
      foreach(BRbacTask::getTasks() as $task => $title)
      {
        $delimiterPosition = strpos($task, ':');
        if( $delimiterPosition !== false )
          $moduleName = substr($task, 0, $delimiterPosition);
        else
          $moduleName = $task;

        self::$moduleList[$moduleName][$task] = $task;
      }
    }

    if( !$module )
      return self::$moduleList;

    if( isset(self::$moduleList[$module]) )
      return self::$moduleList[$module];

    return array();
  }

  public static function getChildList()
  {
    if( !is_null(self::$childList) )
      return self::$childList;

    $criteria = new CDbCriteria();
    $command = Yii::app()->db->commandBuilder->createFindCommand(Yii::app()->authManager->itemChildTable, $criteria);

    self::$childList = array();
    foreach($command->queryAll() as $item)
      self::$childList[$item['parent']][$item['child']] = $item['child'];

    return self::$childList;
  }

  public static function getAssignments($userId)
  {
    if( isset(self::$assignments[$userId]) )
      return self::$assignments[$userId];

    self::$assignments[$userId] = Yii::app()->authManager->getAuthAssignments($userId);

    return self::$assignments[$userId];
  }

  public static function getControllerTaskName(BModule $module, $controllerName)
  {
    foreach($module->controllerMap as $controllerMappedId => $controllerMappedName)
    {
      if( $controllerMappedName == $controllerName )
        return $controllerMappedId;
    }

    return null;
  }

  public static function clearCache()
  {
    self::$moduleList = null;
    self::$childList = null;
    self::$assignments = null;
  }

  /**
   * Проверка доступа по операции (контроллер->экшен)
   *
   * @return boolean
   */
  protected function checkOperationAccess()
  {
    if( in_array($this->operationName, $this->excludes) )
      return true;

    if( BRbacOperation::operationExists($this->operationName) )
      return Yii::app()->user->checkAccess($this->operationName, array('userId' => Yii::app()->user->id));
    else
    {
      $this->createOperation();
      $this->fillAccessData();

      return false;
    }
  }

  /**
   * Создание системного имени задачи
   */
  protected function createTaskName()
  {
    if( !empty($this->module) )
      $this->taskName = $this->module . ':';

    $this->taskName .= $this->controller;
  }

  /**
   * Создание системного имени операции
   */
  protected function createOperationName()
  {
    if( $this->action )
      $this->operationName = $this->taskName . ':' . $this->action;
  }

  /**
   * Инициализация параметров для проверки доступа
   *
   * @param string $module
   * @param string $controller
   * @param string $action
   *
   * @return bool
   */
  private function initProperties($module, $controller, $action)
  {
    if( $module === null || $controller === null )
    {
      if( !empty(Yii::app()->controller->module->id) )
      {
        $module = Yii::app()->controller->module;
        if( !$module->enabled || !Yii::app()->controller->enabled )
          return false;

        $this->module = $module->getName();
        $this->controller = array_search(get_class(Yii::app()->controller), $module->controllerMap);

        $this->taskHumanityName = $module->name . '-' . Yii::app()->controller->name;
      }
      else
      {
        if( !Yii::app()->controller->enabled )
          return false;

        $this->controller = Yii::app()->controller->id;
        $this->taskHumanityName = Yii::app()->controller->name;
      }

      $this->action = Yii::app()->controller->action->id;
    }
    else
    {
      $this->module = $module;
      $this->controller = $controller;
      $this->action = $action;
    }

    $this->operationHumanityName = $this->taskHumanityName;

    if( $this->action )
      $this->operationHumanityName .= '-'.(isset($this->baseActionNames[$this->action]) ? $this->baseActionNames[$this->action] : $this->action);

    return true;
  }

  /**
   * Создание новой записи задачи
   */
  private function createTask()
  {
    $task = new BRbacTask();
    $task->title = $this->taskHumanityName;
    $task->name = $this->taskName;
    $task->save(false);
  }

  /**
   * Создание новой записи операции
   */
  private function createOperation()
  {
    $operation = new BRbacOperation;
    $operation->title = $this->operationHumanityName;
    $operation->name  = $this->operationName;
    $operation->save(false);
  }

  /**
   * Добавление новых операции в существующую задачу
   */
  private function fillAccessData()
  {
    if( !empty($this->operationName) )
    {
      $parts  = explode(':', $this->operationName);
      $parent = $parts[0] . ':' . $parts[1];

      if( $parent == $this->taskName )
      {
        if( !BRbacTask::taskExists($this->taskName) )
          $this->createTask();

        if( !BRbacOperation::operationExists($this->operationName) )
          $this->createOperation();

        Yii::app()->authManager->addItemChild($this->taskName, $this->operationName);
      }
    }
  }

  /**
   * Проверка доступа по задаче (контроллеру)
   *
   * @return boolean
   */
  private function checkTaskAccess()
  {
    if( in_array($this->taskName, $this->excludes) )
      return true;

    if( BRbacTask::taskExists($this->taskName) )
      return BRbacTask::checkTask($this->taskName, Yii::app()->user->id);
    else
    {
      $this->createTask();
      $this->fillAccessData();
      return false;
    }
  }
}