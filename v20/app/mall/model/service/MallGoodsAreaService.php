<?php


namespace app\mall\model\service;


use app\common\model\db\Area;
use app\common\model\service\AreaService;
use app\mall\model\db\MallGoods;
use app\mall\model\db\MallGoodsArea;
use PhpOffice\PhpSpreadsheet\IOFactory;
use think\facade\Db;

class MallGoodsAreaService
{
    /**
     * @param $param
     * @return bool
     * @throws \think\Exception
     * 批量新增
     */
    public function addMallGoodsArea($param)
    {
        $goodsIds = $param['goods_id'];
        $areaArray = $param['area_id'];
        $store_id = $param['store_id'];
        $set_type = $param['set_type'];
        $goodsDB = (new MallGoods());
        $goodsAreaDB = (new MallGoodsArea());
        $goodsList = $goodsDB->getSome([['goods_id', 'in', $goodsIds]])->toArray();

        //只保留顶级区域id
        $areaArray = $this->getRealArea($areaArray);
        if(!$areaArray){
            return true;
        }
        Db::startTrans();
        try {
            //删除已经设置的区域
            $goodsAreaDB->delData([['goods_id', 'in', $goodsIds], ['store_id', '=', $store_id]]);
            //创建区域
            $addData = [];
            foreach ($areaArray as $v){
                foreach ($goodsList as $gk => $gv) {
                    $areaLevel = count(explode('-',$v));
                    $areaId = explode('-',$v)[$areaLevel-1]; 
                    $addData[] = [
                        'goods_id'=>$gv['goods_id'],
                        'all_area_id'=>$v,
                        'area_id'=>$areaId,
                        'level'=>$areaLevel,
                        'store_id'=>$store_id,
                        'set_type'=>$set_type,
                        'create_time'=>time()
                    ];
                }
            }
            $result = $goodsAreaDB->insertAll($addData);
            if(!$result){
                throw_exception('创建可见区域失败');
            }
            
            
            $goodsOtherList = $goodsDB->getColumn('goods_id',[['goods_id', 'not in', $goodsIds],['status','=',1],['is_del','=',0]]);
            $goodsOtherAreaList = $goodsAreaDB->getColumn('goods_id',[['area_id', '>', 0]]);
            $goodsId=array_diff($goodsOtherList,$goodsOtherAreaList);
            foreach ($goodsId as $k=>$v){
                $msg=$goodsAreaDB->getOne(array('goods_id' => $v, 'area_id' => 0, 'all_area_id' => 0));
                if(empty($msg)) {
                    $goods=(new MallGoods())->getOne($v);
                    $goodsAreaDB->add(array('goods_id' =>$v, 'area_id' => 0, 'all_area_id' => 0, 'level' => 0, 'store_id' => $goods['store_id'], 'set_type' => $set_type, 'create_time' => time()));
                }
            }
            Db::commit();
            return true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            throw new \think\Exception($e->getMessage(), 1001);
        }
    }

    /**
     * 全选区域后，筛选出顶级区域
     */
    public function getRealArea($areaArray)
    {
        $p = [];
        $city = [];
        foreach ($areaArray as $v) {
            //获取省
            if(count(explode('-',$v)) == 1){
                $p[] = $v;
            }
            //获取市
            if(count(explode('-',$v)) == 2){
                $city[] = $v;
            }
        }
        if($p){
            foreach ($areaArray as $kk=>$vv) {
                //删除省下所有
                if(count(explode('-',$vv)) > 1 && in_array(explode('-',$vv)[0],$p)){
                    unset($areaArray[$kk]);
                }
            }
        }
        if($city){
            foreach ($areaArray as $kkk=>$vvv) {
                //删除市下所有
                if(count(explode('-',$vvv)) > 2 && in_array(explode('-',$vvv)[0].'-'.explode('-',$vvv)[1],$city)){
                    unset($areaArray[$kkk]);
                }
            }
        }
        $areaArray = array_values(array_unique($areaArray));
        return $areaArray;
    }
    /**
     * 修改单个商品区域
     */
    public function addOneMallGoodsArea($param){
        $goodsAreaDB = (new MallGoodsArea());
        if(!isset($param['area_id'])){//绑定城市为空，则删除全部绑定的城市
            $goodsAreaDB->delData(['goods_id'=>$param['goods_id'],'store_id'=> $param['store_id']]);
            return true;
        }
        $goodsIds = $param['goods_id'];
        $areaArray = $param['area_id'];
        $store_id = $param['store_id'];
        $set_type = $param['set_type'];
        $goodsDB = (new MallGoods());
        Db::startTrans();
        try {
            $goodsList = $goodsDB->getSome([['goods_id', 'in', $goodsIds]])->toArray();
            $data = array();
            $all_data=array();
            //删除多余的不全选的上级id
            foreach ($areaArray as $row) {
                $arr = explode('-', $row);
                if (count($arr) == 1) {
                    $data[$arr[0]] = 1;
                    $all_data[$arr[0]]=$row;
                } elseif (count($arr) == 2) {
                    unset($data[$arr[0]]);
                    $data[$arr[1]] = 2;
                    $all_data[$arr[1]]=$row;
                } elseif (count($arr) == 3) {
                    unset($data[$arr[0]]);
                    unset($data[$arr[1]]);
                    $data[$arr[2]] = 3;
                    $all_data[$arr[2]]=$row;
                }
            }
            // 查询区域等级
            $area_list = (new Area())->getSome([['area_id', 'in', array_keys($data)]])->toArray();
            $area_level_arr = array();
            //key为区域，值为等级
            foreach ($area_list as $key => $value) {
                $area_level_arr[$value['area_id']] = $value['area_type'];
            }
            //删除已经设置的区域
            $goodsAreaDB->delData(['goods_id'=>$param['goods_id'],'store_id'=> $param['store_id']]);
            //$goodsDB->updateThis(['goods_id' => $param['goods_id']], ['use_area'=>0]);
            //重新保存选择的区域
            if (!empty($areaArray)) {
                foreach ($goodsList as $gk => $gv) {
                    //$data1['use_area'] = 1;
                    //$goodsDB->updateThis(['goods_id' => $gv['goods_id']], $data1);
                    foreach ($data as $aid => $level) {
                            $goodsAreaDB->add(array('goods_id' => $gv['goods_id'], 'area_id' => $aid, 'all_area_id' => $all_data[$aid], 'level' => $area_level_arr[$aid], 'store_id' => $store_id, 'set_type' => $set_type, 'create_time' => time()));
                    }
                }
            }

            $goodsOtherList = $goodsDB->getColumn('goods_id',[['goods_id', 'not in', $goodsIds],['status','=',1],['is_del','=',0]]);
            $goodsOtherAreaList = $goodsAreaDB->getColumn('goods_id',[['area_id', '>', 0]]);
            $goodsId=array_diff($goodsOtherList,$goodsOtherAreaList);
            foreach ($goodsId as $k=>$v){
                $msg=$goodsAreaDB->getOne(array('goods_id' => $v, 'area_id' => 0, 'all_area_id' => 0));
                if(empty($msg)){
                    $goods=(new MallGoods())->getOne($v);
                    $goodsAreaDB->add(array('goods_id' =>$v, 'area_id' => 0, 'all_area_id' => 0, 'level' => 0, 'store_id' => $goods['store_id'],'set_type' => $set_type, 'create_time' => time()));
                }
            }
            Db::commit();
            return true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            throw new \think\Exception($e->getMessage(), 1001);
        }
    }
    /**
     * @return array
     * 获取区域
     */
    public function getAreaList($area_id=[]){
        $area = (new AreaService())->getAllArea(2, "*");
        $data=array();
        $areaList=[];
        if(!empty($area)){
            foreach ($area as $k=>$v){
                if($area_id&&!in_array($v['area_id'],$area_id)){
                    continue;
                }
                $data['title']=$v['area_name'];
                $data['area_id']=$v['area_id'];
                $data['key']=$v['area_id'];
                $data['area_type']=$v['area_type'];
                $data['children']=[];
                if(!empty($v['children'])){
                    $data1=array();
                    foreach ($v['children'] as $k1=>$v1){
                        if($area_id&&!in_array($v1['area_id'],$area_id)){
                            continue;
                        }
                        $data1['title']=$v1['area_name'];
                        $data1['area_id']=$v1['area_id'];
                        $data1['area_type']=$v1['area_type'];
                        $data1['key']=$v['area_id'].'-'.$v1['area_id'];
                        $arr2=array();
                        $data1['children']=[];
                        if(isset($v1['children'])){
                            foreach ($v1['children'] as $k2=>$v2){
                                if($area_id&&!in_array($v2['area_id'],$area_id)){
                                    continue;
                                }
                                $data2['title']=$v2['area_name'];
                                $data2['area_id']=$v2['area_id'];
                                $data2['area_type']=$v2['area_type'];
                                $data2['key']=$v['area_id'].'-'.$v1['area_id'].'-'.$v2['area_id'];
                                $arr2[]=$data2;
                            }
                            $data1['children']=$arr2;
                        }
                        $data['children'][]=$data1;
                    }
                }
                $areaList[]=$data;
            }
        }
        return $areaList;
    }

    /**
     * 查找区域
     */
    public function findAreaList($param){
        $goodsAreaDB = (new MallGoodsArea());
        $ret=$goodsAreaDB->getSome([['goods_id','=',$param['goods_id']],['store_id','=',$param['store_id']],['area_id','>',0]])->toArray();
        $arr=array();
        if(!empty($ret)){
           foreach ($ret as $k=>$v){
               if (strpos($v['all_area_id'], '-') === false) {
                   $v['all_area_id']=$v['all_area_id']*1;
               }
              $arr[]= $v['all_area_id'];
           }
        }
        return $arr;
    }

    /**
     * @param $user
     * @return array|bool|mixed
     * 返回区id
     */
    public function getAreaId($user){
        $area_id=array();
        if(empty($user['area_id']) || $user['area_id']==0){
            if(empty($user['city_id'])){
                if(empty($user['province_id'])){
                    return false;
                }else{
                    $arr=$this->getChildArea($user['province_id']);
                    for($i=0;$i<count($arr);$i++){
                        $area_id=$this->getChildArea($arr[$i]);
                    }
                    array_push($area_id,$user['province_id']);
                    return $area_id;
                }
            }else{
                $area_id=(new Area())->getNowCityColumn('area_id',array('area_pid'=>$user['city_id'],'is_open'=>1));
                //$area_id = D('Area')->where(array('area_pid'=>$user['city_id'],'is_open'=>1))->getField('area_id',true);
                array_push($area_id,$user['city_id']);
                if(!$user['province_id']){
                    $provinceId = (new Area())->where('area_id',$user['city_id'])->value('area_pid');
                    $user['province_id'] = $provinceId;
                }
                array_push($area_id,$user['province_id']);
                return $area_id;
            }
        }else{
            $area_id[0]=$user['area_id'];
            $area_id[1]=$user['city_id'];
            $area_id[2]=$user['province_id'];
            return $area_id;
        }
    }

    /**
     * @param $area_id
     * @return mixed
     * 取得子区域
     */
    public function getChildArea($area_id){
        $area_list=(new Area())->getNowCityColumn('area_id',array('area_pid'=>$area_id,'is_open'=>1));
        //$area_list = D('Area')->where(array('area_pid'=>$area_id))->getField('area_id',true);
        return $area_list;
    }

    /**
     * 批量导入商品城市区域
     * @param $param
     * @return bool
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function importGoodsArea($param){
        $file_url = $param['file_url']?:'';
        $goods_id = $param['goods_id']?:[];
        $mer_id = $param['mer_id']?:'';
        if(!$file_url){
            throw new \think\Exception(L_('请导入文件！'));
        }
        if(!$goods_id){
            throw new \think\Exception(L_('请选择商品！'));
        }
        if(file_exists($_SERVER['DOCUMENT_ROOT'].$file_url)){
            $file_url = $_SERVER['DOCUMENT_ROOT'].$file_url;
        }else{
            $file_url = replace_file_domain($file_url);
        }
        $file_arr=explode('/',$file_url);
        $count=count($file_arr);
        $file_type = '';
        if ($count>1){
            $file_data=$file_arr[$count-1];
            $file_arr = explode('.',$file_data);
            if(count($file_arr)==2)
            {
                $file_type = $file_arr[1];
            }
        }
        if (!$file_type){
            throw new \think\Exception(L_('文件上传错误，导入失败！'));
        }
        if ($file_type!='xlsx'){
            throw new \think\Exception(L_('文件格式错误，请上传xlsx格式文件！'));
        }
        
        $filed = [
            'A' => 'province',
            'B' => 'city',
            'C' => 'area',
        ];
        $read = IOFactory::createReader('Xlsx');
        $PHPExcel = $read->load($file_url);
        $sheet = $PHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestDataRow(); // 取得总行数
        $highestColumm = $sheet->getHighestColumn(); // 取得总列数
        $data = [];
        for ($row = 2; $row <= $highestRow; $row++) //行号从1开始
        {
            for ($column = 'A'; $column <= 'C'; $column++) //列数是以A列开始
            {
                $data[$row][$filed[$column]] = $sheet->getCell($column . $row)->getValue();
            }
        }
        if(!$data){
            throw new \think\Exception(L_('表格内容格式错误！'));
        }
        $area_model = new Area();
        $fields = 'area_id, area_name, area_pid, area_type';
        $area_id = [];
        foreach ($data as $key=>$item){
            $area_name = array_values($item);
            if(count($area_name)<3){
                throw new \think\Exception(L_('第'.$key.'行格式错误！'));
            }
            $province = $area_model->where([['is_open', '=', 1], ['area_type', '<>', 4],['area_name','in',$area_name]])->field($fields)->order('area_type asc')->select()->toArray();
            if(count($province)<2){
                throw new \think\Exception(L_('第'.$key.'行省/市名称错误！'));
            }
            $area_type = array_column($province,'area_type');
            if(!in_array(1,$area_type)||!in_array(2,$area_type)){
                throw new \think\Exception(L_('第'.$key.'行省/市错误！'));
            }
            $area_id[] = $province[0]['area_id'];
            $area_id[] = $province[1]['area_id'];
            if(!in_array(3,$area_type)){
                //获取市下面所有区县
                $area_list = $area_model->where([['is_open', '=', 1], ['area_type', '<>', 4],['area_pid','=',$province[1]['area_id']]])->column('area_id');
                if(!$area_list){
                    throw new \think\Exception(L_('第'.$key.'行市下面无匹配区县！'));
                }
                $area_id = array_merge($area_id,$area_list);
            }else{
                $area_id[] = $province[2]['area_id'];
            }
            //$area_id[] = $province[0]['area_id'].'-'.$province[1]['area_id'].'-'.$province[2]['area_id'];
        }
        if(!$area_id){
            throw new \think\Exception(L_('导入文件内容格式有误！'));
        }
        //$param['area_id'] = $area_id;
        //$res = $this->addMallGoodsArea($param);
        $res = $this->getAreaList($area_id);
        if($res){
            return $res;
        }else{
            throw new \think\Exception(L_('导入失败！'));
        }
    }

    public function exportViewArea($goodsId, $goodsName = '')
    {

        $allArea = Db::name('area')->whereIn('area_type', [1, 2, 3])->column('area_name', 'area_id');
        $lists = (new MallGoodsArea())->where('goods_id', $goodsId)->select()->each(function ($res) use ($allArea,$goodsName) {
            $arr = explode('-', $res->all_area_id);
            $res->goods_name = $goodsName;
            $res->province = $res->city = $res->area = '';
            if (count($arr) == 3) {
                //省市区
                $res->province = $allArea[$arr[0]] ?? '';
                $res->city = $allArea[$arr[1]] ?? '';
                $res->area = $allArea[$arr[2]] ?? '';
            } else if (count($arr) == 2) {
                //省市
                $res->province = $allArea[$arr[0]] ?? '';
                $res->city = $allArea[$arr[1]] ?? '';
            } else {
                //省份
                $res->province = $allArea[$arr[0]] ?? '';
            }
        });
        $xlsCell = [
            ['goods_name', '商品名称'],
            ['province', '省份'],
            ['city', '城市'],
            ['area', '区域'],
        ];
        $exportResult = export_excel('展示区域', $xlsCell, $lists->toArray(), $goodsId . time());
        return $exportResult;
    }
}