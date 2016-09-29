<?php

namespace Athens\Core\Test;

use PHPUnit_Framework_TestCase;

use Athens\Propel\ORMWrapper\PropelObjectWrapper;

use AthensTest\TestClass;
use AthensTest\TestClassTwo;
use AthensTest\ParentClass;
use AthensTest\ChildClass;

class PropelObjectWrapperTest extends PHPUnit_Framework_TestCase
{
    public function testGetPascalCasedObjectName()
    {
        $wrappedTestClass = new PropelObjectWrapper(new TestClass());
        $wrappedTestClassTwo = new PropelObjectWrapper(new TestClassTwo());
        $wrappedParentClass = new PropelObjectWrapper(new ParentClass());
        $wrappedChildClass = new PropelObjectWrapper(new ChildClass());

        $this->assertEquals("TestClass", $wrappedTestClass->getPascalCasedObjectName());
        $this->assertEquals("TestClassTwo", $wrappedTestClassTwo->getPascalCasedObjectName());
        $this->assertEquals("ParentClass", $wrappedParentClass->getPascalCasedObjectName());
        $this->assertEquals("ChildClass", $wrappedChildClass->getPascalCasedObjectName());
    }

    public function testGetTitleCasedObjectName()
    {
        $wrappedTestClass = new PropelObjectWrapper(new TestClass());
        $wrappedTestClassTwo = new PropelObjectWrapper(new TestClassTwo());
        $wrappedParentClass = new PropelObjectWrapper(new ParentClass());
        $wrappedChildClass = new PropelObjectWrapper(new ChildClass());

        $this->assertEquals("Test Class", $wrappedTestClass->getTitleCasedObjectName());
        $this->assertEquals("Test Class Two", $wrappedTestClassTwo->getTitleCasedObjectName());
        $this->assertEquals("Parent Class", $wrappedParentClass->getTitleCasedObjectName());
        $this->assertEquals("Child Class", $wrappedChildClass->getTitleCasedObjectName());
    }

    public function testGetQualifiedPascalCasedColumnNames()
    {
        $wrappedTestClass = new PropelObjectWrapper(new TestClass());
        $wrappedTestClassTwo = new PropelObjectWrapper(new TestClassTwo());
        $wrappedParentClass = new PropelObjectWrapper(new ParentClass());
        $wrappedChildClass = new PropelObjectWrapper(new ChildClass());

        /** TestClass */
        $this->assertEquals(
            ['TestClass.Id', 'TestClass.FieldSmallVarchar', 'TestClass.FieldLargeVarchar', 'TestClass.FieldInteger', 'TestClass.FieldFloat', 'TestClass.FieldTimestamp', 'TestClass.FieldBoolean', 'TestClass.RequiredField', 'TestClass.UnrequiredField'],
            array_keys($wrappedTestClass->getQualifiedPascalCasedColumnNames())
        );
        $this->assertEquals(
            ['TestClass.Id', 'TestClass.FieldSmallVarchar', 'TestClass.FieldLargeVarchar', 'TestClass.FieldInteger', 'TestClass.FieldFloat', 'TestClass.FieldTimestamp', 'TestClass.FieldBoolean', 'TestClass.RequiredField', 'TestClass.UnrequiredField'],
            array_values($wrappedTestClass->getQualifiedPascalCasedColumnNames())
        );

        /** TestClassTwo */
        $this->assertEquals(
            ['TestClassTwo.Id', 'TestClassTwo.FieldVarchar', 'TestClassTwo.TestClassId'],
            array_keys($wrappedTestClassTwo->getQualifiedPascalCasedColumnNames())
        );
        $this->assertEquals(
            ['TestClassTwo.Id', 'TestClassTwo.FieldVarchar', 'TestClassTwo.TestClassId'],
            array_values($wrappedTestClassTwo->getQualifiedPascalCasedColumnNames())
        );

        /** ParentClass */
        $this->assertEquals(
            ['ParentClass.Id', 'ParentClass.ParentData'],
            array_keys($wrappedParentClass->getQualifiedPascalCasedColumnNames())
        );
        $this->assertEquals(
            ['ParentClass.Id', 'ParentClass.ParentData'],
            array_values($wrappedParentClass->getQualifiedPascalCasedColumnNames())
        );
        
        /** ChildClass */
        $this->assertEquals(
            ['ChildClass.Id', 'ChildClass.ParentData', 'ChildClass.ChildData', 'ChildClass.ParentClassId'],
            array_keys($wrappedChildClass->getQualifiedPascalCasedColumnNames())
        );
        $this->assertEquals(
            ['ChildClass.Id', 'ChildClass.ParentData', 'ChildClass.ChildData', 'ChildClass.ParentClassId'],
            array_values($wrappedChildClass->getQualifiedPascalCasedColumnNames())
        );
    }

    public function testUnqualifiedPascalCasedColumnNames()
    {
        $wrappedTestClass = new PropelObjectWrapper(new TestClass());
        $wrappedTestClassTwo = new PropelObjectWrapper(new TestClassTwo());
        $wrappedParentClass = new PropelObjectWrapper(new ParentClass());
        $wrappedChildClass = new PropelObjectWrapper(new ChildClass());

        /** TestClass */
        $this->assertEquals(
            ['TestClass.Id', 'TestClass.FieldSmallVarchar', 'TestClass.FieldLargeVarchar', 'TestClass.FieldInteger', 'TestClass.FieldFloat', 'TestClass.FieldTimestamp', 'TestClass.FieldBoolean', 'TestClass.RequiredField', 'TestClass.UnrequiredField'],
            array_keys($wrappedTestClass->getUnqualifiedPascalCasedColumnNames())
        );
        $this->assertEquals(
            ['Id', 'FieldSmallVarchar', 'FieldLargeVarchar', 'FieldInteger', 'FieldFloat', 'FieldTimestamp', 'FieldBoolean', 'RequiredField', 'UnrequiredField'],
            array_values($wrappedTestClass->getUnqualifiedPascalCasedColumnNames())
        );

        /** TestClassTwo */
        $this->assertEquals(
            ['TestClassTwo.Id', 'TestClassTwo.FieldVarchar', 'TestClassTwo.TestClassId'],
            array_keys($wrappedTestClassTwo->getUnqualifiedPascalCasedColumnNames())
        );
        $this->assertEquals(
            ['Id', 'FieldVarchar', 'TestClassId'],
            array_values($wrappedTestClassTwo->getUnqualifiedPascalCasedColumnNames())
        );

        /** ParentClass */
        $this->assertEquals(
            ['ParentClass.Id', 'ParentClass.ParentData'],
            array_keys($wrappedParentClass->getUnqualifiedPascalCasedColumnNames())
        );
        $this->assertEquals(
            ['Id', 'ParentData'],
            array_values($wrappedParentClass->getUnqualifiedPascalCasedColumnNames())
        );

        /** ChildClass */
        $this->assertEquals(
            ['ChildClass.Id', 'ChildClass.ParentData', 'ChildClass.ChildData', 'ChildClass.ParentClassId'],
            array_keys($wrappedChildClass->getUnqualifiedPascalCasedColumnNames())
        );
        $this->assertEquals(
            ['Id', 'ParentData', 'ChildData', 'ParentClassId'],
            array_values($wrappedChildClass->getUnqualifiedPascalCasedColumnNames())
        );
    }

    public function testGetQualifiedTitleCasedColumnNames()
    {
        $wrappedTestClass = new PropelObjectWrapper(new TestClass());
        $wrappedTestClassTwo = new PropelObjectWrapper(new TestClassTwo());
        $wrappedParentClass = new PropelObjectWrapper(new ParentClass());
        $wrappedChildClass = new PropelObjectWrapper(new ChildClass());

        /** TestClass */
        $this->assertEquals(
            ['TestClass.Id', 'TestClass.FieldSmallVarchar', 'TestClass.FieldLargeVarchar', 'TestClass.FieldInteger', 'TestClass.FieldFloat', 'TestClass.FieldTimestamp', 'TestClass.FieldBoolean', 'TestClass.RequiredField', 'TestClass.UnrequiredField'],
            array_keys($wrappedTestClass->getQualifiedTitleCasedColumnNames())
        );
        $this->assertEquals(
            ['Test Class Id', 'Test Class Field Small Varchar', 'Test Class Field Large Varchar', 'Test Class Field Integer', 'Test Class Field Float', 'Test Class Field Timestamp', 'Test Class Field Boolean', 'Test Class Required Field', 'Test Class Unrequired Field'],
            array_values($wrappedTestClass->getQualifiedTitleCasedColumnNames())
        );

        /** TestClassTwo */
        $this->assertEquals(
            ['TestClassTwo.Id', 'TestClassTwo.FieldVarchar', 'TestClassTwo.TestClassId'],
            array_keys($wrappedTestClassTwo->getQualifiedTitleCasedColumnNames())
        );
        $this->assertEquals(
            ['Test Class Two Id', 'Test Class Two Field Varchar', 'Test Class Two Test Class Id'],
            array_values($wrappedTestClassTwo->getQualifiedTitleCasedColumnNames())
        );

        /** ParentClass */
        $this->assertEquals(
            ['ParentClass.Id', 'ParentClass.ParentData'],
            array_keys($wrappedParentClass->getQualifiedTitleCasedColumnNames())
        );
        $this->assertEquals(
            ['Parent Class Id', 'Parent Class Parent Data'],
            array_values($wrappedParentClass->getQualifiedTitleCasedColumnNames())
        );

        /** ChildClass */
        $this->assertEquals(
            ['ChildClass.Id', 'ChildClass.ParentData', 'ChildClass.ChildData', 'ChildClass.ParentClassId'],
            array_keys($wrappedChildClass->getQualifiedTitleCasedColumnNames())
        );
        $this->assertEquals(
            ['Child Class Id', 'Child Class Parent Data', 'Child Class Child Data', 'Child Class Parent Class Id'],
            array_values($wrappedChildClass->getQualifiedTitleCasedColumnNames())
        );
    }

    public function testUnqualifiedTitleCasedColumnNames()
    {
        $wrappedTestClass = new PropelObjectWrapper(new TestClass());
        $wrappedTestClassTwo = new PropelObjectWrapper(new TestClassTwo());
        $wrappedParentClass = new PropelObjectWrapper(new ParentClass());
        $wrappedChildClass = new PropelObjectWrapper(new ChildClass());

        /** TestClass */
        $this->assertEquals(
            ['TestClass.Id', 'TestClass.FieldSmallVarchar', 'TestClass.FieldLargeVarchar', 'TestClass.FieldInteger', 'TestClass.FieldFloat', 'TestClass.FieldTimestamp', 'TestClass.FieldBoolean', 'TestClass.RequiredField', 'TestClass.UnrequiredField'],
            array_keys($wrappedTestClass->getUnqualifiedTitleCasedColumnNames())
        );
        $this->assertEquals(
            ['Id', 'Field Small Varchar', 'Field Large Varchar', 'Field Integer', 'Field Float', 'Field Timestamp', 'Field Boolean', 'Required Field', 'Unrequired Field'],
            array_values($wrappedTestClass->getUnqualifiedTitleCasedColumnNames())
        );

        /** TestClassTwo */
        $this->assertEquals(
            ['TestClassTwo.Id', 'TestClassTwo.FieldVarchar', 'TestClassTwo.TestClassId'],
            array_keys($wrappedTestClassTwo->getUnqualifiedTitleCasedColumnNames())
        );
        $this->assertEquals(
            ['Id', 'Field Varchar', 'Test Class Id'],
            array_values($wrappedTestClassTwo->getUnqualifiedTitleCasedColumnNames())
        );

        /** ParentClass */
        $this->assertEquals(
            ['ParentClass.Id', 'ParentClass.ParentData'],
            array_keys($wrappedParentClass->getUnqualifiedTitleCasedColumnNames())
        );
        $this->assertEquals(
            ['Id', 'Parent Data'],
            array_values($wrappedParentClass->getUnqualifiedTitleCasedColumnNames())
        );

        /** ChildClass */
        $this->assertEquals(
            ['ChildClass.Id', 'ChildClass.ParentData', 'ChildClass.ChildData', 'ChildClass.ParentClassId'],
            array_keys($wrappedChildClass->getUnqualifiedTitleCasedColumnNames())
        );
        $this->assertEquals(
            ['Id', 'Parent Data', 'Child Data', 'Parent Class Id'],
            array_values($wrappedChildClass->getUnqualifiedTitleCasedColumnNames())
        );
    }

    public function testGetFields()
    {
        $testClass = new TestClass();
        $wrappedTestClass = new PropelObjectWrapper($testClass);

        $values = [
            'TestClass.Id' => null,
            'TestClass.FieldSmallVarchar' => (string)rand(),
            'TestClass.FieldLargeVarchar' => (string)rand(),
            'TestClass.FieldInteger' => rand(),
            'TestClass.FieldFloat' => mt_rand() / mt_getrandmax(),
            'TestClass.FieldTimestamp' => '2016-09-28T13:40:10-07:00',
            'TestClass.FieldBoolean' => (boolean)(rand(0, 1)),
            'TestClass.RequiredField' => (string)rand(),
            'TestClass.UnrequiredField' => (string)rand(),
        ];
        
        $expectedTypes = [
            'TestClass.Id' => 'primary-key',
            'TestClass.FieldSmallVarchar' => 'text',
            'TestClass.FieldLargeVarchar' => 'textarea',
            'TestClass.FieldInteger' => 'text',
            'TestClass.FieldFloat' => 'text',
            'TestClass.FieldTimestamp' => 'datetime',
            'TestClass.FieldBoolean' => 'boolean',
            'TestClass.RequiredField' => 'text',
            'TestClass.UnrequiredField' => 'text',
        ];

        foreach ($values as $fieldName => $value) {
            $fieldName = explode('.', $fieldName)[1];
            $testClass->{"set$fieldName"}($value);
        }

        $fields = $wrappedTestClass->getFields();

        $fieldValues = [];
        foreach ($fields as $fieldName => $field) {
            $fieldValues[$fieldName] = $field->getInitial();
        }
        $fieldValues['TestClass.FieldTimestamp'] = $fieldValues['TestClass.FieldTimestamp']->format('c');

        $this->assertEquals($values, $fieldValues);

        $fieldTypes = [];
        foreach ($fields as $fieldName => $field) {
            $fieldTypes[$fieldName] = $field->getType();
        }

        $this->assertEquals($expectedTypes, $fieldTypes);
    }

    public function testGetValues()
    {
        $testClass = new TestClass();
        $wrappedTestClass = new PropelObjectWrapper($testClass);

        $values = [
            'TestClass.Id' => null,
            'TestClass.FieldSmallVarchar' => (string)rand(),
            'TestClass.FieldLargeVarchar' => (string)rand(),
            'TestClass.FieldInteger' => rand(),
            'TestClass.FieldFloat' => mt_rand() / mt_getrandmax(),
            'TestClass.FieldTimestamp' => '2016-09-28T13:40:10-07:00',
            'TestClass.FieldBoolean' => (boolean)(rand(0, 1)),
            'TestClass.RequiredField' => (string)rand(),
            'TestClass.UnrequiredField' => (string)rand(),
        ];

        foreach ($values as $fieldName => $value) {
            $fieldName = explode('.', $fieldName)[1];
            $testClass->{"set$fieldName"}($value);
        }
        
        $this->assertEquals($values, $wrappedTestClass->getValues());
    }

    public function testGetValuesWithInheritance()
    {
        $childClass = new ChildClass();
        $wrappedTestClass = new PropelObjectWrapper($childClass);

        $values = [
            'ChildClass.Id' => null,
            'ChildClass.ParentData' => (string)rand(),
            'ChildClass.ChildData' => (string)rand(),
            'ChildClass.ParentClassId' => null,
        ];

        foreach ($values as $fieldName => $value) {
            $fieldName = explode('.', $fieldName)[1];
            $childClass->{"set$fieldName"}($value);
        }

        $this->assertEquals($values, $wrappedTestClass->getValues());
    }

    public function testGetPrimaryKey()
    {
        $testClass = $this->createMock(TestClass::class);
        $wrappedTestClass = new PropelObjectWrapper($testClass);

        $testClass->expects($this->once())->method('getPrimaryKey');

        $wrappedTestClass->getPrimaryKey();
    }

    public function testFillFromFields()
    {
        $this->fail('Not Written Yet');
    }

    public function testSave()
    {
        $testClass = $this->createMock(TestClass::class);
        $wrappedTestClass = new PropelObjectWrapper($testClass);

        $testClass->expects($this->once())->method('save');

        $wrappedTestClass->save();

    }

    public function testDelete()
    {
        $testClass = $this->createMock(TestClass::class);
        $wrappedTestClass = new PropelObjectWrapper($testClass);

        $testClass->expects($this->once())->method('delete');

        $wrappedTestClass->delete();
    }

    public function testToString()
    {
        $testClass = $this->createMock(TestClass::class);
        $wrappedTestClass = new PropelObjectWrapper($testClass);

        $testClass->method('__toString')->willReturn('');

        $testClass->expects($this->once())->method('__toString');

        (string)$wrappedTestClass;
    }
}
