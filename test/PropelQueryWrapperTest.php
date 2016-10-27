<?php

namespace Athens\Core\Test;

use Athens\Propel\ORMWrapper\PropelCollectionWrapper;
use Athens\Propel\ORMWrapper\PropelObjectWrapper;
use PHPUnit_Framework_TestCase;

use Athens\Core\ORMWrapper\ObjectWrapperInterface;
use Athens\Propel\ORMWrapper\PropelQueryWrapper;

use AthensTest\Map\TestClassTableMap;
use AthensTest\TestClassQuery;
use AthensTest\TestClassTwoQuery;
use AthensTest\ParentClassQuery;
use AthensTest\ChildClassQuery;

use AthensTest\TestClass;
use Propel\Runtime\Collection\ObjectCollection;

class PropelQueryWrapperTest extends PHPUnit_Framework_TestCase
{
    /** @var TestClassQuery */
    protected $testClassQuery;
    
    /** @var TestClass[] */
    protected $testClassInstances;

    public function setUp()
    {
        $this->testClassQuery = $this->createMock(TestClassQuery::class);
        
        $this->testClassInstances = [
            0 => $this->createMock(TestClass::class),
            1 => $this->createMock(TestClass::class),
        ];

        $instanceMap = [
            [0, $this->testClassInstances[0]],
            [1, $this->testClassInstances[1]],
            [2, null],
        ];
        
        $this->testClassQuery->method('getModelName')->willReturn('AthensTest\TestClass');
        $this->testClassQuery->method('getTableMap')->willReturn(new TestClassTableMap());
        $this->testClassQuery->method('findOneById')->willReturnMap($instanceMap);
        $this->testClassQuery->method('find')->willReturn(new ObjectCollection($this->testClassInstances));
        $this->testClassQuery->method('filterBy')->willReturn($this->testClassQuery);
        $this->testClassQuery->method('count')->willReturn(2);
        $this->testClassQuery->method('exists')->willReturn(true);
    }
    public function testGetPascalCasedObjectName()
    {
        $wrappedTestClassQuery = new PropelQueryWrapper(TestClassQuery::create());
        $wrappedTestClassTwoQuery = new PropelQueryWrapper(TestClassTwoQuery::create());
        $wrappedParentClassQuery = new PropelQueryWrapper(ParentClassQuery::create());
        $wrappedChildClassQuery = new PropelQueryWrapper(ChildClassQuery::create());

        $this->assertEquals("TestClass", $wrappedTestClassQuery->getPascalCasedObjectName());
        $this->assertEquals("TestClassTwo", $wrappedTestClassTwoQuery->getPascalCasedObjectName());
        $this->assertEquals("ParentClass", $wrappedParentClassQuery->getPascalCasedObjectName());
        $this->assertEquals("ChildClass", $wrappedChildClassQuery->getPascalCasedObjectName());
    }

    public function testGetTitleCasedObjectName()
    {
        $wrappedTestClassQuery = new PropelQueryWrapper(TestClassQuery::create());
        $wrappedTestClassTwoQuery = new PropelQueryWrapper(TestClassTwoQuery::create());
        $wrappedParentClassQuery = new PropelQueryWrapper(ParentClassQuery::create());
        $wrappedChildClassQuery = new PropelQueryWrapper(ChildClassQuery::create());

        $this->assertEquals("Test Class", $wrappedTestClassQuery->getTitleCasedObjectName());
        $this->assertEquals("Test Class Two", $wrappedTestClassTwoQuery->getTitleCasedObjectName());
        $this->assertEquals("Parent Class", $wrappedParentClassQuery->getTitleCasedObjectName());
        $this->assertEquals("Child Class", $wrappedChildClassQuery->getTitleCasedObjectName());
    }

    public function testGetQualifiedPascalCasedColumnNames()
    {
        $wrappedTestClassQuery = new PropelQueryWrapper(TestClassQuery::create());
        $wrappedTestClassTwoQuery = new PropelQueryWrapper(TestClassTwoQuery::create());
        $wrappedParentClassQuery = new PropelQueryWrapper(ParentClassQuery::create());
        $wrappedChildClassQuery = new PropelQueryWrapper(ChildClassQuery::create());

        /** TestClass */
        $this->assertEquals(
            [
                'TestClass.Id', 'TestClass.FieldSmallVarchar', 'TestClass.FieldLargeVarchar', 'TestClass.FieldInteger',
                'TestClass.FieldFloat', 'TestClass.FieldTimestamp', 'TestClass.FieldBoolean', 'TestClass.RequiredField',
                'TestClass.UnrequiredField'
            ],
            array_keys($wrappedTestClassQuery->getQualifiedPascalCasedColumnNames())
        );
        $this->assertEquals(
            [
                'TestClass.Id', 'TestClass.FieldSmallVarchar', 'TestClass.FieldLargeVarchar', 'TestClass.FieldInteger',
                'TestClass.FieldFloat', 'TestClass.FieldTimestamp', 'TestClass.FieldBoolean', 'TestClass.RequiredField',
                'TestClass.UnrequiredField'
            ],
            array_values($wrappedTestClassQuery->getQualifiedPascalCasedColumnNames())
        );

        /** TestClassTwo */
        $this->assertEquals(
            ['TestClassTwo.Id', 'TestClassTwo.FieldVarchar', 'TestClassTwo.TestClassId'],
            array_keys($wrappedTestClassTwoQuery->getQualifiedPascalCasedColumnNames())
        );
        $this->assertEquals(
            ['TestClassTwo.Id', 'TestClassTwo.FieldVarchar', 'TestClassTwo.TestClassId'],
            array_values($wrappedTestClassTwoQuery->getQualifiedPascalCasedColumnNames())
        );

        /** ParentClass */
        $this->assertEquals(
            ['ParentClass.Id', 'ParentClass.ParentData'],
            array_keys($wrappedParentClassQuery->getQualifiedPascalCasedColumnNames())
        );
        $this->assertEquals(
            ['ParentClass.Id', 'ParentClass.ParentData'],
            array_values($wrappedParentClassQuery->getQualifiedPascalCasedColumnNames())
        );
        
        /** ChildClass */
        $this->assertEquals(
            ['ChildClass.Id', 'ChildClass.ParentData', 'ChildClass.ChildData', 'ChildClass.ParentClassId'],
            array_keys($wrappedChildClassQuery->getQualifiedPascalCasedColumnNames())
        );
        $this->assertEquals(
            ['ChildClass.Id', 'ChildClass.ParentData', 'ChildClass.ChildData', 'ChildClass.ParentClassId'],
            array_values($wrappedChildClassQuery->getQualifiedPascalCasedColumnNames())
        );
    }

    public function testGetUnqualifiedPascalCasedColumnNames()
    {
        $wrappedTestClassQuery = new PropelQueryWrapper(TestClassQuery::create());
        $wrappedTestClassTwoQuery = new PropelQueryWrapper(TestClassTwoQuery::create());
        $wrappedParentClassQuery = new PropelQueryWrapper(ParentClassQuery::create());
        $wrappedChildClassQuery = new PropelQueryWrapper(ChildClassQuery::create());

        /** TestClass */
        $this->assertEquals(
            [
                'TestClass.Id', 'TestClass.FieldSmallVarchar', 'TestClass.FieldLargeVarchar', 'TestClass.FieldInteger',
                'TestClass.FieldFloat', 'TestClass.FieldTimestamp', 'TestClass.FieldBoolean', 'TestClass.RequiredField',
                'TestClass.UnrequiredField'
            ],
            array_keys($wrappedTestClassQuery->getUnqualifiedPascalCasedColumnNames())
        );
        $this->assertEquals(
            [
                'Id', 'FieldSmallVarchar', 'FieldLargeVarchar', 'FieldInteger', 'FieldFloat', 'FieldTimestamp',
                'FieldBoolean', 'RequiredField', 'UnrequiredField'
            ],
            array_values($wrappedTestClassQuery->getUnqualifiedPascalCasedColumnNames())
        );

        /** TestClassTwo */
        $this->assertEquals(
            ['TestClassTwo.Id', 'TestClassTwo.FieldVarchar', 'TestClassTwo.TestClassId'],
            array_keys($wrappedTestClassTwoQuery->getUnqualifiedPascalCasedColumnNames())
        );
        $this->assertEquals(
            ['Id', 'FieldVarchar', 'TestClassId'],
            array_values($wrappedTestClassTwoQuery->getUnqualifiedPascalCasedColumnNames())
        );

        /** ParentClass */
        $this->assertEquals(
            ['ParentClass.Id', 'ParentClass.ParentData'],
            array_keys($wrappedParentClassQuery->getUnqualifiedPascalCasedColumnNames())
        );
        $this->assertEquals(
            ['Id', 'ParentData'],
            array_values($wrappedParentClassQuery->getUnqualifiedPascalCasedColumnNames())
        );

        /** ChildClass */
        $this->assertEquals(
            ['ChildClass.Id', 'ChildClass.ParentData', 'ChildClass.ChildData', 'ChildClass.ParentClassId'],
            array_keys($wrappedChildClassQuery->getUnqualifiedPascalCasedColumnNames())
        );
        $this->assertEquals(
            ['Id', 'ParentData', 'ChildData', 'ParentClassId'],
            array_values($wrappedChildClassQuery->getUnqualifiedPascalCasedColumnNames())
        );
    }

    public function testGetQualifiedTitleCasedColumnNames()
    {
        $wrappedTestClassQuery = new PropelQueryWrapper(TestClassQuery::create());
        $wrappedTestClassTwoQuery = new PropelQueryWrapper(TestClassTwoQuery::create());
        $wrappedParentClassQuery = new PropelQueryWrapper(ParentClassQuery::create());
        $wrappedChildClassQuery = new PropelQueryWrapper(ChildClassQuery::create());

        /** TestClass */
        $this->assertEquals(
            [
                'TestClass.Id', 'TestClass.FieldSmallVarchar', 'TestClass.FieldLargeVarchar', 'TestClass.FieldInteger',
                'TestClass.FieldFloat', 'TestClass.FieldTimestamp', 'TestClass.FieldBoolean', 'TestClass.RequiredField',
                'TestClass.UnrequiredField'
            ],
            array_keys($wrappedTestClassQuery->getQualifiedTitleCasedColumnNames())
        );
        $this->assertEquals(
            [
                'Test Class Id', 'Test Class Field Small Varchar', 'Test Class Field Large Varchar',
                'Test Class Field Integer', 'Test Class Field Float', 'Test Class Field Timestamp',
                'Test Class Field Boolean', 'Test Class Required Field', 'Test Class Unrequired Field'
            ],
            array_values($wrappedTestClassQuery->getQualifiedTitleCasedColumnNames())
        );

        /** TestClassTwo */
        $this->assertEquals(
            ['TestClassTwo.Id', 'TestClassTwo.FieldVarchar', 'TestClassTwo.TestClassId'],
            array_keys($wrappedTestClassTwoQuery->getQualifiedTitleCasedColumnNames())
        );
        $this->assertEquals(
            ['Test Class Two Id', 'Test Class Two Field Varchar', 'Test Class Two Test Class Id'],
            array_values($wrappedTestClassTwoQuery->getQualifiedTitleCasedColumnNames())
        );

        /** ParentClass */
        $this->assertEquals(
            ['ParentClass.Id', 'ParentClass.ParentData'],
            array_keys($wrappedParentClassQuery->getQualifiedTitleCasedColumnNames())
        );
        $this->assertEquals(
            ['Parent Class Id', 'Parent Class Parent Data'],
            array_values($wrappedParentClassQuery->getQualifiedTitleCasedColumnNames())
        );

        /** ChildClass */
        $this->assertEquals(
            ['ChildClass.Id', 'ChildClass.ParentData', 'ChildClass.ChildData', 'ChildClass.ParentClassId'],
            array_keys($wrappedChildClassQuery->getQualifiedTitleCasedColumnNames())
        );
        $this->assertEquals(
            ['Child Class Id', 'Child Class Parent Data', 'Child Class Child Data', 'Child Class Parent Class Id'],
            array_values($wrappedChildClassQuery->getQualifiedTitleCasedColumnNames())
        );
    }

    public function testGetUnqualifiedTitleCasedColumnNames()
    {
        $wrappedTestClassQuery = new PropelQueryWrapper(TestClassQuery::create());
        $wrappedTestClassTwoQuery = new PropelQueryWrapper(TestClassTwoQuery::create());
        $wrappedParentClassQuery = new PropelQueryWrapper(ParentClassQuery::create());
        $wrappedChildClassQuery = new PropelQueryWrapper(ChildClassQuery::create());

        /** TestClass */
        $this->assertEquals(
            [
                'TestClass.Id', 'TestClass.FieldSmallVarchar', 'TestClass.FieldLargeVarchar', 'TestClass.FieldInteger',
                'TestClass.FieldFloat', 'TestClass.FieldTimestamp', 'TestClass.FieldBoolean', 'TestClass.RequiredField',
                'TestClass.UnrequiredField'
            ],
            array_keys($wrappedTestClassQuery->getUnqualifiedTitleCasedColumnNames())
        );
        $this->assertEquals(
            [
                'Id', 'Field Small Varchar', 'Field Large Varchar', 'Field Integer', 'Field Float', 'Field Timestamp',
                'Field Boolean', 'Required Field', 'Unrequired Field'
            ],
            array_values($wrappedTestClassQuery->getUnqualifiedTitleCasedColumnNames())
        );

        /** TestClassTwo */
        $this->assertEquals(
            ['TestClassTwo.Id', 'TestClassTwo.FieldVarchar', 'TestClassTwo.TestClassId'],
            array_keys($wrappedTestClassTwoQuery->getUnqualifiedTitleCasedColumnNames())
        );
        $this->assertEquals(
            ['Id', 'Field Varchar', 'Test Class Id'],
            array_values($wrappedTestClassTwoQuery->getUnqualifiedTitleCasedColumnNames())
        );

        /** ParentClass */
        $this->assertEquals(
            ['ParentClass.Id', 'ParentClass.ParentData'],
            array_keys($wrappedParentClassQuery->getUnqualifiedTitleCasedColumnNames())
        );
        $this->assertEquals(
            ['Id', 'Parent Data'],
            array_values($wrappedParentClassQuery->getUnqualifiedTitleCasedColumnNames())
        );

        /** ChildClass */
        $this->assertEquals(
            ['ChildClass.Id', 'ChildClass.ParentData', 'ChildClass.ChildData', 'ChildClass.ParentClassId'],
            array_keys($wrappedChildClassQuery->getUnqualifiedTitleCasedColumnNames())
        );
        $this->assertEquals(
            ['Id', 'Parent Data', 'Child Data', 'Parent Class Id'],
            array_values($wrappedChildClassQuery->getUnqualifiedTitleCasedColumnNames())
        );
    }

    public function testFindOneByPrimaryKeySuccess()
    {
        $wrappedTestClassQuery = new PropelQueryWrapper($this->testClassQuery);

        $id = 0;

        $this->testClassQuery
            ->expects($this->once())
            ->method('findOneById')
            ->with(
                $this->equalTo($id)
            );

        $result = $wrappedTestClassQuery->findOneByPrimaryKey($id);

        $this->assertInstanceOf(ObjectWrapperInterface::class, $result);
    }

    public function testFindOneByPrimaryKeyFailure()
    {
        $wrappedTestClassQuery = new PropelQueryWrapper($this->testClassQuery);

        $id = 2;

        $this->testClassQuery
            ->expects($this->once())
            ->method('findOneById')
            ->with(
                $this->equalTo($id)
            );

        $result = $wrappedTestClassQuery->findOneByPrimaryKey($id);

        $this->assertNull($result);
    }

    public function testFind()
    {
        $wrappedTestClassQuery = new PropelQueryWrapper($this->testClassQuery);

        $this->testClassQuery
            ->expects($this->once())
            ->method('find');

        $result = $wrappedTestClassQuery->find();

        $this->assertInstanceOf(PropelCollectionWrapper::class, $result);
        $this->assertEquals(2, $result->count());
    }

    public function testOrderBy()
    {
        $wrappedTestClassQuery = new PropelQueryWrapper($this->testClassQuery);

        $columnName = (string)rand();
        $order = (string)rand();

        $this->testClassQuery
            ->expects($this->once())
            ->method('orderBy')
            ->with(
                $this->equalTo($columnName),
                $this->equalTo($order)
            );

        $result = $wrappedTestClassQuery->orderBy($columnName, $order);

        $this->assertInstanceOf(PropelQueryWrapper::class, $result);
    }

    public function testFilterBy()
    {
        $wrappedTestClassQuery = new PropelQueryWrapper($this->testClassQuery);

        $columnName = "FieldInteger";
        $order = (string)rand();

        $this->testClassQuery
            ->expects($this->once())
            ->method('filterBy' . $columnName)
            ->with(
                $this->equalTo($order)
            );

        $result = $wrappedTestClassQuery->filterBy($columnName, $order);

        $this->assertInstanceOf(PropelQueryWrapper::class, $result);
    }

    public function testOffset()
    {
        $wrappedTestClassQuery = new PropelQueryWrapper($this->testClassQuery);

        $offset = rand();

        $this->testClassQuery
            ->expects($this->once())
            ->method('offset')
            ->with(
                $this->equalTo($offset)
            );

        $result = $wrappedTestClassQuery->offset($offset);

        $this->assertInstanceOf(PropelQueryWrapper::class, $result);
    }

    public function testLimit()
    {
        $wrappedTestClassQuery = new PropelQueryWrapper($this->testClassQuery);

        $limit = rand();

        $this->testClassQuery
            ->expects($this->once())
            ->method('limit')
            ->with(
                $this->equalTo($limit)
            );

        $result = $wrappedTestClassQuery->limit($limit);

        $this->assertInstanceOf(PropelQueryWrapper::class, $result);
    }

    public function testCreateObject()
    {
        $wrappedTestClassQuery = new PropelQueryWrapper($this->testClassQuery);

        $result = $wrappedTestClassQuery->createObject();

        $this->assertInstanceOf(PropelObjectWrapper::class, $result);
    }

    public function testCount()
    {
        $wrappedTestClassQuery = new PropelQueryWrapper($this->testClassQuery);

        $this->testClassQuery
            ->expects($this->once())
            ->method('count');

        $result = $wrappedTestClassQuery->count();

        $this->assertEquals(2, $result);
    }

    public function testExists()
    {
        $wrappedTestClassQuery = new PropelQueryWrapper($this->testClassQuery);

        $this->testClassQuery
            ->expects($this->once())
            ->method('exists');

        $result = $wrappedTestClassQuery->exists();

        $this->assertEquals(true, $result);
    }
}
