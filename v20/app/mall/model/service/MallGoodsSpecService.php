<?php

/**
 * @Author: jjc
 * @Date:   2020-06-12 10:48:46
 * @Last Modified by:   jjc
 * @Last Modified time: 2020-06-12 14:10:56
 */

namespace app\mall\model\service;

use app\mall\model\db\MallGoodsSku;
use app\mall\model\db\MallGoodsSpec as MallGoodsSpecModel;
use app\mall\model\db\MallGoodsSpec;
use app\mall\model\service\MallGoodsSpecValService as MallGoodsSpecValService;
use think\facade\Cache;

class MallGoodsSpecService
{

    public $MallGoodsSpecModel = null;

    public function __construct()
    {
        $this->MallGoodsSpecModel = new MallGoodsSpecModel();
    }

    //获取商品规格属性值
    public function getList($goodsId)
    {
        if (!$goodsId) {
            throw new \think\Exception("goodsId缺失！");
        }
        $spec_where = [
            ['goods_id', '=', $goodsId],
            ['is_del', '=', 0],
        ];
        $spce_list = $this->MallGoodsSpecModel->getSpecList($spec_where);

        if ($spce_list) {
            $spec_ids = array_column($spce_list, 'spec_id');
            $spec_val_where = [
                ['spec_id', 'in', $spec_ids],
                ['is_del', '=', 0],
            ];
            $spec_val_mod = new MallGoodsSpecValService();
            $spec_val = $spec_val_mod->getList($spec_val_where);
            foreach ($spce_list as $key => $val) {
                $spce_list[$key]['val_list'] = $spec_val[$val['spec_id']];
            }
            return $spce_list;
        } else {
            return [];
        }
    }


    //获取商品规格属性值且商品信息
    public function getList1($goodsId, $spec_info, $spec_info1, $num)
    {
        //当规格不传
        if ($goodsId && empty($spec_info)) {
            $spec_where = [
                ['goods_id', '=', $goodsId],
                ['is_del', '=', 0],
            ];
            $spce_list = $this->MallGoodsSpecModel->getSpecList($spec_where);
            if ($spce_list) {
                $spec_ids = array_column($spce_list, 'spec_id');
                $spec_val_where = [
                    ['spec_id', 'in', $spec_ids],
                    ['is_del', '=', 0],
                ];
                $spec_val_mod = new MallGoodsSpecValService();
                $spec_val = $spec_val_mod->getList($spec_val_where);

                foreach ($spce_list as $key => $val) {
                    foreach ($spec_val[$val['spec_id']] as $k => $v) {
                        $spec_val[$val['spec_id']][$k]['is_show'] = 1;//是否可点击
                        $spec_val[$val['spec_id']][$k]['is_active'] = 0;//是否选中
                    }
                    $spce_list[$key]['val_list'] = $spec_val[$val['spec_id']];
                }
                Cache::set("spec_val", $spce_list);
                return $spce_list;
            } else {
                return [];
            }
        } elseif ($goodsId && !empty($spec_info)) {
            //规格和规格值传
            //存起原来搜索的规格，在这个基础上改状态
            $arr = Cache::get('spec_val');
            $arr3 = explode(":", $spec_info);//传过来的点击规格和规格值分割数组
            //组装成点击状态
            foreach ($arr as $key => $val) {
                if ($val['spec_id'] == $arr3[0]) {
                    foreach ($val['val_list'] as $key1 => $val1) {
                        if ($val1['id'] == $arr3[1]) {
                            $arr[$key]['val_list'][$key1]['is_active'] = 1;
                        }
                    }
                }
            }

            //条件查出库存为零的数据;
            $spec_where = [
                ['goods_id', '=', $goodsId],
                ['is_del', '=', 0],
                ['sku_info', 'like', "%" . $spec_info . "%"],
                ['stock_num', '=', 0]
            ];
            $MallGoodsSku = new MallGoodsSku();
            $spce_list = $MallGoodsSku->getZeroGood($spec_where, "sku_info");
            if ($spce_list) {
                foreach ($spce_list as $k => $v) {
                    if (strpos($v['sku_info'], '|')) {
                        $arr1 = explode("|", $v['sku_info']);
                        //去掉搜索出来的子串里面的的规格属性id，重新组合
                        unset($arr1[array_search($spec_info, $arr1)]);
                        foreach ($arr1 as $k1 => $v1) {
                            $arr2 = explode(":", $v1);
                            $spec_id = $arr2[0];//规格id
                            $spec_val_id = $arr2[1];//规格值id
                            //对应修改is_show
                            foreach ($arr as $k2 => $v2) {
                                foreach ($v2['val_list'] as $k3 => $v3) {
                                    if ($v3['spec_id'] == $spec_id && $v3['id'] == $spec_val_id) {
                                        $arr[$k2]['val_list'][$k3]['is_show'] = 0;
                                    }
                                }
                            }
                        }
                    }
                }
                //组装修改完成传回
                Cache::set("spec_val", $arr);
            }
            //$arr['pay_status']=0;
            return $arr;
        }
        /*}*/
    }

    public function getList2($goodsId, $spec_info, $spec_info1, $num)
    {
        //var_dump($arr);
        $spec_where = [
            ['goods_id', '=', $goodsId],
            ['is_del', '=', 0],
            ['sku_info', '=', $spec_info1]
        ];
        $field = "image,price,cost_price,stock_num,max_num,sku_str,sku_info,sku_id";
        $MallGoodsSku = new MallGoodsSku();
        $spce_list = $MallGoodsSku->getGood($spec_where, $field);
        $arr = Cache::get('spec_val');
        if (strpos($spec_info1, '|')) { //当有多个规格时候
            //var_dump("9999999999");
            $arr4 = explode("|", $spec_info1);
            foreach ($arr4 as $it => $iv) {
                //传过来的点击规格和规格值分割数组
                $arr3 = explode(":", $iv);
                //选中的组装成点击状态
                foreach ($arr as $key => $val) {
                    foreach ($arr3 as $key7 => $val7) {
                        if ($val['spec_id'] == $arr3[0]) {
                            foreach ($val['val_list'] as $key1 => $val1) {
                                if ($val1['id'] == $arr3[1]) {
                                    $arr[$key]['val_list'][$key1]['is_active'] = 1;
                                }
                            }
                        }
                    }
                }
            }
        } else {
            //只有一个规格时候
            $arr3 = explode(":", $spec_info);//传过来的点击规格和规格值分割数组
            //组装成点击状态
            foreach ($arr as $key => $val) {
                if ($val['spec_id'] == $arr3[0]) {
                    foreach ($val['val_list'] as $key1 => $val1) {
                        if ($val1['id'] == $arr3[1]) {
                            $arr[$key]['val_list'][$key1]['is_active'] = 1;
                        }
                    }
                }
            }
        }
        $rtn[0] = $arr;
        $rtn[1] = $spce_list;

        return $rtn;
    }

    //获取商品规格及规格数量
    public function getSpecStatus($goodsId)
    {
        $spec_where = [
            ['goods_id', '=', $goodsId],
            ['is_del', '=', 0],
        ];
        $arr = (new MallGoodsSpec())->getSpecList($spec_where);
        //返回数量
        return count($arr);
    }

    /**
     * @param $where
     * @return mixed
     * 删除
     */
    public function delSome($where)
    {
        $res = $this->MallGoodsSpecModel->delSome($where);
        return $res;
    }

    /**
     * @param $data
     * @return mixed
     * 添加数据
     */
    public function addOne($data){
        $res = $this->MallGoodsSpecModel->addOne($data);
        return $res;
    }
}
