<?php


namespace app\life_tools\model\service;


use app\life_tools\model\db\LifeTools;
use app\life_tools\model\db\LifeToolsCarPark;
use app\life_tools\model\db\LifeToolsCarParkTools;
use think\Model;

class LifeToolsCarParkService
{
    public function __construct()
    {
        $this->LifeTools                = new LifeTools();
        $this->LifeToolsCarPark                = new LifeToolsCarPark();
    }

    /**
     * 获取停车场列表
     * @param $param
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCarParkList($param){
        $kewords = $param['keywords']??'';
        $page = $param['page']??1;
        $page_size = $param['page_size']??10;
        $where = [];
        $where[] = ['mer_id','=',$param['mer_id']];
        if($kewords){
            $where[] = ['name','like','%'.$kewords.'%'];
        }
        $list = $this->LifeToolsCarPark->getList($where,$page,$page_size);
        foreach ($list as $key=>$item){
            $list[$key]['icon'] = replace_file_domain($item['icon']);
        }
        return $list;
    }

    /**
     * 停车场-添加/编辑
     */
    public function addCarPark($param){
        $id = $param['car_park_id']??'';
        $mer_id = $param['mer_id'];
        $save_data = [
            'name' => $param['name']??'',
            'business_hours' => $param['business_hours']??'',
            'price' => $param['price']??'',
            'cars_num' => $param['cars_num']??'',
            'long' => $param['long']??'',
            'lat' => $param['lat']??'',
            'icon' => $param['icon']??'',
            'pic' => $param['pic']??'',
            'notice' => $param['notice']??'',
            'status' => $param['status']??0,
            'local_name' => $param['local_name']??'',
            'mer_id' => $mer_id
        ];

        $scenic_arr = $param['scenic']??[];
        $ids_arr = $param['ids_arr']??[];
        if(!$save_data['name']||!$save_data['business_hours']||!$save_data['long']||!$save_data['lat']||!$save_data['icon']){
            throw new \think\Exception('必填项不能为空！');
        }
        $save_data['pic'] = $save_data['pic']?:'';
        if(is_array($save_data['pic'])&&$save_data['pic']){
            $save_data['pic'] = implode(',',$save_data['pic']);
        }
        //获取景区/课程/场馆列表
        $life_tools_model = new LifeTools();
        $tools_list = $life_tools_model->field('tools_id,title,type')->where([['type','in',['scenic','stadium','course']],['is_del','=',0],['status','=',1],['mer_id','=',$mer_id]])->select();
        $scenic_info = $other_info = [];
        foreach ($tools_list as $item){
            if($item['type']=='scenic'){
                $scenic_info[] = $item['tools_id'];
            }else{
                $other_info[] = $item['tools_id'];
            }
        }
        foreach ($scenic_arr as $k=>$v){
            if(!in_array($v,$scenic_info)){
                unset($scenic_arr[$k]);
            }
        }
        foreach ($ids_arr as $k=>$v){
            if(!in_array($v,$other_info)){
                unset($ids_arr[$k]);
            }
        }
        $tools_ids = array_merge($scenic_arr,$ids_arr);
        $car_park_tools = [];
        if($id){
            //查询信息是否存在
            $car_park_info = $this->LifeToolsCarPark->where(['car_park_id'=>$id])->find();
            if(!$car_park_info){
                throw new \think\Exception('修改信息不存在！');
            }
            foreach ($tools_ids as $key=>$item){
                $car_park_tools[$key]['tools_id'] = (int)$item;
                $car_park_tools[$key]['car_park_id'] = (int)$id;
            }
            try {
                //删除已保存的关系
                (new LifeToolsCarParkTools())->where(['car_park_id'=>$id])->delete();
                $order_id = $this->LifeToolsCarPark->where(['car_park_id'=>$id])->save($save_data);
                if($car_park_tools){
                    (new LifeToolsCarParkTools())->saveAll($car_park_tools);
                }
            } catch (\Exception $e) {
                throw new \think\Exception($e->getMessage());
            }
        }else{
            $save_data['add_time'] = time();
            try {
                $id = $this->LifeToolsCarPark->insertGetId($save_data);
                //获取信息
                if(!$id){
                    throw new \think\Exception('添加失败');
                }
                foreach ($tools_ids as $key=>$item){
                    $car_park_tools[$key]['tools_id'] = (int)$item;
                    $car_park_tools[$key]['car_park_id'] = $id;
                }
                if($car_park_tools){
                    (new LifeToolsCarParkTools())->saveAll($car_park_tools);
                }
            } catch (\Exception $e) {
                throw new \think\Exception($e->getMessage());
            }
        }
        return true;
    }

    /**
     * 停车场详情
     * @param $param
     * @return \think\Collection
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function showCarPark($param){
        $id = $param['id']??'';
        $mer_id = $param['mer_id'];
        if(!$id){
            throw new \think\Exception('参数错误！');
        }
        //查询信息是否存在
        $car_park_info = $this->LifeToolsCarPark->where(['car_park_id'=>$id])->find();
        if(!$car_park_info){
            throw new \think\Exception('信息不存在！');
        }
        //获取停车场下面的景区和场馆、课程信息
        //获取景区/课程/场馆列表
        /*$life_tools_model = new LifeTools();
        $tools_list = $life_tools_model->field('tools_id,title,type')->where([['type','in',['scenic','stadium','course']],['is_del','=',0],['status','=',1]])->select();*/
        $prefix = config('database.connections.mysql.prefix');
        $tools_list = (new LifeTools())->alias('a')
            ->field('a.tools_id,a.title,a.type')
            ->join($prefix.'life_tools_car_park_tools b','a.tools_id = b.tools_id')
            ->where(['b.car_park_id'=>$id,'a.mer_id'=>$mer_id])
            ->select();
        $scenic_info = $other_info = [];
        foreach ($tools_list as $item){
            if($item['type']=='scenic'){
                $scenic_info[] = $item;
            }else{
                $other_info[] = $item;
            }
        }
        $car_park_info['icon'] = replace_file_domain($car_park_info['icon']);
        $pic = $car_park_info['pic']?explode(',',$car_park_info['pic']):[];
        $car_park_info['pic'] = [];
        foreach ($pic as $k=>$item){
            if($item){
                $pic[$k] = replace_file_domain($item);
            }
        }
        $car_park_info['pic'] = $pic;
        $car_park_info['scenic'] = $scenic_info;
        $car_park_info['ids_arr'] = $other_info;
        return $car_park_info;
    }

    /**
     * 停车场-景区/场馆、课程列表
     * @param $param
     * @return \think\Collection
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getToolsList($param){
        $type = $param['type']??1;
        $mer_id = $param['mer_id'];
        if(!in_array($type,array(1,2))){
            throw new \think\Exception('参数错误！');
        }
        $where[] = ['is_del','=',0];
        $where[] = ['status','=',1];
        $where[] = ['mer_id','=',$mer_id];
        if($type==1){
            $where[] = ['type','=','scenic'];
        }else{
            $where[] = ['type','in',['stadium','course']];
        }
        //获取景区/课程/场馆列表
        $life_tools_model = new LifeTools();
        $tools_list = $life_tools_model->field('tools_id,title')->where($where)->select();
        return $tools_list;
    }

    /**
     * 停车场-删除
     * @param $param
     * @return bool
     * @throws \think\Exception
     */
    public function deleteCarPark($param){
        $id = $param['id']??'';
        if(!$id){
            return true;
        }
        try {
            $this->LifeToolsCarPark->where([['car_park_id','in',$id]])->delete();
            //删除已保存的关系
            (new LifeToolsCarParkTools())->where([['car_park_id','in',$id]])->delete();
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 停车场状态修改
     * @param $param
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function statusCarPark($param){
        $id = $param['id']??'';
        $status = $param['status']??0;
        if(!in_array($status,array(0,1))){
            throw new \think\Exception("状态有误");
        }
        if(!$id){
            throw new \think\Exception('参数错误！');
        }
        //查询信息是否存在
        $car_park_info = $this->LifeToolsCarPark->where(['car_park_id'=>$id])->find();
        if(!$car_park_info){
            throw new \think\Exception('修改信息不存在！');
        }
        try {
            $this->LifeToolsCarPark->where(['car_park_id'=>$id])->save(['status'=>$status]);
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 用户端获取当前景区/场馆/课程停车场
     * @param $tools_id
     * @return array
     */
    public function getToolsCarPark($tools_id, $lat,$lng){
        $prefix = config('database.connections.mysql.prefix');
        $where = [
            'a.is_del' => 0,
            'a.status' => 1,
            'a.tools_id' => $tools_id
        ];
        $list = (new LifeTools())->alias('a')
            ->field('a.long as along,a.lat as alat,c.*')
            ->join($prefix.'life_tools_car_park_tools b','a.tools_id = b.tools_id')
            ->join($prefix.'life_tools_car_park c','b.car_park_id = c.car_park_id and c.status = 1')
            ->where($where)
            ->select();
        foreach ($list as $k=>$v){
            $list[$k]['icon'] = replace_file_domain($v['icon']);
            $pic = $v['pic']?explode(',',$v['pic']):[];
            $list[$k]['pic'] = [];
            foreach ($pic as $kk=>$item){
                if($item){
                    $pic[$kk] = replace_file_domain($item);
                }
            }
            $list[$k]['pic'] = $pic;
            $list[$k]['distance'] = getDistance($lat,$lng,$v['lat'],$v['long']);
            $list[$k]['distance'] = get_range($list[$k]['distance'],false);
        }
        return $list;
    }
}