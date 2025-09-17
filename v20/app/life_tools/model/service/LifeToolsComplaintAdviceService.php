<?php
/**
 * 投诉建议service
 */

namespace app\life_tools\model\service;

use app\life_tools\model\db\LifeToolsComplaintAdvice; 
use app\life_tools\model\db\User;
use think\facade\Db;

/**
 * 投诉建议service
 */
class LifeToolsComplaintAdviceService
{
    public $lifeToolsComplaintAdviceModel = null;
    public $userModel = null;

    public function __construct()
    {
        $this->lifeToolsComplaintAdviceModel = new LifeToolsComplaintAdvice();
        $this->userModel = new User();
    }

    /**
     * 获取求助列表
     */
    public function getComplaintAdviceList($params)
    {
        $condition = 'is_del = 0';
        if(!empty($params['is_main'])){
            $is_main = $params['is_main'] == 1 ?: 0;
            $condition .= ' AND is_main = ' . $is_main;
        }
        if(!empty($params['keywords'])){
            $condition .= ' AND ( content LIKE "%'.$params['keywords'].'%"';
            $uids = $this->userModel->where([['phone|nickname|real_name', 'like', '%' . $params['keywords'] . '%']])->column('uid');
            if(count($uids)){
                $condition .= ' OR uid IN ('.implode(',', $uids).')';
            }
            $condition .= ')'; 
            
        } 

        $with = [];
        $with['user'] = function($query){
            $query->field(['uid','phone','nickname','real_name']);
        };
        $with['tools'] = function($query){
            $query->field(['tools_id','title']);
        };

        return $this->lifeToolsComplaintAdviceModel
                ->with($with)
                ->where($condition) 
                ->order('add_time DESC')
                ->paginate($params['page_size'])
                ->each(function($item, $key){
                    $item->append(['cont', 'create_time']);
                    $item->hidden(['content']);
                });
    }

     /**
     * 改变投诉建议状态
     */
    public function changeComplaintAdviceStatus($params)
    {
        $condition = [];
        $condition[] = ['pigcms_id', '=', $params['pigcms_id']];
        $ComplaintAdvice = $this->lifeToolsComplaintAdviceModel->where($condition)->find();
        if(!$ComplaintAdvice){
            throw new \think\Exception('记录不存在！');
        }
        $ComplaintAdvice->is_main = $params['is_main'];
        return $ComplaintAdvice->save();
    }

     /**
     * 删除投诉建议
     */
    public function delComplaintAdvice($params)
    {
        $condition = [];
        $condition[] = ['pigcms_id', '=', $params['pigcms_id']];
        $ComplaintAdvice = $this->lifeToolsComplaintAdviceModel->where($condition)->find();
        if(!$ComplaintAdvice){
            throw new \think\Exception('记录不存在！');
        }
        $ComplaintAdvice->is_del = 1;
        return $ComplaintAdvice->save();
    }

    /**
     * 投诉建议详情
     */
    public function getComplaintAdviceDetail($params)
    {
        $condition = [];
        $condition[] = ['pigcms_id', '=', $params['pigcms_id']];
        $condition[] = ['is_del', '=', 0];

        $with = [];
        $with['user'] = function($query){
            $query->field(['uid','phone','nickname','real_name','sex'])->append(['gender']);
        };
        $with['tools'] = function($query){
            $query->field(['tools_id','title','phone','address','area_id'])->append(['area']);
        };

        $ComplaintAdvice = $this->lifeToolsComplaintAdviceModel
                        ->with($with)
                        ->append(['create_time', 'image_arr'])
                        ->where($condition)
                        ->find();
        if(!$ComplaintAdvice){
            throw new \think\Exception('记录不存在！');
        }
        return $ComplaintAdvice;
    }
}