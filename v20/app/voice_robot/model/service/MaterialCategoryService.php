<?php
namespace app\voice_robot\model\service;

use app\voice_robot\model\db\VoiceRobotMaterialCategory;
use app\voice_robot\model\db\VoiceRobotMaterialContent;
use PhpOffice\PhpSpreadsheet\IOFactory;
use think\facade\Db;

class MaterialCategoryService
{
    /**
     * 获取分类列表
     */
    public function getList($param)
    {
        $dataArr = ['list' => array(), 'total_limit' => $param['pageSize'], 'count' => 0];
        $where = [];
        $where[] = ['is_del','=',0];
        $where[] = ['xtype','=',$param['xtype']];
        if($param['keyword']){
            $param['keyword'] = htmlspecialchars($param['keyword'], ENT_QUOTES);
            $where[] = ['categoryname','like','%'.$param['keyword'].'%'];
        }
        if($param['dateArr'] && is_array($param['dateArr']) && !empty($param['dateArr']['0'])){
            $startTime = strtotime($param['dateArr']['0']);
            $endTime = strtotime($param['dateArr']['1'].' 23:59:59');
            $where[] = ['update_time','>=',$startTime];
            $where[] = ['update_time','<=',$endTime];
        }
        $data = (new VoiceRobotMaterialCategory())->getList($where,true,$param['pageSize']);
        foreach ($data['data'] as $k=>$v){
            $data['data'][$k]['add_time_str'] = date('Y-m-d H:i:s', $v['add_time']);
            $data['data'][$k]['update_time_str'] = $v['update_time'] > 0 ? date('Y-m-d H:i:s', $v['update_time']) : '';
        }
        $dataArr['list'] = $data['data'];
        $dataArr['count'] = $data['total'];
        $dataArr['role_addcategory']=1;  //新建关键词(默认有权限)
        $dataArr['role_editcategory']=1;  //编辑关键词(默认有权限)
        $dataArr['role_delcategory']=1;  //删除关键词(默认有权限)
        $dataArr['role_managecategory']=1;  //管理(默认有权限)
        return $dataArr;
    }

    /**
     * 编辑分类
     * @date 2023/3/1
     * @param $param
     * @return int|mixed|string|\think\response\Json
     */
    public function editMaterialCategory($param)
    {
        $categoryname = htmlspecialchars($param['categoryname'], ENT_QUOTES);
        $xtype = $param['xtype'];
        $cate_id = $param['cate_id'];
        if (empty($categoryname)) {
            throw new \think\Exception('分类名称不能为空！');
        }
        if(!in_array($xtype,array(1,2,3))){
            throw new \think\Exception('素材类型错误！');
        }
        $saveArr = array('categoryname' => $categoryname, 'xtype' =>$xtype);
        $whereArrTmp=array();
        $whereArrTmp[]=array('xtype','=',$xtype);
        $whereArrTmp[]=array('categoryname','=',$categoryname);
        $whereArrTmp[]=array('is_del','=',0);
        if($cate_id > 0){
            $whereArrTmp[]=array('cate_id','<>',$cate_id);
        }
        $existCategory=(new VoiceRobotMaterialCategory())->detail($whereArrTmp);
        if(!empty($existCategory)){
            $errmsg='此分类名称【'.$categoryname.'】已经存在了，请修改分类名称！';
            throw new \think\Exception($errmsg);
        }
        if ($cate_id > 0) {
            $whereArr = array('cate_id' => $cate_id);
            $saveArr['update_time'] = time();
            (new VoiceRobotMaterialCategory())->where($whereArr)->update($saveArr);
        } else {
            $saveArr['update_time'] = time();
            $cate_id = (new VoiceRobotMaterialCategory())->insertGetId($saveArr);
        }
        return $cate_id;
    }

    /**
     * 删除分类
     */
    public function delMaterialCategory($param)
    {
        if ($param['cate_ids']<0) {
            throw new \think\Exception('分类参数ID错误！');
        }
        $cate_ids=explode(',',$param['cate_ids']);
        $whereArr[]=array('cate_id','in',$cate_ids);
        $whereArr[]=array('xtype','=',$param['xtype']);
        (new VoiceRobotMaterialCategory())->where($whereArr)->update([
            'is_del'=>1
        ]);
        return [];
    }
    
    /**
     * 新建或者编辑素材
     */
    public function saveContent($param)
    {
        if ($param['cate_id']<0) {
            throw new \think\Exception('分类参数ID错误！');
        }
        $xname=htmlspecialchars($param['xname'], ENT_QUOTES);
        $saveArr=[];
        if($param['xtype'] == 1){//文本
            if(empty($param['xcontent'])){
                throw new \think\Exception('回复内容不能为空！');
            }
            $saveArr['xcontent']=htmlspecialchars($param['xcontent'], ENT_QUOTES);
        }elseif($param['xtype'] == 2){//音频
            $saveArr['xname']=$xname;
            if($param['material_id']>0){
                if(empty($param['audio_url']) ){
                    throw new \think\Exception('请上传回复音频文件！');
                }
                if(is_array($param['audio_url'])){
                    $saveArr['xcontent']=$param['audio_url']['0']['url'];
                }else{
                    $saveArr['xcontent']=$param['audio_url'];
                }
            }else{
                if(empty($param['audio_url']) || !is_array($param['audio_url'])){
                    throw new \think\Exception('请上传回复音频文件！');
                }
            }
            
        }elseif($param['xtype'] == 3){//图片
            if(empty($param['word_imgs'])){
                throw new \think\Exception('请上传回复图片文件！');
            }
            $saveArr['xcontent']=implode(',',$param['word_imgs']);
        }else{
            throw new \think\Exception('素材类型错误！');
        }
        
        
        if($param['material_id']>0){
            $saveArr['update_time'] = time();
            $update = (new VoiceRobotMaterialContent())->where([
                'material_id'=>$param['material_id'],
                'cate_id'=>$param['cate_id'],
                'xtype'=>$param['xtype']
            ])->update($saveArr);
            if(!$update){
                throw new \think\Exception('操作失败！');
            }
            $ret = $param['material_id'];
        }else{
            $saveArr['cate_id']=$param['cate_id'];
            $saveArr['xtype']=$param['xtype'];
            $ret=0;
            $num=0;
            if($param['xtype']==2){
                $saveArr['xname']=$xname;
                foreach ($param['audio_url'] as $vcc){
                    if(!empty($vcc['url'])){
                        $saveArr['xcontent']=$vcc['url'];
                        $saveArr['add_time'] = time();
                        $saveArr['update_time'] = time();
                        $ret = (new VoiceRobotMaterialContent())->insertGetId($saveArr);
                        $num++;
                    }
                }
            }else{
                $saveArr['add_time'] = time();
                $saveArr['update_time'] = time();
                $ret = (new VoiceRobotMaterialContent())->insertGetId($saveArr);
                $num=1;
            }
            if(!$ret){
                throw new \think\Exception('新增失败！');
            }
            (new VoiceRobotMaterialCategory())->where(['cate_id'=>$param['cate_id']])->inc('subcount',$num)->update();
        }
        
        return $ret;
    }
    
    /**
     * 素材列表
     */
    public function contentList($param)
    {
        $dateArr = $param['dateArr'];
        $xtype = $param['xtype'];
        $cate_id = $param['cate_id'];
        $pageSize = $param['pageSize'];
        if($cate_id<1){
            throw new \think\Exception('分类参数ID错误！');
        }
        $where[] = ['cate_id', '=', $cate_id];
        $where[] = ['is_del', '=', 0];
        $where[] = ['xtype', '=', $xtype];
        if ($dateArr && is_array($dateArr) && !empty($dateArr['0'])) {
            $starttime = strtotime($dateArr['0']);
            if ($starttime > 0) {
                $where[] = ['update_time', '>=', $starttime];
            }
            $endtime = strtotime($dateArr['1'].' 23:59:59');
            if ($endtime > 0) {
                $where[] = ['update_time', '<=', $endtime];
            }
        }
        $list = (new VoiceRobotMaterialContent())->getList($where,true,$pageSize);
        $dataArr = ['list' => array(), 'total_limit' => $pageSize, 'count' => 0];
        foreach ($list['data'] as $kk=>$vv){
            $list['data'][$kk]['add_time_str'] = date('Y-m-d H:i:s', $vv['add_time']);
            $list['data'][$kk]['update_time_str'] = $vv['update_time'] > 0 ? date('Y-m-d H:i:s', $vv['update_time']) : '';
            $list['data'][$kk]['data'][$kk]['audio_url']='';
            $list['data'][$kk]['xtype']=intval($vv['xtype']);
            $list['data'][$kk]['word_imgs']=array();
            if($vv['xtype']==2){
                $audio_url=htmlspecialchars_decode($vv['xcontent'],ENT_QUOTES);
                $audio_url=replace_file_domain($audio_url);
                $list['data'][$kk]['audio_url']=$audio_url;
            }elseif ($vv['xtype']==3){
                $img_url=htmlspecialchars_decode($vv['xcontent'],ENT_QUOTES);
                $tmpimgs=explode(',',$img_url);
                $img_url_arr=array();
                foreach ($tmpimgs as $imgv){
                    $newimgsrc=replace_file_domain($imgv);
                    $img_url_arr[]=$newimgsrc;
                }
                $list['data'][$kk]['word_imgs']=$img_url_arr;
            }elseif ($vv['xtype']==1){
                $list['data'][$kk]['xcontent']=htmlspecialchars_decode($vv['xcontent'],ENT_QUOTES);
            }
        }
        $dataArr['list'] = $list['data'];
        $dataArr['count'] = $list['total'];
        $dataArr['role_addmaterial']=1;  //添加回复内容
        $dataArr['role_editmaterial']=1;  //编辑回复内容
        $dataArr['role_delmaterial']=1;  //删除回复内容
        return $dataArr;
    }
    
    /**
     * 删除素材
     */
    public function delContent($param)
    {
        $cate_id = $param['cate_id'];
        if($cate_id<1){
            throw new \think\Exception('分类参数ID错误！');
        }

        $material_ids=explode(',',$param['material_ids']);
        if(!$material_ids){
            throw new \think\Exception('操作对象不能为空！');
        }
        $whereArr = [
            ['material_id','in',$material_ids],
            ['cate_id','=',$cate_id],
            ['xtype','=',$param['xtype']],
        ];
        (new VoiceRobotMaterialContent())->where($whereArr)->update(['is_del'=>1]);
        $count = (new VoiceRobotMaterialContent())->where([
            'cate_id'=>$cate_id,
            'xtype'=>$param['xtype'],
            'is_del'=>0
        ])->count();
        (new VoiceRobotMaterialCategory())->where('cate_id',$cate_id)->update(['subcount'=>$count]);
        return [];
    }
    
    /**
     * 获取素材库
     */
    public function getHotWordMaterialLibrary($param)
    {
        $retArr = array('list' => array());
        $where = [];
        $where[] = ['xtype','=',$param['xtype']];
        $where[] = ['is_del','=',0];
        $where[] = ['subcount','>',0];
        $list = (new VoiceRobotMaterialCategory())->getList($where,true,0);
        $retArr['list'] = $list;
        return $retArr;
    }
    
    /**
     * 获取素材详情
     */
    public function getHotWordMaterialLibraryDetail($param)
    {
        $cate_id = $param['cate_id'];
        if($cate_id<1){
            throw new \think\Exception('分类参数ID错误！');
        }
        $dataArr = ['list' => array(), 'total_limit' => $param['pageSize'], 'count' => 0];
        $where = [];
        $where[] = ['xtype','=',$param['xtype']];
        $where[] = ['cate_id','=',$cate_id];
        $where[] = ['is_del','=',0];
        $list = (new VoiceRobotMaterialContent())->getList($where,true,$param['pageSize']);
        foreach ($list['data'] as $kk => $vv) {
            $list['data'][$kk]['audio_url']='';
            $list['data'][$kk]['xtype']=intval($vv['xtype']);
            $list['data'][$kk]['word_imgs']=array();
            if($vv['xtype']==2){
                $audio_url=htmlspecialchars_decode($vv['xcontent'],ENT_QUOTES);
                $audio_url=replace_file_domain($audio_url);
                $list['data'][$kk]['audio_url']=$audio_url;
            }elseif ($vv['xtype']==3){
                $img_url=htmlspecialchars_decode($vv['xcontent'],ENT_QUOTES);
                $tmpimgs=explode(',',$img_url);
                $img_url_arr=array();
                foreach ($tmpimgs as $imgv){
                    $newimgsrc=replace_file_domain($imgv);
                    $img_url_arr[]=$newimgsrc;
                }
                $list['data'][$kk]['word_imgs']=$img_url_arr;
            }elseif ($vv['xtype']==1){
                $list['data'][$kk]['xcontent']=htmlspecialchars_decode($vv['xcontent'],ENT_QUOTES);
            }
        }
        $dataArr['list'] = $list['data'];
        $dataArr['count'] = $list['total'];
        return $dataArr;
    }
    
    /**
     * 导入文字素材
     */
    public function exportMaterialCategory($param)
    {
        if(empty($param['xtype']) || $param['xtype']!=1){
            throw new \think\Exception('参数出错，请确认导入操作！');
        }
        $savenum = $this->uploadMaterial($param['file'],$param['xtype']);
        return $savenum;
    }
    
    /**
     * 文件导入
     */
    public function uploadMaterial($file='',$xtype=1)
    {
        $uploadfile = urldecode($file);
        $uploadfile=trim($uploadfile);
        $uploadfile=ltrim($uploadfile,'/');
        $file_arr = explode('.',$uploadfile);
        $xcount=count($file_arr);
        $xcount=$xcount-1;
        $file_type=$file_arr[$xcount];
        $file_type=strtolower($file_type);
        $file_type=trim($file_type);
        if(!in_array($file_type,array('xlsx','xls'))){
            throw new \think\Exception("表格格式不对，请上传扩展名为xlsx或者是xls的Excel表格！");
        }
        if($xtype!=1){
            throw new \think\Exception("参数出错，请确认导入的是文字素材！");
        }
        $filepath=$_SERVER['DOCUMENT_ROOT'] .'/'. $uploadfile;
        $file_type=ucfirst($file_type);
        $reader = IOFactory::createReader($file_type); //设置以Excel5格式(Excel97-2003工作簿)
        $PHPExcel = $reader->load($filepath); // 载入excel文件
        $sheet = $PHPExcel->getSheet(0); // 读取第一個工作表
        $highestRow = $sheet->getHighestDataRow(); // 取得总行数
        $highestColumm = $sheet->getHighestColumn(); // 取得总列数
        $datas = [];
        $filed = [
            'A' => 'categoryname',
            'B' => 'xcontent',
        ];
        for ($row = 2; $row <= $highestRow; $row++) //行号从1开始
        {
            for ($column = 'A'; $column <= $highestColumm; $column++) //列数是以A列开始
            {
                if(!isset($datas[$row])){
                    $datas[$row]=array();
                }
                if(!isset($filed[$column])){
                    continue;
                }
                $datas[$row][$filed[$column]] = $sheet->getCell($column . $row)->getValue();
            }
        }
        if($datas){
            $nowtime=time();
            $materialCategoryArr=array();
            foreach ($datas as $fvv){
                $categoryname=trim($fvv['categoryname']);
                $categoryname = htmlspecialchars($categoryname, ENT_QUOTES);
                $xcontent=trim($fvv['xcontent']);
                $xcontent = htmlspecialchars($xcontent, ENT_QUOTES);
                if (!empty($categoryname) && !empty($xcontent)) {
                    $cate_id = 0;
                    $saveArr = array('cate_id' => 0, 'xtype' => $xtype);
                    $saveArr['xcontent'] = $xcontent;
                    $saveArr['add_time'] = $nowtime;
                    $saveArr['update_time'] = $nowtime;
                    if ($materialCategoryArr && isset($materialCategoryArr[$categoryname]) && $materialCategoryArr[$categoryname]) {
                        $cate_id = $materialCategoryArr[$categoryname];
                    }
                    if ($cate_id < 1) {
                        $fieldStr = 'cate_id';
                        $whereArr = array();
                        $whereArr[] = array('categoryname', '=', $categoryname);
                        $whereArr[] = array('xtype', '=', $xtype);
                        $whereArr[] = array('is_del', '=', 0);
                        $materialCategory = (new VoiceRobotMaterialCategory())->detail($whereArr, $fieldStr);
                        if ($materialCategory) {
                            $cate_id = $materialCategory['cate_id'];
                            $materialCategoryArr[$categoryname] = $cate_id;
                        } else {
                            $materialCategorySave = array( 'xtype' => $xtype);
                            $materialCategorySave['categoryname'] = $categoryname;
                            $materialCategorySave['add_time'] = $nowtime;
                            $materialCategorySave['update_time'] = $nowtime;
                            $cate_id = (new VoiceRobotMaterialCategory())->insertGetId($materialCategorySave);
                            $materialCategoryArr[$categoryname] = $cate_id;
                        }
                    }
                    $saveArr['cate_id'] = $cate_id;
                    $ret=(new VoiceRobotMaterialContent())->insertGetId($saveArr);
                    $whereCategory=array();
                    $whereCategory[]=array('cate_id','=',$cate_id);
//                    $houseHotwordMaterialCategory->updateFieldPlusNum($whereCategory,'subcount',1);
                    (new VoiceRobotMaterialCategory())->where($whereCategory)->inc('subcount',1)->update();
                }
            }
            return ['error'=>true,'msg'=>'导入成功','data'=>[]];
        }
        return ['error'=>true,'msg'=>'导入失败，请检查表格数据！','data'=>[]];
        
    }
}