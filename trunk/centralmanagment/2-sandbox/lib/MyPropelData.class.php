<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This class is the Propel implementation of sfData.  It interacts with the data source
 * and loads data.
 *
 * @package    symfony
 * @subpackage propel
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id: sfPropelData.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class MyPropelData extends sfPropelData
{
  

  /**
   * Dumps data to fixture from one or more tables.
   *
   * @param string $directoryOrFile   The directory or file to dump to
   * @param mixed  $tables            The name or names of tables to dump (or all to dump all tables)
   * @param mixed  $exception_tables  The name or names of tables NOT to dump
   * @param string $connectionName    The connection name (default to propel)
   */
  public function dumpData($directoryOrFile, $tables = 'all', $exception_tables = array(), $connectionName = 'propel')
  {
    $dumpData = $this->getData($tables, $exception_tables, $connectionName);

    // save to file(s)
    if (!is_dir($directoryOrFile))
    {
      file_put_contents($directoryOrFile, sfYaml::dump($dumpData, 3));
    }
    else
    {
      $i = 0;
      foreach ($tables as $tableName)
      {
        if (!isset($dumpData[$tableName]))
        {
          continue;
        }

        file_put_contents(sprintf("%s/%03d-%s.yml", $directoryOrFile, ++$i, $tableName), sfYaml::dump(array($tableName => $dumpData[$tableName]), 3));
      }
    }
  }

  /**
   * Returns data from one or more tables.
   *
   * @param  mixed  $tables           name or names of tables to dump (or all to dump all tables)
   * @param  mixed  $exception_tables name or names of tables NOT to dump
   * @param  string $connectionName   connection name
   *
   * @return array  An array of database data
   */
  public function getData($tables = 'all', $exception_tables, $connectionName = 'propel')
  {
    $this->loadMapBuilders();
    $this->con = Propel::getConnection($connectionName);
    $this->dbMap = Propel::getDatabaseMap($connectionName);

    // get tables
    if ('all' === $tables || null === $tables)
    {
      $tables = array();
      foreach ($this->dbMap->getTables() as $table)
      {
        $tables[] = $table->getPhpName();
      }
    }
    else if (!is_array($tables))
    {
      $tables = array($tables);
    }

    $dumpData = array();

    $tables = array_diff($tables, $exception_tables);

    $tables = $this->fixOrderingOfForeignKeyData($tables);
    foreach ($tables as $tableName)
    {
      $tableMap = $this->dbMap->getTable(constant(constant($tableName.'::PEER').'::TABLE_NAME'));
      $hasParent = false;
      $haveParents = false;
      $fixColumn = null;
      foreach ($tableMap->getColumns() as $column)
      {
        $col = strtolower($column->getName());
        if ($column->isForeignKey())
        {
          $relatedTable = $this->dbMap->getTable($column->getRelatedTableName());
          if ($tableName === $relatedTable->getPhpName())
          {
            if ($hasParent)
            {
              $haveParents = true;
            }
            else
            {
              $fixColumn = $column;
              $hasParent = true;
            }
          }
        }
      }

      if ($haveParents)
      {
        // unable to dump tables having multi-recursive references
        continue;
      }

      // get db info
      $resultsSets = array();
      if ($hasParent)
      {
        $resultsSets[] = $this->fixOrderingOfForeignKeyDataInSameTable($resultsSets, $tableName, $fixColumn);
      }
      else
      {
        $in = array();
        foreach ($tableMap->getColumns() as $column)
        {
          $in[] = strtolower($column->getName());
        }
        $stmt = $this->con->query(sprintf('SELECT %s FROM %s', implode(',', $in), constant(constant($tableName.'::PEER').'::TABLE_NAME')));

        $resultsSets[] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);
      }

      foreach ($resultsSets as $rows)
      {
        if(count($rows) > 0 && !isset($dumpData[$tableName]))
        {
          $dumpData[$tableName] = array();

          foreach ($rows as $row)
          {
            $pk = $tableName;
            $values = array();
            $primaryKeys = array();
            $foreignKeys = array();

            foreach ($tableMap->getColumns() as $column)
            {
              $col = strtolower($column->getName());
              $isPrimaryKey = $column->isPrimaryKey();

              if (null === $row[$col])
              {
                continue;
              }

              if ($isPrimaryKey)
              {
                $value = $row[$col];
                $pk .= '_'.$value;
                $primaryKeys[$col] = $value;
              }

              if ($column->isForeignKey())
              {
                $relatedTable = $this->dbMap->getTable($column->getRelatedTableName());
                if ($isPrimaryKey)
                {
                  $foreignKeys[$col] = $row[$col];
                  $primaryKeys[$col] = $relatedTable->getPhpName().'_'.$row[$col];
                }
                else
                {
                  $values[$col] = $relatedTable->getPhpName().'_'.$row[$col];

                  $values[$col] = strlen($row[$col]) ? $relatedTable->getPhpName().'_'.$row[$col] : '';
                }
              }
              elseif (!$isPrimaryKey || ($isPrimaryKey && !$tableMap->isUseIdGenerator()))
              {
                // We did not want auto incremented primary keys
                $values[$col] = $row[$col];
              }
            }

            if (count($primaryKeys) > 1 || (count($primaryKeys) > 0 && count($foreignKeys) > 0))
            {
              $values = array_merge($primaryKeys, $values);
            }

            $dumpData[$tableName][$pk] = $values;
          }
        }
      }
    }

    return $dumpData;
  }

}
