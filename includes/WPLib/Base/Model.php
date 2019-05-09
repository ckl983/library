<?php
/**
 * WPLib\Base\Model
 *
 * @author
 * @copyright 2014-2018
 */

namespace WPLib\Base;

use Phalcon\Db;
use Phalcon\Exception;
use Phalcon\Mvc\Model as PBaseModel,
    Phalcon\Mvc\Model\Transaction\Manager as TxManager,
    Phalcon\Mvc\Model\MetaDataInterface,
    Phalcon\Mvc\ModelInterface,
    Phalcon\Mvc\Model\Message,
    Phalcon\Mvc\Model\Resultset\Simple as Resultset;

class Model extends PBaseModel implements ModelInterface
{

    const MODE_CONNECTION_READ  = 1;

    const MODE_CONNECTION_WRITE = 2;

    const MODE_CONNECTION_ALL   = 3;

    protected static $_inst = [];

    /**
     * @var di
     */
    // protected $_di = null;

    /**
     * @var db
     */
    protected $_db = null;

    /**
     * @var string 表前缀
     */
    public $tablePrefix = null;

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        if (isset(static::$readConnectionService) && !empty(static::$readConnectionService)) {
            $this->setReadConnectionService(static::$readConnectionService);
        }

        if (isset(static::$writeConnectionService) && !empty(static::$writeConnectionService)) {
            $this->setWriteConnectionService(static::$writeConnectionService);
        }

        $this->_db = $this->di->getShared('db');

        if (isset($this->di->getConfig()['database']['prefix'])) {
            $this->tablePrefix = $this->di->getConfig()['database']['prefix'];
        }
    }

    public function getSource()
    {
        if ($this->tablePrefix === null) {
            if (isset($this->di->getConfig()['database']['prefix'])) {
                $this->tablePrefix = $this->di->getConfig()['database']['prefix'];
            }
        }
        return "{$this->tablePrefix}{$this->tableName}";
    }

    /**
     * 设置读操作走主库
     *
     * @param bool $isMaster
     * @return bool
     */
    public function setReadConnectionToMaster($isMaster = true)
    {
        if ($isMaster) {
            $this->setReadConnectionService($this->getWriteConnectionService());
        } elseif (isset(static::$readConnectionService) && !empty(static::$readConnectionService)) {
            $this->setReadConnectionService(static::$readConnectionService);
        } else {
            return false;
        }

        return true;
    }

    /**
     * 确认连接（自动重连）
     *
     * @param int $mode
     * @return bool
     */
    public function ensureConnection($mode = self::MODE_CONNECTION_WRITE)
    {
        if ($mode == self::MODE_CONNECTION_ALL) {
            $read_db  = $this->getReadConnection();
            $write_db = $this->getWriteConnection();
            if ($read_db->getConnectionId() == $write_db->getConnectionId()) {
                try {
                    $read_db->getInternalHandler()
                        ->getAttribute(\PDO::ATTR_SERVER_INFO);
                } catch (\Exception $e) {
                    \Logger::info("ENSURE CONNECTION: " . $e->getMessage());
                    $read_db->connect();
                    unset($e);
                }
            } else {
                try {
                    $status = $read_db->getInternalHandler()
                        ->getAttribute(\PDO::ATTR_SERVER_INFO);
                } catch (\Exception $e) {
                    \Logger::info("ENSURE CONNECTION: " . $e->getMessage());
                    $read_db->connect();
                    unset($e);
                }
                try {
                    $status = $write_db->getInternalHandler()
                        ->getAttribute(\PDO::ATTR_SERVER_INFO);
                } catch (\Exception $e) {
                    \Logger::info("ENSURE CONNECTION: " . $e->getMessage());
                    $write_db->connect();
                    unset($e);
                }
            }
            unset($read_db, $write_db, $status);
        } else {
            if (($mode & self::MODE_CONNECTION_WRITE) == self::MODE_CONNECTION_WRITE) {
                $db = $this->getWriteConnection();
            } elseif (($mode & self::MODE_CONNECTION_READ) == self::MODE_CONNECTION_READ) {
                $db = $this->getReadConnection();
            }
            try {
                $status = $db->getInternalHandler()
                    ->getAttribute(\PDO::ATTR_SERVER_INFO);
            } catch (\Exception $e) {
                \Logger::info("ENSURE CONNECTION: " . $e->getMessage());
                $db->connect();
                unset($e);
            }
            unset($db, $status);
        }

        return true;
    }

    /**
     * Inserts data into a table using custom RBDM SQL syntax
     *
     * <code>
     * //Inserting a new robot
     * $success = $connection->insert(
     *	 "robots",
     *	 array(
     *		  "name" => "Astro Boy",
     *		  "year" => 1952
     *	  )
     * );
     *
     * //Next SQL sentence is sent to the database system
     * INSERT INTO `robots` (`name`, `year`) VALUES ("Astro boy", 1952);
     * </code>
     *
     * @param 	string table
     * @param 	array data
     * @param 	array dataTypes
     * @return 	boolean
     */
    public function insert($data, $dataTypes = null, $isAutoConnect = false)
    {
        if ($isAutoConnect) {
            $this->ensureConnection(self::MODE_CONNECTION_WRITE);
        }
        $db = $this->getWriteConnection();

        $flag = $db->insertAsDict($this->getSource(), $data, $dataTypes);
        if ($flag) {
            return $db->lastInsertId();
        }
        return false;
    }

    /**
     * Updates data on a table using custom RBDM SQL syntax
     *
     * <code>
     * //Updating existing robot
     * $success = $connection->update(
     *	 "robots",
     *	 array("name"),
     *	 array("New Astro Boy"),
     *	 "id = 101"
     * );
     *
     * //Next SQL sentence is sent to the database system
     * UPDATE `robots` SET `name` = "Astro boy" WHERE id = 101
     *
     * //Updating existing robot with array condition and $dataTypes
     * $success = $connection->update(
     *	 "robots",
     *	 array("name"),
     *	 array("New Astro Boy"),
     *	 array(
     *		 'conditions' => "id = ?",
     *		 'bind' => array($some_unsafe_id),
     *		 'bindTypes' => array(PDO::PARAM_INT) //use only if you use $dataTypes param
     *	 ),
     *	 array(PDO::PARAM_STR)
     * );
     *
     * </code>
     *
     * Warning! If $whereCondition is string it not escaped.
     *
     * @param   string|array table
     * @param 	array fields
     * @param 	array values
     * @param 	string|array whereCondition
     * @param 	array dataTypes
     * @return 	boolean
     */
//    public function updateA($fields, $values, $whereCondition = null, $dataTypes = null)
//	{
//        return $this->_db->update($this->getSource(), $fields, $values, $whereCondition, $dataTypes);
//	}

    /**
     * @param $data
     * @param null $conditions
     * @param null $bindParams
     * @param null $dataTypes
     * @param bool $isAutoConnect
     * @param null $transaction
     * @return bool
     */
    public function modify($data, $conditions = null, $bindParams = null, $dataTypes = null, $isAutoConnect = false, $transaction = null)
    {
        if (empty($data)) {
            return false;
        }

        if ($isAutoConnect) {
            $this->ensureConnection(self::MODE_CONNECTION_WRITE);
        }
        if ($transaction) {
            $this->setTransaction($transaction);
        }
        $db = $this->getWriteConnection();
        if (is_array($data)) {
            $set = '';
            foreach ($data as $k => $v) {
                $set .= (!empty($set) ? ', ' : '') . "`{$k}`=:s_{$k}";
                $bindParams["s_{$k}"] = $v;
            }

            if (is_array($conditions)) {
                $where = '';
                foreach ($conditions as $k => $v) {
                    $where .= (!empty($where) ? ' AND ' : '') . "`{$k}`=:w_{$k}";
                    $bindParams["w_{$k}"] = $v;
                }
            } else {
                $where = $conditions;
            }
        }
        unset($conditions);

        if (!empty($where)) {
            $sql = sprintf("UPDATE %s SET %s WHERE %s", $this->getSource(), $set, $where);

            $res = $db->execute($sql, $bindParams);

            if ($res) {
                $count = $db->affectedRows();
                unset($set, $bindParams, $where, $sql, $res);
                return $count;
            }
        }

        unset($db, $transaction, $set, $bindParams, $where);

        return false;
    }

    /**
     * Deletes data from a table using custom RBDM SQL syntax
     *
     * <code>
     * //Deleting existing robot
     * $success = $connection->delete(
     *	 "robots",
     *	 "id = 101"
     * );
     *
     * //Next SQL sentence is generated
     * DELETE FROM `robots` WHERE `id` = 101
     * </code>
     *
     * @param  string|array table
     * @param  string whereCondition
     * @param  array placeholders
     * @param  array dataTypes
     * @return boolean
     */
//    public function deleteA($whereCondition = null, $placeholders = null, $dataTypes = null)
//	{
//        return $this->_db->deleteA($this->getSource(), $whereCondition = null, $placeholders = null, $dataTypes = null)
//	}

    public function remove($whereCondition = null, $placeholders = null, $dataTypes = null, $isAutoConnect = false)
    {
        if ($isAutoConnect) {
            $this->ensureConnection(self::MODE_CONNECTION_WRITE);
        }
        $db = $this->getWriteConnection();
        return $db->delete($this->getSource(), $whereCondition, $placeholders, $dataTypes);
    }

    /**
     * 影响行数
     *
     * @param bool $isAutoConnect
     * @return int
     */
    public function affectedRows($isAutoConnect = false)
    {
        if ($isAutoConnect) {
            $this->ensureConnection(self::MODE_CONNECTION_READ);
        }
        $db = $this->getReadConnection();
        return $db->affectedRows();
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return Model[]
     */
    static public function find($parameters = null)
    {
        if (isset($parameters[0]) && is_array($parameters[0])) {
            $conditions = $parameters[0];
            unset($parameters[0]);
        } elseif (isset($parameters['conditions']) && is_array($parameters['conditions'])) {
            $conditions = $parameters['conditions'];
            unset($parameters['conditions']);
        }

        if (isset($conditions)) {
            $parameters['bind'] = isset($parameters['bind']) ? $parameters['bind'] : [];
            $where = '';
            foreach ($conditions as $k => $v) {
                $where .= (!empty($where) ? " and " : "") . "{$k} = :{$k}:";
                $parameters['bind'][$k] = $v;
            }
            $parameters[0] = $where;
        }
        try {
            return parent::find($parameters);
        } catch (\Exception $e) {
            $modelName = get_called_class();
            (new $modelName())->ensureConnection(self::MODE_CONNECTION_READ);
        }

        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return Model
     */
    static public function findFirst($parameters = null)
    {
        if (isset($parameters[0]) && is_array($parameters[0])) {
            $conditions = $parameters[0];
            unset($parameters[0]);
        } elseif (isset($parameters['conditions']) && is_array($parameters['conditions'])) {
            $conditions = $parameters['conditions'];
            unset($parameters['conditions']);
        }

        if (is_array($conditions)) {
            $parameters['bind'] = isset($parameters['bind']) ? $parameters['bind'] : [];
            $where = '';
            foreach ($conditions as $k => $v) {
                $where .= (!empty($where) ? " and " : "") . "{$k} = :{$k}:";
                $parameters['bind'][$k] = $v;
            }
            $parameters[0] = $where;
        }

        try {
            return parent::findFirst($parameters);
        } catch (\Exception $e) {
            $modelName = get_called_class();
            (new $modelName())->ensureConnection(self::MODE_CONNECTION_READ);
        }

        return parent::findFirst($parameters);
    }

    /**
     * 通过原生成SQL查询
     *
     */
    public static function findByRawSql($sql, $params = null)
    {
        $modelName = get_called_class();
        $model = new $modelName();

        return new Resultset(null, $model, $model->getReadConnection()->query($sql, $params));
    }

    /**
     * Executes internal hooks before save a record
     */
    protected function _preSave(MetadataInterface $metaData, $exists, $identityField)
    {
        /**
         * Run Validation Callbacks Before
         */
        if (get_cfg_var("orm.events")) {

            /**
             * Call the beforeValidation
             */
            if ($this->fireEventCancel("beforeValidation") === false) {
                return false;
            }

            /**
             * Call the specific beforeValidation event for the current action
             */
            if (!$exists) {
                if ($this->fireEventCancel("beforeValidationOnCreate") === false) {
                    return false;
                }
            } else {
                if ($this->fireEventCancel("beforeValidationOnUpdate") === false) {
                    return false;
                }
            }
        }

        /**
         * Check for Virtual foreign keys
         */
        if (get_cfg_var("orm.virtual_foreign_keys")) {
            if ($this->_checkForeignKeysRestrict() === false) {
                return false;
            }
        }

        /**
         * Columns marked as not null are automatically validated by the ORM
         */
        if (get_cfg_var("orm.not_null_validations")) {

            $notNull = $metaData->getNotNullAttributes($this);
            // if (typeof $notNull == "array") {
            if (is_array($notNull)) {

                /**
                 * Gets the fields that are numeric, these are validated in a diferent way
                 */
                $dataTypeNumeric = $metaData->getDataTypesNumeric($this);

                if (get_cfg_var("orm.column_renaming")) {
                    $columnMap = $metaData->getColumnMap($this);
                } else {
                    $columnMap = null;
                }

                /**
                 * Get fields that must be omitted from the SQL generation
                 */
                if ($exists) {
                    $automaticAttributes = $metaData->getAutomaticUpdateAttributes($this);
                } else {
                    $automaticAttributes = $metaData->getAutomaticCreateAttributes($this);
                }
                $defaultValues = $metaData->getDefaultValues($this);

                /**
                 * Get string attributes that allow empty strings as defaults
                 */
                $emptyStringValues = $metaData->getEmptyStringAttributes($this);

                $error = false;
                foreach ($notNull as $field) {

                    /**
                     * We don't check fields that must be omitted
                     */
                    if (!isset($automaticAttributes[$field])) {

                        $isNull = false;

                        if (is_array($columnMap)) {
                            if (isset($columnMap[$field]) && ($attributeField = $columnMap[$field])) {
                                //if !fetch $attributeField, $columnMap[$field] {
                                throw new Exception("Column '" . $field . "' isn't part of the column map");
                            }
                        } else {
                            $attributeField = $field;
                        }

                        /**
                         * Field is null when: 1) is not set, 2) is numeric but its value is not numeric, 3) is null or 4) is empty string
                         * Read the attribute from the this_ptr using the real or renamed name
                         */
                        // if (fetch $value, $this->{$attributeField}) {
                        if ($value = $this->{$attributeField}) {

                            /**
                             * Objects are never treated as null, numeric fields must be numeric to be accepted as not null
                             */
                            if (!is_object($value)) {
                                if (!isset($dataTypeNumeric[$field])) {
                                    if (isset($emptyStringValues[$field])) {
                                        if ($value === null) {
                                            $isNull = true;
                                        }
                                    } else {
                                        if ($value === null || $value === "") {
                                            $isNull = true;
                                        }
                                    }
                                } else {
                                    if (!is_numeric($value)) {
                                        $isNull = true;
                                    }
                                }
                            }

                        } else {
                            $isNull = true;
                        }

                        if ($isNull === true) {

                            if (!$exists) {
                                /**
                                 * The identity field can be null
                                 */
                                if ($field == $identityField) {
                                    continue;
                                }
                            }

                            /**
                             * The field have default value can be null
                             */
                            if (isset($defaultValues[$field])) {
                                continue;
                            }

                            /**
                             * A implicit PresenceOf message is created
                             */
                            $this->_errorMessages[] = new Message($attributeField . " is required", $attributeField, "PresenceOf");
                            $error = true;
                        }
                    }
                }

                if ($error === true) {
                    if (get_cfg_var("orm.events")) {
                        $this->fireEvent("onValidationFails");
                        $this->_cancelOperation();
                    }
                    return false;
                }
            }
        }

        /**
         * Call the main validation event
         */
        if ($this->fireEventCancel("validation") === false) {
            if (get_cfg_var("orm.events")) {
                $this->fireEvent("onValidationFails");
            }
            return false;
        }

        /**
         * Run Validation
         */
        if (get_cfg_var("orm.events")) {

            /**
             * Run Validation Callbacks After
             */
            if (!$exists) {
                if ($this->fireEventCancel("afterValidationOnCreate") === false) {
                    return false;
                }
            } else {
                if ($this->fireEventCancel("afterValidationOnUpdate") === false) {
                    return false;
                }
            }

            if ($this->fireEventCancel("afterValidation") === false) {
                return false;
            }

            /**
             * Run Before Callbacks
             */
            if ($this->fireEventCancel("beforeSave") === false) {
                return false;
            }

            $this->_skipped = false;

            /**
             * The operation can be skipped here
             */
            if ($exists) {
                if ($this->fireEventCancel("beforeUpdate") === false) {
                    return false;
                }
            } else {
                if ($this->fireEventCancel("beforeCreate") === false) {
                    return false;
                }
            }

            /**
             * Always return true if the operation is skipped
             */
            if ($this->_skipped === true) {
                return true;
            }

        }

        return true;
    }

    /**
     * 修复toArray不调用get/set方法BUG
     */
    public function toArray($columns = null)
    {
        $data = [];
        $metaData = $this->getModelsMetaData();
        $columnMap = $metaData->getColumnMap($this);

        foreach ($metaData->getAttributes($this) as $attribute) {
            /**
             * Check if the columns must be renamed
             */
            if (is_array($columnMap)) {
                if (!($attributeField = $columnMap[$attribute])) {
                    if (!get_cfg_var("orm.ignore_unknown_columns")) {
                        throw new Exception("Column '" . $attribute . "' doesn't make part of the column map");
                    } else {
                        continue;
                    }
                }
            } else {
                $attributeField = $attribute;
            }

            if (is_array($columns)) {
                if (!in_array($attributeField, $columns)) {
                    continue;
                }
            }

            if (($value = $this->{$attributeField}) !== null) {
                $data[$attributeField] = $value;
            } else {
                $data[$attributeField] = null;
            }
        }

        return $data;
    }

    /**
     * 检测读操作 sql 语句
     *
     * 关键字： SELECT,DECRIBE,SHOW
     * 写操作:UPDATE,INSERT,DELETE,ALTER
     * */
    public static function isReadOperation($sql) {
        return preg_match('/^\s*(SELECT|SHOW|DESCRIBE)/i', $sql);
    }

    /**
     * @return mixed
     */
    public static function inst()
    {
        $className = get_called_class();
        if (!isset(self::$_inst[$className])) {
            self::$_inst[$className] = new $className();
        }

        return self::$_inst[$className];
    }

}
