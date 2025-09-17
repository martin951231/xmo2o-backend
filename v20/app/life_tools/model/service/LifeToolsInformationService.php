<?php
/**
 * 景区首页装修service
 */

namespace app\life_tools\model\service;

use app\life_tools\model\db\LifeToolsInformation;
use think\facade\Db;

/**
 * 资讯service
 */
class LifeToolsInformationService
{
    public $lifeToolsInformationModel = null;
    public function __construct()
    {
        $this->lifeToolsInformationModel = new LifeToolsInformation();
    }

    /**
     * 获取资讯列表
     */
    public function getInformationList($params)
    {
        $params['type'] = $params['type'] ?? 'sports';
        $condition = [];
        $condition[] = ['is_del', '=', 0];
        $condition[] = ['type', '=', $params['type']];

        if(isset($params['from']) && $params['from'] == 'user'){// 显示时间限制
            $condition[] = ['show_type', 'exp', Db::raw('=1 OR (start_time<='. time() .' AND end_time>=' . time() . ')')];
        }

        // 排序值
        $order = [
            'add_time' => 'DESC',
        ];

        if(isset($params['sort_value']) && $params['sort_value'] == 1){// 人气排序
            $order = [
                'view_count' => 'DESC',
                'add_time' => 'DESC',
            ];
        }

        if(!empty($params['keywords'])){
            $condition[] = ['title|content', 'like', '%'.$params['keywords'].'%'];
        }
        $data = $this->lifeToolsInformationModel 
                ->where($condition)
                ->order($order)
                ->paginate($params['page_size'])
                ->each(function($item, $key){
                    $item->append(['cont','start_time_text','end_time_text','add_time_text']);
                    $item->hidden(['content']);
                    $item->id = $item->pigcms_id;
                    if($item->images){
                        $item->images = explode(',', $item->images);
                        $item->images = array_map('replace_file_domain', $item->images);
                    }else{
                        $item->images = [];
                    }
                });

       
        return $data;
    }

    /**
     * 添加修改资讯
     */
    public function addEditInformation($params)
    {
        if(!in_array($params['type'], ['sports', 'scenic'])){
            throw new \think\Exception('类型不存在！');
        }

        if($params['show_type'] == 2 && (empty($params['start_time']) || empty($params['end_time']))){
            throw new \think\Exception('请选择日期！');
        }

        if($params['show_type'] == 2 && (strtotime($params['end_time'] . ' 23:59:59') < time())){
            throw new \think\Exception('不可添加已过期时间！');
        }

        if(empty($params['images'])){
            throw new \think\Exception('images不能为空！');
        }


        //添加
        if(empty($params['pigcms_id'])){
            
            $Information = $this->lifeToolsInformationModel; 
            $Information->add_time = time(); 
        }else{
            $condition = [];
            $condition[] = ['pigcms_id', '=', $params['pigcms_id']];
            $condition[] = ['is_del', '=', 0];
            $Information = $this->lifeToolsInformationModel->where($condition)->find();
            if(!$Information){
                throw new \think\Exception('资讯不存在！');
            }
        }
        $Information->title = $params['title'];
        $Information->images = rtrim($params['images'], ',');
        $Information->tools_id = $params['tools_id'] ?? 0;
        $Information->type = $params['type'];
        $Information->content = $params['content'];
        $Information->show_type = $params['show_type'];
        $Information->start_time = $params['show_type'] == 2 ? strtotime($params['start_time']) : 0;
        $Information->end_time = $params['show_type'] == 2 ? strtotime($params['end_time'] . ' 23:59:59') : 0;
        $Information->is_del = 0;
        return $Information->save();
    }

    /**
     * 获取资讯详情
     */
    public function getInformationDetail($params)
    {
        $condition = [];
        $condition[] = ['pigcms_id', '=', $params['pigcms_id']];
        $condition[] = ['is_del', '=', 0];

        if(isset($params['from']) && $params['from'] == 'user'){// 显示时间限制
            $condition[] = ['show_type', 'exp', Db::raw('=1 OR (start_time<='. time() .' AND end_time>=' . time() . ')')];
        }

        $Information = $this->lifeToolsInformationModel
                        ->where($condition)
                        ->append(['start_time_text', 'end_time_text','add_time_text', 'images_arr'])
                        ->find();
        if(!$Information){
            throw new \think\Exception('资讯不存在！');
        }

        $Information->content = replace_file_domain_content_img($Information->content);
        $Information->content_html = $Information->content;
        if(isset($params['from']) && $params['from'] == 'user'){// 添加浏览量
            $this->lifeToolsInformationModel->where($condition)->inc('view_count',1)->update();
        }
        return $Information;
    }

    /**
     * 删除资讯
     */
    public function delInformation($params)
    {
        $condition = [];
        $condition[] = ['pigcms_id', '=', $params['pigcms_id']];
        $condition[] = ['is_del', '=', 0];
        $Information = $this->lifeToolsInformationModel
                        ->where($condition)
                        ->append(['start_time_text', 'end_time_text','content_html'])
                        ->hidden(['content'])
                        ->find();
        if(!$Information){
            throw new \think\Exception('资讯不存在！');
        } 

        $Information->is_del = 1; 
        return $Information->save();
    }
}