<?php
/**
 * 餐饮桌台service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/30 11:30
 */

namespace app\foodshop\model\service\order_print;
use app\foodshop\model\db\DiningPrintRule;
use app\foodshop\model\service\goods\FoodshopGoodsLibraryService;
use app\foodshop\model\service\goods\FoodshopGoodsSortService;

class DiningPrintRuleService {
    public $diningPrintRuleModel = null;
    public function __construct()
    {
        $this->diningPrintRuleModel = new DiningPrintRule();
    }


    /**
     * 根据条件返回列表
     * @param $where array 条件
     * @return array
     */
    public function getPrintRuleList($param){
        $storeId = $param['store_id'] ?? 0;
        $pageSize = $param['pageSize'] ?? 10;
        $page = $param['page'] ?? 1;
        $page = 10*($page-1);

        $where['store_id'] = $storeId;
        $order = [
            'id' => 'desc'
        ];
        $list = $this->getSome($where, true, $order, $page, $pageSize);
        $count = $this->getCount($where);

        foreach ($list as &$rule){
            $where = [
                'rule_id' =>   $rule['id']
            ];
            $print_count = (new DiningPrintRulePrintService())->getCount($where);
            $rule['print_count'] = $print_count;
        }
        $returnArr = [];
        $returnArr['list'] = $list;
        $returnArr['total_count'] = $count;

        return $returnArr;
    }


    /**
     * 根据id获取打印规则信息
     * @param $param array
     * @return array
     */
    public function getDetail($param){
        $id = $param['id'] ?? 0;
        if(!$id){
            throw new \think\Exception(L_("缺少参数"),1001);
        }

        // 打印规则信息
        $where = [];
        $where['id'] = $id;
        $where['store_id'] = $param['store_id'];
        $returnArr = $this->getOne($where);
        if(!$returnArr) {
            throw new \think\Exception(L_("打印规则不存在"),1003);
        }

        // 绑定的打印机
        $printList = (new DiningPrintRulePrintService())->getSome(['rule_id'=>$id]);

        // 绑定的分类或商品
        $goodsList = (new DiningPrintRuleGoodsService())->getSome(['rule_id'=>$id]);
        $goodsDetailList = [];
        if($returnArr['dangkou_select'] == 2 && $goodsList){
            $ids = array_column($goodsList,'bind_id');
            $where = [
                'goods_ids' => implode(',',$ids)
            ];
            $goodsDetailList = (new FoodshopGoodsLibraryService())->getGoodsList($where)['list'];
            $sortId = array_column($goodsDetailList,'spec_sort_id');
            $sortList = (new FoodshopGoodsSortService())->getList([['sort_id','in',implode(',',$sortId)] ]);
            $sortList = array_column($sortList,'sort_name','sort_id');
            foreach ($goodsDetailList as &$_goods){
                $_goods['sort_name'] = $sortList[$_goods['spec_sort_id']] ?? '';
            }

        }

        $returnArr['print_list'] = array_column($printList,'print_id');
        $returnArr['goods_list'] = $returnArr['dangkou_select'] == 2 ? array_column($goodsList,'bind_id') : [];
        $returnArr['goods_detail_list'] = $returnArr['dangkou_select'] == 2 ? $goodsDetailList : [];
        $returnArr['goods_sort_list'] = $returnArr['dangkou_select'] == 1 ? array_column($goodsList,'bind_id') :[];
        $returnArr['print_type'] = empty($returnArr['print_type']) ? [] : explode(',', $returnArr['print_type']);
        $returnArr['front_print'] = empty($returnArr['front_print']) ? [] : explode(',', $returnArr['front_print']);
        $returnArr['back_print'] = empty($returnArr['back_print']) ? [] : explode(',', $returnArr['back_print']);
        $returnArr['reciept_type'] = $returnArr['reciept_type'];

        return $returnArr;
    }


    /**
     * 保存或添加规则
     * @param $storeId
     * @author 衡婷妹
     * @date 2020/09/21
     */
    public function editPrintRule($param)
    {

        $id = $param['id'] ?? 0;
        $storeId = $param['store_id'] ?? 0;

        if(empty($storeId)){
            throw new \think\Exception(L_("缺少参数"),1003);
        }

        $data = [
            'name' => $param['name'] ?? '',
            'number' => $param['number'] ?? 0,
            'reciept_type' => $param['reciept_type'] ?? 1,
        ];

        if(isset($param['dangkou_select']) && $param['dangkou_select']>0){
            // 档口选择
            $data['dangkou_select'] = $param['fendangkou_select'] ?? 0;
        }else{
            $data['dangkou_select'] = 0;
        }

        if(isset($param['print_type']) && $param['print_type']){
            $data['print_type'] = implode(',',$param['print_type']);
        }else{
            $data['print_type'] = '';
        }
        if(isset($param['front_print']) && $param['front_print']){
            $data['front_print'] = implode(',',$param['front_print']);
        }else{
            $data['front_print'] = '';
        }

        if(isset($param['back_print']) && $param['back_print']){
            $data['back_print'] = implode(',',$param['back_print']);
        }else{
            $data['back_print'] = '';
        }

        $updateTableTypeIds = [];
        if ($id > 0) {
            //修改
            $where = [
                'id' => $id
            ];

            $item = $this->getOne($where);
            if (empty($item)) {
                throw new \think\Exception("数据不存在");
            }
            $rs = $this->updateThis($where, $data);

        } else {
            $data['store_id'] = $storeId;
            $data['mer_id'] = $param['mer_id'] ?? 0;

            //新增
            $rs = $id = $this->add($data);
        }

        if($rs === false){
            if($id){
                throw new \think\Exception("修改失败");
            }else{
                throw new \think\Exception("添加失败");
            }
        }

        //删除绑定的商品或分类数据
        $whereBind = [
            'rule_id' => $id
        ];
        (new DiningPrintRuleGoodsService())->delthis($whereBind);

        //删除绑定的打印机
        (new DiningPrintRulePrintService())->delthis($whereBind);


        // 分档口 保存绑定的商品或分类数据
        if(isset($param['dangkou_select']) && $param['dangkou_select'] != 0){
                if(isset($param['dangkou_select_goods'] ) && $param['dangkou_select_goods'] ){
                $bindData = [];
                foreach ($param['dangkou_select_goods'] as $bind){
                    if($bind){
                        $bindData[] = [
                            'store_id' =>   $storeId,
                            'bind_id' =>   $bind,
                            'rule_id' =>   $id,
                        ];
                    }
                }
                (new DiningPrintRuleGoodsService())->addAll($bindData);
            }
        }

        // 保存绑定的打印机
        if(isset($param['print_list'] ) && $param['print_list'] ){
            $bindData = [];
            foreach ($param['print_list'] as $bind){
                if($bind) {
                    $bindData[] = [
                        'store_id' => $storeId,
                        'print_id' => $bind,
                        'rule_id' => $id,
                    ];
                }
            }
            (new DiningPrintRulePrintService())->addAll($bindData);
        }

        return true;
    }

    /**
     * 获得打印机选择商品列表
     * @param $where array 条件
     * @return array
     */
    public function getPrintGoodsList($param){
        $storeId = $param['store_id'] ?? 0;
        $sortId = $param['sort_id'] ?? 0;
        $name= $param['keywords'] ?? 0;
        $id = $param['id'] ?? 0; // 规则id

        $where = [
            'store_id' => $storeId,
            'sort_id' => $sortId,
            'name' => $name,
        ];
        $goodsList = (new FoodshopGoodsLibraryService())->getGoodsList($where);

        $selectGoodsArr = [];
        if($id){
            $where = [
                'store_id' => $storeId,
                'rule_id' => $id,
            ];
            $selectGoods = (new DiningPrintRuleGoodsService())->getSome($where);
            $selectGoodsArr = array_column($selectGoods,'bind_id');
        }
        $returnArr = [];
        if($goodsList['list']){
            $sortList = (new FoodshopGoodsSortService())->getSortListByStoreId($storeId);
            $sortList = array_column($sortList,'sort_name', 'sort_id');
            foreach ($goodsList['list'] as $goods){
                $selected = 0;
                if(in_array($goods['goods_id'],$selectGoodsArr)){
                    $selected = 1;
                }
                $returnArr['list'][] = [
                    'pigcms_id' => $goods['pigcms_id'],
                    'goods_id' => $goods['goods_id'],
                    'price' => $goods['min_price'] ?? $goods['price'],
                    'image' => $goods['product_image'],
                    'name' => $goods['name'],
                    'sort_name' => $sortList[$goods['spec_sort_id']] ?? '',
                    'selected' => $selected,
                ];
            }
        }
        return $returnArr;
    }

    /**
     * 根据条件返回
     * @param $where array 条件
     * @return array
     */
    public function getOne($where){
        $detail = $this->diningPrintRuleModel->getOne($where);
        if(!$detail) {
            return [];
        }
        return $detail->toArray();
    }

    /**
     * 根据条件获取打印机以及打印规则的列表
     * @param $where
     * @author 衡婷妹
     * @date 2020/09/21
     */
    public function getRuleAndPrint($where = [])
    {
        $res = $this->diningPrintRuleModel->getRuleAndPrint($where);

        if(!$res){
            return [];
        }

        return $res->toArray();
    }

    /**
     * 根据条件获取标签打印机以及打印规则的列表
     * @param $where
     * @author 衡婷妹
     * @date 2020/09/21
     */
    public function getRuleAndPrintLabel($where = [])
    {
        $res = $this->diningPrintRuleModel->getRuleAndPrintLabel($where);

        if(!$res){
            return [];
        }

        return $res->toArray();
    }

    /**
     * 根据条件返回总数
     * @param $where array 条件
     * @return array
     */
    public function getCount($where = []){
        $res = $this->diningPrintRuleModel->getCount($where);
        if(!$res) {
            return 0;
        }
        return $res;
    }

    /**
     * 根据条件获取列表
     * @param $storeId
     * @author 衡婷妹
     * @date 2020/09/21
     */
    public function getSome($where = [], $field = true, $order=true, $page=0, $limit=0)
    {
        $res = $this->diningPrintRuleModel->getSome($where, $field, $order, $page, $limit);

        if(!$res){
            return [];
        }

        return $res->toArray();
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $data['create_time'] = time();
        $id = $this->diningPrintRuleModel->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     * 更新数据
     * @param $where
     * @param $data
     */
    public function updateThis($where, $data)
    {
        $rs = $this->diningPrintRuleModel->where($where)->update($data);

        if ($rs === false) {
            return false;
        }
        return true;
    }

    /**
     * 删除
     * @author 衡婷妹
     * @date 2020/09/21
     */
    public function delthis($param)
    {
        $id = $param['id'] ?? 0;
        $storeId = $param['store_id'] ?? 0;
        if ($id < 1) {
            throw new \think\Exception("请选择一条记录");
        }
        $where = ['id' => $id];
        $storeId > 0 && $where['store_id'] = $storeId;
        $rs = $this->diningPrintRuleModel->where($where)->delete();
        if (!$rs) {
            throw new \think\Exception("删除失败");
        }

        //删除绑定的商品或分类数据
        $whereBind = [
            'rule_id' => $id
        ];
        (new DiningPrintRuleGoodsService())->delthis($whereBind);

        //删除绑定的打印机
        (new DiningPrintRulePrintService())->delthis($whereBind);

        return true;
    }

}