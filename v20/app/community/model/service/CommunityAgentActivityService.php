<?php
namespace app\community\model\service;

use think\facade\Db;
use app\social_activity\model\service\SocialActivityService;
use app\social_activity\model\service\SocialActivityOrderService;
use app\social_activity\model\service\SocialActivityParticipantService;
use app\social_activity\model\db\SocialActivity;

/** Read-only discovery/preview; only submit creates a social_activity order. */
class CommunityAgentActivityService
{
    /** Paginate after eligibility filtering so unavailable rows cannot hide later valid activities. */
    public static function availableActivities(array $session, array $form)
    {
        $members=(new SocialActivityParticipantService())->roomMembers($session['uid'],$session['village_id'],$session['bind_id']);
        $current=null;
        foreach ($members as $member) { if ((int)$member['pigcms_id']===(int)$session['bind_id']) { $current=$member; break; } }
        if (!$current) { throw new \InvalidArgumentException('当前住户身份已失效，请重新选择房屋'); }
        $items=[]; $matched=0; $cursor=0; $skip=($form['page']-1)*10;
        $participantService=new SocialActivityParticipantService();
        $orderService=new SocialActivityOrderService();
        do {
            // Keep the same poster accessor and position_poster fallback as the original activity APIs.
            $query=(new SocialActivity())->where('status',10)->where('end_time','>=',date('Y-m-d'))->where('id','>',$cursor);
            if ($form['activity_keyword']!=='') { $query->whereLike('name','%'.addcslashes($form['activity_keyword'],'%_\\').'%'); }
            $rows=$query->order('id asc')->limit(50)->select()->toArray();
            foreach ($rows as $activity) {
                $cursor=(int)$activity['id'];
                if ($participantService->duplicate($activity['id'],$current)) { continue; }
                $sessions=Db::name('social_activity_session')->where('activity_id',$activity['id'])
                    ->where('ticket_start_time','<=',date('Y-m-d H:i:s'))->where('ticket_end_time','>=',date('Y-m-d H:i:s'))
                    ->where('end_time','>',date('Y-m-d H:i:s'))->order('start_time asc,id asc')->select()->toArray();
                $prices=[]; $firstSlot=null;
                foreach ($sessions as $slot) {
                    if (!in_array((int)$session['village_id'],array_map('intval',explode(',',(string)$slot['village_id'])),true)
                        || (int)$slot['identity']===3) { continue; }
                    // Missing ID information is completed later; known ineligible gender must not be advertised.
                    $card=$current['id_card'] ?? '';
                    if (preg_match('/^\d{17}[\dXx]$/D',$card)) {
                        $gender=((int)substr($card,16,1)%2) ? 2 : 3;
                        if ((int)$slot['gender']!==1 && (int)$slot['gender']!==$gender) { continue; }
                    }
                    $tickets=Db::name('social_activity_session_ticket')->where(['activity_id'=>$activity['id'],'session_id'=>$slot['id']])->select()->toArray();
                    foreach ($tickets as $ticket) {
                        try {
                            // Preview mode reuses original scope, age, capacity and participant rules without writing or requiring missing identity fields.
                            $orderService->checkOrder(['uid'=>$session['uid'],'village_id'=>$session['village_id'],'pigcms_id'=>$session['bind_id'],
                                'activity_id'=>$activity['id'],'session_id'=>$slot['id'],'ticket_id'=>$ticket['id'],'is_save'=>0,
                                'participants'=>[['bind_id'=>$session['bind_id'],'is_external'=>0]]]);
                        } catch (\Exception $e) { self::businessMessage($e); continue; }
                        $prices[]=(float)$ticket['price'];
                        if ($firstSlot===null) { $firstSlot=$slot; }
                    }
                }
                if (!$prices) { continue; }
                if ($matched++<$skip) { continue; }
                $start=strtotime($firstSlot['start_time']); $end=strtotime($firstSlot['end_time']);
                $items[]=['id'=>(int)$activity['id'],'label'=>$activity['name'],
                    'time'=>date('Y-m-d H:i',$start).' - '.date(date('Y-m-d',$start)===date('Y-m-d',$end) ? 'H:i' : 'Y-m-d H:i',$end),
                    'poster'=>$activity['poster'] ?? '', 'position_poster'=>$activity['position_poster'] ?? '',
                    'min_ticket_price'=>number_format(min($prices),2,'.',''), 'price_varies'=>min($prices)!==max($prices),
                    'signup_count'=>(int)Db::name('social_activity_order')->where(['activity_id'=>$activity['id'],'status'=>10])->sum('num')];
                if (count($items)>10) { return ['booking_items'=>array_slice($items,0,10),'has_more'=>true]; }
            }
        } while (count($rows)===50);
        return ['booking_items'=>$items,'has_more'=>false];
    }

    public static function normalize(array $input)
    {
        $form = ['booking_kind'=>'activity'];
        foreach (['item_id','activity_session_id','ticket_id','activity_step','page'] as $key) {
            $value = $input[$key] ?? ($key === 'page' ? 1 : 0);
            if ((!is_int($value) && !is_string($value)) || !ctype_digit((string)$value) || strlen((string)$value)>10) { throw new \InvalidArgumentException('活动选项格式错误'); }
            $form[$key] = (int)$value;
        }
        if ($form['activity_step']>3 || $form['page']<1 || $form['page']>1000) { throw new \InvalidArgumentException('活动步骤或页码无效'); }
        foreach (['activity_keyword'=>60,'quote'=>64] as $key=>$limit) {
            $value=$input[$key] ?? '';
            if (!is_string($value) || mb_strlen($value)>$limit) { throw new \InvalidArgumentException('活动搜索内容过长'); }
            $form[$key]=trim($value);
        }
        $people=$input['participants'] ?? [];
        if (!is_array($people) || count($people)>50) { throw new \InvalidArgumentException('最多选择50位参加人'); }
        $form['participants']=[];
        foreach ($people as $person) {
            if (!is_array($person)) { throw new \InvalidArgumentException('参加人格式错误'); }
            $row=['bind_id'=>max(0,(int)($person['bind_id'] ?? 0)), 'is_external'=>!empty($person['is_external']) ? 1 : 0];
            foreach (['user_name'=>100,'phone'=>20,'id_number'=>18] as $key=>$limit) {
                $value=$person[$key] ?? '';
                if (!is_string($value) || mb_strlen($value)>$limit) { throw new \InvalidArgumentException('参加人资料格式错误'); }
                $row[$key]=trim($value);
            }
            $form['participants'][]=$row;
        }
        $form['form_question']=$input['form_question'] ?? [];
        if (!is_array($form['form_question']) || strlen(json_encode($form['form_question']))>24000) { throw new \InvalidArgumentException('报名表单过长'); }
        return $form;
    }

    public static function metadata(array $session, array $form)
    {
        $meta=['booking_items'=>[], 'booking_fields'=>[], 'missing_fields'=>[], 'blocking_reason'=>'', 'quote'=>'', 'price_label'=>'',
            'activity_sessions'=>[], 'activity_tickets'=>[], 'participant_options'=>[], 'survey'=>null, 'has_more'=>false];
        if (!$form['item_id'] || $form['activity_step']===0) {
            $meta=array_merge($meta,self::availableActivities($session,$form));
        }
        if (!$form['item_id']) { $meta['missing_fields'][]='item_id'; return $meta; }
        $active=(new SocialActivity())->where(['id'=>$form['item_id'],'status'=>10])->where('end_time','>=',date('Y-m-d'))->findOrEmpty()->toArray();
        if (!$active) { $meta['blocking_reason']='活动已结束或已下架，请返回重新选择'; return $meta; }
        $detail=(new SocialActivityService())->getActivityDetail(['id'=>$form['item_id'],'uid'=>$session['uid'],'session_date'=>'']);
        $meta['activity_name']=$detail['name']; $meta['activity_notice']=strip_tags($detail['notice'] ?? '');
        $meta['poster']=$active['poster'] ?? '';
        $meta['position_poster']=$active['position_poster'] ?? '';
        $chosen=null;
        foreach ($detail['sessions'] as $row) {
            if (strtotime($row['end_time'])<=time() || strtotime($row['ticket_end_time'])+59<time()) { continue; }
            $reason=empty($row['show']) ? '报名尚未开始' : '';
            if ((int)$row['identity']!==3 && !in_array((int)$session['village_id'],$row['village_id'])) { $reason='当前小区不在活动范围'; }
            $meta['activity_sessions'][]=['id'=>(int)$row['id'],'label'=>$row['name'],'time'=>$row['start_time'].' ~ '.$row['end_time'],
                'location'=>$row['location_name'],'disabled'=>$reason!=='','reason'=>$reason,
                'eligibility'=>[(int)$row['identity']===2 ? '仅业主' : ((int)$row['identity']===3 ? '仅外部用户' : '业主及外部用户'),
                    (int)$row['gender']===2 ? '仅男生' : ((int)$row['gender']===3 ? '仅女生' : '不限性别'),
                    (int)$row['age_max']>0 ? $row['age_min'].'～'.$row['age_max'].'岁' : '不限年龄']];
            if ((int)$row['id']===$form['activity_session_id']) { $chosen=$row; if ($reason) { $meta['blocking_reason']=$reason; } }
        }
        if (!$chosen) { $meta['missing_fields'][]='activity_session_id'; if ($form['activity_session_id']) { $meta['blocking_reason']='场次已结束或报名已截止，请重新选择'; } return $meta; }
        $ticket=null;
        foreach ($chosen['tickets'] as $row) {
            $left=max(0,(int)$row['num']-(int)$row['order_count']);
            $meta['activity_tickets'][]=['id'=>(int)$row['id'],'label'=>$row['name'],'price'=>number_format((float)$row['price'],2,'.',''),'remaining'=>$left,'disabled'=>$left<1];
            if ((int)$row['id']===$form['ticket_id']) { $ticket=$row; if ($left<1) { $meta['blocking_reason']='该票种已满，请重新选择'; } }
        }
        $service=new SocialActivityParticipantService();
        $options=$service->options(['activity_id'=>$form['item_id'],'uid'=>$session['uid'],'village_id'=>$session['village_id'],'pigcms_id'=>$session['bind_id']]);
        $members=array_column($service->roomMembers($session['uid'],$session['village_id'],$session['bind_id']),null,'pigcms_id');
        foreach ($options['groups'] as $group) { foreach ($group['people'] as $person) {
            $member=$members[$person['bind_id']];
            $person['user_name']=$member['name'] ?: ''; $person['phone']=$member['phone'] ?: ''; $person['id_number']=$member['id_card'] ?: '';
            $person['name_locked']=$person['user_name']!==''; $person['id_number_locked']=$person['id_number']!=='';
            if ((int)$chosen['identity']===3) { $person['disabled']=true; $person['status_text']='仅限外部用户'; }
            $meta['participant_options'][]=$person;
        } }
        $meta['allow_external']=in_array((int)$chosen['identity'],[1,3],true);
        if (!empty($active['form_id'])) { $meta['survey']=(new \app\survey\service\FormService())->getFormDetail((int)$active['form_id'])->toArray(); }
        if (!$ticket) { $meta['missing_fields'][]='ticket_id'; return $meta; }
        $total=number_format((float)$ticket['price']*count($form['participants']),2,'.','');
        $meta['price_label']='共'.count($form['participants']).'人，合计 ¥'.$total;
        $meta['quote']=hash('sha256',json_encode([$form['item_id'],$chosen['id'],$ticket['id'],number_format((float)$ticket['price'],2,'.',''),count($form['participants'])]));
        if (!$form['participants']) { $meta['missing_fields'][]='participants'; return $meta; }
        try {
            (new SocialActivityOrderService())->checkOrder(self::params($session,$form,1));
            self::validateSurvey($meta['survey'],$form['form_question']);
        } catch (\Exception $e) { $meta['blocking_reason']=self::businessMessage($e); }
        return $meta;
    }

    public static function validateSurvey($survey, array $answers)
    {
        if (!$survey) { return; }
        if ((int)($answers['form_id'] ?? 0)!==(int)$survey['id']) { throw new \InvalidArgumentException('请填写活动报名表单'); }
        $indexed=array_column($answers['answers'] ?? [],null,'id');
        foreach ($survey['questions'] as $question) {
            if (!isset($indexed[$question['id']])) { throw new \InvalidArgumentException('请填写'.$question['title']); }
            // Reuse the same per-question validators as the normal signup form (no writes).
            $question['extra_config']=json_encode($question['extra_config'] ?? []);
            $model=new \app\survey\model\SurveyQuestion($question);
            \app\survey\service\strategy\QuestionStrategyFactory::create($question['type'])->validateAnswer($model,$indexed[$question['id']]);
        }
    }

    private static function params(array $session,array $form,$save)
    {
        return ['uid'=>$session['uid'],'village_id'=>$session['village_id'],'pigcms_id'=>$session['bind_id'],
            'activity_id'=>$form['item_id'],'session_id'=>$form['activity_session_id'],'ticket_id'=>$form['ticket_id'],
            'participants'=>$form['participants'],'buy_num'=>count($form['participants']),'is_save'=>$save,'form_question'=>$form['form_question']];
    }

    public static function submit(array $session,array $form)
    {
        Db::name('social_activity')->where('id',$form['item_id'])->lock(true)->find();
        $meta=self::metadata($session,$form);
        if ($meta['blocking_reason'] || $meta['missing_fields']) { throw new \InvalidArgumentException($meta['blocking_reason'] ?: '请补齐活动报名信息'); }
        if ($form['quote']!==$meta['quote']) { throw new \InvalidArgumentException('报名费用已变化，请核对后重新确认'); }
        try { return (new SocialActivityOrderService())->saveOrder(self::params($session,$form,1)); }
        catch (\Exception $e) { throw new \InvalidArgumentException(self::businessMessage($e)); }
    }

    private static function businessMessage(\Exception $error)
    {
        if ($error instanceof \think\db\exception\DbException || $error instanceof \PDOException) { throw $error; }
        // Legacy services wrap database errors in plain Exception. Never expose those details.
        $message=$error->getMessage();
        if (mb_strlen($message)>160 || preg_match('/SQLSTATE|SELECT |INSERT |UPDATE |DELETE |SQL:|\\\\|Stack trace/i',$message)) { throw new \RuntimeException('活动服务暂时不可用'); }
        return $message ?: '活动报名校验失败，请重试';
    }

    public static function result(array $session)
    {
        $row=Db::name('social_activity_order')->where(['id'=>$session['application_id'],'uid'=>$session['uid']])->find();
        if (!$row) { throw new \RuntimeException('报名订单不存在'); }
        $pay=(int)$row['status']===0 && !(int)$row['paid'] && (float)$row['amount']>0;
        return ['application_id'=>(int)$row['id'],'application_status'=>(int)$row['status'],'booking_kind'=>'activity','order_type'=>'social_activity',
            'payment_required'=>$pay,'message'=>(int)$row['status']===20 ? '报名订单已取消' : ($pay ? '报名订单已创建，待支付' : '活动报名成功')];
    }
}
