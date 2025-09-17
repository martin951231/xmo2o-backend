<?php
/**
 * 缓存
 * User: 衡婷妹
 * Date: 2020/09/26 11:30
 */

namespace app\common\model\service;

use app\common\model\db\FdumpSql;
//use think\facade\Db;

class FdumpSqlService
{
    public $fdumpSqlModel = null;
    public function __construct()
    {
        $this->fdumpSqlModel = new FdumpSql();
    }


    /**
     * 添加记录
     * @author: 衡婷妹
     * @date: 2020/09/26
     */
    public function add($data)
    {
        $id = $this->fdumpSqlModel->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }
    public function delFdumpSqlData($limit=300){
        $returnArr=array('start_time'=>date('Y-m-d H:i:s'),'end_time'=>0,'num'=>$limit);
        $whereArr=array(['id','>',0]);
        $retObj=$this->fdumpSqlModel->getListData($whereArr,'id','id ASC',1,$limit);
        if($retObj && !$retObj->isEmpty()){
            $retTmp=$retObj->toArray();
            foreach ($retTmp as $vv){
                $tmpWhere=array(['id','=',$vv['id']]);
                $this->fdumpSqlModel->del_data($tmpWhere);
            }
        }
        $returnArr['end_time'] =date('Y-m-d H:i:s');
        return $returnArr;
    }
    public function getListData($where, $field = '*', $orderby = 'id DESC', $page = 1, $page_size = 10)
    {
        $res=$this->fdumpSqlModel->getListData($where, $field , $orderby , $page , $page_size );
        return $res;
    }
    public function del_data($where=array())
    {
         $res=$this->fdumpSqlModel->del_data();
        return $res;
    }
}