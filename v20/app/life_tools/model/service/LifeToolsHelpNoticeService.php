<?php
/**
 * 寻人求助service
 */

namespace app\life_tools\model\service;

use app\common\model\db\Area;
use app\life_tools\model\db\LifeToolsHelpNotice; 
use app\life_tools\model\db\User;
use map\Coordinate;

/**
 * 寻人求助service
 */
class LifeToolsHelpNoticeService
{
    public $lifeToolsHelpNoticeModel = null;
    public $userModel = null;

    public function __construct()
    {
        $this->lifeToolsHelpNoticeModel = new LifeToolsHelpNotice();
        $this->userModel = new User();
    }

    /**
     * 获取求助列表
     */
    public function getHelpNoticeList($params)
    {
        $condition = 'is_del = 0';
        if(!empty($params['is_solve'])){
            $is_main = $params['is_solve'] == 1 ?: 0;
            $condition .= ' AND is_solve = ' . $is_main;
        }
        if(!empty($params['keywords'])){
            $condition .= ' AND (title LIKE "%'.$params['keywords'].'%" OR content LIKE "%'.$params['keywords'].'%"';
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

        return $this->lifeToolsHelpNoticeModel
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
     * 删除寻人求助
     */
    public function delHelpNotice($params)
    {
        $condition = [];
        $condition[] = ['pigcms_id', '=', $params['pigcms_id']];
        $helpNotice = $this->lifeToolsHelpNoticeModel->where($condition)->find();
        if(!$helpNotice){
            throw new \think\Exception('记录不存在！');
        }
        $helpNotice->is_del = 1;
        return $helpNotice->save();
    }

    /**
     * 寻人求助详情
     */
    public function getHelpNoticeDetail($params)
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

        $helpNotice = $this->lifeToolsHelpNoticeModel
                        ->with($with)
                        ->append(['create_time', 'image_arr'])
                        ->where($condition)
                        ->find();
        if(!$helpNotice){
            throw new \think\Exception('记录不存在！');
        }
        $helpNotice->ak = cfg('baidu_map_ak');
        $helpNotice->address = (new Area())->getAreaByAreaId($helpNotice->area_id);

        if (cfg('tianditu_web_key')) {
            list($helpNotice->lng, $helpNotice->lat) = Coordinate::bd09ToWgs84($helpNotice->lng, $helpNotice->lat);
        }
        return $helpNotice;
    }

    /**
     * 改变寻人求助状态
     */
    public function changeHelpNoticeStatus($params)
    {
        $condition = [];
        $condition[] = ['pigcms_id', '=', $params['pigcms_id']];
        $helpNotice = $this->lifeToolsHelpNoticeModel->where($condition)->find();
        if(!$helpNotice){
            throw new \think\Exception('记录不存在！');
        }
        $helpNotice->is_solve = $params['is_solve'];
        return $helpNotice->save();
    }
}