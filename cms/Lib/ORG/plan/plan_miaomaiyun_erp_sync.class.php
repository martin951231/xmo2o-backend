<?php

/**
 * 淼迈云ERP同步计划任务
 */
class plan_miaomaiyun_erp_sync extends plan_base
{
    public function runTask()
    {
        $tm = time();
        $taskMod = M('Miaomaiyun_erp_sync_task');
        $tasks = $taskMod->where(['status' => 0])->limit(20)->select();
        import("@.ORG.Erp.ErpService");
        $isOpenMiaomaiyunErp = M('Config')->where(['name' => 'miaomaiyun_erp_open'])->getField('value');
        foreach ($tasks as $k) {
            $taskMod->where(['id' => $k['id']])->save(['status' => 1, 'exec_time' => $tm]);
            if ($isOpenMiaomaiyunErp != 1) {
                continue;
            }
            $params = json_decode($k['params'], true);
            if (empty($params)) {
                continue;
            }
            switch ($params['table']) {
                case 'shop_goods_sort':
                    if ($params['action'] == 'delete') {
                        //ErpService::delGoodsCategory($params['id']);
                    } else {
                        //ErpService::syncGoodsCategory($params['id']);
                    }
                    break;
                case 'shop_goods':
                    //ErpService::syncGoods($params['id']);
                    break;
                case 'user':
                    ErpService::syncUser($params['id']);
                    break;
                case 'user_score_list':
                    ErpService::syncUserScoreChange($params['uid'], $params['score'], $params['desc']);
                    break;

            }
        }
        return true;
    }
}