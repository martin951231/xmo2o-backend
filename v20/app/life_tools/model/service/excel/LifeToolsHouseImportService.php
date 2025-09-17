<?php
namespace app\life_tools\model\service\excel;

use app\life_tools\model\db\LifeToolsHouseBuilding;
use app\life_tools\model\db\LifeToolsHouseFloor;
use app\life_tools\model\db\LifeToolsHouseRoom;
use app\life_tools\model\db\LifeToolsHouseUnit;
use PhpOffice\PhpSpreadsheet\IOFactory;
use think\facade\Db;

class LifeToolsHouseImportService
{
    public function importHouse($params){
        $file_url = $params['file_url']?:'';
        $id = $params['id'];
        if(!$file_url){
            throw new \think\Exception(L_('请导入文件！'));
        }
        if(!$id){
            throw new \think\Exception(L_('请选择楼盘！'));
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
            'A' => 'building_name',
            'B' => 'building_number',
            'C' => 'building_sort',
            'D' => 'unit_name',
            'E' => 'unit_number',
            'F' => 'unit_sort',
            'G' => 'floor_name',
            'H' => 'floor_number',
            'I' => 'floor_sort',
            'J' => 'room_name',
            'K' => 'room_number',
            'L' => 'room_sort',
        ];
        $read = IOFactory::createReader('Xlsx');
        $PHPExcel = $read->load($file_url);
        $sheet = $PHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestDataRow(); // 取得总行数
        $highestColumm = $sheet->getHighestColumn(); // 取得总列数
        $data = [];
        for ($row = 2; $row <= $highestRow; $row++) //行号从1开始
        {
            for ($column = 'A'; $column <= 'L'; $column++) //列数是以A列开始
            {
                $data[$row][$filed[$column]] = $sheet->getCell($column . $row)->getValue();
            }
        }
        if(!$data){
            throw new \think\Exception(L_('表格内容格式错误！'));
        }
        $building_arr = $unit_arr = $floor_arr = $room_arr = [];
        foreach ($data as $key=>$item){
            $building_arr[$item['building_number']] = [
                'name' => $item['building_name'],
                'serial_number' => $item['building_number'],
                'sort' => $item['building_sort']
            ];
            $unit_arr[$item['building_number'].'_'.$item['unit_number']] = [
                'name' => $item['unit_name'],
                'serial_number' => $item['unit_number'],
                'sort' => $item['unit_sort'],
                'p_number' => $item['building_number'],
            ];
            $floor_arr[$item['building_number'].'_'.$item['unit_number'].'_'.$item['floor_number']] = [
                'name' => $item['floor_name'],
                'serial_number' => $item['floor_number'],
                'sort' => $item['floor_sort'],
                'p_number' => $item['unit_number'],
                'b_number' => $item['building_number'],
            ];
            $room_arr[$item['building_number'].'_'.$item['unit_number'].'_'.$item['floor_number'].'_'.$item['room_number']] = [
                'name' => $item['room_name'],
                'serial_number' => $item['room_number'],
                'sort' => $item['room_sort'],
                'p_number' => $item['floor_number'],
                'u_number' => $item['unit_number'],
                'b_number' => $item['building_number'],
            ];
        }
        if(!$building_arr||!$unit_arr||!$floor_arr||!$room_arr){
            throw new \think\Exception(L_('导入文件内容格式有误！'));
        }
        Db::startTrans();
        try {
            //楼栋
            foreach ($building_arr as $val){
                //查询是否已存在
                $building_old = (new LifeToolsHouseBuilding())->where(['house_id'=>$id,'serial_number'=>$val['serial_number']])->value('id');
                if($building_old){
                    $val['update_time'] = time();
                    (new LifeToolsHouseBuilding())->where(['id'=>$building_old])->save($val);
                }else{
                    $val['create_time'] = time();
                    $val['house_id'] = $id;
                    $val['status'] = 1;
                    (new LifeToolsHouseBuilding())->save($val);
                }
            }
            //单元
            foreach ($unit_arr as $val){
                //查询是否已存在
                $unit_old = (new LifeToolsHouseUnit())->where(['house_id'=>$id,'p_number'=>$val['p_number'],'serial_number'=>$val['serial_number']])->value('id');
                if($unit_old){
                    $val['update_time'] = time();
                    (new LifeToolsHouseUnit())->where(['id'=>$unit_old])->save($val);
                }else{
                    $val['create_time'] = time();
                    $val['house_id'] = $id;
                    $val['status'] = 1;
                    (new LifeToolsHouseUnit())->save($val);
                }
            }
            //楼层
            foreach ($floor_arr as $val){
                //查询是否已存在
                $floor_old = (new LifeToolsHouseFloor())->where(['house_id'=>$id,'b_number'=>$val['b_number'],'p_number'=>$val['p_number'],'serial_number'=>$val['serial_number']])->value('id');
                if($floor_old){
                    $val['update_time'] = time();
                    (new LifeToolsHouseFloor())->where(['id'=>$floor_old])->save($val);
                }else{
                    $val['create_time'] = time();
                    $val['house_id'] = $id;
                    $val['status'] = 1;
                    (new LifeToolsHouseFloor())->save($val);
                }
            }
            //房间
            foreach ($room_arr as $val){
                //查询是否已存在
                $room_old = (new LifeToolsHouseRoom())->where(['house_id'=>$id,'b_number'=>$val['b_number'],'u_number'=>$val['u_number'],'p_number'=>$val['p_number'],'serial_number'=>$val['serial_number']])->value('id');
                if($room_old){
                    $val['update_time'] = time();
                    (new LifeToolsHouseRoom())->where(['id'=>$room_old])->save($val);
                }else{
                    $val['create_time'] = time();
                    $val['house_id'] = $id;
                    $val['status'] = 1;
                    (new LifeToolsHouseRoom())->save($val);
                }
            }
            Db::commit();
            return true;
        }catch (\Exception $e){
            Db::rollback();
            throw new \think\Exception(L_($e->getMessage()));
        }
    }
}