<?php
class Voice_robot_hotwordModel extends Model
{
	/**
     * 获取配置
     */
	public function getSetInfo()
    {
        $site_url=C('config.site_url');
        $site_url=rtrim($site_url,'/');
        $return_arr = [
//            'title'=>'请说出你想要搜索的内容',
            'tips'=>'',
            'tencent_voice_asr_open' => intval(C('config.tencent_voice_asr_open')),  //是否开启语音助手  1：开启   0：关闭
            'animation_pic' => replace_file_domain(C('config.tencent_voice_asr_animation_pic')),  //平台首页弹窗动图
            'logo' => C('config.tencent_voice_asr_logo') ? replace_file_domain(C('config.tencent_voice_asr_logo')) : C('config.site_url') . '/static/wxapp/speech_record/speech_log.png',  //右下角悬浮logo
            'voice_icon' => C('config.site_url') . '/static/wxapp/speech_record/speech_icon.png',
            'alert_rule' => intval(C('config.tencent_voice_asr_alert_rule')),  //1:不弹窗、2:每次打开APP均弹窗、3:每隔几天弹窗
            'alert_second' => intval(C('config.tencent_voice_asr_alert_second')), //弹窗几秒后消失
            'interval_days' => intval(C('config.tencent_voice_asr_interval_days')) * 86400,  //多少秒再次弹窗，单位：秒
            
        ];
        $return_arr['voice_starttips'] = C('config.tencent_voice_asr_starttips') ? trim(C('config.tencent_voice_asr_starttips')) : '';
        $return_arr['voice_startmusic'] = C('config.tencent_voice_asr_startmusic') ? replace_file_domain(C('config.tencent_voice_asr_startmusic')) : '';
        $return_arr['title'] = C('config.tencent_voice_asr_starttips') ? trim(C('config.tencent_voice_asr_starttips')) : '您可以说';
        //新增语音助手字段
        $return_arr['pop_close_icon'] = $site_url . '/static/wxapp/speech_record/popclose_icon.png';
        $return_arr['pop_logo'] = $site_url . '/static/wxapp/speech_record/bird_icon.png';
        $poplogo = C('config.tencent_voice_asr_poplogo');
        if ($poplogo) {
            $poplogo = replace_file_domain($poplogo);
            $return_arr['pop_logo'] = $poplogo;
        }
        $return_arr['voice_mname'] = '小点';
        $voice_mname = $poplogo = C('config.tencent_voice_asr_mname');
        if ($voice_mname) {
            $return_arr['voice_mname'] = trim($voice_mname);
        }
        $return_arr['voice_pausetype'] = C('config.tencent_voice_asr_pausetype') ? intval(C('config.tencent_voice_asr_pausetype')) : 1;
        $return_arr['voice_durationtime'] = C('config.tencent_voice_asr_durationtime') ? intval(C('config.tencent_voice_asr_durationtime')) : 5;
        $return_arr['voice_soundwave'] = $site_url . '/static/wxapp/speech_record/speech_wave_icon.gif';
        $voice_soundwave = C('config.tencent_voice_asr_soundwave');
        if ($voice_soundwave) {
            $voice_soundwave = replace_file_domain($voice_soundwave);
            $return_arr['voice_soundwave'] = $voice_soundwave;
        }
        $return_arr['voice_microphone'] = $site_url . '/static/wxapp/speech_record/speech_icon.png';
        $voice_microphone = C('config.tencent_voice_asr_microphone');
        if ($voice_microphone) {
            $voice_microphone = replace_file_domain($voice_microphone);
            $return_arr['voice_microphone'] = $voice_microphone;
        }
        $return_arr['voice_successtips'] = '找到了！看看是不是您想要的';
        $successtips = C('config.tencent_voice_asr_successtips');
        if (!empty($successtips)) {
            $return_arr['voice_successtips'] = $successtips;
        }
        $return_arr['voice_successmusic'] = '';
        $successmusic = C('config.tencent_voice_asr_successmusic');
        if ($successmusic) {
            $successmusic = replace_file_domain($successmusic);
            $return_arr['voice_successmusic'] = $successmusic;
        }
        $return_arr['voice_failtips'] = '抱歉，什么都没有找到，麻烦您在说一次试试';
        $failtips = C('config.tencent_voice_asr_failtips');
        if (!empty($failtips)) {
            $return_arr['voice_failtips'] = trim($failtips);
        }
        $return_arr['voice_failmusic'] = '';
        $failmusic = C('config.tencent_voice_asr_failmusic');
        if ($failmusic) {
            $failmusic = replace_file_domain($failmusic);
            $return_arr['voice_failmusic'] = $failmusic;
        }
        $return_arr['voice_unrecognizedtips'] = '抱歉，我没有听清，麻烦您在说一次呢';
        $unrecognizedtips =C('config.tencent_voice_asr_unrecognizedtips');
        if (!empty($unrecognizedtips)) {
            $return_arr['voice_unrecognizedtips'] = trim($unrecognizedtips);
        }
        $return_arr['voice_unrecognizedmusic'] = '';
        $unrecognizedmusic = C('config.tencent_voice_asr_unrecognizedmusic');
        if ($unrecognizedmusic) {
            $unrecognizedmusic = replace_file_domain($unrecognizedmusic);
            $return_arr['voice_unrecognizedmusic'] = $unrecognizedmusic;
        }
        $return_arr['voice_funcjumpmusic'] = '';
        $funcjumpmusic = C('config.tencent_voice_asr_funcjumpmusic');
        if ($funcjumpmusic) {
            $funcjumpmusic = replace_file_domain($funcjumpmusic);
            $return_arr['voice_funcjumpmusic'] = $funcjumpmusic;
        }
        $return_arr['voice_processtips'] = '好的，请您稍等';
        $processtips = C('config.tencent_voice_asr_processtips');
        if (!empty($processtips)) {
            $return_arr['voice_processtips'] = trim($processtips);
        }
        $return_arr['voice_processmusic'] = '';
        $processmusic = C('config.tencent_voice_asr_processmusic');
        if ($processmusic) {
            $processmusic = replace_file_domain($processmusic);
            $return_arr['voice_processmusic'] = $processmusic;
        }

        //获取关键词
        $wordList = $this->where(['is_del'=>0,'status'=>1])->field('wordname')->order('rand()')->limit(100)->select();
        if($wordList){
            foreach ($wordList as $k=>$v){
                $wordList[$k]['wordname'] = '“'.$v['wordname'].'”';
            }
            $wordName = array_column($wordList,'wordname');
            $return_arr['tips']=implode('，',$wordName);
        }
        return $return_arr;
    }
	
}
?>