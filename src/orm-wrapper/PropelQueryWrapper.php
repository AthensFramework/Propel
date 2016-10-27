<?php

namespace Athens\Propel\ORMWrapper;

use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveQuery\Criteria;

use Athens\Core\ORMWrapper\AbstractQueryWrapper;
use Athens\Core\ORMWrapper\ObjectWrapperInterface;
use Athens\Core\ORMWrapper\QueryWrapperInterface;
use Athens\Core\Etc\StringUtils;

/**
 * Class PropelQueryWrapper
 *
 * @package Athens\Core\QueryWrapper
 */
class PropelQueryWrapper extends AbstractQueryWrapper implements QueryWrapperInterface
{
    use PropelORMWrapperTrait;

    /** @var ModelCriteria */
    protected $query;

    /**
     * @var string[]
     */
    protected $conditionMap = [
        QueryWrapperInterface::CONDITION_EQUAL => Criteria::EQUAL,
        QueryWrapperInterface::CONDITION_NOT_EQUAL => Criteria::NOT_EQUAL,
        QueryWrapperInterface::CONDITION_CONTAINS => Criteria::CONTAINS_SOME,
        QueryWrapperInterface::CONDITION_GREATER_THAN => Criteria::GREATER_THAN,
        QueryWrapperInterface::CONDITION_GREATER_THAN_OR_EQUAL => Criteria::GREATER_EQUAL,
        QueryWrapperInterface::CONDITION_LESS_THAN => Criteria::LESS_THAN,
        QueryWrapperInterface::CONDITION_LESS_THAN_OR_EQUAL => Criteria::LESS_EQUAL,
    ];

    /**
     * PropelQueryWrapper constructor.
     *
     * @param ModelCriteria $query
     */
    public function __construct(ModelCriteria $query)
    {
        $this->query = $query;

        $this->setTableMap($query->getTableMap());
    }

    /**
     * Does not handle multiple primary keys.
     *
     * @param mixed $primaryKeyValue
     * @return ObjectWrapperInterface|null
     */
    public function findOneByPrimaryKey($primaryKeyValue)
    {
        $primaryKeys = $this->getTableMap()->getPrimaryKeys();
        $primaryKeyPhpName = array_values($primaryKeys)[0]->getPhpName();

        $result = $this->query->{"findOneBy$primaryKeyPhpName"}($primaryKeyValue);

        $result = $result === null ? null : PropelObjectWrapper::fromObject($result);

        return $result;
    }

    /**
     * @return PropelCollectionWrapper
     */
    public function find()
    {
        $collection = $this->query->find();
        return new PropelCollectionWrapper($collection);
    }

    /**
     * @param string $columnName
     * @param mixed  $condition
     * @return $this
     */
    public function orderBy($columnName, $condition)
    {
        $this->query->orderBy($columnName, $condition);
        return $this;
    }

    /**
     * @param string $columnName
     * @param mixed  $value
     * @param string $comparison
     * @return $this
     */
    public function filterBy($columnName, $value, $comparison = QueryWrapperInterface::CONDITION_EQUAL)
    {
        $comparison = $this->conditionMap[$comparison];

        /** @var boolean $columnNameIsQualified */
        $columnNameIsQualified = strpos($columnName, '.') !== false;

        /** @var string $modelName */
        $modelName = $columnNameIsQualified === true ?
            strtok($columnName, '.') : $this->query->getTableMap()->getPhpName();

        /** @var string $columnName */
        $columnName = $columnNameIsQualified === true ? $this->getUnqualifiedFieldName($columnName) : $columnName;

        /** @var boolean $queryIsOnModel */
        $queryIsOnModel = $modelName === $this->query->getTableMap()->getPhpName();

        if ($queryIsOnModel === true) {
            $this->query->{"filterBy" . $columnName}($value, $comparison);
        } else {
            $this->query->{"use{$modelName}Query"}()
                ->{"filterBy" . $columnName}($value, $comparison)
                ->endUse();
        }

        return $this;
    }

    /**
     * @param string $columnName
     * @param mixed  $value
     * @param string $condition
     * @return boolean
     */
    public function canFilterBy($columnName, $value, $condition = QueryWrapperInterface::CONDITION_EQUAL)
    {
        return $this->canQueryColumnName($columnName);
    }

    /**
     * @param string $columnName
     * @param string $condition
     * @return boolean
     */
    public function canOrderBy($columnName, $condition)
    {
        return $this->canQueryColumnName($columnName);
    }

    /**
     * @param string $columnName
     * @return boolean
     */
    protected function canQueryColumnName($columnName)
    {

        /** @var boolean $columnNameIsQualified */
        $columnNameIsQualified = strpos($columnName, '.') !== false;

        /** @var string $objectName */
        $objectName = $columnNameIsQualified === true ? strtok($columnName, '.') : $this->getPascalCasedObjectName();

        /** @var string $columnName */
        $columnName = $columnNameIsQualified === true ? $this->getUnqualifiedFieldName($columnName) : $columnName;

        if ($objectName === $this->getPascalCasedObjectName()) {
            return in_array($columnName, $this->getUnqualifiedPascalCasedColumnNames()) === true;
        } else {
            /** @var \Propel\Runtime\Map\TableMap $foreignMap */
            $foreignMap = $this->query->getTableMap()->getRelation($objectName)->getForeignTable();

            foreach ($foreignMap->getColumns() as $column) {
                if ($columnName === $column->getPhpName()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param integer $offset
     * @return $this
     */
    public function offset($offset)
    {
        $this->query->offset($offset);
        return $this;
    }

    /**
     * @param integer $limit
     * @return $this
     */
    public function limit($limit)
    {
        $this->query->limit($limit);
        return $this;
    }

    /**
     * @return ObjectWrapperInterface
     */
    public function createObject()
    {
        $className = $this->query->getModelName();
        return PropelObjectWrapper::fromObject(new $className());
    }

    /**
     * @return integer
     */
    public function count()
    {
        return $this->query->count();
    }

    /**
     * @return boolean
     */
    public function exists()
    {
        return $this->query->exists();
    }

    /**
     * Predicate that reports whether a given field name in a given class
     * table map name is under the athens/encryption Propel behavior.
     *
     * @param string $fieldName
     * @param string $classTableMapName
     * @return boolean
     */
    protected function isEncrypted($fieldName, $classTableMapName)
    {
        $qualifiedPropelFieldName = $this->getQualifiedPropelFieldName($fieldName, $classTableMapName);

        return method_exists($classTableMapName, 'isEncryptedColumnName')
        && $classTableMapName::isEncryptedColumnName($qualifiedPropelFieldName);
    }

    /**
     * @param string $columnName
     * @return boolean
     */
    protected function isColumn($columnName)
    {
        $columns = $this->getColumns();
        return array_key_exists($columnName, $columns);
    }


    /**
     * Retrieve an unqualified field name from a qualified one.
     *
     * @param string $qualifiedFieldName
     * @return string
     */
    protected function getUnqualifiedFieldName($qualifiedFieldName)
    {
        return explode('.', $qualifiedFieldName)[1];
    }

    /**
     * @param string $fieldName
     * @param string $classTableMapName
     * @return string
     */
    protected function getQualifiedPropelFieldName($fieldName, $classTableMapName)
    {
        $unqualifiedFieldName = explode('.', $fieldName)[1];

        $unqualifiedPropelFieldName = $classTableMapName::translateFieldName(
            $unqualifiedFieldName,
            $classTableMapName::TYPE_PHPNAME,
            $classTableMapName::TYPE_FIELDNAME
        );

        return $classTableMapName::TABLE_NAME . "." . $unqualifiedPropelFieldName;
    }

    /**
     * @param string           $fieldPhpName
     * @param mixed            $value
     * @param "find"|"findOne" $findType
     * @return \Propel\Runtime\ActiveRecord\ActiveRecordInterface[]|\Propel\Runtime\ActiveRecord\ActiveRecordInterface
     */
    protected function baseFindAmongInheritance($fieldPhpName, $value, $findType)
    {
        // If the field is native to this class...
        try {
            // Test that the table has the prescribed column. Will fail with a ColumnNotFoundException if it does not.
            $unused = $this->getClassTableMap($this->tableMapClass)->getColumnByPhpName($fieldPhpName);

            return $this->createQuery($this->tableMapClass)
                ->{"filterBy" . $fieldPhpName}($value)
                ->$findType();

            // Else, the field is native to a parent class (we hope!)...
        } catch (ColumnNotFoundException $e) {
            // Make recursive. Include error for when we cannot find the field.

            return $this->createQuery($this->tableMapClass)
                ->{"use" . $this->findParentTables($this->tableMapClass)[0]->getPhpName() . "Query"}()
                ->{"filterBy" . $fieldPhpName}($value)
                ->endUse()
                ->$findType();
        }
    }

    /**
     * @param string $fieldPhpName
     * @param mixed  $value
     * @return \Propel\Runtime\ActiveRecord\ActiveRecordInterface[]
     */
    protected function findByAmongInheritance($fieldPhpName, $value)
    {
        return $this->baseFindAmongInheritance($fieldPhpName, $value, "find");
    }

    /**
     * @param string $fieldPhpName
     * @param mixed  $value
     * @return \Propel\Runtime\ActiveRecord\ActiveRecordInterface[]
     */
    protected function findOneByAmongInheritance($fieldPhpName, $value)
    {
        return $this->baseFindAmongInheritance($fieldPhpName, $value, "findOne");
    }

    /**
     * @param integer $id
     * @return \Propel\Runtime\ActiveRecord\ActiveRecordInterface
     */
    protected function getObjectById($id)
    {
        return $this->createQuery()->findPk($id);
    }

    /**
     * @return string
     */
    protected function getTableName()
    {
        $columns = $this->getColumns();
        return array_values($columns)[0]->getTableName();
    }

    /**
     * @return string
     */
    protected function getObjectClass()
    {
        $search = ["\\Map", "TableMap"];
        $replace = ["", ""];

        return str_replace($search, $replace, $this->tableMapClass);
    }

    /**
     * Return a Propel ActiveQuery object corresponding to $this->_classTableMapName
     *
     * @return \Propel\Runtime\ActiveQuery\PropelQuery
     */
    protected function createQuery()
    {

        $queryName = str_replace(["TableMap", "\\Map\\"], ["Query", "\\"], $this->tableMapClass);
        return $queryName::create();
    }
}
