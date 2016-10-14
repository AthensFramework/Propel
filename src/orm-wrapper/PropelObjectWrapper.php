<?php

namespace Athens\Propel\ORMWrapper;

use Propel\Runtime\Map\Exception\ColumnNotFoundException;
use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Map\ColumnMap;

use Athens\Core\ORMWrapper\AbstractObjectWrapper;
use Athens\Core\ORMWrapper\ObjectWrapperInterface;
use Athens\Core\Field\Field;
use Athens\Core\Field\FieldInterface;
use Athens\Core\Field\FieldBuilder;
use Athens\Core\Choice\ChoiceBuilder;
use Athens\Core\Choice\ChoiceInterface;

/**
 * Class ORMUtils provides static methods for interpreting and interfacing
 * with ORM entities.
 *
 * @package Athens\Core\Etc
 */
class PropelObjectWrapper extends AbstractObjectWrapper implements ObjectWrapperInterface
{

    use PropelORMWrapperTrait;

    /** @var array */
    protected static $db_type_to_field_type_association = [
        "VARCHAR" => "text",
        "LONGVARCHAR" => "text",
        "INTEGER" => "text",
        "VARBINARY" => "text",
        "DATE" => "datetime",
        "TIMESTAMP" => "datetime",
        "BOOLEAN" => "boolean",
        "FLOAT" => "text",
    ];
    
    /** @var ActiveRecordInterface */
    public $object;

    /**
     * Receive and wrap a Propel ORM entity.
     *
     * @param mixed $object
     */
    protected function __construct($object)
    {
        $this->object = $object;
        $tableMapClass = $object::TABLE_MAP;
        $this->setTableMap($tableMapClass::getTableMap());
    }

    /**
     * @return ActiveRecordInterface
     */
    protected function getObject()
    {
        return $this->object;
    }

    /**
     * @return mixed
     */
    public function getPrimaryKey()
    {
        return $this->getObject()->getPrimaryKey();
    }

    /**
     * @return $this
     */
    public function save()
    {
        $this->getObject()->save();

        return $this;
    }

    /**
     * @return void
     */
    public function delete()
    {
        $this->getObject()->delete();
    }

    /**
     * @return mixed[]
     */
    public function getValues()
    {
        $objectName = $this->getPascalCasedObjectName();

        $values = [];
        foreach ($this->getObject()->toArray() as $unqualifiedPascalCasedColumnName => $value) {
            $values[$objectName . '.' . $unqualifiedPascalCasedColumnName] = $value;
        }

        return $values;
    }

    /**
     * @return Field[]
     */
    public function getFields()
    {
        $fieldNames = $this->getQualifiedPascalCasedColumnNames();

        $fields = array_combine($fieldNames, $this->fieldsFromColumns());

        $fields = $this->addBehaviorConstraintsToFields($fields);

        foreach ($this->getColumns() as $fieldName => $column) {
            $phpName = $column->getPhpName();

            if ($column->isForeignKey() === true) {
                $initial = $this->getObject()->{"get" . str_replace("Id", "", $phpName)}();
            } else {
                $initial = $this->getObject()->{"get" . $phpName}();
            }

            $fields[$fieldName]->setInitial($initial);
        }

        return $fields;
    }


    /**
     * @param FieldInterface[] $fields
     * @return mixed
     */
    protected function addBehaviorConstraintsToFields(array $fields)
    {

        $behaviors = $this->getTableMap()->getBehaviors();
        $validateBehaviors = array_key_exists("validate", $behaviors) === true ? $behaviors["validate"] : [];

        $validateBehaviorsByColumn = [];
        foreach ($validateBehaviors as $behavior) {
            $columnName = $behavior["column"];
            if (array_key_exists($columnName, $validateBehaviorsByColumn) === false) {
                $validateBehaviorsByColumn[$columnName] = [];
            }

            $validateBehaviorsByColumn[$columnName][] = $behavior;
        }

        foreach ($this->getColumns() as $fieldName => $column) {
            $columnName = $column->getName();

            if (array_key_exists($columnName, $validateBehaviorsByColumn) === true) {
                foreach ($validateBehaviorsByColumn[$columnName] as $behavior) {
                    if ($behavior["validator"] === "Choice") {
                        $fields[$fieldName]->setType("choice");

                        /** @var ChoiceInterface[] $choices */
                        $choices = [];
                        foreach ($behavior['options']['choices'] as $choiceText) {
                            $choices[] = ChoiceBuilder::begin()->setValue($choiceText)->build();
                        }

                        $fields[$fieldName]->setChoices($choices);
                    }
                }
            }
        }

        return $fields;
    }

    /**
     * Fills an object's attributes from the validated data of an array
     * of fields.
     *
     * Expects that $fields contains a set of $fieldName => $field pairs.
     *
     * @param FieldInterface[] $fields
     * @return ObjectWrapperInterface
     */
    public function fillFromFields(array $fields)
    {
        $fieldNames = array_keys($fields);
        $columns = $this->getColumns();

        $columns = array_combine($fieldNames, $columns);

        foreach ($columns as $fieldName => $column) {
            $field = $fields[$fieldName];

            if ($field->hasValidatedData() === true) {
                $value = $field->getValidatedData();

                if ($column->isPrimaryKey() === true) {
                    // Don't accept form input for primary keys. These should be set at object creation.
                } elseif ($column->isForeignKey() === true) {
                    $this->getObject()->{"set" . $column->getPhpName()}($value);
                    $field->setInitial($field->getValidatedData());
                } elseif ($column->getPhpName() === "UpdatedAt" || $column->getPhpName() === "CreatedAt") {
                    // Don't accept updates to the UpdatedAt or CreatedAt timestamps
                } else {
                    $this->getObject()->{"set" . $column->getPhpName()}($value);
                    $field->setInitial($field->getValidatedData());
                }
            }
        }

        return $this;
    }

    /**
     * @param ColumnMap $column
     * @return string
     */
    protected function chooseFieldType(ColumnMap $column)
    {

        $map = $column->getTable();

        if (method_exists($map, 'isHTMLFieldColumnName') === true
            && $map::isHTMLFieldColumnName($column->getFullyQualifiedName()) === true
        ) {
            $type = 'html';
        } else {
            $type = static::$db_type_to_field_type_association[$column->getType()];
        }

        if ($type === "text" && $column->getSize() >= 128) {
            $type = "textarea";
        }

        return $type;
    }

    /**
     * @return Field[]
     */
    protected function fieldsFromColumns()
    {
        $fields = [];
        $initial = "";

        $columns = $this->getColumns();

        $tableMap = current($columns)->getTable();

        $versionColumnName = "";
        if (array_key_exists('versionable', $tableMap->getBehaviors()) === true) {
            $versionColumnName = $tableMap->getBehaviors()['versionable']['version_column'];
        }

        foreach ($columns as $column) {
            $label = $column->getName();
            $choices = [];

            // The primary key ID field should be presented as a hidden html field
            if ($column->isPrimaryKey() === true) {
                $fieldType = FieldBuilder::TYPE_PRIMARY_KEY;
                $fieldRequired = false;
            } elseif ($column->isForeignKey() === true) {
                $foreignTable = $column->getRelation()->getForeignTable();
                $queryName = '\\' . str_replace('.', '\\', $foreignTable->getOMClass(true)) . 'Query';

                $query = $queryName::create();

                foreach ($query->find() as $object) {
                    $choices[] = ChoiceBuilder::begin()
                        ->setValue($object->getId())
                        ->setAlias((string)$object)
                        ->build();
                }
                $fieldType = FieldBuilder::TYPE_CHOICE;
                $fieldRequired = false;
            } elseif ($column->getPhpName() === "UpdatedAt" || $column->getPhpName() === "CreatedAt") {
                $fieldType = FieldBuilder::TYPE_AUTO_TIMESTAMP;
                $fieldRequired = false;
            } elseif ($column->getName() === $versionColumnName) {
                $fieldType = FieldBuilder::TYPE_VERSION;
                $fieldRequired = false;
            } else {
                $fieldType = self::chooseFieldType($column);
                $fieldRequired = $column->isNotNull();
            }

            $label = ucwords(str_replace('_', ' ', $label));

            $fieldSize = $column->getSize();

            $fields[] = new Field([], [], $fieldType, $label, $initial, $fieldRequired, $choices, $fieldSize, "", "");
        }

        return $fields;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)($this->getObject());
    }

    /**
     * @param string $name
     * @param array  $arguments
     * @return mixed
     */
    public function __call($name, array $arguments)
    {
        return call_user_func_array([$this->getObject(), $name], $arguments);
    }
}
