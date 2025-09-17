<?php

class MerchantAction extends BaseAction
{
    public function index()
    {
        import('@.ORG.merchant_page');
        $qiyeId = $this->enterprise_session['qiye_id'];
		$where = array();
		$where['q.qiye_id'] = $qiyeId;
		if($_GET['province_idss']){
			$where['m.province_id'] = $_GET['province_idss'];
		}
		if($_GET['city_idss']){
			$where['m.city_id'] =$_GET['city_idss'];
		}
		if($_GET['area_id']){
			$where['m.area_id'] =$_GET['area_id'];
		}
        $count = M('Qiye_merchant as q')->join('LEFT JOIN ' . C('DB_PREFIX') . 'merchant AS m ON m.mer_id = q.mer_id')->where($where)->count();
        $p = new Page($count, 15);
        if ($count > 0) {
            $merchants = M('Qiye_merchant')->alias('qm')
                ->join('LEFT JOIN ' . C('DB_PREFIX') . 'merchant AS m ON m.mer_id = qm.mer_id')
                ->join('LEFT JOIN ' . C('DB_PREFIX') . 'merchant_store AS s ON s.mer_id = m.mer_id')
                ->join('LEFT JOIN ' . C('DB_PREFIX') . 'area AS a1 ON a1.area_id = m.province_id')
                ->join('LEFT JOIN ' . C('DB_PREFIX') . 'area AS a2 ON a2.area_id = m.city_id')
                ->join('LEFT JOIN ' . C('DB_PREFIX') . 'area AS a3 ON a3.area_id = m.area_id')
                ->where(['qm.qiye_id' => $qiyeId])->field('qm.id,count(s.store_id) AS store_num,m.*,CONCAT(a1.`area_name`," ",a2.`area_name`," ",a3.`area_name`) AS address')
                ->group('m.mer_id')
                ->order('qm.id DESC')
                ->limit($p->firstRow . ',' . $p->listRows)->select();
        } else {
            $merchants = [];
        }
        $this->assign('merchants', $merchants);
        $this->assign('pagebar', $p->show());
        $this->display();
    }

    public function del()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id < 1) {
            $this->error(L_('参数有误'));
        } else {
            M('Qiye_merchant')->where(['qiye_id' => $this->enterprise_session['qiye_id'], 'id' => $id])->delete();
            $this->success(L_('删除成功'));
        }
    }

    public function add()
    {
        import('@.ORG.merchant_page');
        $where = [];
        if (!empty($_GET['keyword'])) {
            $where['m.name|m.phone'] = ['like', '%' . trim($_GET['keyword']) . '%'];
        }
		if($_GET['province_idss']){
			$where['m.province_id'] = $_GET['province_idss'];
		}
		if($_GET['city_idss']){
			$where['m.city_id'] =$_GET['city_idss'];
		}
		if($_GET['area_id']){
			$where['m.area_id'] =$_GET['area_id'];
		}
        $count = M('Merchant')->alias('m')->where($where)->count();
        $p = new Page($count, 10);
        if ($count > 0) {
            $merchants = M('Merchant')->alias('m')
                ->join('LEFT JOIN ' . C('DB_PREFIX') . 'qiye_merchant AS qm ON qm.mer_id = m.mer_id AND qm.qiye_id = ' . $this->qiyeId)
                ->join('LEFT JOIN ' . C('DB_PREFIX') . 'merchant_store AS s ON s.mer_id = m.mer_id')
                ->join('LEFT JOIN ' . C('DB_PREFIX') . 'area AS a1 ON a1.area_id = m.province_id')
                ->join('LEFT JOIN ' . C('DB_PREFIX') . 'area AS a2 ON a2.area_id = m.city_id')
                ->join('LEFT JOIN ' . C('DB_PREFIX') . 'area AS a3 ON a3.area_id = m.area_id')
                ->field('IF(qm.`id`,"1","0") AS has_choose,count(s.store_id) AS store_num,m.*,CONCAT(a1.`area_name`," ",a2.`area_name`," ",a3.`area_name`) AS address')->where($where)->group('m.mer_id')->limit($p->firstRow . ',' . $p->listRows)->select();
        } else {
            $merchants = [];
        }
        $this->assign('merchants', $merchants);
        $this->assign('pagebar', $p->show());
        $this->display();
    }

    public function modify()
    {
        $merId = isset($_POST['mer_id']) ? intval($_POST['mer_id']) : 0;
        $operate = isset($_POST['operate']) ? trim($_POST['operate']) : '';
        $qiyeId = $this->enterprise_session['qiye_id'];
        $rs = ['error_no' => 0, 'error_msg' => ''];
        try {
            if ($qiyeId < 1) {
                throw new \Exception(L_('企业未登录'));
            }
            if (!in_array($operate, ['add', 'del'])) {
                throw new \Exception(L_('请选择操作'));
            }
            if ($merId < 1) {
                throw new \Exception(L_('请选择一个商家'));
            }
            if ($operate == 'del') {
                if (M('Qiye_merchant')->where(['qiye_id' => $qiyeId, 'mer_id' => $merId])->delete() === false) {
                    throw new \Exception(L_('删除失败'));
                }
            } else {
                if (!M('Qiye')->where(['qiye_id' => $qiyeId])->find()) {
                    throw new \Exception(L_('企业不存在'));
                }
                if (!M('Merchant')->where(['mer_id' => $merId])->find()) {
                    throw new \Exception(L_('商家不存在'));
                }
                if (M('Qiye_merchant')->where(['qiye_id' => $qiyeId, 'mer_id' => $merId])->find()) {
                    throw new \Exception(L_('该商家已是消费商家，请勿重复添加'));
                }
                $data = [
                    'qiye_id' => $qiyeId,
                    'mer_id' => $merId,
                    'add_time' => time()
                ];
                if (!M('Qiye_merchant')->add($data)) {
                    throw new \Exception(L_('添加失败'));
                }
            }

        } catch (\Exception $e) {
            $rs['error_no'] = 1;
            $rs['error_msg'] = $e->getMessage();
        }
        $this->json($rs);
    }
}