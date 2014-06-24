<?php
/**
 * @author Alexey Tatarivov <tatarinov@shogo.ru>
 * @link https://github.com/shogodev/argilla/
 * @copyright Copyright &copy; 2003-2014 Shogo
 * @license http://argilla.ru/LICENSE
 * @package frontend.tests.unit.compontnts
 */
class FCollectionTest extends CTestCase
{
  public function setUp()
  {
    Yii::app()->setUnitEnvironment('Product', 'one', array('url' => 'new_product1'));

    parent::setUp();
  }

  public function testCreate()
  {
    $collection = new FCollection('basket');
    $this->assertInstanceOf('FCollection', $collection);
  }

  public function testAdd()
  {
    $collection = new FCollection('basket', array('color'), false);
    $collection->add(array(
      'id' => 1,
      'type' => 'product'
    ));
    $this->assertEquals($collection->count(), 1);

    $collection->add(array(
      'id' => 1,
      'amount' => 3,
      'type' => 'product'
    ));
    $this->assertEquals($collection->count(), 1);

    $collection->add(array(
      'id' => 2,
      'amount' => 2,
      'type' => 'product',
      'items' => array('color' => 'red')
    ));
    $this->assertEquals($collection->count(), 2);

    $collection->add(array(
      'id' => 2,
      'amount' => 2,
      'type' => 'product',
      'items' => array('color' => 'red')
    ));
    $this->assertEquals($collection->count(), 2);

    $collection->add(array(
      'id' => 2,
      'amount' => 2,
      'type' => 'product',
      'items' => array('color' => 'yellow')
    ));
    $this->assertEquals($collection->count(), 3);

    $collection->add(array(
      'id' => 4,
      'amount' => 2,
      'type' => 'product',
      'items' => array(
        'size' => '10',
        'options' => array(
          'id' => 3,
          'type' => 'product'
        )
      )
    ));
    $this->assertEquals($collection->count(), 4);

    $collection = new FCollection('basket', array(), false);

    $collection->add(array(
      'id' => 1,
      'amount' => 2,
      'type' => 'product',
      'items' => array(
        'size' => '10',
        'options' => array(
          array(
            'id' => 3,
            'type' => 'product'
          ),
          array(
            'id' => 1,
            'type' => 'product'
          ),
          array(
            'id' => 3,
            'type' => 'product'
          ),
        )
      )
    ));

    $this->assertEquals($collection->count(), 1);

    $collection = new FCollection('basket', array('options'), false);

    $collection->add(array(
      'id' => 1,
      'amount' => 2,
      'type' => 'product',
      'items' => array(
        'size' => '10',
        )
      )
    );

    $collection->add(array(
        'id' => 1,
        'amount' => 1,
        'type' => 'product',
        'items' => array(
          'size' => '20',
        )
      )
    );

    $this->assertEquals($collection->count(), 1);
  }

  /**
   * @expectedException CHttpException
   * @expectedExceptionMessage Ошибка! Не найдено поведение.
   */
  public function testAddNotWithBehavior()
  {
    $collection = new FCollection('basket', array('options'), false);
    $collection->add(array('id' => 1, 'type' => 'info'));
  }

  public function testGetElementByIndex()
  {
    $collection = new FCollection('basket', array(), false);
    $collection->add(array(
      'id' => 1,
      'type' => 'product'
    ));

    $collection->add(array(
      'id' => 2,
      'amount' => 3,
      'type' => 'product'
    ));

    $collection->add(array(
      'id' => 3,
      'amount' => 2,
      'type' => 'product'
    ));

    $this->assertEquals($collection[1]->collectionIndex, 1);
    $this->assertEquals($collection[1]->primaryKey, 2);
    $this->assertEquals($collection[1]->collectionAmount, 3);

    $collection = new FCollection('basket', array(), false);
    $collection->add(array(
      'id' => 1,
      'type' => 'product',
      'items' => array(
        'size' => '10',
        'options' => array(
          array(
            'id' => 3,
            'type' => 'product'
          ),
          array(
            'id' => 1,
            'type' => 'product'
          ),
          array(
            'id' => 3,
            'type' => 'product'
          ),
        )
      )
    ));

    $this->assertEquals($collection[4]->primaryKey, 1);
    $this->assertEquals($collection[4]->collectionItems['size'], 10);
    $this->assertEquals($collection[4]->collectionItems['options'][1]->primaryKey, 3);

    $collection = new FCollection('basket', array(), false);
    $collection->add(array(
      'id' => 1,
      'type' => 'product',
      'items' => array(
        'option' => array(
          'id' => 3,
          'type' => 'product'
        )
      )
    ));

    $this->assertEquals($collection[1]->primaryKey, 1);
    $this->assertEquals($collection[1]->collectionItems['option']->primaryKey, 3);
  }

  public function testCountAmount()
  {
    $collection = new FCollection('basket', array(), false);

    $collection->add(array(
      'id' => 1,
      'type' => 'product',
      'amount' => 2
    ));

    $collection->add(array(
      'id' => 3,
      'type' => 'product',
    ));

    $this->assertEquals($collection->countAmount(), 3);

    $index = $collection->add(array(
      'id' => 2,
      'amount' => 2,
      'type' => 'product',
      'items' => array(
        'size' => '10',
        'options' => array(
          array(
            'id' => 3,
            'type' => 'product'
          ),
          array(
            'id' => 1,
            'type' => 'product'
          ),
          array(
            'id' => 3,
            'type' => 'product'
          ),
        )
      )
    ));

    $this->assertEquals($collection->countAmount(), 5);

    $element = $collection[$index];

    $this->assertEquals($element->collectionItems['options']->countAmount(), 3);
  }

  public function testRemove()
  {
    $collection = new FCollection('basket');

    $collection->add(array(
      'id' => 1,
      'type' => 'product'
    ));

    $index = $collection->add(array(
      'id' => 2,
      'amount' => 3,
      'type' => 'product'
    ));

    $collection->add(array(
      'id' => 4,
      'amount' => 2,
      'type' => 'product',
      'items' => array('color' => 'red')
    ));

    $collection->add(array(
      'id' => 3,
      'amount' => 2,
      'type' => 'product',
      'items' => array(
        'size' => '10',
        'options' => array(
          array(
            'id' => 3,
            'type' => 'product'
          ),
          array(
            'id' => 1,
            'type' => 'product'
          ),
          array(
            'id' => 3,
            'type' => 'product'
          ),
        )
      )
    ));

    $this->assertEquals($collection->count(), 4);
    $collection->remove($index);
    $this->assertEquals($collection->count(), 3);
    $this->assertEquals($collection[7]->collectionItems['options']->count(), 2);
    $collection->remove(4);
    $this->assertEquals($collection[6]->collectionItems['options']->count(), 1);
    $this->assertEquals($collection->count(), 3);
  }

  public function testJsonSerialize()
  {
    $data = array(
      'id' => 2,
      'type' => 'product',
      'amount' => 2,
      'items' => array(
        'size' => '14',
        'options' => array(
          array(
            'id' => 1,
            'type' => 'product',
            'amount' => 2
          ),
          array(
            'id' => 3,
            'type' => 'product',
            'amount' => 1
          ),
        )
      )
    );

    $collection = new FCollection('basket');
    $collection->add($data);
    $this->assertEquals(json_decode(json_encode($collection), true)[0], $data);
  }

  public function testSave()
  {
    $collection = new FCollection('basket');

    $this->assertTrue(!isset($_SESSION['basket']));

    $collection->add(array(
      'id' => 1,
      'type' => 'product'
    ));

    $data = array(
      0 => array(
        'id' => 1,
        'type' => 'product',
        'amount' => 1,
      )
    );

    $this->assertEquals(json_decode($_SESSION['basket'], true), $data);

    $index = $collection->add(array(
      'id' => 2,
      'type' => 'product',
      'amount' => 2,
      'items' => array(
        'size' => '10',
        'options' => array(
          array(
            'id' => 3,
            'type' => 'product'
          ),
          array(
            'id' => 1,
            'type' => 'product'
          ),
          array(
            'id' => 3,
            'type' => 'product'
          ),
        )
      )
    ));

    $data = array(
      'id' => 2,
      'type' => 'product',
      'amount' => 2,
      'items' => array(
        'size' => '10',
        'options' => array(
          array(
            'id' => 3,
            'type' => 'product',
            'amount' => 2,
          ),
          array(
            'id' => 1,
            'type' => 'product',
            'amount' => 1,
          ),
        )
      )
    );

    $this->assertEquals(json_decode($_SESSION['basket'], true)[1], $data);

    $collection = new FCollection('basket');

    $collection->add(array(
      'id' => 3,
      'type' => 'product'
    ));

    $collection = new FCollection('basket');
    $this->assertEquals($collection->count(), 3);

    unset($_SESSION['basket']);
    $collection = new FCollection('basket');
    $collection->add(array(
      'id' => 2,
      'type' => 'product',
      'amount' => 2,
      'items' => array(
        'parameters' => array(
          '10' => '20',
          '14' => '17',
        ) ,
        'options' => array(
          array(
            'id' => 3,
            'type' => 'product'
          ),
          array(
            'id' => 1,
            'type' => 'product'
          ),
          array(
            'id' => 3,
            'type' => 'product'
          ),
        )
      )
    ));

    $this->assertEquals(json_decode($_SESSION['basket'], true)[0], array(
      'id' => 2,
      'type' => 'product',
      'amount' => 2,
      'items' => array(
        'parameters' => array(
          '10' => '20',
          '14' => '17',
        ) ,
        'options' => array(
          array(
            'id' => 3,
            'type' => 'product',
            'amount' => 2,
          ),
          array(
            'id' => 1,
            'type' => 'product',
            'amount' => 1,
          ),
        )
      )
    ));
  }

  public function testLoad()
  {
    $_SESSION['basket'] = json_encode(array(
      0 => array(
        'id' => 1,
        'type' => 'product',
        'amount' => 2,
        'params' => array()
      ),
      1 => array(
        'id' => 3,
        'type' => 'product',
        'amount' => 2,
        'items' => array(
          'size' => '10',
          'color' => array(
            'id' => 4,
            'type' => 'product',
          ),
          'options' => array(
            array(
              'id' => 3,
              'type' => 'product'
            ),
            array(
              'id' => 2,
              'type' => 'product'
            ),
            array(
              'id' => 3,
              'type' => 'product'
            ),
          )
        )
      )
    ));

    $productCollection = new FCollection('basket');

    $this->assertEquals($productCollection->count(), 2);

    $this->assertEquals($productCollection[6]->collectionItems['size'], '10');
    $this->assertEquals($productCollection[6]->collectionItems['color']->primaryKey, 4);


    $_SESSION['basket'] = json_encode(array(
      array(
        'id' => 2,
        'type' => 'product',
        'amount' => 2,
        'index' => 0,
        'items' => array(
          'parameters' => array(
            '10' => '20',
            '14' => '17',
          ) ,
          'options' => array(
            array(
              'id' => 3,
              'type' => 'product',
              'amount' => 2,
              'index' => 0,
              'items' => array()
            ),
            array(
              'id' => 1,
              'type' => 'product',
              'amount' => 1,
              'index' => 1,
              'items' => array()
            ),
          )
        )
      )
    ));

    $collection = new FCollection('basket');

    $this->assertEquals($collection[4]->collectionItems['parameters'], array(
      '10' => '20',
      '14' => '17',
    ));
  }

  public function testChangeAmount()
  {
    $collection = new FCollection('basket', array('size'));

    $collection->add(array(
      'id' => 1,
      'amount' => 2,
      'type' => 'product',
      'items' => array('size' => 3)
    ));

    $collection->add(array(
      'id' => 1,
      'amount' => 1,
      'type' => 'product',
      'items' => array('color' => 'red', 'size' => 3)
    ));

    $collection->add(array(
      'id' => 2,
      'amount' => 1,
      'type' => 'product',
      'items' => array('color' => 'red', 'size' => 3)
    ));

    $collection->add(array(
      'id' => 2,
      'amount' => 1,
      'type' => 'product',
      'items' => array('size' => 10)
    ));

    $collection->add(array(
      'id' => 3,
      'amount' => 2,
      'type' => 'product',
      'items' => array(
        'size' => '10',
        'options' => array(
          array(
            'id' => 3,
            'type' => 'product'
          ),
          array(
            'id' => 1,
            'type' => 'product'
          ),
          array(
            'id' => 3,
            'type' => 'product'
          ),
        )
      )
    ));

    $this->assertEquals($collection->countAmount(), 7);

    $collection->changeAmount(1, 1);

    $this->assertEquals($collection->countAmount(), 5);
    $this->assertEquals($collection->count(), 4);

    $collection->changeAmount(11, 3);
    $this->assertEquals($collection[13]->collectionItems['options']->countAmount(), 5);
  }

  public function testChangeItems()
  {
    $collection = new FCollection('basket', array('size'));

    $collection->add(array(
      'id' => 1,
      'amount' => 2,
      'type' => 'product',
      'items' => array('size' => 3)
    ));

    $this->assertEquals($collection[1]->collectionItems['size'], 3);

    $collection->changeItems(1, array('size' => 10, 'width' => 50));

    $this->assertEquals($collection[2]->collectionItems['size'], 10);
    $this->assertEquals($collection[2]->collectionItems['width'], 50);

    $collection->changeItems(2, array(
      'size' => 40,
      'options' =>  array(
        array(
          'id' => 2,
          'type' => 'product',
          'amount' => 3,
        )
      )
    ));

    $this->assertEquals($collection[3]->collectionItems['size'], 40);
    $this->assertEquals($collection[3]->collectionItems['options'][1]->primaryKey, 2);
  }

  public function testClear()
  {
    $collection = new FCollection('basket', array('color'), array('Product'), false);
    $collection->add(array(
      'id' => 1,
      'type' => 'product'
    ));

    $collection->add(array(
      'id' => 2,
      'amount' => 3,
      'type' => 'product'
    ));

    $collection->add(array(
      'id' => 3,
      'amount' => 2,
      'type' => 'product',
      'items' => array('color' => 'red')
    ));
    $this->assertEquals($collection->count(), 3);

    $collection->clear();

    $this->assertEquals($collection->count(), 0);
  }

  public function testIterator()
  {
    $collection = new FCollection('basket', array('color'), false);
    $collection->add(array(
      'id' => 1,
      'type' => 'product'
    ));
    $collection->add(array(
      'id' => 2,
      'amount' => 3,
      'type' => 'product',
      'items' => array(
        'options' => array(
          array(
          'id' => 3,
          'type' => 'product',
        )),
      )
    ));

    $keys = array('0', '3');

    $st = 0;
    foreach($collection as $key => $element)
    {
      $this->assertEquals($key, $keys[$st]);
      $st++;
    }

    foreach($collection[3]->collectionItems  as $key => $element)
    {
      $this->assertEquals($key, 'options');
    }
  }

  public function testIsInCollection()
  {
    $collection = new FCollection('basket', array(), false);
    $collection->add(array(
      'id' => 1,
      'type' => 'product'
    ));

    $collection->add(array(
      'id' => 2,
      'amount' => 3,
      'type' => 'product',
      'items' => array(
        'options' => array(
          array(
            'id' => 3,
            'type' => 'product',
          )
        ),
      )
    ));

    $this->assertTrue($collection->isInCollection('product', 2));

    $this->assertTrue($collection->isInCollection(array('id' => '3', 'type' => 'product')));

    $this->assertTrue($collection->isInCollection(new FCollectionElement( array('id' => '2', 'type' => 'product'))));

    $this->assertTrue($collection->isInCollection(Product::model()->findByPk(1)));

    $this->assertFalse($collection->isInCollection('product', 5));
  }
}