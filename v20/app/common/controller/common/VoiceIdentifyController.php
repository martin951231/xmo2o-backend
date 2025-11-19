<?php
/**
 * 公共语音识别
 * Author: 
 * Date Time: 2022-10-14
 */

namespace app\common\controller\common;
use app\common\controller\CommonBaseController;
use app\common\model\service\asr\AsrService;
use app\community\model\service\HouseHotWordManageService;
class VoiceIdentifyController extends CommonBaseController
{
    
    public function getVoiceWssUrl(){
        $village_id = $this->request->param('village_id','0','int');
        $village_id=$village_id ? intval($village_id):0;
        try {
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
            $retArr=array('wss_url'=>$wssUrl);
            $retArr['title']='您可以说';
            $retArr['tips']='';
            $retArr['voice_starttips'] = '您可以说';
            $starttips =cfg('tencent_voice_asr_starttips');
            if (!empty($starttips)) {
                $retArr['title'] = trim($starttips);
                $retArr['voice_starttips'] = trim($starttips);
            }
            $retArr['voice_startmusic'] = '';
            $startmusic = cfg('tencent_voice_asr_startmusic');
            if ($startmusic) {
                $startmusic = replace_file_domain($startmusic);
                $retArr['voice_startmusic'] = $startmusic;
            }
            $site_url=cfg('site_url');
            $site_url=rtrim($site_url,'/');
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
            
            if($village_id>0){
                $houseHotWordManageService= new HouseHotWordManageService();
                $retArr['tips']=$houseHotWordManageService->getHotWordTipsByVillageId($village_id);
            }
            return api_output(0,$retArr);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
       }
    }
    
    /***
    *小区语音识别
    */
    public function getVillageIdentifyInfo()
    {
        $village_id = $this->request->param('village_id', '0', 'int');
        $village_id = $village_id ? intval($village_id) : 0;
        $keywords = $this->request->param('keywords', '', 'trim');
        $retArr = array();
        $retArr['title'] = '请说出你想要搜索的内容';
        $retArr['tips'] = '试试提高你的音量，或放慢语速';
        $retArr['keywords'] = $keywords;
        $retArr['err_msg']='';
        $retArr['searchList'] = array('func_url' => array(), 'text_reply' => array(), 'audio_reply' => array(), 'img_reply' => array(),'tcount'=>0);
        if ($village_id < 1 || empty($keywords)) {
            return api_output(0, $retArr);
        }
        try {
            $houseHotWordManageService = new HouseHotWordManageService();
            $searchList = $houseHotWordManageService->getHotWordIdentifyInfo($village_id, $keywords);
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
                /*
                if($village_id>0){
                    $houseHotWordManageService= new HouseHotWordManageService();
                    $tips=$houseHotWordManageService->getHotWordTipsByVillageId($village_id);
                    if($tips){
                        $retArr['tips'] .='，请尝试语音搜索：'.$tips;
                    }
                }
                */
            }
        } catch (\Exception $e) {
            $retArr['err_msg']=$e->getMessage();
            return api_output(0, $retArr);
        }
        return api_output(0, $retArr);
    }
    
}
