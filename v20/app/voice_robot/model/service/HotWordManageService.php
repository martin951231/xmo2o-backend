<?php
namespace app\voice_robot\model\service;

use app\common\model\service\asr\AsrService;
use app\voice_robot\model\db\VoiceRobotHotword;
use app\voice_robot\model\db\VoiceRobotHotwordUrllist;
use app\voice_robot\model\db\VoiceRobotMaterialCategory;
use app\voice_robot\model\db\VoiceRobotMaterialContent;
use think\facade\Db;

class HotWordManageService
{

    /**
     * 关键字列表
     */
    public function hotWordList($param)
    {
        $dataArr = ['list' => array(), 'total_limit' => $param['pageSize'], 'count' => 0];
        $where = [];
        $where[] = ['is_del','=',0];
        if($param['keyword']){
            $where[] = ['wordname','like','%'.$param['keyword'].'%'];
        }
        if($param['dateArr'] && is_array($param['dateArr']) && !empty($param['dateArr']['0'])){
            $startTime = strtotime($param['dateArr']['0']);
            $endTime = strtotime($param['dateArr']['1'].' 23:59:59');
            $where[] = ['update_time','>=',$startTime];
            $where[] = ['update_time','<=',$endTime];
        }
        $list = (new VoiceRobotHotword())->getList($where,'id,wordname,update_time,xtype,status',$param['pageSize']);
        foreach ($list['data'] as $k => $v){
            $list['data'][$k]['update_time_str'] = date('Y-m-d H:i:s',$v['update_time']);
            $list['data'][$k]['status_str']= $v['status']>0 ? '已启用':'已禁用';
            $list['data'][$k]['xtype_str']='功能链接';
            if($v['xtype']==1){
                $list['data'][$k]['xtype_str']='文字回复';
            }else if($v['xtype']==2){
                $list['data'][$k]['xtype_str']='音频回复';
            }else if($v['xtype']==3){
                $list['data'][$k]['xtype_str']='图片回复';
            }
        }
        $dataArr['list'] = $list['data'];
        $dataArr['count'] = $list['total'];
        $dataArr['role_addword']=1;  //新建关键词(默认有权限)
        $dataArr['role_editword']=1;  //编辑关键词(默认有权限)
        $dataArr['role_delword']=1;  //删除关键词(默认有权限)
        return $dataArr;
    }
    
    /**
     * 新增或者编辑关键字信息
     */
    public function editHotWord($param)
    {
        if (empty($param['wordname'])) {
            throw new \think\Exception('关键词名称不能为空！');
        }
        $editData = [];
        $addUrlData = [];
        $editData['wordname'] = $param['wordname'];
        $editData['xtype'] = $param['xtype'];
        $editData['update_time'] = time();
        $editData['comfrom'] = $param['comfrom'];
        if($param['xtype'] == 0){
            $wordurllist = $param['wordurllist'];
            //功能链接
            if (empty($wordurllist) || !is_array($wordurllist)) {
                throw new \think\Exception('链接数据不能为空！');
            }
            foreach ($wordurllist as $kkk => $vv) {
                $tmpArr = array();
                $tmpArr['wordname'] = $param['wordname'];
                $vv['showtitle']=trim($vv['showtitle']);
                $tmpArr['showtitle'] = htmlspecialchars($vv['showtitle'], ENT_QUOTES);
                $tmpArr['jumpurl'] = trim($vv['jumpurl'], ENT_QUOTES);
                $tmpArr['xsort'] = $kkk;
                $addUrlData[] = $tmpArr;
            }
            
            //生成新增或者编辑数据
            $editData['showtitle'] = $wordurllist['0']['showtitle'];
            $editData['jumpurl'] = $wordurllist['0']['jumpurl'];
        }elseif($param['xtype'] == 1){
            //文本
            if($param['comfrom']==1){
                //从素材库
                if($param['cate_id']<1 && $param['material_id']<1){
                    throw new \think\Exception('请从素材库中选择文字回复数据！');
                }
                $editData['material_id']=$param['material_id'];
                $editData['cate_id']=$param['cate_id'];
                $editData['xcontent']='';
            }else{
                if(empty($param['xcontent'])){
                    throw new \think\Exception('回复内容不能为空！');
                }
                $editData['xcontent']=htmlspecialchars($param['xcontent'], ENT_QUOTES);
            }
            
        }elseif($param['xtype'] == 2){
            //音频
            if($param['comfrom']==1){
                //从素材库
                if($param['cate_id']<1 && $param['material_id']<1){
                    throw new \think\Exception('请从素材库中选择音频回复数据！');
                }
                $editData['material_id']=$param['material_id'];
                $editData['cate_id']=$param['cate_id'];
                $editData['xcontent']='';
            }else{
                if(empty($param['audio_url'] )){
                    throw new \think\Exception('请上传回复音频文件！');
                }

                $editData['showtitle']=$param['xname'];
                $editData['xcontent']=$param['audio_url'] ;
            }

        }elseif($param['xtype'] == 3){
            //图片
            if($param['comfrom']==1){
                //从素材库
                if($param['cate_id']<1 && $param['material_id']<1){
                    throw new \think\Exception('请从素材库中选择图片回复数据！');
                }
                $editData['material_id']=$param['material_id'];
                $editData['cate_id']=$param['cate_id'];
                $editData['xcontent']='';
            }else{
                if(empty($param['word_imgs'])){
                    throw new \think\Exception('请上传回复图片文件！');
                }
                $editData['xcontent']=implode(',',$param['word_imgs']);
            }
            
        }

        $where = [];
        $where[] = ['is_del','=',0];
        $where[] = ['wordname','=',$param['wordname']];
        if($param['word_id']){
            $info = (new VoiceRobotHotword())->detail(['id'=>$param['word_id']],true);
            if(!$info){
                throw new \think\Exception('操作对象不能为空！');
            }
            $where[] = ['id','<>',$param['word_id']];
        }
        $selectHotWordByName = (new VoiceRobotHotword())->detail($where,'id');
        if($selectHotWordByName){
            throw new \think\Exception('关键词【'.$param['wordname'].'】在当前关键词类型下已经存在了！');
        }
        
        Db::startTrans();
        try {
            if($param['word_id']){//更新
                (new VoiceRobotHotword())->where('id',$param['word_id'])->update($editData);
                if($addUrlData && $param['xtype'] == 0){
                    foreach ($addUrlData as $k=>$v){
                        $addUrlData[$k]['word_id'] = $param['word_id'];
                        $addUrlData[$k]['update_time'] = time();
                    }
                    (new VoiceRobotHotwordUrllist())->where('word_id',$param['word_id'])->delete();
                    $addUrlList = (new VoiceRobotHotwordUrllist())->insertAll($addUrlData);
                    if(!$addUrlList){
                        throw new \think\Exception('新增关键词功能链接记录失败！');
                    }
                }
            }else{//新增
                $editData['add_time'] = time();
                $workId = (new VoiceRobotHotword())->insertGetId($editData);
                if(!$workId){
                    throw new \think\Exception('新增失败！');
                }
                if($addUrlData && $param['xtype'] == 0){
                    foreach ($addUrlData as $k=>$v){
                        $addUrlData[$k]['word_id'] = $workId;
                        $addUrlData[$k]['update_time'] = time();
                    }
                    $addUrlList = (new VoiceRobotHotwordUrllist())->insertAll($addUrlData);
                    if(!$addUrlList){
                        throw new \think\Exception('新增关键词功能链接记录失败！');
                    }
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return [];
    }
    
    /**
     * 删除关键词
     */
    public function delHotWord($param)
    {
        if(!$param['word_id']){
            throw new \think\Exception('操作对象不能为空！');
        }
        $info = (new VoiceRobotHotword())->detail(['id'=>$param['word_id']],true);
        if(!$info){
            throw new \think\Exception('操作对象不存在！');
        }
        $del = (new VoiceRobotHotword())->where('id',$param['word_id'])->update(
            ['is_del'=>1,'update_time'=>time()]
        );
        if(!$del){
            throw new \think\Exception('删除失败！');
        }
        return [];
    }
    
    /**
     * 编辑关键词状态
     */
    public function editHotWordStatus($param)
    {
        if(!$param['word_id']){
            throw new \think\Exception('操作对象不能为空！');
        }
        $info = (new VoiceRobotHotword())->detail(['id'=>$param['word_id']],true);
        if(!$info){
            throw new \think\Exception('操作对象不存在！');
        }

        $update = (new VoiceRobotHotword())->where('id',$param['word_id'])->update(
            ['status'=>$param['status'],'update_time'=>time()]
        );
        if(!$update){
            throw new \think\Exception('操作失败！');
        }
        return [];
    }
    
    /**
     * 关键词详情
     */
    public function hotWordDetail($param)
    {
        if(!$param['word_id']){
            throw new \think\Exception('操作对象不能为空！');
        }
        $data = (new VoiceRobotHotword())->detail(['id'=>$param['word_id']],true);
        if(!$data){
            throw new \think\Exception('操作对象不存在！');
        }

        $data['audio_url']='';
        $data['word_imgs']=array();
        $data['categoryname']='';
        $data['material_type']='';
        $data['material_info']='';

        if(empty($data['xcontent'])){
            $data['xcontent']='';
        }

        if($data['comfrom']==1){
            if($data['cate_id']>0){
                $fieldStr = 'cate_id,categoryname,xtype';
                $data['material_type']='material_category';
                $whereArr=array();
                $whereArr[]=array('cate_id','=',$data['cate_id']);
                $whereArr[]=array('is_del','=',0);
                $materialCategory=(new VoiceRobotMaterialCategory())->detail($whereArr,$fieldStr);
                if($materialCategory){
                    $data['material_info']=$materialCategory;
                    $data['categoryname']=$materialCategory['categoryname'];
                }
            }
            if($data['material_id']>0){
                $data['xcontent']='';
                $data['material_info']='';
                $data['material_type']='material_content';
                $fieldStr='material_id,cate_id,xtype,xcontent,xname';
                $whereArr=array();
                $whereArr[]=array('material_id','=',$data['material_id']);
                $whereArr[]=array('is_del','=',0);
                $materialContent=(new VoiceRobotMaterialContent())->detail($whereArr,$fieldStr);
                if($materialContent){
                    $materialContent['audio_url']='';
                    $materialContent['word_imgs']=array();
                    if($materialContent['xtype']==2){
                        $audio_url=htmlspecialchars_decode($materialContent['xcontent'],ENT_QUOTES);
                        $audio_url=replace_file_domain($audio_url);
                        $data['audio_url']=$audio_url;
                        $materialContent['audio_url']=$audio_url;
                    }elseif ($materialContent['xtype']==3){
                        $img_url=htmlspecialchars_decode($materialContent['xcontent'],ENT_QUOTES);
                        $tmpimgs=explode(',',$img_url);
                        $img_url_arr=array();
                        foreach ($tmpimgs as $imgv){
                            $newimgsrc=replace_file_domain($imgv);
                            $img_url_arr[]=$newimgsrc;
                        }
                        $data['word_imgs']=$img_url_arr;
                        $materialContent['word_imgs']=$img_url_arr;
                    }elseif ($materialContent['xtype']==1){
                        $data['xcontent']=htmlspecialchars_decode($materialContent['xcontent'],ENT_QUOTES);
                        $materialContent['xcontent']=$data['xcontent'];
                    }

                    $data['material_info']=$materialContent;
                }
            }
        }else{
            if($data['xtype']==2){
                $data['audio_url']=$data['xcontent'] ? replace_file_domain($data['xcontent']):'';
            }else if($data['xtype']==3){
                $tmpimgs=explode(',',$data['xcontent']);
                foreach ($tmpimgs as $imgv){
                    $newimgsrc=replace_file_domain($imgv);
                    $data['word_imgs'][]=$newimgsrc;
                }
            }
        }

        $data['xtype']=strval($data['xtype']);
        $data['comfrom']=intval($data['comfrom']);
        $wordUrlList=array();
        if(!$data['xtype']){
            $wordUrlList=(new VoiceRobotHotwordUrllist())->getList(['word_id'=>$data['id']],true);
        }
        $returnArr = array('hotword' => $data, 'wordurllist' => $wordUrlList);
        return $returnArr;
    }
    
    /**
     * 根据关键词查询信息
     */
    public function getVillageIdentifyInfo($param)
    {
        $keywords = $param['keywords'];
        $retArr = array();
        $retArr['title'] = '请说出你想要搜索的内容';
        $retArr['tips'] = '试试提高你的音量，或放慢语速';
        $retArr['keywords'] = $keywords;
        $retArr['err_msg']='';
        $retArr['searchList'] = array('func_url' => array(), 'text_reply' => array(), 'audio_reply' => array(), 'img_reply' => array(),'tcount'=>0);
        if (empty($keywords)) {
            return $retArr;
        }

        $searchList = array('func_url' => array(), 'text_reply' => array(), 'audio_reply' => array(), 'img_reply' => array(), 'tcount' => 0);
        $tcount = 0;
        //查询关键词
        $houseHotwordMaterialContent = (new VoiceRobotMaterialContent());
        $dbHouseHotwordUrllist = (new VoiceRobotHotwordUrllist());
        $whereArr = [];
        $whereArr[] = ['is_del', '=', 0];
        $whereArr[] = ['status', '=', 1];
        $whereRaw = " (wordname like '%" . $keywords . "%' OR LOCATE(wordname,'" . $keywords . "')) ";
        $wordList = (new VoiceRobotHotword())->where($whereArr)->whereRaw($whereRaw)->select()->toArray();
        foreach($wordList as $v){
            if ($v['comfrom'] == 1) {
                $v['xcontent'] = '';
                if ($v['material_id'] > 0) {
                    $fieldStr = 'material_id,cate_id,xtype,xcontent,xname';
                    $whereArr = array();
                    $whereArr[] = array('material_id', '=', $v['material_id']);
                    $whereArr[] = array('xtype', '=', $v['xtype']);
                    $whereArr[] = array('is_del', '=', 0);
                    $materialContent = $houseHotwordMaterialContent->detail($whereArr, $fieldStr);
                    if ($materialContent) {
                        $v['showtitle']= $materialContent['xname'];
                        $v['xcontent'] = $materialContent['xcontent'];
                    }
                } elseif ($v['cate_id'] > 0) {
                    $fieldStr = 'material_id,cate_id,xtype,xcontent,xname';
                    $whereArr = array();
                    $whereArr[] = array('cate_id', '=', $v['cate_id']);
                    $whereArr[] = array('xtype', '=', $v['xtype']);
                    $whereArr[] = array('is_del', '=', 0);
                    $isgetmaterial = false;
                    $materialContentObj = $houseHotwordMaterialContent->where($whereArr)->field('material_id')->order('material_id desc')->select();
                    if ($materialContentObj && !$materialContentObj->isEmpty()) {
                        $materialContentArr = $materialContentObj->toArray();
                        if (!empty($materialContentArr)) {
                            $materialContentKey = array_rand($materialContentArr);  //随机取一条数据 直接返回key
                            $materialContentOne = $materialContentArr[$materialContentKey];
                            $whereArr[] = array('material_id', '=', $materialContentOne['material_id']);
                            $isgetmaterial = true;
                        }
                    }
                    if (!$isgetmaterial) {
                        continue;
                    }
                    $materialContentOne = $houseHotwordMaterialContent->detail($whereArr, $fieldStr);
                    if ($materialContentOne) {
                        $v['showtitle']= $materialContentOne['xname'];
                        $v['xcontent'] = $materialContentOne['xcontent'];
                    }
                } else {
                    continue;
                }
            }


            if ($v['xtype'] == 0) {
                $whereArr = array('word_id' => $v['id']);
                $urllist = $dbHouseHotwordUrllist->getList($whereArr);
                if ($urllist) {
                    $func_url = array();
                    foreach ($urllist as $uvv) {
                        $tmpArr = array();
                        if(!empty($uvv['showtitle']) && !empty($uvv['jumpurl'])){
                            $tmpArr['label'] = $uvv['showtitle'];
                            $tmpArr['url'] = $uvv['jumpurl'];
                            $tmpArr['value'] = $uvv['word_id'] . '_' . $uvv['id'];
                            $searchList['func_url'][] = $tmpArr;
                        }
                    }
                    $tcount++;
                }
            } else if ($v['xtype'] == 1) {
                /***文本***/
                if (empty($v['xcontent'])) {
                    continue;
                }
                $text_reply = htmlspecialchars_decode($v['xcontent'], ENT_QUOTES);
                $searchList['text_reply'][] = ['title'=>'','value' => $text_reply];
                $tcount++;
            } else if ($v['xtype'] == 2) {
                /**音频**/
                if (empty($v['xcontent'])) {
                    continue;
                }
                $audio_reply = htmlspecialchars_decode($v['xcontent'], ENT_QUOTES);
                $audio_reply = replace_file_domain($audio_reply);
                $searchList['audio_reply'][] = ['title'=>$v['showtitle'],'value' => $audio_reply];
                $tcount++;
            } else if ($v['xtype'] == 3) {
                /**图片**/
                if (empty($v['xcontent'])) {
                    continue;
                }
                $img_reply = htmlspecialchars_decode($v['xcontent'], ENT_QUOTES);
                $tmpimgs = explode(',', $img_reply);
                $img_reply_arr = array();
                foreach ($tmpimgs as $imgv) {
                    $newimgsrc = replace_file_domain($imgv);
                    $img_reply_arr[] = $newimgsrc;
                }
                $searchList['img_reply'][] = ['title'=>'','value' => $img_reply_arr];
                $tcount++;
            }
        }
        $searchList['tcount'] = $tcount;
        
        if ($searchList && $searchList['tcount']>0) {
            $retArr['tips'] = '找到了！看看是不是您想要的';
            $successtips = cfg('tencent_voice_asr_successtips');
            if (!empty($successtips)) {
                $retArr['tips'] = $successtips;
            }
            $retArr['searchList'] = $searchList;
        } else {
            $retArr['tips'] = '抱歉，什么都没有找到，麻烦您在说一次试试';
            $failtips = cfg('tencent_voice_asr_failtips');
            if (!empty($failtips)) {
                $retArr['tips'] = trim($failtips);
            }
        }
        return $retArr;
    }
    
    /**
     * 获取关键词配置
     */
    public function getVoiceWssUrl()
    {
        $clientAsr = new AsrService();
        $nowtime=time();
        $timestamp=$nowtime+1;
        $expired=$nowtime+864000;
        $params=array('timestamp'=>$timestamp,'expired'=>$expired);
        $params['nonce']=rand(1000000,9999999);
        $params['engine_model_type']='16k_zh';
        $params['voice_id']=generate_password(16);
        $params['voice_format']=8;
        $params['filter_modal']=2;
        $params['filter_punc']=1;
        $wssUrl=$clientAsr->signatureWss($params);
        $retArr = [];
        $retArr['wss_url'] = $wssUrl;
        $retArr['title'] = cfg('tencent_voice_asr_starttips') ? trim(cfg('tencent_voice_asr_starttips')) : '您可以说';
        $retArr['tips'] = '';//获取到的关键词
        $retArr['voice_starttips'] = cfg('tencent_voice_asr_starttips') ? trim(cfg('tencent_voice_asr_starttips')) : '您可以说';
        $retArr['voice_startmusic'] = cfg('tencent_voice_asr_startmusic') ? replace_file_domain(cfg('tencent_voice_asr_startmusic')) : '';
        $site_url = rtrim(cfg('site_url'),'/');
        $retArr['pop_close_icon'] = $site_url . '/static/wxapp/speech_record/popclose_icon.png';
        $retArr['pop_logo'] = $site_url . '/static/wxapp/speech_record/bird_icon.png';
        $poplogo = cfg('tencent_voice_asr_poplogo');
        if ($poplogo) {
            $poplogo = replace_file_domain($poplogo);
            $retArr['pop_logo'] = $poplogo;
        }
        $retArr['voice_mname'] = '小点';
        $voice_mname = $poplogo = cfg('tencent_voice_asr_mname');
        if ($voice_mname) {
            $retArr['voice_mname'] = trim($voice_mname);
        }
        $tencent_voice_asr_logo = cfg('tencent_voice_asr_logo');
        if ($tencent_voice_asr_logo) {
            $retArr['logo'] = replace_file_domain($tencent_voice_asr_logo);
        }
        $retArr['voice_pausetype'] = cfg('tencent_voice_asr_pausetype') ? intval(cfg('tencent_voice_asr_pausetype')) : 1;
        $retArr['voice_durationtime'] = cfg('tencent_voice_asr_durationtime') ? intval(cfg('tencent_voice_asr_durationtime')) : 5;
        $retArr['voice_soundwave'] = $site_url . '/static/wxapp/speech_record/speech_wave_icon.gif';
        $voice_soundwave = cfg('tencent_voice_asr_soundwave');
        if ($voice_soundwave) {
            $voice_soundwave = replace_file_domain($voice_soundwave);
            $retArr['voice_soundwave'] = $voice_soundwave;
        }
        $retArr['voice_microphone'] = $site_url . '/static/wxapp/speech_record/speech_icon.png';
        $voice_microphone = cfg('tencent_voice_asr_microphone');
        if ($voice_microphone) {
            $voice_microphone = replace_file_domain($voice_microphone);
            $retArr['voice_microphone'] = $voice_microphone;
        }
        $retArr['voice_successtips'] = '找到了！看看是不是您想要的';
        $successtips = cfg('tencent_voice_asr_successtips');
        if (!empty($successtips)) {
            $retArr['voice_successtips'] = $successtips;
        }
        $retArr['voice_successmusic'] = '';
        $successmusic = cfg('tencent_voice_asr_successmusic');
        if ($successmusic) {
            $successmusic = replace_file_domain($successmusic);
            $retArr['voice_successmusic'] = $successmusic;
        }
        $retArr['voice_failtips'] = '抱歉，什么都没有找到，麻烦您在说一次试试';
        $failtips = cfg('tencent_voice_asr_failtips');
        if (!empty($failtips)) {
            $retArr['voice_failtips'] = trim($failtips);
        }
        $retArr['voice_failmusic'] = '';
        $failmusic = cfg('tencent_voice_asr_failmusic');
        if ($failmusic) {
            $failmusic = replace_file_domain($failmusic);
            $retArr['voice_failmusic'] = $failmusic;
        }
        $retArr['voice_unrecognizedtips'] = '抱歉，我没有听清，麻烦您在说一次呢';
        $unrecognizedtips =cfg('tencent_voice_asr_unrecognizedtips');
        if (!empty($unrecognizedtips)) {
            $retArr['voice_unrecognizedtips'] = trim($unrecognizedtips);
        }
        $retArr['voice_unrecognizedmusic'] = '';
        $unrecognizedmusic = cfg('tencent_voice_asr_unrecognizedmusic');
        if ($unrecognizedmusic) {
            $unrecognizedmusic = replace_file_domain($unrecognizedmusic);
            $retArr['voice_unrecognizedmusic'] = $unrecognizedmusic;
        }
        $retArr['voice_funcjumpmusic'] = '';
        $funcjumpmusic = cfg('tencent_voice_asr_funcjumpmusic');
        if ($funcjumpmusic) {
            $funcjumpmusic = replace_file_domain($funcjumpmusic);
            $retArr['voice_funcjumpmusic'] = $funcjumpmusic;
        }
        $retArr['voice_processtips'] = '好的，请您稍等';
        $processtips = cfg('tencent_voice_asr_processtips');
        if (!empty($processtips)) {
            $retArr['voice_processtips'] = trim($processtips);
        }
        $retArr['voice_processmusic'] = '';
        $processmusic = cfg('tencent_voice_asr_processmusic');
        if ($processmusic) {
            $processmusic = replace_file_domain($processmusic);
            $retArr['voice_processmusic'] = $processmusic;
        }

        $retArr['logo']=$site_url.'/static/wxapp/speech_record/speech_icon.png';
        
        //获取关键词
        $wordList = (new VoiceRobotHotword())->getList(['is_del'=>0,'status'=>1],'wordname',100,1);
        if($wordList['data']){
            foreach ($wordList['data'] as $k=>$v){
                $wordList['data'][$k]['wordname'] = '“'.$v['wordname'].'”';
            }
            $wordName = array_column($wordList['data'],'wordname');
            $retArr['tips']=implode('，',$wordName);
        }
        
        return $retArr;
    }
}