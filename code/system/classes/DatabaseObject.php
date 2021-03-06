<?php

/**
* WizyTówka 5
* Abstract class of database object.
*/
namespace WizyTowka;

abstract class DatabaseObject implements \IteratorAggregate
{
	static protected $_tableName = '';
	static protected $_tablePrimaryKey = 'id';
	static protected $_tableColumns = [];    // Must not contain primary key.

	static protected $_tableColumnsJSON = [];
	static protected $_tableColumnsTimeAtInsert = [];
	static protected $_tableColumnsTimeAtUpdate = [];

	private $_data = [];
	private $_dataNewlyCreated;

	private $_PDO;

	public function __construct()
	{
		$this->_PDO = WT()->database;

		$this->__clone();

		foreach (static::$_tableColumns as $column) {
			$this->_data[$column] = in_array($column, static::$_tableColumnsJSON) ? new \stdClass : null;
		}
	}

	public function __clone()
	{
		$this->_data[static::$_tablePrimaryKey] = null;
		$this->_dataNewlyCreated = true;
	}

	public function __get(string $column)
	{
		return $this->_data[$column];
	}

	public function __set(string $column, $value) : void
	{
		if ($column == static::$_tablePrimaryKey) {
			throw DatabaseObjectException::setterPrimaryKeyReadOnly($column);
		}
		elseif (in_array($column, static::$_tableColumnsJSON) and !is_object($value)) {
			throw DatabaseObjectException::setterJSONColumnNonObject($column);
		}

		// Prevent from setting non-existent column. If column does not exists, error will be thrown.
		$this->_data[$column];

		$this->_data[$column] = $value;
	}

	public function __isset(string $column) : bool
	{
		return isset($this->_data[$column]);
	}

	public function __debugInfo() : array
	{
		return $this->_data;
	}

	public function getIterator() : iterable // For IteratorAggregate interface.
	{
		foreach ($this->_data as $key => $value) {
			yield $key => $value;
		}
	}

	public function save() : bool
	{
		if ($this->_dataNewlyCreated) {
			foreach (static::$_tableColumnsTimeAtInsert as $column) {
				$this->_data[$column] = time();
			}

			$parameters = array_map(function($column){ return ':' . $column; }, static::$_tableColumns);
			$sqlQuery = 'INSERT INTO ' . static::$_tableName .
			            '(' . implode(', ', static::$_tableColumns) . ') VALUES(' . implode(', ', $parameters) . ')';
		}
		else {
			foreach (static::$_tableColumnsTimeAtUpdate as $column) {
				$this->_data[$column] = time();
			}

			$columnAndParameterAssignments = array_map(function($column){ return $column . ' = :' . $column; }, static::$_tableColumns);
			$sqlQuery = 'UPDATE ' . static::$_tableName .
			            ' SET ' . implode(', ', $columnAndParameterAssignments) .
			            ' WHERE ' . static::$_tablePrimaryKey . ' = :' . static::$_tablePrimaryKey;
		}

		$sqlQueryData = $this->_data;

		if ($this->_dataNewlyCreated) {
			unset($sqlQueryData[static::$_tablePrimaryKey]);  // Remove primary key from INSERT query parameters.
		}

		foreach ($sqlQueryData as &$value) {
			if (is_bool($value)) {
				$value = (integer)$value;
				// Convert boolean values to integers. PostgreSQL has BOOLEAN type, but it's not used for compatibility.
				// MySQL and SQLite have not BOOLEAN data type and store logical values as 0/1 integer.
				// PDOStatement::execute() converts each value to string. "true" is converted to "1", "false" to empty string.
				// It is needed to convert "false" bool value to "0" to avoid errors of data type and for data consistency.
			}
			elseif ($value === '') {
				$value = null;
				// Convert empty string (exactly string type) to null to avoid problems with wrong data type
				// caused by convertion made in PDOStatement::execute() and to put NULL value in DBMS table.
			}
		}
		foreach (static::$_tableColumnsJSON as $column) {
			$sqlQueryData[$column] = json_encode($sqlQueryData[$column], JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
			if (json_last_error() != JSON_ERROR_NONE) {
				throw DatabaseObjectException::JSONError($column);
			}
		}

		$statement = $this->_PDO->prepare($sqlQuery);
		$execution = $statement->execute($sqlQueryData);

		if ($execution and $this->_dataNewlyCreated) {
			// Fill in primary key field after INSERT. PostgreSQL needs different strategy.
			$this->_data[static::$_tablePrimaryKey] = $this->_PDO->lastInsertId(
				($this->_PDO->getAttribute(\PDO::ATTR_DRIVER_NAME) == 'pgsql')
				? (static::$_tableName . '_' . static::$_tablePrimaryKey . '_seq')
				: static::$_tablePrimaryKey
			);
			$this->_dataNewlyCreated = false;
		}

		$statement->closeCursor();

		return $execution;
	}

	public function delete() : bool
	{
		if ($this->_dataNewlyCreated) {
			return false;
		}

		$sqlQuery = 'DELETE FROM ' . static::$_tableName . ' WHERE ' . static::$_tablePrimaryKey . ' = :id';

		$statement = $this->_PDO->prepare($sqlQuery);
		$execution = $statement->execute(['id' => $this->_data[static::$_tablePrimaryKey]]);

		if ($execution) {
			$this->_data[static::$_tablePrimaryKey] = null;
			$this->_dataNewlyCreated = true;
		}

		$statement->closeCursor();

		return $execution;
	}

	static protected function _getByWhereCondition(string $sqlQueryWhere = null, array $parameters = [], bool $onlyOneRecord = false)
	{
		$PDO = WT()->database;

		$allColumnsNames = static::$_tableColumns;
		array_unshift($allColumnsNames, static::$_tablePrimaryKey);  // Add primary key column to the beginning of columns names list.

		$sqlQuery = 'SELECT ' . implode(', ', $allColumnsNames) . ' FROM ' . static::$_tableName;
		if ($sqlQueryWhere) {
			$sqlQuery .= ' WHERE ' . $sqlQueryWhere;
		}
		$sqlQuery .= ' ORDER BY ' . static::$_tablePrimaryKey;
		if ($onlyOneRecord) {
			$sqlQuery .= ' LIMIT 1';
		}

		$statement = $PDO->prepare($sqlQuery);
		$statement->setFetchMode(\PDO::FETCH_NUM);   // PDO::FETCH_NUM style is used because values must not be duplicated.
		$execution = $statement->execute($parameters);

		$elementsToReturn = [];

		if ($execution) {
			foreach ($statement as $record) {
				$object = new static;
				$object->_dataNewlyCreated = false;
				$object->_data = array_combine($allColumnsNames, $record);
				// Normally it is possible to use PDO::FETCH_NAMED fetch mode and $object->_data = $record syntax,
				// but this is PostgreSQL workaround. By default PostgreSQL lowercases unquoted names
				// of columns and tables — this makes camelCase columns names inaccesible.

				foreach (static::$_tableColumnsJSON as $column) {
					$object->_data[$column] = $object->_data[$column] ? json_decode($object->_data[$column]) : new \stdClass;
					if (json_last_error() != JSON_ERROR_NONE) {
						throw DatabaseObjectException::JSONError($column);
					}
				}

				$elementsToReturn[] = $object;
			}
		}

		if ($onlyOneRecord) {
			return $elementsToReturn[0] ?? null;
		}
		return $elementsToReturn;
	}

	static public function getById($id) : ?self
	{
		return static::_getByWhereCondition(static::$_tablePrimaryKey.' = :id', ['id' => $id], true);
	}

	static public function getAll() : array
	{
		return static::_getByWhereCondition();
	}
}

class DatabaseObjectException extends Exception
{
	static public function setterPrimaryKeyReadOnly($column)
	{
		return new self('Primary key cannot be edited in column "' . $column . '".', 1);
	}
	static public function setterJSONColumnNonObject($column)
	{
		return new self('JSON object cannot be replaced by non-object value in column "' . $column . '".', 2);
	}
	static public function JSONError($column)
	{
		return new self('Error "' . json_last_error_msg() . '" during JSON operation on encoded column "' . $column . '".', 3);
	}
}