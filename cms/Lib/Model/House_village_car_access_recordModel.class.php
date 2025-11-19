<?php
/**
 * @author : liukezhu
 * @date : 2022/1/14
 */
class House_village_car_access_recordModel extends Model{

    public function check_park_time($standing_time_val){
        if (!$standing_time_val) {
            $standing_time = '暂无';
        } elseif ($standing_time_val>0 && $standing_time_val<60) {
            $standing_time = $standing_time_val . '秒';
        } elseif ($standing_time_val>0 && $standing_time_val<3600) {
            $standing_time = gmdate('i分钟s秒',$standing_time_val);
        } elseif ($standing_time_val>0 && $standing_time_val<86400) {
            $standing_time = gmdate('H小时i分钟s秒',$standing_time_val);
        } else {
            $standing_time = round_number($standing_time_val/3600,2).'小时';
        }
        return $standing_time;
    }

    //todo 获取车辆进出详情
    public function getCarSnapInfo($village_id,$id){
        $info=$this->where(['record_id'=>$id,'business_type'=>0,'business_id'=>$village_id])->find();
        $data=[];
        if(!$info){
            return $data;
        }
        $where=[
            'a.village_id'=>$village_id,
            'a.car_id'=>$info['car_id']
        ];
        $car_info=D('House_village_parking_car')->get_parking_position($where,'b.position_num');
        $standing_time=self::check_park_time(intval($info['park_time']));
        $accessTime=!empty($info['accessTime']) ? date('Y-m-d H:i:s',$info['accessTime']) : '暂无';
        $car_img=[];
        $in_time='暂无';
        $out_time='暂无';
        if($info['accessType'] == 1 && !empty($info['accessBigImage'])){
            $in_time=$accessTime;
            $car_img[]=array(
                'key'=>replace_file_domain($info['accessBigImage']),
                'value'=>'进入抓拍'
            );
        }elseif ($info['accessType'] == 2 && !empty($info['accessBigImage'])){
            $out_time=$accessTime;
            $car_img[]=array(
                'key'=>replace_file_domain($info['accessBigImage']),
                'value'=>'离开抓拍'
            );
        }
        $car_list=array(
            array(
                'key'=>'车辆号码',
                'value'=>$info['car_number'],
            ),
            array(
                'key'=>'车位号',
                'value'=>isset($car_info['position_num']) ? $car_info['position_num'] : '暂无',
            ),
            array(
                'key'=>'车主姓名',
                'value'=>$info['user_name'],
            ),
            array(
                'key'=>'车主手机号',
                'value'=>$info['user_phone'],
            ),
            array(
                'key'=>'进场时间',
                'value'=>$in_time,
            ),
            array(
                'key'=>'出场时间',
                'value'=>$out_time,
            ),
            array(
                'key'=>'场内时间',
                'value'=>$standing_time,
            ),
            array(
                'key'=>'当前订单',
                'value'=>'暂无',
            )
        );
        $data['car_list']=$car_list;
        $data['car_img']=$car_img;
        return $data;
    }

    //todo 统计车辆
    public function getlist($village_id,$where=[],$park_sys_type='D5'){
        import('@.ORG.merchant_page');
        $count=$this->alias('r')
            ->JOIN('left join '. C('DB_PREFIX') . 'house_village_parking_car as c on c.car_id=r.car_id')
            ->where($where)->count();
        $p = new Page($count, 20, 'page');
        $order = 'r.third_id desc,record_id desc';
        $field = 'r.record_id as id,r.car_number,c.car_number as position_num,r.user_name as car_user_name,r.user_phone as car_user_phone,r.accessType,r.accessTime,r.park_time,r.order_id';
        $list = $this->alias('r')
            ->JOIN('left join '. C('DB_PREFIX') . 'house_village_parking_car as c on c.car_id=r.car_id')
            ->where($where)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->order($order)
            ->field($field)
            ->select();
        if($list){
            foreach ($list as &$v){
                $v['standing_time']=self::check_park_time(intval($v['park_time']));
                if($v['accessType'] == 1){
                    $accessType='进场';
                    $in_time=!empty($v['accessTime']) ? date('Y-m-d H:i:s',$v['accessTime']) : '暂无';
                    $out_time='--';
                }else{
                    $accessType='出场';
                    $in_time='--';
                    $out_time=!empty($v['accessTime']) ? date('Y-m-d H:i:s',$v['accessTime']) : '暂无';
                }
                $v['is_out_text']=$accessType;
                $v['in_time']=$in_time;
                $v['out_time']=$out_time;
                $v['order_id']='--';
            }
            unset($v);
        }
        $s_time = strtotime(date('Y-m-d',time()));
        $e_time = strtotime(date('Y-m-d 23:59:59',time()));
        $where=[
            'business_type'=>0,
            'business_id'=>$village_id,
            'park_sys_type'=>$park_sys_type,
            'accessType'=>1,
            'accessTime'=>[['gt',$s_time],['lt',$e_time]]
        ];
        $in_count = $this->where($where)->count();//今日进场
        $where=[
            'business_type'=>0,
            'business_id'=>$village_id,
            'park_sys_type'=>$park_sys_type,
            'accessType'=>2,
            'accessTime'=>[['gt',$s_time],['lt',$e_time]]
        ];
        $out_count = $this->where($where)->count();//今日出场
        $where=[
            'business_type'=>0,
            'business_id'=>$village_id,
            'park_sys_type'=>$park_sys_type,
            'is_out'=>0
        ];
        $total_count = $this->where($where)->group('car_number')->field('record_id')->select();//目前在场车辆
        $data=[
            'in_count'=>$in_count,
            'out_count'=>$out_count,
            'in_car_num'=>count($total_count),
            'list'=>$list,
            'pagebar'=>$p->show()
        ];
        return $data;
    }

    //todo 统计车辆导出
    public function inOutCarExport($where,$village_name,$order='r.third_id desc,record_id desc')
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $field = 'r.record_id as id,r.car_number,c.car_number as position_num,r.user_name as car_user_name,r.user_phone as car_user_phone,r.accessType,r.accessTime,r.park_time,r.order_id';
        $in_park_data = $this->alias('r')
            ->JOIN('left join '. C('DB_PREFIX') . 'house_village_parking_car as c on c.car_id=r.car_id')
            ->where($where)
            ->order($order)
            ->field($field)
            ->select();

        if(!$in_park_data){
            throw new Exception("暂无数据导出!");
        }
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';

        $title =  $village_name. '车辆进出统计表格';

        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $length = ceil(count($in_park_data)/1000);

        $pCoordinate_arr = [
            'A','B','C','D','E','F','G'
        ];
        $pValue_arr = [
            '车辆号码',
            '车位号',
            '车主姓名',
            '车主手机号',
            '进出场类型',
            '进出场时间',
            '所在场内时间（h）',
        ];
        $key_length = count($pValue_arr) - 1;
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle('共' . count($in_park_data) . '条数据');
            $objActSheet = $objExcel->getActiveSheet();

            foreach ($pValue_arr as $k1=>$v1) {
                $objActSheet->setCellValue($pCoordinate_arr[$k1].'1', $v1);
            }

            if (!empty($in_park_data)) {
                $index = 2;
                $cell_list = range($pCoordinate_arr[0],$pCoordinate_arr[$key_length]);
                foreach ($cell_list as $cell) {
                    $objActSheet->getColumnDimension($cell)->setWidth(25);
                }
                foreach ($in_park_data as $value) {
                    $value['standing_time'] = self::check_park_time(intval($value['park_time']));
                    $value['in_out_time'] = date('Y-m-d H:i:s',$value['accessTime']);
                    if($value['accessType'] == 1){
                        if($value['accessTime']){
                            $value['int_time'] = date('Y-m-d H:i:s',$value['accessTime']);
                        }else{
                            $value['int_time'] = '--';
                        }
                        $value['out_time'] = '--';
                        $value['is_out_text'] = '出场';
                    }else{
                        if($value['accessTime']){
                            $value['out_time'] = date('Y-m-d H:i:s',$value['accessTime']);
                        }else{
                            $value['out_time'] = '--';
                        }
                        $value['int_time'] = '--';
                        $value['is_out_text'] = '进场';
                    }

                    $objActSheet->setCellValueExplicit($pCoordinate_arr[0] . $index, $value['car_number']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[1] . $index, $value['position_num']);
                    if($value['car_user_phone']) {
                        $objActSheet->setCellValueExplicit($pCoordinate_arr[2] . $index, $value['car_user_name']);
                    }else{
                        $objActSheet->setCellValueExplicit($pCoordinate_arr[2] . $index, '--');
                    }
                    if($value['car_user_phone']){
                        $objActSheet->setCellValueExplicit($pCoordinate_arr[3] . $index, $value['car_user_phone']);
                    }else{
                        $objActSheet->setCellValueExplicit($pCoordinate_arr[3] . $index, '--');
                    }
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[4] . $index, $value['is_out_text']);

                    $objActSheet->setCellValueExplicit($pCoordinate_arr[5] . $index, $value['in_out_time']);

                    $objActSheet->setCellValueExplicit($pCoordinate_arr[6] . $index, $value['standing_time']);

                    $index++;
                }
            }
            sleep(2);
            //输出
            $objWriter = new PHPExcel_Writer_Excel5($objExcel);
            ob_end_clean();
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
            header("Content-Transfer-Encoding:binary");
            $objWriter->save('php://output');
            exit();
        }
    }

    /**
     * 获取当前生效的临时车收费标准
     * @author:zhubaodi
     * @date_time: 2022/3/17 15:42
     */
    public function getTempRuleId($village_id){
        $whereRule=['village_id'=>$village_id,'fees_type'=>3,'status'=>1,'charge_valid_time'=>array('elt',time())];
        $rule_info = M('house_new_charge_rule')->where($whereRule)->field('*')->order('charge_valid_time DESC')->find();
        return $rule_info;
    }

}