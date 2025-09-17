<?php


namespace app\group\model\service;


use app\common\model\db\Config;
use app\common\model\service\CacheSqlService;
use think\facade\Cache;

class GroupHomeMenuService
{
    /**
     * 附近好点是否团购首页展示
     * @param $is_show
     * @return bool
     * @throws \think\Exception
     */
    public function changeShow($is_show){
        $is_show = $is_show?1:0;
        $res = (new Config())->where(['name'=>'group_good_store_nearby'])->save(['value' => $is_show]);
        if ($res) {
            return true;
        }else{
            throw new \think\Exception('操作失败，请重试');
        }
    }

    /**
     * 获取装修渲染的信息
     * @return array|int[]
     */
    public function getShow(){
        $res = (new Config())->where(['name'=> 'group_good_store_nearby'])->find();

        $is_show = $res?intval($res['value']):0;

        $url = get_base_url('pages/group/index/home?currentPage=index');
        //活动推荐配置
        $group_activity = [
            'data' => [
                ['value'=>'0','label'=>'店铺活动推荐'],
                ['value'=>'1','label'=>'商品活动推荐'],
            ],
            'group_activity_recommendations' => (new Config())->where(['name'=> 'group_activity_recommendations'])->value('value'),
        ];
        return ['is_show' => $is_show, 'url' => $url, 'group_activity' => $group_activity];
    }

    /**
     * 团购首页顶部配置-编辑
     */
    public function getHomeTopConfig(){
        $res['top_config'] = [
            'group_home_top_bg_style' => '0',
            'group_home_top_bg_value' => ''
        ];
        $top_info = (new Config())->where([['name','in', ['group_home_top_bg_style','group_home_top_bg_value']]])
            ->column('value','name');
        if($top_info){
            if($top_info['group_home_top_bg_style']=='1'){
                $top_info['group_home_top_bg_value'] = replace_file_domain($top_info['group_home_top_bg_value']);
            }
            $res['top_config'] = $top_info;
        }
        return $res;
    }

    /**
     * 团购首页顶部配置-编辑
     * @param $params
     * @return bool
     * @throws \think\Exception
     */
    public function editHomeTopConfig($params){
        if(!in_array($params['group_home_top_bg_style'],['0','1'])){
            throw new \think\Exception(L_('背景类型错误！'));
        }
        try {
            foreach ($params as $key=>$val){
                (new Config())->where(['name'=>$key])->save(['value'=>$val]);
            }
        }catch (\Exception $e){
            throw new \think\Exception(L_($e->getMessage()));
        }
        return true;
    }

    /**
     * 团购首页配置-活动推荐选择
     */
    public function changeActivityType($params){
        $group_activity_recommendations = $params['group_activity_recommendations'];
        if(!in_array($group_activity_recommendations,[0,1])){
            throw new \think\Exception(L_('参数错误！'));
        }
        try {
            (new Config())->where(['name'=>'group_activity_recommendations'])->save(['value'=>$group_activity_recommendations]);
            (new CacheSqlService())->clearCache();
        }catch (\Exception $e){
            throw new \think\Exception(L_($e->getMessage()));
        }
        return true;
    }
}