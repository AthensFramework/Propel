<?php

namespace Athens\Propel\ORMWrapper;

use Propel\Runtime\Map\ColumnMap;
use Propel\Runtime\Map\TableMap;

trait PropelORMWrapperTrait
{

    /** @var string[] */
    protected $keys;

    /** @var  ColumnMap[] */
    protected $columns;

    /** @var TableMap */
    protected $tableMap;

    /**
     * @param TableMap $tableMap
     * @return void
     */
    protected function setTableMap(TableMap $tableMap)
    {
        $this->tableMap = $tableMap;
    }

    /**
     * @return TableMap
     */
    protected function getTableMap()
    {
        return $this->tableMap;
    }
    
    /**
     * @return string
     */
    public function getTitleCasedObjectName()
    {
        $tableMap = $this->getTableMap();

        return ucwords(str_replace('_', ' ', $tableMap::TABLE_NAME));
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
     * @return ColumnMap[]
     */
    protected function getColumns()
    {
        if ($this->columns === null) {
            $columns = [];

            foreach ($this->findParentTables() as $parentTable) {
                $columns = array_merge($columns, $parentTable->getColumns());
            }

            $columns = array_merge($columns, $this->getTableMap()->getColumns());

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
     * Return a Propel TableMap corresponding to a table within the same schema as
     * $fullyQualifiedTableMapName.
     *
     * In some cases, a foreign table map within the same database as $this may not be initialized
     * by Propel. If we try to access a foreign table map using runtime introspection and it has
     * not yet been initialized, then Propel will throw a TableNotFoundException. This method
     * accesses the table map by access to its fully qualified class name, which it determines by
     * modifying $this->_classTableMapName.
     *
     * @param   string $tableName The SQL name of the related table for which you
     *                            would like to retrieve a table map.
     *
     * @return  \Propel\Runtime\Map\TableMap
     */
    protected function getRelatedTableMap($tableName)
    {
        $fullyQualifiedTableMapName = get_class($this->getTableMap());
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
        $behaviors = $this->getTableMap()->getBehaviors();

        $parentTables = [];
        if (array_key_exists("delegate", $behaviors) === true) {
            $parentTables[] = $this->getRelatedTableMap($behaviors["delegate"]["to"], $this->getTableMap());
        }
        return $parentTables;
    }

    /**
     * @return string
     */
    public function getPascalCasedObjectName()
    {
        return $this->getTableMap()->getPhpName();
    }
}
