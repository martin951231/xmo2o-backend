<?php
/**
 * MerchantCorrService.php
 * 营业信息纠错service
 * Create on 2021/5/8
 * Created by wangchen
 */

namespace app\merchant\model\service;

use app\merchant\model\db\MerchantCorr;

class MerchantCorrService
{
    public function __construct()
    {
        $this->MerchantCorrModel = new MerchantCorr();
    }

    /**
     * 根据条件查询service
     * @param $mer_id
     * @param $store_id
     * @param $begin_time
     * @param $end_time
     * @param $page
     * @param $pageSize
     * @return array|mixed
     */
    public function searchCorr($type, $content, $begin_time, $end_time, $status,$page, $pageSize)
    {
        $where = [];
        if (!empty($begin_time) && !empty($end_time)) {
            $arr = [['a.add_time', '>=', strtotime($begin_time)], ['a.add_time', '<=', strtotime($end_time)]];
            $where = array_merge($where, $arr);
        }
        if ($type == 1) {
            //按照店铺搜索
            $store_name = $content;
            $arr = [['m1.name', 'like', '%' . $store_name . '%']];
            $where = array_merge($where, $arr);
        }
        if ($status != 2) {
            $arr = [['a.status', '=', $status]];
            $where = array_merge($where, $arr);
        }
        $list = $this->MerchantCorrModel->getAll($where, $page, $pageSize);

        if (!empty($list)) {
            //处理图片
            foreach ($list as $key => $val) {
                // 图片多张以英文分号隔开
                if (!empty($val['pic'])) {
                    $pic = [];
                    $pic_arr = explode(";",$val['pic']);
                    foreach ($pic_arr as $val1) {
                        $pic[] =  replace_file_domain($val1);
                    }
                    $list[$key]['pic']  = $pic;
                } else {
                    $list[$key]['pic'] = [];
                }
                // 操作处理
                $list[$key]['actions'] = ['id'=>$val['id'],'status'=>$val['status']];

                $list[$key]['pic'] = replace_file_domain($val['pic']);
                $list[$key]['add_time'] = date('Y-m-d H:i:s', $val['add_time']);
            }

            //获取总数
            $list1['list'] = $list;
            $list1['count'] = $this->MerchantCorrModel->getCorrCount($where);
            // print_r($list1);
            return $list1;
        } else {
            return [];
        }
    }

    /**
     * 查看反馈内容service
     * @param $id
     * @return array
     * @throws \think\Exception
     */
    public function getCorrDetails($id)
    {
        if (empty($id)) {
            throw new \think\Exception('id参数缺失');
        }
        $where = ['id' => $id];
        $arr = $this->MerchantCorrModel->getByCondition($where);

        if (!empty($arr)) {
			//评论图片
			if($arr['pic']){
				$arr['pic'] = explode(';', $arr['pic']);
				foreach ($arr['pic'] as $key => $val) {
					$arr['pic'][$key] = replace_file_domain($val);
				}
			}

            $details = [
                'content' => $arr['content'],
                'pic' => $arr['pic'] ?: [],
            ];
            return $details;
        } else {
            return [];
        }
    }

    /**
     * 设置为已处理
     * @param $id
     * @return MallGoodReply
     * @throws \think\Exception
     */
    public function getEditCorr($id)
    {
        if (empty($id)) {
            throw new \think\Exception('id参数缺失');
        }
        $where = ['id' => $id];
        $data = [
            'status' => 1
        ];
        $result = $this->MerchantCorrModel->getEditCorr($where, $data);
        if ($result === false) {
            throw new \think\Exception('操作失败，请重试');
        }
        return $result;
    }

    public function add($data)
    {
        return $this->MerchantCorrModel->insertGetId($data);
    }
}