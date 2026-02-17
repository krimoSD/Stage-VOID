<?php

class SQLQuery {

    protected $_dbHandle;
    protected $_result;
    protected $_table;

    /** Connects to database **/

    function connect($address, $account, $pwd, $name) {
        $this->_dbHandle = @mysqli_connect($address, $account, $pwd, $name);
        if ($this->_dbHandle) {
            return 1;
        } else {
            die("Database connection failed: " . mysqli_connect_error());
        }
    }

    /** Disconnects from database **/

    function disconnect() {
        if (@mysqli_close($this->_dbHandle)) {
            return 1;
        } else {
            return 0;
        }
    }

    /** Select all rows from the model's table **/

    function selectAll() {
        $query = 'SELECT * FROM `' . $this->_table . '`';
        return $this->query($query);
    }

    /** Select a single row by id **/

    function select($id) {
        $id    = mysqli_real_escape_string($this->_dbHandle, $id);
        $query = 'SELECT * FROM `' . $this->_table . '` WHERE `id` = \'' . $id . '\'';
        return $this->query($query, 1);
    }

    /** Execute a custom SQL query **/

    function query($query, $singleResult = 0) {

        $this->_result = mysqli_query($this->_dbHandle, $query);

        if (preg_match("/^select/i", trim($query))) {

            $result       = array();
            $table        = array();
            $field        = array();
            $tempResults  = array();
            $numOfFields  = mysqli_num_fields($this->_result);

            for ($i = 0; $i < $numOfFields; ++$i) {
                $fieldInfo = mysqli_fetch_field_direct($this->_result, $i);
                array_push($table, $fieldInfo->table);
                array_push($field, $fieldInfo->name);
            }

            while ($row = mysqli_fetch_row($this->_result)) {
                for ($i = 0; $i < $numOfFields; ++$i) {
                    // Convert plural table name to singular Model name
                    $modelName = ucfirst(rtrim($table[$i], 's'));
                    $tempResults[$modelName][$field[$i]] = $row[$i];
                }
                if ($singleResult == 1) {
                    mysqli_free_result($this->_result);
                    return $tempResults;
                }
                array_push($result, $tempResults);
            }

            mysqli_free_result($this->_result);
            return $result;
        }
    }

    /** Get number of rows from last result **/

    function getNumRows() {
        return mysqli_num_rows($this->_result);
    }

    /** Free resources allocated by a query **/

    function freeResult() {
        mysqli_free_result($this->_result);
    }

    /** Safely escape a string **/

    function escape($value) {
        return mysqli_real_escape_string($this->_dbHandle, $value);
    }

    /** Get last error string **/

    function getError() {
        return mysqli_error($this->_dbHandle);
    }
}