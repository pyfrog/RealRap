<?php
/**
 * Created by PhpStorm.
 * User: wugang
 * Date: 16/9/6
 * Time: 15:27
 */

namespace RealRap;

class Builder
{
    /**
     * @var \CI_Controller
     */
    private $ci;

    /**
     * @var \CI_DB_query_builder
     */
    private $db;

    /**
     * @var Model
     */
    private $model;

    /**
     * @var array
     */
    private $column;

    /**
     * @var array
     */
    private $where = [];

    /**
     * @var array
     */
    private $order = [];

    private $limit;

    private $offset = 0;

    public function __construct()
    {
        $this->ci = &get_instance();
        if(!isset($this->db)){
            $this->ci->load->database();
            $this->db = &$this->ci->db;
        }
    }


    /**
     * @param Model $model
     */
    public function setModel(Model &$model){
        $this->model = $model;
    }

    /**
     * @param $column
     */
    public function setColumn($column){
        $this->column = $column;
    }


    /**
     * 条件筛选字段
     * @param $where array
     * @return $this
     */
    public function where($where){
        if(is_array($where)){
            $this->where = array_merge($this->where,$where);
        }
        return $this;
    }

    /**
     * 排序字段
     * @param $order
     * @return $this
     */
    public function order($order){
        $this->order = array_merge($this->order,$order);
        return $this;
    }

    public function limit($limit){
        $this->limit = $limit;
        return $this;
    }

    public function offset($offset){
        $this->offset = $offset;
        return $this;
    }
    /**
     * 获取列表集合
     * @return Model[]
     */
    public function get(){
        $this->db->start_cache();
        $this->db->select($this->column);
        $this->db->from($this->model->getTable());
        if($this->where){
            $this->db->where($this->where);
        }
        if($this->order){
            foreach($this->order as $order => $sort){
                $this->db->order_by($order,$sort);
            }
        }
        if($this->limit){
            $this->db->limit($this->limit,$this->offset);
        }
        $query = $this->db->get();
        $tempArray = [];
        foreach($query->result_array() as $row){
            $tempArray[] = $row;
        }
        $this->db->flush_cache();
        return $this->model->resultHandle($tempArray);
    }


    /**
     * @return Model|null
     */
    public function getOne(){
        $this->limit(1);
        $result = $this->get();
        return $result ? $result[0] : null;
    }


    /**
     * @return bool|null
     * @throws \ErrorException
     */
    public function update(){
        if($updateArray = $this->getUpdateFieldAndValue()){
            $primaryKey = $this->model->getPrimaryKey();
            if(isset($this->model->$primaryKey)){
                $primaryValue = $this->model->$primaryKey;
            }else{
                $originField = $this->model->getAttributes()[$primaryKey];
                $primaryValue = $this->model->$originField;
            }
            if(!$primaryValue){
                throw new \ErrorException;
            }
            $this->db->where($primaryKey,$primaryValue);
            return $this->db->update($this->model->getTable(),$updateArray);
        }
        return false;
    }

    private function getUpdateFieldAndValue(){
        if($field = $this->model->getOriginFields()){
            $updateArray = [];
            foreach($field as $item){
                if(isset($this->model->$item)){
                    $updateArray[$item] = $this->model->$item;
                }
            }
            return $updateArray;
        }
        return null;
    }

    public function insert(){
        return true;
    }

    /**
     * 获取最后一条sql
     * @return string
     */
    public function getLastQuery(){
        return $this->db->last_query();
    }
}