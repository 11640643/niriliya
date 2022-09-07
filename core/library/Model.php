<?php

namespace C\L;

use \Phalcon\Mvc\Model\Query as PHQL;

class Model extends \Phalcon\Mvc\Model
{
    //设置模型是新建还是更新
    protected $_new = true;

    public function setNew($new)
    {
        $this->_new = $new;
    }

    public function getNew()
    {
        return $this->_new;
    }

    /**
     * 新增数据model
     * @param array $data 一维关联数组(需要插入的数据)
     * @return boolean  1:success 0:error
     * */
    public function add($data)
    {
        $id = $this->getModelsMetaData()->getPrimaryKeyAttributes($this)[0];
        if (isset($this->$id)) {
            $this->$id = null;
        }
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
        try {
            if ($this->create()) {
                $insertId = $this->getWriteConnection()->lastInsertId($this->getSource());

                return $insertId;
            }
            $msg = '创建失败';
            foreach ($this->getMessages() as $message) {
                $msg .= $message;
            }
            $this->getDI()['message']->setSerMsg($msg);
            return false;
        } catch (\PDOException $e) {
            $this->getDI()['message']->setSerMsg($e->getMessage());
            return false;
        }
    }

    /**
     * 批量添加数据
     * @param
     * @return
     */
    public function multiAdd($data)
    {
        $keys = array_keys($data[0]);
        $keys = implode(',', $keys);
        $sql = 'insert into ' . $this->getSource() . ' (' . $keys . ') values';
        foreach ($data as $value) {
            $val = $this->implodeArray($value);
            $sql .= $val;
        }
        $sql = rtrim($sql, ',');
        $sql .= ';';
        return $this->getWriteConnection()->execute($sql);
    }

    /**
     *  根据条件查询数据
     * @param array $data 查询条件组成的数组
     * @param string $columns 结果集中显示的字段
     * @param int $currentPage 当前页数
     * @param string $order 排序方式     'money desc, source'
     * @param int $pageNum 每页数据数
     * @return array
     * */
    public function search($data, $dataType = [], $columns = [], $order = '', $pageCurren = 1, $pageNum = 10, $showDel = false)
    {
        $data = $data ? $data : [1 => 1];
        $currentPage = $pageCurren < 1 ? 1 : $pageCurren;
        $offset = ($currentPage - 1) * $pageNum;

        if (!$showDel) {
            $data['is_delete'] = 0;
        }
        $data = $this->setConditions($data, $dataType);

        if ($columns) {
            $data['columns'] = $columns;
        }
        if (!empty($order)) {
            $data['order'] = $order;
        } else {
            $id = $this->getModelsMetaData()->getPrimaryKeyAttributes($this)[0];
            $data['order'] = "$id desc";
        }

        $data['limit'] = array(intval($pageNum), $offset);
        return $this->find($data)->toArray();
    }

    /**
     *  根据条件查询单条数据
     * @param array $data 查询条件组成的数组
     * @return array
     * */
    public function get($data, $dataType = [], $columns = [], $order = '', $showDel = false)
    {
        if ($data = $this->getObj($data, $dataType, $columns, $order, $showDel)) {
            $data = $data->toArray();
        }
        return empty($data) ? [] : $data;
    }

    public function getObj($data, $dataType, $columns = [], $order = '', $showDel = false)
    {
        $data = $data ? $data : [1 => 1];
        if (!$showDel)
            $data['is_delete'] = 0;
        $data = $this->setConditions($data, $dataType);
        if (!empty($columns)) {
            $data['columns'] = $columns;
        }

        if (!empty($order)) {
            $data['order'] = $order;
        } else {
            $id = $this->getModelsMetaData()->getPrimaryKeyAttributes($this)[0];
            $data['order'] = "$id desc";
        }

        return $this->findFirst($data);
    }

    public function getByPrimary($value, $dataType = [], $columns = [], $order = '', $showDel = false)
    {
        $id = $this->getModelsMetaData()->getPrimaryKeyAttributes($this)[0];
        return $this->get([$id => $value], $dataType, $columns, $order, $showDel);
    }

    public function getByPrimaryName()
    {
        return $this->getModelsMetaData()->getPrimaryKeyAttributes($this)[0];
    }

    /**
     *  根据条件查询多条数据
     * @param array $data 查询条件组成的数组
     * @return array
     * */
    public function getAll($data = false, $dataType = false, $columns = false, $order = false, $limit = '', $showDel = false)
    {
        $data = $data ? $data : [1 => 1];
        if (!$showDel)
            $data['is_delete'] = 0;
        $data = $this->setConditions($data, $dataType);
        if ($order) {
            $data['order'] = $order;
        }
        if ($columns) {
            $data['columns'] = $columns;
        }
        if (!empty($limit)) {
            $data['limit'] = $limit;
        }
        $data = $this->find($data);
        if ($data) {
            return $data->toArray();
        } else {
            return [];
        }
    }

    /**
     * value必须是整型数组
     * @param type $value
     */
    public function getAllByPrimary($value, $order = null, $columns = null)
    {
        if ($data = $this->getAllByPrimaryObj($value, $order, $columns)) {
            $data = $data->toArray();
        }
        return $data;
    }

    public function getBySql($condition)
    {
        return $this->find($condition)->toArray();
    }

    /**
     * value必须是整型数组
     * @param type $value
     */
    public function getAllByPrimaryObj($value, $order = null, $columns = null)
    {
        if (is_array($value)) {
            $value = trim(implode(',', $value), ',');
        }

        $id = $this->getModelsMetaData()->getPrimaryKeyAttributes($this)[0];
        $data['conditions'] = "$id in ($value)";
        if ($order !== null) {
            $data['order'] = $order;
        }
        if ($columns !== null) {
            $data['columns'] = $columns;
        }
        return $this->find($data);
    }

    /**
     *  根据条件查询数据总数
     * @param array $data $data = array('name' => 'aaa');
     * @param array $dataType $data = array('name' => 'like');
     * @param boolen $getPageTotal 是否计算分页总数, 1 计算 0 不计算
     * @param int $pageNum 分页数目
     * @return int/array                       $getPageTotal = FALSE 返回int类型的总数据条数
     *                                          $getPageTotal = FALSE 返回array $arr[count]为总数据条数,  $arr[pageTotal]为分页总数
     * */
    public function getCount($data = false, $dataType = false, $showDel = false)
    {

        $data = $data ? $data : [1 => 1];
        if (!$showDel)
            $data['is_delete'] = 0;
        $data = $this->setConditions($data, $dataType);
        $query = new PHQL("select count(*) as count  from " . get_called_class() . ' where ' . $data['conditions'], $this->getDI());
        $totalCount = $query->setBindParams($data['bind'])->execute()->toArray();
        $totalCount = $totalCount[0]['count'];
        return intval($totalCount);
    }

    public function getSum($key, $data = false, $dataType = false, $showDel = false)
    {

        $data = $data ? $data : [1 => 1];
        if (!$showDel) {
            $data['is_delete'] = 0;
        }
        $data = $this->setConditions($data, $dataType);
        $query = new PHQL("select SUM($key) as sum  from " . get_called_class() . ' where ' . $data['conditions'], $this->getDI());
        $totalCount = $query->setBindParams($data['bind'])->execute()->toArray();
        $totalCount = $totalCount[0]['sum'];
        return $totalCount;
    }

    /**
     *  根据主键删除数据
     * @param array $data 主键数组
     * @param string $key 查询字段
     *
     * */
    public function dropByPrimary($primarys, $columns = null)
    {
        if ($columns === null) {
            $columns = $this->getModelsMetaData()->getPrimaryKeyAttributes($this)[0];
        }
        if (strpos($primarys, ',')) {
            $primarys = implode(',', $primarys);
        }
        $query = new PHQL("delete  from " . get_called_class() . " where $columns in ($primarys)", $this->getDI());

        return $query->execute()->success();
    }

    /**
     *  根据主键删除单条数据
     * @param int/str        $value              查询字段对应值
     * @param string $key 查询字段
     *
     * */
    public function dropByColumn($value, $columns = null)
    {
        if ($columns === null) {
            $columns = $this->getModelsMetaData()->getPrimaryKeyAttributes($this)[0];
        }
        $data = array($columns => $value);
        $query = new PHQL("delete  from " . get_called_class() . " where $columns=:$columns:", $this->getDI());
        return $query->setBindParams($data)->execute()->success();
    }

    public function incrByColumn($key, $value, $incr = 1, $columns = null)
    {
        if ($columns === null) {
            $columns = $this->getModelsMetaData()->getPrimaryKeyAttributes($this)[0];
        }
        $data = array($columns => $value);
        $query = new PHQL("update " . get_called_class() . " set $key=$key+$incr where $columns=:$columns:", $this->getDI());
        return $query->setBindParams($data)->execute()->success();
    }

    /**
     *  根据条件更新数据
     * @param array $primarys 主键数组
     * @param array $data 需要插入的数据
     * @param string $columns 查询主键字段名
     * @return int          受影响行数
     *  TODO:   给键名加上反引号
     * */
    public function editByPrimary($primarys, $data, $columns = null)
    {
        $sql = 'update ' . get_called_class() . ' set ';
        foreach ($data as $key => $value) {
            $sql .= "$key=:$key:,";
        }
        if ($columns === null) {
            $columns = $this->getModelsMetaData()->getPrimaryKeyAttributes($this)[0];
        }
        if (is_array($primarys)) {
            $primarys = implode(',', $primarys);
            $sql = rtrim($sql, ',') . " where $columns in ($primarys)";
        } else {
            $sql = rtrim($sql, ',') . " where $columns = '$primarys'";
        }

        $query = new PHQL($sql, $this->getDI());

        $result = $query->setBindParams($data)->execute();
        if ($result->success() === false) {
            $messages = $result->getMessages();

            foreach ($messages as $message) {
                echo $message->getMessage();
            }
            exit;
        }
        return true;
    }

    /**
     *  根据条件更新单条数据
     * @param str/int      $value             条件字段的值
     * @param array $data 需要插入的数据
     * @param string $columns 查询字段名
     * @return int          受影响行数
     *  TODO:   给键名加上反引号
     * */
    public function editByColumn($value, $data, $columns = null)
    {
        if ($columns === null) {
            return $this->editByPrimary($value, $data);
        }
        $sql = 'update ' . get_called_class() . ' set ';
        foreach ($data as $key => $val) {
            $sql .= "$key=:$key:,";
        }
        if (is_array($value)) {
            $value = implode(',', $value);
            $sql = rtrim($sql, ',') . " where $columns in ($value)";
        } else {
            $sql = rtrim($sql, ',') . " where $columns = $value";
        }
        $query = new PHQL($sql, $this->getDI());
        return $query->setBindParams($data)->execute()->success();
    }

    public function updates($update, $data, $dataType = [])
    {
        if (empty($update) || empty($data)) {
            return false;
        }

        $data = $data ? $data : [1 => 1];
        $data = $this->setConditions($data, $dataType);

        $sql = 'update ' . get_called_class() . ' set ';
        $updateBind = [];
        foreach ($update as $key => $val) {
            $updateKey = 'update_' . $key;
            $sql .= "$key=:$updateKey:,";
            $updateBind[$updateKey] = $val;
        }

        if(empty($data['conditions'])){
            return false;
        }

        $query = new PHQL(rtrim($sql, ',') . ' where ' . $data['conditions'], $this->getDI());
        return $query->setBindParams(array_merge($updateBind, $data['bind']))->execute()->success();
    }

    public function edit($where, $data)
    {
        $sql = 'update ' . get_called_class() . ' set ';
        foreach ($data as $key => $val) {
            $sql .= "$key=:$key:,";
        }

        $whereSql = "1 = 1";
        foreach ($where as $key => $value) {
            $whereSql .= " and $key = $value";
        }
        $sql = rtrim($sql, ',') . " where $whereSql";
        $query = new PHQL($sql, $this->getDI());
        $result = $query->setBindParams($data)->execute();
        if ($result->success() === false) {
            $messages = $result->getMessages();

            foreach ($messages as $message) {
                echo $message->getMessage();
            }
            exit;
        }
        return true;
    }

    /* 私有方法,绑定sql条件与参数 */

    private function setConditions($data, $dataType = false)
    {
        $params = array('conditions' => '', 'bind' => [], 'group' => '');
        $keys = array_keys($data);

        $dataType = $dataType ? $dataType : array_fill_keys($keys, '=');
        foreach ($data as $key => $value) {
            if ($key === 1) {
                $params['conditions'] .= " 1=1 and ";
                $params['bind'] = [];
                continue;
            }
            if (isset($dataType[$key])) {
                switch ($dataType[$key]) {
                    case 'like':
                        $params['conditions'] .= "$key {$dataType[$key]} :{$key}: and ";
                        $params['bind'][$key] = '%' . $value . '%';
                        break;
                    case 'between':
                        $params['conditions'] .= "$key between :{$key}: and :{$key}s: and ";
                        $params['bind'][$key] = $value[0];
                        $params['bind'][$key . 's'] = $value[1];
                        break;
                    case '!=':
                        $params['conditions'] .= "$key!=:{$key}: and ";
                        $params['bind'][$key] = $value;
                        break;
                    case '=|':
                        $params['conditions'] .= "($key=:{$key}: or ";
                        $params['bind'][$key] = $value;
                        break;
                    case '|':
                        $params['conditions'] .= "$key=:{$key}: or ";
                        $params['bind'][$key] = $value;
                        break;
                    case '|=':
                        $params['conditions'] .= "$key=:{$key}:) and ";
                        $params['bind'][$key] = $value;
                        break;
                    case 'in':
                        $vs = "";
                        if (is_array($value)) {
                            foreach ($value as $son) {
                                if (is_numeric($son) && strlen($son) < 11)
                                    $vs .= $son . ",";
                                else
                                    $vs .= "'" . $son . "',";
                            }
                            // $value = implode(',', $value);
                            $vs = trim($vs, ',');
                        } else {
                            $vs = $value;
                        }

                        $params['conditions'] .= "$key in ($vs) and ";
                        break;
                    case 'in|':
                        $vs = "";
                        if (is_array($value)) {
                            foreach ($value as $son) {
                                if (is_numeric($son))
                                    $vs .= $son . ",";
                                else
                                    $vs .= "'" . $son . "',";
                            }
                            // $value = implode(',', $value);
                            $vs = trim($vs, ',');
                        } else {
                            $vs = $value;
                        }

                        $params['conditions'] .= "$key in ($vs) or ";
                        break;
                    case '|>':
                        $params['conditions'] .= "($key > :{$key}: or ";
                        $params['bind'][$key] = $value;
                        break;
                    case '>|':
                        $params['conditions'] .= "$key > :{$key}:) and ";
                        $params['bind'][$key] = $value;
                        break;
                    case 'sql':
                        $params['conditions'] .= "$value and ";
                        break;
                    case 'group':
                        $params['group'] .= ",$key";
                        break;
                    case '|%' :
                        $params['conditions'] .= "($key like :{$key}: or ";
                        $params['bind'][$key] = '%' . $value;
                        break;
                    case '%' :
                        $params['conditions'] .= "$key like :{$key}: and ";
                        $params['bind'][$key] = '%' . $value;
                        break;
                    case '%|' :
                        $params['conditions'] .= "$key like :{$key}:) and ";
                        $params['bind'][$key] = '%' . $value;
                        break;
                    default:
                        $params['conditions'] .= "$key {$dataType[$key]}:{$key}: and ";
                        $params['bind'][$key] = $value;
                }
            } else {
                $params['conditions'] .= "$key =:{$key}: and ";
                $params['bind'][$key] = $value;
            }
        }
        $params['conditions'] = rtrim($params['conditions'], ' and ');
        $params['conditions'] = rtrim($params['conditions'], ' or ');
        $params['group'] = trim($params['group'], ',');
        if (empty($params['group'])) {
            unset($params['group']);
        }
        if (empty($params['order'])) {
            unset($params['order']);
        }

        return $params;
    }

    /**
     * @title 内部私有方法拼接sql语句value部分
     * @param
     * @return
     */
    private function implodeArray($array)
    {
        $str = '(';
        foreach ($array as $value) {
            $str .= '"' . $value . '",';
        }
        return rtrim($str, ',') . '),';
    }

    public function createTable($name, $desc)
    {

        $query = new PHQL("show create table `$name`;", $this->getDI());
        if (!$query->execute()->success()) {
            $query = new PHQL("$desc", $this->getDI());
            return $query->execute()->success();
        }
        return true;
    }

    public function branchSource($tableName)
    {
        $this->setSource($tableName);
        return true;
    }

    public function branchConnect($connect)
    {
        $this->setConnectionService($connect);
        return true;
    }

    public function exec($sql)
    {
        return $this->execute($sql);
    }
}
