<?php


namespace app\building\model;

use app\api\model\service\building\BuildingApiService;
use think\facade\Db;
use think\Model;

class ShoppingMallMembershipBenefits extends Model
{
    public function getList($where, $field = true)
    {
        $data = (new ShoppingMallBenefits())->alias('b')->field($field)
            ->where($where)
            ->join('shopping_mall_membership_benefits mb', 'mb.b_id = b.id','left')
            ->order('mb.id desc')
            ->select()->each(function ($item) {
                $item['icon'] = $item['icon'] ? replace_file_domain($item['icon']) : '';
                return $item;
            })->toArray();
        return $data;
    }

    public function userLevels($mallId)
    {
        $levels = Db::name('user_level')->field('id,level,lname,icon,validity')->select()->toArray();
        if ($levels) {
            //所有权益列表
            $benefits_list = (new ShoppingMallBenefits())
                ->field('*,0 as is_checked,0 as coupon_id,"" as coupon_name,0 as merchant_id')
                ->select()
                ->each(function ($benefit) {
                    $benefit['icon'] = $benefit['icon'] ? replace_file_domain($benefit['icon']) : '';
                    return $benefit;
                })->toArray();
            $benefits_list = array_column($benefits_list, null, 'id');
            foreach ($levels as &$level) {
                $level['icon'] = $level['icon'] ? replace_file_domain($level['icon']) : '';
                //重组当前等级权益列表
                $new_benefits_list = $benefits_list;
                //当前等级已配置权益列表
                $level_benefits_list = $this->field('b_id,coupon_id,coupon_name,merchant_id,1 as is_checked,coupon_data')->where(['mall_id' => $mallId, 'level' => $level['level']])->select()->toArray();
                $title_configured_arr = []; //已配置的权益标题
                foreach ($level_benefits_list as $benefit) {
                    $benefit['coupon_data'] = $benefit['coupon_data'] ? json_decode($benefit['coupon_data'], true) : '{}';
                    if (isset($new_benefits_list[$benefit['b_id']])) {
                        $title_configured_arr[] = $new_benefits_list[$benefit['b_id']]['title'];
                        $new_benefits_list[$benefit['b_id']] = array_merge($new_benefits_list[$benefit['b_id']], $benefit);
                    }
                }
                $level['benefits'] = $new_benefits_list ? array_values($new_benefits_list) : [];
                $level['parking_config_txt'] = $title_configured_arr ? implode('、', $title_configured_arr) : '未配置';
            }
        }
        return $levels;
    }
    
    public function saveMembershipBenefits($params){
        $mall_id = $params['mall_id'];
        $level = $params['level'];
        $benefits = $params['benefits'];
        $uid = $params['uid'];
        if (empty($mall_id)) {
            throw new \Exception("请选择商场");
        }
        if(!$level){
            throw new \Exception("请选择会员等级");
        }
        if(!$benefits || !is_array($benefits)){
            throw new \Exception("请配置会员权益");
        }
        $b_ids = array_column($benefits,'id');
        $b_info = (new ShoppingMallBenefits())->where([['id','in',$b_ids]])->column('id');
        if(!$b_info){
            throw new \Exception("请先添加权益在配置");
        }
        $old_info = $this->where(['mall_id'=>$mall_id,'level'=>$level])->column('id','b_id');
        Db::startTrans();
        try{
            foreach ($benefits as $benefit) {
                if(in_array($benefit['id'],$b_info)){
                    if(isset($old_info[$benefit['id']])){
                        $this->where(['id'=>$old_info[$benefit['id']]])->save([
                            'coupon_id' => $benefit['coupon_id']?:0,
                            'coupon_name'=>$benefit['coupon_name']??'',
                            'merchant_id'=>$benefit['merchant_id']??0,
                            'coupon_data'=>isset($benefit['coupon_data'])?json_encode($benefit['coupon_data'],JSON_UNESCAPED_UNICODE):'',
                            'update_time' => time()
                        ]);
                    }else{
                        (new ShoppingMallMembershipBenefits())->save([
                            'mall_id' => $params['mall_id'],
                            'level' => $params['level'],
                            'b_id' => $benefit['id'],
                            'coupon_id' => $benefit['coupon_id']??0,
                            'coupon_name'=>$benefit['coupon_name']??'',
                            'merchant_id'=>$benefit['merchant_id']??0,
                            'coupon_data'=>isset($benefit['coupon_data'])?json_encode($benefit['coupon_data'],JSON_UNESCAPED_UNICODE):'',
                            'create_time' => time(),
                            'create_uid' => $uid,
                        ]);
                    }
                }
            }
            $this->where([['level','=',$params['level']],['mall_id','=',$params['mall_id']],['b_id','not in',$b_info]])->delete();
            Db::commit();
            return true;
        }catch (\Exception $e){
            Db::rollback();
            throw new \Exception($e->getMessage());
        }
        return true;
    }
}