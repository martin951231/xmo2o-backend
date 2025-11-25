<?php


namespace app\common\model\service;


use app\common\model\db\ConfigData;
use app\common\model\db\Merchant;
use app\common\model\db\MerchantContract;
use think\facade\Db;

class MerchantContractService
{
    /**
     * 获取商家签约合同列表
     * @param $param
     * @return \think\Collection
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($param){
        $mer_id = $param['mer_id']?:0;
        if(!$mer_id){
            throw new \think\Exception(L_('商家信息有误！'));
        }
        $where[] = ['mer_id','=',$mer_id];
        $list = (new MerchantContract())->field('id,mer_id,contract_number,FROM_UNIXTIME(sign_time,"%Y-%m-%d %H:%i:%s") as sigin_time')->where($where)->order('id desc')->select();
        foreach ($list as $k=>$v){
            $list[$k]['detail_url'] = cfg('site_url').'/admin.php?g=System&c=Merchant&a=viewContract&mer_id='.$v['mer_id'].'&id='.$v['id'];
            $list[$k]['download_url'] = cfg('site_url').'/admin.php?g=System&c=Merchant&a=downloadContract&mer_id='.$v['mer_id'].'&id='.$v['id'];
        }
        return $list;
    }

    /**
     * 重新签署合同文案修改
     * @param $param
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addResignTip($param){
        $tips = $param['tips']?:'';
        if(!$tips){
            throw new \think\Exception(L_('重签文案不能为空！'));
        }
        //查询是否有信息
        $config_data_model = new ConfigData();
        $where['name'] = 'contract_resign_tip';
        $data_info = $config_data_model->where($where)->find();
        Db::startTrans();
        try {
            $saveData = [
                'value' => $tips,
            ];
            if($data_info){
                $config_data_model->where($where)->save($saveData);
            }else{
                $saveData['value'] = $tips;
                $saveData['name'] = 'contract_resign_tip';
                $saveData['gid'] = 0;
                $config_data_model->save($saveData);
            }
            //修改已签约商家为需要重新签约状态
            (new Merchant())->where(['is_sign_contract'=>1])->save(['is_sign_contract'=>2,'contract_end_time'=>NULL]);
            (new MerchantContract())->where(['status'=>1])->save(['status'=>0]);
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            throw new \think\Exception(L_($e->getMessage()));
        }
    }
}