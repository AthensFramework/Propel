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
    protected $object;
    
    /** @var string */
    protected $tableMapClass;

    /** @var  ColumnMap[] */
    protected $columns;

    /** @var string[] */
    protected $keys;

    /**
     * Receive and wrap a Propel ORM entity.
     *
     * @param ActiveRecordInterface $object
     */
    public function __construct(ActiveRecordInterface $object)
    {
        $this->object = $object;
        
        $this->tableMapClass = $object::TABLE_MAP;
    }

    /**
     * @return mixed
     */
    public function getPrimaryKey()
    {
        return $this->object->getPrimaryKey();
    }

    /**
     * @return $this
     */
    public function save()
    {
        $this->object->save();

        return $this;
    }

    /**
     * @return void
     */
    public function delete()
    {
        $this->object->delete();
    }

    /**
     * @return string
     */
    public function getTitleCasedObjectName()
    {
        $tableMap = $this->getClassTableMap();
        $tableName = $tableMap::TABLE_NAME;

        return ucwords(str_replace('_', ' ', $tableName));
    }

    /**
     * @return string[]
     */
    protected function getKeys()
    {
        if ($this->keys === null) {
            $objectName = $this->getPascalCasedObjectName();
    
            $this->keys = [];
            foreach ($this->getColumnPhpNames() as $columnPhpName) {
                $this->keys[] = $objectName . '.' . $columnPhpName;
            }
        }
        
        return $this->keys;
    }

    /**
     * @return ColumnMap[]
     */
    protected function getColumns()
    {
        if ($this->columns === null) {
            $columns = [];

            foreach ($this->findParentTables() as $parentTable) {
                $columns = array_merge($columns, $parentTable->getColumns());
            }

            $columns = array_merge($columns, $this->getClassTableMap()->getColumns());

            $objectName = $this->getPascalCasedObjectName();
            $keys = [];
            foreach ($columns as $column) {
                $keys[] = $objectName . '.' . $column->getPhpName();
            }

            $this->columns = array_combine($keys, $columns);
        }

        return $this->columns;
    }

    /**
     * @return mixed[]
     */
    public function getValues()
    {
        $objectName = $this->getPascalCasedObjectName();

        $values = [];
        foreach ($this->object->toArray() as $unqualifiedPascalCasedColumnName => $value) {
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
                $initial = $this->object->{"get" . str_replace("Id", "", $phpName)}();
            } else {
                $initial = $this->object->{"get" . $phpName}();
            }

            $fields[$fieldName]->setInitial($initial);
        }

        return $fields;
    }

    /**
     * @return string[]
     */
    public function getUnqualifiedTitleCasedColumnNames()
    {
        $labels = [];
        foreach ($this->getColumns() as $column) {
            $label = $column->getName();

            $labels[] = ucwords(str_replace('_', ' ', $label));
        }

        return array_combine(
            $this->getKeys(),
            $labels
        );
    }

    /**
     * @param FieldInterface[] $fields
     * @return mixed
     */
    protected function addBehaviorConstraintsToFields(array $fields)
    {

        $behaviors = $this->getClassTableMap()->getBehaviors();
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
     * @return void
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
                    $this->object->{"set" . $column->getPhpName()}($value);
                    $field->setInitial($field->getValidatedData());
                } elseif ($column->getPhpName() === "UpdatedAt" || $column->getPhpName() === "CreatedAt") {
                    // Don't accept updates to the UpdatedAt or CreatedAt timestamps
                } else {
                    $this->object->{"set" . $column->getPhpName()}($value);
                    $field->setInitial($field->getValidatedData());
                }
            }
        }
    }

    /**
     * @return string[]
     */
    protected function getColumnPhpNames()
    {
        $columnPhpNames = [];
        foreach ($this->getColumns() as $column) {
            $columnPhpNames[] = $column->getPhpName();
        }
        
        return $columnPhpNames;
    }

    /**
     * @return string[]
     */
    public function getQualifiedPascalCasedColumnNames()
    {
        return array_combine(
            $this->getKeys(),
            $this->getKeys()
        );
    }

    /**
     * @return \Propel\Runtime\Map\TableMap
     */
    protected function getClassTableMap()
    {
        $tableMapClass = $this->tableMapClass;
        return $tableMapClass::getTableMap();
    }

    /**
     * Return a Propel TableMap corresponding to a table within the same schema as
     * $fullyQualifiedTableMapName.
     *
     * In some cases, a foreign table map within the same database as $this may not be initialized
     * by Propel. If we try to access a foreign table map using runtime introspection and it has
     * not yet been initialized, then Propel will throw a TableNotFoundException. This method
     * accesses the table map by access to its fully qualified class name, which it determines by
     * modifying $this->_classTableMapName.
     *
     * @param   string $tableName                  The SQL name of the related table for which you
     *                                             would like to retrieve a table map.
     * @param   string $fullyQualifiedTableMapName A fully qualified table map name.
     *
     * @return  \Propel\Runtime\Map\TableMap
     */
    protected function getRelatedTableMap($tableName, $fullyQualifiedTableMapName)
    {
        $upperCamelCaseTableName = str_replace('_', '', ucwords($tableName, '_')) . "TableMap";

        // We build he fully qualified name of the related table map class
        // by doing some complicated search and replace on the fully qualified
        // table map name of the child.
        $fullyQualifiedRelatedTableName = substr_replace(
            $fullyQualifiedTableMapName,
            $upperCamelCaseTableName,
            strrpos(
                $fullyQualifiedTableMapName,
                "\\",
                -1
            ) + 1
        ) . "\n";
        $fullyQualifiedParentTableName = trim($fullyQualifiedRelatedTableName);

        return $fullyQualifiedParentTableName::getTableMap();
    }

    /**
     * @return \Propel\Runtime\Map\TableMap[]
     */
    protected function findParentTables()
    {
        // Make recursive
        $behaviors = $this->getClassTableMap()->getBehaviors();

        $parentTables = [];
        if (array_key_exists("delegate", $behaviors) === true) {
            $parentTables[] = $this->getRelatedTableMap($behaviors["delegate"]["to"], $this->tableMapClass);
        }
        return $parentTables;
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

                foreach ($query->find() as $this->object) {
                    $choices[] = ChoiceBuilder::begin()
                        ->setValue($this->object->getId())
                        ->setAlias((string)$this->object)
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
    public function getPascalCasedObjectName()
    {
        $tableMapName = $this->tableMapClass;

        /** @var \Propel\Runtime\Map\TableMap $tableMap */
        $tableMap = new $tableMapName();
        return $tableMap->getPhpName();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)($this->object);
    }
}
