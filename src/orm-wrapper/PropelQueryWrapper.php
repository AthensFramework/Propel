<?php

namespace Athens\Core\QueryWrapper;


class PropelQueryWrapper
{

    /**
     * @param ModelCriteria $query
     * @param string        $fieldName
     * @return boolean
     */
    protected function queryContainsFieldName(ModelCriteria $query, $fieldName)
    {
        /** @var \Propel\Runtime\Map\TableMap $map */
        $map = $query->getTableMap();
        $objectName = $map->getPhpName();

        foreach ($map->getColumns() as $column) {
            $thisFieldName = $objectName . "." . StringUtils::toUpperCamelCase($column->getPhpName());

            if ($fieldName === $thisFieldName) {
                return true;
            }
        }

        foreach ($map->getRelations() as $relation) {
            $map = $relation->getForeignTable();
            $objectName = $map->getPhpName();

            foreach ($map->getColumns() as $column) {
                $thisFieldName = $objectName . "." . StringUtils::toUpperCamelCase($column->getPhpName());

                if ($fieldName === $thisFieldName) {
                    return true;
                }
            }
        }

        return false;
    }
    
    /**
     * Adds a filter condition to a given query.
     *
     * Adaptively uses Propel's ::useXXXQuery() method for related tables.
     *
     * @param ModelCriteria $query
     * @param string        $fieldName
     * @param string        $criterion
     * @param string        $criteria
     * @return ModelCriteria
     */
    public function applyFilterToQuery(ModelCriteria $query, $fieldName, $criterion, $criteria)
    {
        /** @var boolean $fieldNameIsQualified */
        $fieldNameIsQualified = strpos($fieldName, '.') !== false;

        /** @var string $modelName */
        $modelName = $fieldNameIsQualified === true ? strtok($fieldName, '.') : $query->getTableMap()->getPhpName();

        /** @var string $fieldName */
        $fieldName = $fieldNameIsQualified === true ? $this->getUnqualifiedFieldName($fieldName) : $fieldName;

        /** @var boolean $queryIsOnModel */
        $queryIsOnModel = $modelName === $query->getTableMap()->getPhpName();

        if ($queryIsOnModel === true) {
            $query = $query->{"filterBy" . $fieldName}($criterion, $criteria);
        } else {
            $query = $query->{"use{$modelName}Query"}()
                ->{"filterBy" . $fieldName}($criterion, $criteria)
                ->endUse();
        }

        return $query;
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