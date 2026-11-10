<?php
namespace app\community\model\service;

use think\facade\Db;

/** Resident-owned, versioned workflow. No business write occurs before submit(). */
class CommunityAgentService
{
    public function start($uid, $villageId, $bindId, $message, $intent = '')
    {
        $this->assertTransactionalTables();
        $this->resident($uid, $villageId, $bindId);
        $message = trim($message);
        if (($intent === '' && $message === '') || mb_strlen($message, 'UTF-8') > 1000) {
            throw new \InvalidArgumentException('请输入1至1000字的需求');
        }
        // Retain the user's description in the expiring draft for repair prefilling.
        $recent = Db::name('community_agent_session')->where('uid', $uid)
            ->where('created_at', '>=', time() - 60)->count();
        if ($recent >= 10) { throw new \InvalidArgumentException('操作频繁，请稍后再试'); }
        if ($intent !== '' && !in_array($intent, array_column(CommunityAgentPickupPolicy::options(), 'value'), true)) {
            throw new \InvalidArgumentException('请选择支持的事项');
        }
        $result = $intent === '' ? (new CommunityAgentIntentClient())->recognize($message) : [
            'action'=>'select_intent', 'intent'=>$intent, 'prefill'=>[],
            'booking_kind'=>$intent === 'activity_signup' ? 'activity' : 'merchant',
            'recognition'=>['status'=>'manual', 'code'=>'shortcut', 'message'=>'快捷入口', 'retryable'=>false, 'http_status'=>0],
        ];
        $session = [
            'id'=>bin2hex(random_bytes(16)), 'uid'=>$uid, 'village_id'=>$villageId, 'bind_id'=>$bindId,
            'state'=>$result['action'] === 'confirm_intent' ? 'awaiting_intent' : 'choosing_intent',
            'suggested_intent'=>$result['intent'], 'intent'=>'',
            'payload'=>$this->encode(['description'=>$message, 'prefill'=>$result['prefill'], 'recognition'=>$result['recognition'] ?? null, 'booking_kind'=>$result['booking_kind'] ?? 'activity', 'activity_keyword'=>$result['activity_keyword'] ?? '']), 'version'=>1, 'application_id'=>0,
            'created_at'=>time(), 'updated_at'=>time(), 'expires_at'=>time() + 1800,
        ];
        if (isset($result['decision'])) {
            $payload = json_decode($session['payload'], true);
            foreach (['decision','operation','decision_reason','decision_message'] as $field) { $payload[$field] = $result[$field]; }
            $session['payload'] = $this->encode($payload);
        }
        Db::name('community_agent_session')->insert($session);
        if ($intent !== '') { return $this->choose($uid, $session['id'], 1, 'manual', $intent); }
        // Reuse the same authorized, versioned form initializer as a manual choice.
        // Opening a form never authorizes submitting an application or payment.
        if (($result['decision'] ?? '') === 'OPEN_BUSINESS' && ($result['operation'] ?? '') === 'create' && $result['action'] === 'open_business') {
            return $this->choose($uid, $session['id'], 1, 'manual', $result['intent']);
        }
        return $this->view($session);
    }

    public function detail($uid, $id)
    {
        $session = $this->session($uid, $id);
        $this->resident($uid, $session['village_id'], $session['bind_id']);
        if ($session['state']==='submitting') { return $this->recoverMerchantBooking($session); }
        $payload=json_decode($session['payload'],true);
        if ($session['state']==='awaiting_intent' && (int)$session['expires_at']>time() && ($payload['recognition']['status'] ?? '')==='recognized' &&
            in_array($payload['decision'] ?? 'OPEN_BUSINESS',['OPEN_BUSINESS'],true)) {
            // Resume pre-upgrade drafts without reviving the old redundant confirmation screen.
            return $this->choose($uid,$id,(int)$session['version'],'accept','');
        }
        return $this->view($session);
    }

    public function choose($uid, $id, $version, $decision, $intent, array $form = [])
    {
        $bookingPrefillText='';
        $response=$this->mutate($uid, $id, function (&$session) use ($version, $decision, $intent, $form, &$bookingPrefillText) {
            CommunityAgentPickupPolicy::requireVersion($session, $version);
            CommunityAgentPickupPolicy::requireEditable($session);
            $choosingFromInput=in_array($session['state'],['awaiting_intent','choosing_intent'],true);
            if ($decision === 'switch') {
                if (!in_array($session['state'], ['awaiting_intent','choosing_intent','editing','ready'], true) ||
                    !in_array($intent, array_column(CommunityAgentPickupPolicy::options(), 'value'), true)) {
                    throw new \InvalidArgumentException('当前无法切换到该服务');
                }
                $payload=json_decode($session['payload'],true);
                if (in_array($session['state'], ['editing','ready'], true)) {
                    $draft=$form ?: $payload['form'];
                    if (in_array($session['intent'], ['service_booking','activity_signup'], true) &&
                        (($draft['booking_kind'] ?? '') === 'activity') !== (($payload['form']['booking_kind'] ?? '') === 'activity')) {
                        throw new \InvalidArgumentException('请从对应入口办理');
                    }
                    // Normalize incomplete drafts without submitting or creating a business record.
                    $payload['drafts'][$session['intent']]=$session['intent']==='bill_payment' ? CommunityAgentBillService::normalize($draft) :
                        (in_array($session['intent'], ['service_booking','activity_signup'], true) ? CommunityAgentBookingService::normalize($draft) :
                        ($session['intent']==='repair' ? CommunityAgentRepairPolicy::normalize($draft) : CommunityAgentPickupPolicy::normalize($draft)));
                }
                $payload['manual_navigation']=true;
                $session['payload']=$this->encode($payload);
                $session['state']='choosing_intent';
                $decision='manual';
            }
            if ($decision === 'reject') {
                if ($session['state'] !== 'awaiting_intent') {
                    throw new \InvalidArgumentException('当前不处于需求确认阶段');
                }
                $session['state'] = 'choosing_intent';
                $session['intent'] = '';
                return;
            }
            if ($decision === 'accept' && $session['state'] === 'awaiting_intent') {
                $intent = $session['suggested_intent'];
                if ($intent === 'service_booking' && (json_decode($session['payload'],true)['booking_kind'] ?? '') === 'activity') { $intent = 'activity_signup'; }
            } elseif ($decision !== 'manual' || $session['state'] !== 'choosing_intent') {
                throw new \InvalidArgumentException('请先确认或选择需求');
            }
            if (!in_array($intent, ['express_pickup','repair','service_booking','activity_signup','bill_payment'], true)) {
                throw new \InvalidArgumentException('该业务暂未接入');
            }
            $resident = $this->resident($session['uid'], $session['village_id'], $session['bind_id']);
            $payload=json_decode($session['payload'],true);
            $payload['return_state']=$session['state']==='awaiting_intent' && ($payload['recognition']['status'] ?? '')==='recognized' ? 'choosing_intent' : $session['state'];
            if ($session['state']==='awaiting_intent' && ($payload['recognition']['status'] ?? '')==='recognized') { $payload['manual_navigation']=true; }
            if (!empty($payload['drafts'][$intent])) {
                $payload['form']=$payload['drafts'][$intent];
                $session['payload']=$this->encode($payload); $session['intent']=$intent; $session['state']='editing'; return;
            }
            $description=($choosingFromInput || $session['suggested_intent']===$intent || $session['suggested_intent']==='unknown') ? ($payload['description'] ?? '') : '';
            $autoPrefill=$description!=='' && (
                (($payload['decision'] ?? '')==='OPEN_BUSINESS' && ($payload['operation'] ?? '')==='create') ||
                $decision==='accept' || !empty($payload['manual_navigation']) || ($payload['recognition']['code'] ?? '')==='shortcut');
            $session['payload']=$this->encode($payload);
            if ($intent === 'bill_payment') {
                $payload['form']=CommunityAgentBillService::normalize([]);
                $session['payload']=$this->encode($payload); $session['intent']=$intent; $session['state']='editing'; return;
            }
            if (in_array($intent, ['service_booking','activity_signup'], true)) {
                $payload = json_decode($session['payload'], true);
                $room = $resident['room'];
                $address = (new HouseVillageService())->getSingleFloorRoom($room['single_id'], $room['floor_id'], $room['layer_id'], $room['pigcms_id'], $session['village_id']);
                $village = Db::name('house_village')->where('village_id',$session['village_id'])->find();
                $seed=['booking_kind'=>$intent==='activity_signup' ? 'activity' : 'merchant', 'activity_keyword'=>$autoPrefill && $intent==='activity_signup' ? ($payload['activity_keyword'] ?? '') : '', 'contact_name'=>$resident['bind']['name'] ?? '', 'contact_phone'=>$resident['bind']['phone'] ?? '',
                    'address_text'=>($village['village_name'] ?? '').$address, 'remark'=>$description,
                    'latitude'=>(float)($village['lat'] ?? 0),'longitude'=>(float)($village['long'] ?? 0)];
                if ($autoPrefill && $intent==='service_booking') {
                    $seed=array_merge($seed,CommunityAgentPrefillService::contacts($description));
                    $bookingPrefillText=$description;
                }
                if ($autoPrefill && $intent==='activity_signup' && $seed['activity_keyword']==='') { $seed['activity_keyword']=CommunityAgentPrefillService::activityKeyword($description); }
                $form = CommunityAgentBookingService::normalize($seed);
                $session['payload'] = $this->encode(array_merge($payload,['form'=>$form,'booking_token'=>bin2hex(random_bytes(32))]));
                $session['intent']=$intent; $session['state']='editing'; return;
            }
            if ($intent === 'repair') {
                $payload = json_decode($session['payload'], true);
                $form = CommunityAgentRepairPolicy::normalize(['cat_id'=>0, 'address_type'=>'room',
                    'address_id'=>$resident['room']['pigcms_id'], 'order_content'=>$description, 'order_imgs'=>[]]);
                if ($autoPrefill) { $form=CommunityAgentPrefillService::repair($form,CommunityAgentRepairPolicy::metadata($session,$resident),$description); }
                $session['payload'] = $this->encode(array_merge($payload,['form'=>$form]));
                $session['state'] = 'editing';
                $session['intent'] = 'repair';
                return;
            }
            $default = Db::name('common_pickup_person')->where([
                'user_id'=>$session['uid'], 'village_id'=>$session['village_id'], 'status'=>1, 'is_default'=>1,
            ])->order('id desc')->find();
            $form = [
                'recipient_name'=>$default['recipient_name'] ?? $resident['bind']['name'],
                'recipient_phone'=>$default['recipient_phone'] ?? $resident['bind']['phone'],
                'location_id'=>$default['location_id'] ?? 0,
            ];
            if (!empty($form['location_id']) && !$this->location($session['village_id'], $form['location_id'])) {
                $form['location_id'] = 0;
            }
            $payload = json_decode($session['payload'], true);
            $prefill = $session['suggested_intent']===$intent ? ($payload['prefill'] ?? []) : [];
            $prefill=array_merge($prefill,CommunityAgentPrefillService::pickup($description));
            // Invalid model proposals cannot prevent opening the empty/default form.
            $form=CommunityAgentPickupPolicy::normalize($form);
            foreach (['pickup_code','track_num','recipient_name','recipient_phone'] as $field) {
                if (!isset($prefill[$field]) || !is_string($prefill[$field]) || $prefill[$field]==='' || strpos($description,$prefill[$field])===false) { continue; }
                try { $form=CommunityAgentPickupPolicy::normalize(array_merge($form,[$field=>$prefill[$field]])); }
                catch (\InvalidArgumentException $e) { /* Keep other valid proposals and resident defaults. */ }
            }
            if ($autoPrefill) {
                $locations=Db::name('express_pick_up_location')->where(['village_id'=>$session['village_id'],'status'=>1])->field('id,location')->select()->toArray();
                $location=CommunityAgentPrefillService::uniqueMention($locations,$description,'location');
                if ($location) { $form['location_id']=(int)$location['id']; }
                elseif (preg_match('/驿站|快递柜|取件地址|取件地点/u',$description)) { $form['location_id']=0; }
            }
            $session['payload'] = $this->encode(array_merge($payload,['form'=>$form]));
            $session['state'] = 'editing';
            $session['intent'] = 'express_pickup';
        });
        // Discover CMS options only after the proof is committed. IDs come from
        // its authorized catalogue, never from the model; quotes still need confirmation.
        $item=$bookingPrefillText!=='' ? CommunityAgentPrefillService::uniqueMention($response['booking_items'] ?? [],$bookingPrefillText) : null;
        if (!$item || !empty($response['booking_load_failed'])) { return $response; }
        return $this->mutate($uid,$id,function (&$session) use ($response,$item) {
            if ((int)$session['version']!==$response['version'] || $session['state']!=='editing' || $session['intent']!=='service_booking') { return false; }
            CommunityAgentPickupPolicy::requireEditable($session);
            $payload=json_decode($session['payload'],true);
            if (!empty($payload['form']['item_id'])) { return false; }
            $payload['form']['item_id']=(int)$item['id'];
            $session['payload']=$this->encode($payload);
        });
    }

    public function saveDraft($uid, $id, $version, array $form)
    {
        return $this->mutate($uid, $id, function (&$session) use ($version, $form) {
            CommunityAgentPickupPolicy::requireVersion($session, $version);
            CommunityAgentPickupPolicy::requireEditable($session);
            if (in_array($session['state'], ['editing','ready'], true) && $session['intent']==='bill_payment') {
                $this->assertTransactionalTables(['house_new_pay_order','house_new_select_project_record']);
                $form=CommunityAgentBillService::normalize($form);
                $quote=$form['order_ids'] ? CommunityAgentBillService::quote($session,$form) : [];
                $payload=json_decode($session['payload'],true); $payload['form']=$form; $payload['bill_quote']=$quote;
                $session['payload']=$this->encode($payload); $session['state']=$quote ? 'ready' : 'editing'; return;
            }
            if (in_array($session['state'], ['editing','ready'], true) && in_array($session['intent'], ['service_booking','activity_signup'], true)) {
                $form = CommunityAgentBookingService::normalize($form);
                $previous=json_decode($session['payload'],true)['form'];
                if (($form['booking_kind'] === 'activity') !== ($previous['booking_kind'] === 'activity')) { throw new \InvalidArgumentException('预约服务和社群活动请分别从对应入口办理'); }
                if ($form['booking_kind']==='merchant' && ($previous['booking_kind'] ?? '')!=='merchant') {
                    $resident=$this->resident($session['uid'],$session['village_id'],$session['bind_id']);
                    $form['contact_name']=$form['contact_name'] ?: ($resident['bind']['name'] ?? '');
                    $form['contact_phone']=$form['contact_phone'] ?: ($resident['bind']['phone'] ?? '');
                    $room=$resident['room'];
                    $form['address_text']=$form['address_text'] ?: (new HouseVillageService())->getSingleFloorRoom($room['single_id'],$room['floor_id'],$room['layer_id'],$room['pigcms_id'],$session['village_id']);
                }
                $meta = CommunityAgentBookingService::metadata($session,$form);
                if ($form['booking_kind']==='activity' && $form['activity_step']===3 && ($meta['missing_fields'] || $meta['blocking_reason'])) { $form['activity_step']=2; }
                $payload = json_decode($session['payload'],true); $payload['form']=$form;
                $session['payload']=$this->encode($payload);
                $session['state']=(!$meta['missing_fields'] && !$meta['blocking_reason']) ? 'ready' : 'editing'; return;
            }
            if (in_array($session['state'], ['editing','ready'], true) && $session['intent'] === 'repair') {
                $form = CommunityAgentRepairPolicy::normalize($form);
                $resident = $this->resident($session['uid'], $session['village_id'], $session['bind_id']);
                $meta = CommunityAgentRepairPolicy::metadata($session, $resident);
                CommunityAgentRepairPolicy::validateSelection($form, $meta);
                $payload = json_decode($session['payload'], true);
                $session['payload'] = $this->encode(array_merge($payload,['form'=>$form]));
                $session['state'] = CommunityAgentRepairPolicy::missing($form, $meta['image_required']) ? 'editing' : 'ready';
                return;
            }
            if (!in_array($session['state'], ['editing', 'ready'], true) || $session['intent'] !== 'express_pickup') {
                throw new \InvalidArgumentException('请先选择代取快递');
            }
            $form = CommunityAgentPickupPolicy::normalize($form);
            if ($form['location_id'] && !$this->location($session['village_id'], $form['location_id'])) {
                throw new \InvalidArgumentException('取件地址不存在或不属于当前小区');
            }
            $payload = json_decode($session['payload'], true);
            $session['payload'] = $this->encode(array_merge($payload,['form'=>$form]));
            $session['state'] = CommunityAgentPickupPolicy::missing($form) ? 'editing' : 'ready';
        });
    }

    public function submit($uid, $id, $version, $confirmed)
    {
        $this->assertTransactionalTables();
        $initial = $this->session($uid,$id);
        if (in_array($initial['intent'], ['service_booking','activity_signup'], true) && (json_decode($initial['payload'],true)['form']['booking_kind'] ?? '')==='merchant') {
            return $this->submitMerchantBooking($uid,$id,$version,$confirmed);
        }
        $venueService = null;
        $repairService = null;
        $response = $this->mutate($uid, $id, function (&$session) use ($version, $confirmed, &$repairService, &$venueService) {
            if ($confirmed !== true) { throw new \InvalidArgumentException('请确认提交申请'); }
            // Lost HTTP response: same draft/version returns its original application.
            if ($session['state'] === 'submitted') {
                if ((int)$version !== (int)$session['version'] - 1) {
                    throw new \InvalidArgumentException('提交版本不一致，请查询办理结果');
                }
                return false;
            }
            CommunityAgentPickupPolicy::requireVersion($session, $version);
            CommunityAgentPickupPolicy::requireEditable($session);
            if ($session['state']==='ready' && $session['intent']==='bill_payment') {
                $this->assertTransactionalTables(['house_new_pay_order','house_new_pay_order_summary','house_new_select_project_record','plat_order','house_village_user_bind']);
                $form=CommunityAgentBillService::normalize(json_decode($session['payload'],true)['form']);
                $session['application_id']=CommunityAgentBillService::submit($session,$form); $session['state']='submitted'; return;
            }
            if ($session['state']==='ready' && in_array($session['intent'], ['service_booking','activity_signup'], true)) {
                $activityForm=json_decode($session['payload'],true)['form'];
                if ($activityForm['booking_kind']==='activity') {
                    $this->assertTransactionalTables(['social_activity','social_activity_order','social_activity_participant','survey_submit','survey_submit_answer']);
                    $result=CommunityAgentActivityService::submit($session,$activityForm);
                    if (empty($result['order_id'])) { throw new \RuntimeException('活动报名未返回订单'); }
                    $session['application_id']=$result['order_id']; $session['state']='submitted'; return;
                }
                $this->assertTransactionalTables(['house_venue_activity','house_venue_appoint_record','house_venue_news_record']);
                $form=json_decode($session['payload'],true)['form'];
                if ($form['booking_kind']!=='venue') { throw new \InvalidArgumentException('请选择预约类型'); }
                Db::name('house_venue_activity')->where('id',$form['item_id'])->lock(true)->find();
                $meta=CommunityAgentBookingService::metadata($session,$form);
                if ($meta['missing_fields'] || $meta['blocking_reason']) { throw new \InvalidArgumentException($meta['blocking_reason'] ?: '请补齐预约信息'); }
                $resident=$this->resident($session['uid'],$session['village_id'],$session['bind_id']);
                $venueService=new HouseVenueActivityService();
                $venueService->deferAgentNotice=true;
                $result=$venueService->subAppoint(['village_id'=>$session['village_id'],'pigcms_id'=>$session['bind_id'],
                    'activity_id'=>$form['item_id'],'day'=>$form['day'],'time'=>explode('-',$form['slot']), 'remarks'=>$form['remark'],
                    'user_arr'=>array_merge($resident['bind'],['name'=>$form['contact_name'],'phone'=>$form['contact_phone']])]);
                if (empty($result['record_id'])) { throw new \RuntimeException('预约记录保存失败'); }
                $session['application_id']=$result['record_id']; $session['state']='submitted'; return;
            }
            if ($session['state'] === 'ready' && $session['intent'] === 'repair') {
                $this->assertTransactionalTables(['house_new_repair_works_order', 'house_new_repair_works_order_log']);
                if ((new \app\common\model\service\config\ConfigCustomizationService())->getWSXCustomizedOpen()) {
                    $this->assertTransactionalTables(['house_community_work_order_config', 'house_community_work_order_work_record']);
                }
                $resident = $this->resident($session['uid'], $session['village_id'], $session['bind_id']);
                $meta = CommunityAgentRepairPolicy::metadata($session, $resident);
                $form = CommunityAgentRepairPolicy::normalize(json_decode($session['payload'], true)['form']);
                $category = CommunityAgentRepairPolicy::validateSelection($form, $meta);
                if (CommunityAgentRepairPolicy::missing($form, $meta['image_required'])) { throw new \InvalidArgumentException('请补齐工单分类、位置、描述及必填图片'); }
                if ($meta['blocking_reason']) { throw new \InvalidArgumentException($meta['blocking_reason']); }
                $repairService = new ResidentRepairSubmissionService(true);
                $result = $repairService->create(array_merge($form, [
                    'village_id'=>$session['village_id'], 'pigcms_id'=>$session['bind_id'], 'category_id'=>$category['parent_id'],
                    'cat_fid'=>$category['parent_id'],
                ]));
                $session['application_id'] = $result['order_id'];
                $session['state'] = 'submitted';
                return;
            }
            if ($session['state'] !== 'ready' || $session['intent'] !== 'express_pickup') {
                throw new \InvalidArgumentException('请先确认需求并补齐申请信息');
            }
            $this->assertTransactionalTables(['express_application']);
            $form = CommunityAgentPickupPolicy::normalize(json_decode($session['payload'], true)['form']);
            if (CommunityAgentPickupPolicy::missing($form)) { throw new \InvalidArgumentException('申请信息不完整'); }
            // Serialize submissions from this entry per village; quotas rechecked under lock.
            $village = Db::name('house_village')->where('village_id', $session['village_id'])->lock(true)->find();
            if (!$village) { throw new \InvalidArgumentException('小区不存在'); }
            $resident = $this->resident($session['uid'], $session['village_id'], $session['bind_id']);
            $location = $this->location($session['village_id'], $form['location_id']);
            if (!$location) { throw new \InvalidArgumentException('取件地址已停用，请重新选择'); }
            $this->eligibility($session, $resident, $village);
            $room = $resident['room'];
            $recipient = Db::name('common_pickup_person')->where([
                'user_id'=>$session['uid'], 'village_id'=>$session['village_id'], 'status'=>1,
                'recipient_name'=>$form['recipient_name'], 'recipient_phone'=>$form['recipient_phone'],
            ])->order('id desc')->find();
            // Reuse the existing application store. Do not silently change default contacts.
            $application = array_merge($form, [
                'user_id'=>$session['uid'], 'village_id'=>$session['village_id'], 'status'=>1,
                'recipient_id'=>$recipient['id'] ?? 0,
                'single_id'=>$room['single_id'], 'floor_id'=>$room['floor_id'], 'layer_id'=>$room['layer_id'],
                'room_id'=>$room['pigcms_id'],
                'room_name'=>(new HouseVillageService())->getSingleFloorRoom(
                    $room['single_id'], $room['floor_id'], $room['layer_id'], $room['pigcms_id'], $session['village_id']
                ),
                'create_by'=>$resident['bind']['name'], 'create_time'=>date('Y-m-d H:i:s'),
            ]);
            $applicationId = (new ExpressPickupService())->addPickupApplication($application);
            if (!$applicationId) { throw new \RuntimeException('创建申请失败'); }
            $session['application_id'] = $applicationId;
            $session['state'] = 'submitted';
        });
        if ($repairService) { $repairService->flushNotifications(); }
        if ($venueService) { $venueService->flushAgentNotice(); }
        return $response;
    }

    public function back($uid, $id, $version, array $form = [])
    {
        return $this->mutate($uid,$id,function (&$session) use ($version,$form) {
            CommunityAgentPickupPolicy::requireVersion($session,$version);
            CommunityAgentPickupPolicy::requireEditable($session);
            $payload=json_decode($session['payload'],true);
            // Shortcuts never had a recognition/confirmation step to return to.
            // End the draft so the client returns to its input/shortcut panel.
            if (($payload['recognition']['code'] ?? '') === 'shortcut') {
                $session['state']='cancelled';
                $session['payload']='{}';
                return;
            }
            if (in_array($session['state'],['editing','ready'],true)) {
                if ($form) {
                    if (in_array($session['intent'], ['service_booking','activity_signup'], true) && (($form['booking_kind'] ?? '') === 'activity') !== (($payload['form']['booking_kind'] ?? '') === 'activity')) { throw new \InvalidArgumentException('请从对应入口办理'); }
                    $payload['form']=$session['intent']==='bill_payment' ? CommunityAgentBillService::normalize($form) : (in_array($session['intent'], ['service_booking','activity_signup'], true) ? CommunityAgentBookingService::normalize($form)
                        : ($session['intent']==='repair' ? CommunityAgentRepairPolicy::normalize($form) : CommunityAgentPickupPolicy::normalize($form)));
                }
                $payload['drafts'][$session['intent']]=$payload['form'];
                $session['state']=$payload['return_state'] ?? 'choosing_intent';
                $session['intent']='';
            } elseif ($session['state']==='choosing_intent' && $session['suggested_intent']!=='unknown' && !isset($payload['decision']) && empty($payload['manual_navigation'])) {
                $session['state']='awaiting_intent';
            } else { $session['state']='cancelled'; }
            $session['payload']=$this->encode($payload);
        });
    }

    public function cancel($uid, $id, $version)
    {
        return $this->mutate($uid, $id, function (&$session) use ($version) {
            CommunityAgentPickupPolicy::requireVersion($session, $version);
            CommunityAgentPickupPolicy::requireEditable($session);
            $session['state'] = 'cancelled';
            $session['payload'] = '{}';
        });
    }

    private function mutate($uid, $id, callable $change)
    {
        $merchantSession = null;
        $response = Db::transaction(function () use ($uid, $id, $change, &$merchantSession) {
            $session = $this->session($uid, $id, true);
            $this->resident($uid, $session['village_id'], $session['bind_id']);
            if ($change($session) !== false) {
                $session['version']++;
                $session['updated_at'] = time();
                Db::name('community_agent_session')->where('id', $id)->update($session);
            }
            $payload = json_decode($session['payload'], true);
            if ($session['intent'] === 'service_booking' && ($payload['form']['booking_kind'] ?? '') === 'merchant') {
                // CMS reads this session using another connection. Publish the intent,
                // proof and version before asking it to render the booking metadata.
                $merchantSession = $session;
                return null;
            }
            return $this->view($session);
        });
        return $merchantSession !== null ? $this->view($merchantSession) : $response;
    }

    private function session($uid, $id, $lock = false)
    {
        if (!preg_match('/^[a-f0-9]{32}$/D', $id)) { throw new \InvalidArgumentException('办理记录不存在'); }
        $session = Db::name('community_agent_session')->where(['id'=>$id, 'uid'=>$uid])->lock($lock)->find();
        if (!$session) { throw new \InvalidArgumentException('办理记录不存在'); }
        return $session;
    }

    private function resident($uid, $villageId, $bindId)
    {
        // pigcms_id is a resident binding, not a room ID; never trust posted uid.
        $bind = Db::name('house_village_user_bind')->where([
            'pigcms_id'=>$bindId, 'village_id'=>$villageId, 'uid'=>$uid, 'status'=>1,
        ])->whereIn('type', [0, 1, 2, 3])->find();
        if (!$bind) { throw new \InvalidArgumentException('无有效住户身份，请先绑定自己的房屋'); }
        $room = Db::name('house_village_user_vacancy')->where([
            'pigcms_id'=>$bind['vacancy_id'], 'village_id'=>$villageId,
        ])->find();
        if (!$room) { throw new \InvalidArgumentException('绑定房屋不存在'); }
        return ['bind'=>$bind, 'room'=>$room];
    }

    private function location($villageId, $locationId)
    {
        return Db::name('express_pick_up_location')->where([
            'id'=>$locationId, 'village_id'=>$villageId, 'status'=>1,
        ])->field('id,location')->find();
    }

    private function eligibility(array $session, array $resident, array $village)
    {
        $room = $resident['room'];
        $household = Db::name('house_village_user_bind')->where([
            'village_id'=>$session['village_id'], 'single_id'=>$room['single_id'],
            'floor_id'=>$room['floor_id'], 'layer_id'=>$room['layer_id'],
            'room_addrss'=>$room['room'], 'status'=>1,
        ])->column('authentication_field');
        $signed = false;
        foreach ($household as $raw) {
            $fields = $raw ? @unserialize($raw, ['allowed_classes'=>false]) : [];
            if (is_array($fields) && ($fields['is_sign_agreement']['value'] ?? '') === '是') { $signed = true; break; }
        }
        if (!$signed) { throw new \InvalidArgumentException('请先到物业签署快递代取免责说明，签署后方可提供该服务'); }
        if (CommunityAgentPickupPolicy::blockedTime(
            $village['express_project_startDate'], $village['express_project_endDate'], date('H:i')
        )) { throw new \InvalidArgumentException('当前时段不提供快递代取申请，请在服务时间内办理'); }
        $query = Db::name('express_application')->where('village_id', $session['village_id'])
            ->whereBetween('create_time', [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')]);
        $projectCount = (clone $query)->count();
        $houseCount = $query->where('room_id', $room['pigcms_id'])->count();
        CommunityAgentPickupPolicy::assertQuota(
            (int)$village['express_person_average_entrust'], (int)$village['express_project_daily_entrust'],
            $houseCount, $projectCount
        );
    }

    private function view(array $session)
    {
        $payload = json_decode($session['payload'], true);
        $expired = $session['expires_at'] <= time() && !in_array($session['state'], ['submitted', 'cancelled', 'submitting'], true);
        $state = $expired ? 'expired' : $session['state'];
        $actions = [
            'awaiting_intent'=>'confirm_intent', 'choosing_intent'=>'select_intent',
            'editing'=>'open_form', 'ready'=>'open_form', 'submitted'=>'show_result',
            'cancelled'=>'close_modal', 'expired'=>'restart', 'submitting'=>'query_result',
        ];
        $labels = array_column(CommunityAgentPickupPolicy::options(), 'label', 'value');
        $response = [
            'session_id'=>$session['id'], 'version'=>(int)$session['version'], 'state'=>$state,
            'ui_action'=>$actions[$state], 'intent'=>$session['intent'],
            'suggested_intent'=>$session['suggested_intent'], 'options'=>CommunityAgentPickupPolicy::options(),
            'description'=>in_array($state,['submitted','cancelled','expired'],true) ? '' : ($payload['description'] ?? ''),
            'expires_at'=>(int)$session['expires_at'],
            'message'=>$state === 'awaiting_intent' ? '当前需求为'.($labels[$session['suggested_intent']] ?? '').'，是否选择？' : '',
        ];
        if (($response['intent'] ?? '') === 'service_booking' && ($payload['form']['booking_kind'] ?? '') === 'activity') { $response['intent'] = 'activity_signup'; }
        if (($response['suggested_intent'] ?? '') === 'service_booking' && ($payload['booking_kind'] ?? '') === 'activity') { $response['suggested_intent'] = 'activity_signup'; }
        $response['recognition'] = $payload['recognition'] ?? [
            'status'=>'unknown', 'code'=>'diagnostics_unavailable', 'message'=>'该历史会话未记录识别原因',
            'retryable'=>false, 'http_status'=>0,
        ];
        foreach (['decision','operation','decision_reason','decision_message'] as $field) {
            if (isset($payload[$field])) { $response[$field] = $payload[$field]; }
        }
        if ($state === 'choosing_intent') {
            $response['message'] = $response['recognition']['status'] === 'recognized'
                ? '请手动选择需要办理的事项' : $response['recognition']['message'];
        }
        if (in_array($state, ['editing','ready'],true) && $session['intent']==='bill_payment') {
            $response=array_merge($response,CommunityAgentBillService::metadata($session),$payload['bill_quote'] ?? []);
            $response['form']=$payload['form']; $response['can_submit']=$state==='ready' && !empty($payload['bill_quote']);
        } elseif (in_array($state, ['editing','ready'],true) && in_array($session['intent'], ['service_booking','activity_signup'], true)) {
            try {
                $meta=CommunityAgentBookingService::metadata($session,$payload['form']);
            } catch (\RuntimeException | \InvalidArgumentException $error) {
                if (($payload['form']['booking_kind'] ?? '') !== 'merchant') { throw $error; }
                // Preserve the committed session/version so a read-only retry can recover.
                $meta=['booking_items'=>[], 'booking_fields'=>[], 'missing_fields'=>['item_id'],
                    'blocking_reason'=>'预约服务加载失败，请重新加载；持续失败请联系管理员检查预约服务配置',
                    'booking_load_failed'=>true, 'quote'=>'', 'price_label'=>''];
                if ($error instanceof \InvalidArgumentException && !empty($payload['form']['item_id'])) {
                    // Availability can change after matching. Keep the current draft/version
                    // and expose the latest choices instead of losing the entire start response.
                    try {
                        $empty=CommunityAgentBookingService::normalize(['booking_kind'=>'merchant']);
                        $meta=CommunityAgentBookingService::metadata($session,$empty);
                        $meta['blocking_reason']='预约选项已变化，请重新选择项目并核对信息';
                        $meta['missing_fields']=['item_id'];
                        $meta['quote']='';
                    } catch (\RuntimeException | \InvalidArgumentException $ignored) { /* Retain retryable load failure. */ }
                }
            }
            unset($meta['_custom_schema'],$meta['_platform']);
            $response=array_merge($response,$meta); $response['form']=$payload['form'];
            $response['can_submit']=$state==='ready' && !$meta['missing_fields'] && !$meta['blocking_reason'];
        } elseif (in_array($state, ['editing', 'ready'], true) && $session['intent'] === 'repair') {
            $resident = $this->resident($session['uid'], $session['village_id'], $session['bind_id']);
            $meta = CommunityAgentRepairPolicy::metadata($session, $resident);
            $response = array_merge($response, $meta);
            $response['form'] = $payload['form'];
            $response['missing_fields'] = CommunityAgentRepairPolicy::missing($payload['form'], $meta['image_required']);
            try { CommunityAgentRepairPolicy::validateSelection($payload['form'], $meta); }
            catch (\InvalidArgumentException $e) { $response['blocking_reason'] = $e->getMessage(); }
            $response['can_submit'] = $state === 'ready' && !$response['missing_fields'] && !$response['blocking_reason'];
        } elseif (in_array($state, ['editing', 'ready'], true)) {
            $response['form'] = $payload['form'];
            $response['fields'] = CommunityAgentPickupPolicy::fields();
            $response['missing_fields'] = CommunityAgentPickupPolicy::missing($payload['form']);
            $response['locations'] = Db::name('express_pick_up_location')->where([
                'village_id'=>$session['village_id'], 'status'=>1,
            ])->field('id,location')->order('id desc')->select()->toArray();
            $response['blocking_reason'] = '';
            try {
                $resident = $this->resident($session['uid'], $session['village_id'], $session['bind_id']);
                $village = Db::name('house_village')->where('village_id', $session['village_id'])->find();
                if (!$village) { throw new \InvalidArgumentException('小区不存在'); }
                $this->eligibility($session, $resident, $village);
                if (!$response['locations']) { throw new \InvalidArgumentException('小区暂未配置取件地址'); }
            } catch (\InvalidArgumentException $e) { $response['blocking_reason'] = $e->getMessage(); }
            $response['can_submit'] = $state === 'ready' && $response['blocking_reason'] === '';
        }
        if ($state === 'submitted') {
            $response['application_id'] = (int)$session['application_id'];
            if ($session['intent']==='bill_payment') { return array_merge($response,CommunityAgentBillService::result($session)); }
            if (in_array($session['intent'], ['service_booking','activity_signup'], true)) { return array_merge($response,CommunityAgentBookingService::result($session)); }
            if ($session['intent'] === 'repair') {
                $response['order_id'] = (int)$session['application_id'];
                $response['application_status'] = Db::name('house_new_repair_works_order')->where([
                    'order_id'=>$session['application_id'],'uid'=>$session['uid'],'village_id'=>$session['village_id'],
                ])->value('event_status');
                $response['message'] = '工单已提交';
                return $response;
            }
            $response['application_status'] = Db::name('express_application')->where([
                'id'=>$session['application_id'], 'user_id'=>$session['uid'], 'village_id'=>$session['village_id'],
            ])->value('status');
            $response['message'] = '代取快递申请已提交';
        }
        return $response;
    }

    private function submitMerchantBooking($uid,$id,$version,$confirmed)
    {
        if ($confirmed!==true) { throw new \InvalidArgumentException('请确认提交预约'); }
        // Persist the in-flight state before the CMS HTTP call. It cannot be edited or cancelled on timeout.
        $session=Db::transaction(function() use($uid,$id,$version) {
            $s=$this->session($uid,$id,true); $this->resident($uid,$s['village_id'],$s['bind_id']);
            if ($s['state']==='submitted') {
                if ((int)$version!==(int)$s['version']-1) { throw new \InvalidArgumentException('提交版本不一致，请查询结果'); }
                return $s;
            }
            CommunityAgentPickupPolicy::requireVersion($s,$version);
            if ($s['state']!=='submitting') {
                CommunityAgentPickupPolicy::requireEditable($s);
                if ($s['state']!=='ready') { throw new \InvalidArgumentException('请先补齐预约信息'); }
                $s['state']='submitting'; $s['updated_at']=time();
                Db::name('community_agent_session')->where('id',$id)->update($s);
            }
            return $s;
        });
        if ($session['state']==='submitted') { return $this->view($session); }
        try { $result=CommunityAgentBookingService::bridge($session,'submit'); }
        catch (\InvalidArgumentException $e) {
            // A structured validation rejection means the remote transaction rolled back.
            Db::name('community_agent_session')->where(['id'=>$id,'state'=>'submitting','version'=>$version])->update(['state'=>'editing']);
            throw $e;
        }
        return $this->finishMerchantBooking($session,$result);
    }

    private function recoverMerchantBooking(array $session)
    {
        return $this->finishMerchantBooking($session,CommunityAgentBookingService::bridge($session,'result'));
    }

    private function finishMerchantBooking(array $session,array $result)
    {
        if (!empty($result['pending'])) { return $this->view($session); }
        if (empty($result['application_id'])) { throw new \RuntimeException('预约回执格式异常'); }
        Db::name('community_agent_session')->where(['id'=>$session['id'],'state'=>'submitting','version'=>$session['version']])->update([
            'state'=>'submitted','application_id'=>$result['application_id'],'version'=>$session['version']+1,'updated_at'=>time(),
        ]);
        return $this->view($this->session($session['uid'],$session['id']));
    }

    private function encode(array $value)
    {
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE);
        if ($encoded !== false && strlen($encoded)>60000) { throw new \InvalidArgumentException('填写内容过长，请适当精简'); }
        if ($encoded === false) { throw new \InvalidArgumentException('申请内容编码错误'); }
        return $encoded;
    }

    private function assertTransactionalTables(array $names = ['community_agent_session', 'house_village'])
    {
        foreach ($names as $name) {
            $table = Db::name($name)->getTable();
            $row = Db::query('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', [$table]);
            if (!$row || strtoupper($row[0]['ENGINE']) !== 'INNODB') {
                throw new \InvalidArgumentException('智能办事尚未完成数据库初始化，请联系管理员');
            }
        }
    }
}
