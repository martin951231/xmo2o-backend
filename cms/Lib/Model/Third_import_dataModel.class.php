<?php


class Third_import_dataModel extends Model{
    /** @var int 房产表导入数据类型 */
    const THIRD_YWYL_HOUSE_DATA      = 1;
    /** @var int 业主表导入数据类型 */
    const THIRD_YWYL_USER_DATA       = 2;
    /** @var int 收费表导入数据类型 */
    const THIRD_YWYL_CHARGE_DATA       = 3;

    /*** 出售状态 1正常居住 2出售中 3空关（未入住） 4停用中 5 出售中 **/
    /** @var int 正常居住 */
    const USER_STATUS_LIVE           = 1;
    /** @var int 空关（未入住） */
    const USER_STATUS_NOT_LIVE       = 2;
    /** @var int 出租中 */
    const USER_STATUS_LEASE          = 3;
    /** @var int 停用中 */
    const USER_STATUS_STOP           = 4;
    /** @var int 出售中 */
    const USER_STATUS_SELL           = 5;
    /** @var int 空置 */
    const USER_STATUS_VACANT         = 6;
    /** @var int 装修 */
    const USER_STATUS_RENOVATION     = 7;
    /** @var int 未收房 */
    const USER_STATUS_UNCHECKED_ROOM = 8;

    // 出售 空关 入住 出租 装修 停用 空置 未收房
    /***房产状态 1出售 2空关（未入住） 3入住  4空置 5出租 6停用 7装修 8未收房  **/
    /** @var int 房产状态 1出租 */
    const HOUSE_STATUS_SELL           = 1;
    /** @var int 房产状态 2空关（未入住） */
    const HOUSE_STATUS_NOT_LIVE       = 2;
    /** @var int 房产状态 3入住 */
    const HOUSE_STATUS_LIVE           = 3;
    /** @var int 房产状态 4空置 */
    const HOUSE_STATUS_VACANT         = 4;
    /** @var int 房产状态 5出租 */
    const HOUSE_STATUS_LEASE          = 5;
    /** @var int 房产状态 6停用 */
    const HOUSE_STATUS_STOP           = 6;
    /** @var int 房产状态 7装修 */
    const HOUSE_STATUS_RENOVATION     = 7;
    /** @var int 房产状态 8未收房 */
    const HOUSE_STATUS_UNCHECKED_ROOM = 8;

    /***
     * 房产类型
     *  0未设置 1多层 2小高层 3高层 4别墅 5排屋
     *  6储藏室 7自行车库 8写字楼 9商铺 10商场
     *  11会所 12办公用房 13保姆房 14酒店 15其它
     *  16车库 17车位 18广告位 *
     */
    /** @var int 房产类型 0未设置 */
    const HOUSE_TYPE_NOT_SET           = 0;
    /** @var int 房产类型 1多层  */
    const HOUSE_TYPE_MULTI_STOREY      = 1;
    /** @var int 房产类型 2小高层  */
    const HOUSE_TYPE_SMALL_HEIGHT_RISE = 2;
    /** @var int 房产类型 3高层  */
    const HOUSE_TYPE_HEIGHT_RISE       = 3;
    /** @var int 房产类型 4别墅  */
    const HOUSE_TYPE_VILLA             = 4;
    /** @var int 房产类型 5排屋  */
    const HOUSE_TYPE_TOWNHOUSE         = 5;
    /** @var int 房产类型 6储藏室  */
    const HOUSE_TYPE_STOREROOM         = 6;
    /** @var int 房产类型 7自行车库  */
    const HOUSE_TYPE_BICYCLE_PARKING   = 7;
    /** @var int 房产类型 8写字楼  */
    const HOUSE_TYPE_OFFICE_BUILDING   = 8;
    /** @var int 房产类型 9商铺  */
    const HOUSE_TYPE_SHOPS             = 9;
    /** @var int 房产类型 10商场  */
    const HOUSE_TYPE_MARKET            = 10;
    /** @var int 房产类型 11会所  */
    const HOUSE_TYPE_CLUB              = 11;
    /** @var int 房产类型 12办公用房  */
    const HOUSE_TYPE_OFFICE_SPACE      = 12;
    /** @var int 房产类型 13保姆房  */
    const HOUSE_TYPE_NANNY_ROOM        = 13;
    /** @var int 房产类型 14酒店  */
    const HOUSE_TYPE_HOTEL             = 14;
    /** @var int 房产类型 15其它  */
    const HOUSE_TYPE_OTHER             = 15;
    /** @var int 房产类型 16车库  */
    const HOUSE_TYPE_GARAGE            = 16;
    /** @var int 房产类型 17车位  */
    const HOUSE_TYPE_PARKING_LOT       = 17;
    /** @var int 房产类型 18广告位  */
    const HOUSE_TYPE_ADSENSE           = 18;

    /***
     * 房产性质
     * 1商品房 2经济适用房 3房改房
     */
    /** @var int 房产类型 1商品房 */
    const HOUSE_TYPE_COMMERCIAL_HOUSING = 1;
    /** @var int 房产类型 2经济适用房 */
    const HOUSE_TYPE_AFFORDABLE_HOUSING = 2;
    /** @var int 房产类型 3房改房 */
    const HOUSE_TYPE_HOUSING_REFORM     = 3;


    /*** handle_status 处理状态 ***/
    /** @var int 未处理 */
    const HANDLE_STATUS_NOT        = 0;
    /** @var int 处理中 */
    const HANDLE_STATUS_PROCESSING = 1;
    /** @var int 处理成功 */
    const HANDLE_STATUS_SUCCESS    = 2;
    /** @var int 处理失败 */
    const HANDLE_STATUS_FAIL       = 3;
    /** @var int 删除 */
    const HANDLE_STATUS_DELETE     = 4;


    /**** member_relation 与户主关系 ***/
    /** @var int 业主 */
    const HOUSEHOLDER_RELATIONSHIP_OWNER    = 0;
    /** @var int 家属 */
    const HOUSEHOLDER_RELATIONSHIP_FAMILY   = 1;
    /** @var int 租客 */
    const HOUSEHOLDER_RELATIONSHIP_TENANT   = 2;
    /** @var int 配偶 */
    const HOUSEHOLDER_RELATIONSHIP_SPOUSE   = 11;
    /** @var int 父母 */
    const HOUSEHOLDER_RELATIONSHIP_PARENT   = 12;
    /** @var int 子女 */
    const HOUSEHOLDER_RELATIONSHIP_CHILDREN = 13;
    /** @var int 亲朋好友 */
    const HOUSEHOLDER_RELATIONSHIP_FRIENDS  = 15;

    /**** member_sex 性别 ***/
    /** @var int 未知 */
    const MEMBER_SEX_UNKNOWN = 0;
    /** @var int 男 */
    const MEMBER_SEX_MALE    = 1;
    /** @var int 女 */
    const MEMBER_SEX_FEMALE  = 2;


    /**** member_card_type 证件类型 ***/
    /**
     * 居民身份证、临时身份证、户口簿、居民身份证（台）
     * 军官证、警官证、士兵证、军事学院证、军官退休证、文职干部证、文职干部退休证、离休干部荣誉证、武警警官证、军队学员证、军队文职干部证、军队离退休干部证和军队职工证
     * 护照、港澳台同胞来往通行证、港澳同胞回乡证、港澳居民来往内地通行证、中华人民共和国来往港澳通行证、台湾居民来往大陆通行证、大陆居民往来台湾通行证、
     * 边民出入境通行证、外国人永久居留证、外国人居留证、外国人出入境证、外交官证、领事馆证、
     * 海员证
     * 其他
     */
    /** @var int 无 */
    const CARD_TYPE_NOTHING                                                           = 0;
    /** @var int 居民身份证 */
    const CARD_TYPE_RESIDENT_ID_CARD                                                  = 1;
    /** @var int 临时身份证 */
    const CARD_TYPE_TEMPORARY_ID_CARD                                                 = 2;
    /** @var int 户口簿 */
    const CARD_TYPE_RESIDENCE_BOOKLET                                                 = 3;
    /** @var int 居民身份证（台） */
    const CARD_TYPE_RESIDENT_ID_CARD_TAIWAN                                           = 4;
    /** @var int 军官证 */
    const CARD_TYPE_CERTIFICATE_OF_OFFICERS                                           = 5;
    /** @var int 警官证 */
    const CARD_TYPE_POLICE_OFFICER_CERTIFICAT                                         = 6;
    /** @var int 士兵证 */
    const CARD_TYPE_SOLDIER_ID                                                        = 7;
    /** @var int 军事学院证 */
    const CARD_TYPE_MILITARY_ACADEMY_CERTIFICATE                                      = 8;
    /** @var int 军官退休证 */
    const CARD_TYPE_OFFICER_RETIREMENT_CERTIFICATE                                    = 9;
    /** @var int 文职干部证 */
    const CARD_TYPE_CIVILIAN_CADRE_CERTIFICATE                                        = 10;
    /** @var int 文职干部退休证 */
    const CARD_TYPE_RETIREMENT_CERTIFICATE_FOR_CIVILIAN_CADRES                        = 11;
    /** @var int 离休干部荣誉证 */
    const CARD_TYPE_HONORARY_CERTIFICATE_FOR_RETIRED_CADRES                           = 12;
    /** @var int 武警警官证 */
    const CARD_TYPE_ARMED_POLICE_OFFICER_CERTIFICATE                                  = 13;
    /** @var int 军队学员证 */
    const CARD_TYPE_MILITARY_STUDENT_CERTIFICATE                                      = 14;
    /** @var int 军队文职干部证 */
    const CARD_TYPE_MILITARY_CIVILIAN_CADRE_CERTIFICATE                               = 15;
    /** @var int 军队离退休干部证和军队职工证 */
    const CARD_TYPE_MILITARY_RETIRED_CADRE_CERTIFICATE_AND_MILITARY_STAFF_CERTIFICATE = 16;
    /** @var int 护照 */
    const CARD_TYPE_PASSPORT                                                          = 17;
    /** @var int 港澳台同胞来往通行证 */
    const CARD_TYPE_TRAVEL_PERMIT_FOR_COMPATRIOTS_FROM_HONG_KONG_MACAO_AND_TAIWAN     = 18;
    /** @var int 港澳同胞回乡证 */
    const CARD_TYPE_HOME_VISITING_CERTIFICATE_FOR_HONG_KONG_AND_MACAO_COMPATRIOTS     = 19;
    /** @var int 港澳居民来往内地通行证 */
    const CARD_TYPE_MAINLAND_TRAVEL_PERMIT_FOR_HONG_KONG_AND_MACAO_RESIDENTS          = 20;
    /** @var int 中华人民共和国来往港澳通行证 */
    const CARD_TYPE_PERMIT_OF_THE_PEOPLES_REPUBLIC_OF_CHINA_TO_HONG_KONG_AND_MACAO    = 21;
    /** @var int 台湾居民来往大陆通行证 */
    const CARD_TYPE_TRAVEL_PASSES_FOR_TAIWAN_RESIDENTS_TO_ENTER_OR_LEAVE_THE_MAINLAND = 22;
    /** @var int 大陆居民往来台湾通行证 */
    const CARD_TYPE_MAINLAND_RESIDENTS_TRAVEL_PERMIT_TO_TAIWAN                        = 23;
    /** @var int 边民出入境通行证 */
    const CARD_TYPE_FRONTIER_EXIT_ENTRY_PERMIT                                        = 24;
    /** @var int 外国人永久居留证 */
    const CARD_TYPE_FOREIGNERS_PERMANENT_RESIDENCE_PERMIT                             = 25;
    /** @var int 外国人居留证 */
    const CARD_TYPE_ALIEN_RESIDENCE_PERMIT                                            = 26;
    /** @var int 外国人出入境证 */
    const CARD_TYPE_ALIEN_EXIT_ENTRY_PERMIT                                           = 27;
    /** @var int 外交官证 */
    const CARD_TYPE_DIPLOMATIC_CERTIFICATE                                            = 28;
    /** @var int 领事馆证 */
    const CARD_TYPE_CONSULATE_CARD                                                    = 29;
    /** @var int 海员证 */
    const CARD_TYPE_SEAMANS_CARD                                                      = 30;
    /** @var int 其他 */
    const CARD_TYPE_OTHER                                                             = 31;
    
    /** @var string 三方EXCEL业主信息导入 */
    const THIRD_YWYL_OWNER_EXCEL = 'third_ywyl_owner_excel';
    
    
    
    
    
    // 获取房间关联导入信息
    public function getThirdImportRoomInfo($village_id, $room_id) {
        if (!$village_id || !$room_id) {
            return [];
        }
        $where = [];
        $where['village_id'] = $village_id;
        $where['room_id']    = $room_id;
        $where['cut_type']   ='room';
        $info = M('HouseVillageThirdImportRoom')->where($where)->order('bind_id DESC')->find();
        if ($info && !is_array($info)) {
            $info = $info->toArray();
        }
        if (empty($info)) {
            return [];
        }
        $showArr = [];
        if (isset($info['house_status'])) {
            $house_status_txt = $this->getHouseStatusTxt($info['house_status']);
            if ($house_status_txt) {
                $showArr[] = [
                    'title' => '房产状态',
                    'value' => $house_status_txt,
                ];
            } else {
                $showArr[] = [
                    'title' => '房产状态',
                    'value' => '无',
                ];
            }
        }
        if (isset($info['house_type'])) {
            $house_type_txt = $this->getHouseTypeTxt($info['house_type']);
            if ($house_type_txt) {
                $showArr[] = [
                    'title' => '房产类型',
                    'value' => $house_type_txt,
                ];
            } else {
                $showArr[] = [
                    'title' => '房产类型',
                    'value' => '无',
                ];
            }
        }
        if (isset($info['house_nature'])) {
            $house_nature_txt = $this->getHouseNatureTxt($info['house_nature']);
            if ($house_nature_txt) {
                $showArr[] = [
                    'title' => '房产性质',
                    'value' => $house_nature_txt,
                ];
            } else {
                $showArr[] = [
                    'title' => '房产性质',
                    'value' => '无',
                ];
            }
        }
        if (isset($info['cut_table']) && isset($info['cut_id']) && $info['cut_id'] && $info['cut_table'] == 'house_village_third_import_data') {
            $whereThirdImportData = [];
            $whereThirdImportData['id'] = $info['cut_id'];
            $cutInfo = M('HouseVillageThirdImportData')->where($whereThirdImportData)->find();
            if ($cutInfo && !is_array($cutInfo)) {
                $cutInfo = $cutInfo->toArray();
            }
            if (isset($cutInfo['third_main_value1'])) {
                $showArr[] = [
                    'title' => '建筑面积',
                    'value' => $cutInfo['third_main_value1'] ? $cutInfo['third_main_value1'] : 0,
                ];
            }
            if (isset($cutInfo['third_main_value3'])) {
                $showArr[] = [
                    'title' => '辅助计费面积',
                    'value' => $cutInfo['third_main_value3'] ? $cutInfo['third_main_value3'] : 0,
                ];
            }
            if (isset($cutInfo['third_remark'])) {
                $showArr[] = [
                    'title' => '备注',
                    'value' => $cutInfo['third_remark'] ? $cutInfo['third_remark'] : '无',
                ];
            }
        }
        if (isset($info['third_key'])) {
            $showArr[] = [
                'title' => '关键字',
                'value' => $info['third_key'] ? $info['third_key'] : '无',
            ];
        }
        $arr = [];
        $arr['showArr'] = $showArr;
        return $arr;
    }

    // 获取住户关联导入信息
    public function getThirdImportUserInfo($village_id, $bind_id) {
        if (!$village_id || !$bind_id) {
            return [];
        }
        $where = [];
        $where['village_id'] = $village_id;
        $where['relation_bind_id'] = $bind_id;
        $where['from_type'] = self::THIRD_YWYL_OWNER_EXCEL;
        $info = M('HouseVillageUserBindRelation')->where($where)->order('update_time DESC, add_time DESC')->find();
        if ($info && !is_array($info)) {
            $info = $info->toArray();
        }
        if (empty($info)) {
            return [];
        }
        $showArr = [];
        if (isset($info['sex'])) {
            $member_sex = $this->getMemberSexTxt($info['sex']);
            if ($member_sex) {
                $showArr[] = [
                    'title' => '性別',
                    'value' => $member_sex,
                ];
            }
        }
        if (isset($info['nation']) && $info['nation']) {
            $showArr[] = [
                'title' => '民族',
                'value' => $info['nation'],
            ];
        }
        if (isset($info['special_identity']) && $info['special_identity']) {
            $showArr[] = [
                'title' => '特殊身份',
                'value' => $info['special_identity'],
            ];
        }
        if (isset($info['native_place']) && $info['native_place']) {
            $showArr[] = [
                'title' => '籍贯',
                'value' => $info['native_place'],
            ];
        }
        if (isset($info['birthday']) && $info['birthday']) {
            $showArr[] = [
                'title' => '生日',
                'value' => $info['birthday'],
            ];
        }
        if (isset($info['marital_status']) && $info['marital_status']) {
            $showArr[] = [
                'title' => '成员婚姻状况',
                'value' => $info['marital_status'],
            ];
        }
        $card_type_txt = $this->getCardTypeTxt($info['card_type']);
        if ($info['card'] && $card_type_txt) {
            $showArr[] = [
                'title' => '证件类型',
                'value' => $card_type_txt,
            ];
            $showArr[] = [
                'title' => '证件号码',
                'value' => $info['card'],
            ];
        }
        if (isset($info['education_level']) && $info['education_level']) {
            $showArr[] = [
                'title' => '教育程度',
                'value' => $info['education_level'],
            ];
        }
        if (isset($info['nationality']) && $info['nationality']) {
            $showArr[] = [
                'title' => '国籍',
                'value' => $info['nationality'],
            ];
        }
        if (isset($info['registered_residence']) && $info['registered_residence']) {
            $showArr[] = [
                'title' => '户口所在地',
                'value' => $info['registered_residence'],
            ];
        }
        if (isset($info['province']) && $info['province']) {
            $showArr[] = [
                'title' => '省份',
                'value' => $info['province'],
            ];
        }
        if (isset($info['city']) && $info['city']) {
            $showArr[] = [
                'title' => '城市',
                'value' => $info['city'],
            ];
        }
        if (isset($info['settlement_city']) && $info['settlement_city']) {
            $showArr[] = [
                'title' => '定居城市',
                'value' => $info['settlement_city'],
            ];
        }
        if (isset($info['resident_type']) && $info['resident_type']) {
            $showArr[] = [
                'title' => '常驻类型',
                'value' => $info['resident_type'],
            ];
        }
        if (isset($info['home_address']) && $info['home_address']) {
            $showArr[] = [
                'title' => '家庭住址',
                'value' => $info['home_address'],
            ];
        }
        if (isset($info['emergency_contact']) && $info['emergency_contact']) {
            $showArr[] = [
                'title' => '紧急联系人',
                'value' => $info['emergency_contact'],
            ];
        }
        if (isset($info['emergency_phone']) && $info['emergency_phone']) {
            $showArr[] = [
                'title' => '紧急联系电话',
                'value' => $info['emergency_phone'],
            ];
        }
        $arr = [];
        $arr['showArr'] = $showArr;
        return $arr;
    }

    // 获取订单关联导入信息
    public function getThirdImportOrderInfo($village_id, $order_id) {
        if (!$village_id || !$order_id) {
            return [];
        }
        $where = [];
        $where['cut_table']  = 'house_village_third_import_data';
        $where['village_id'] = $village_id;
        $where['order_id']   =  $order_id;
        $info = M('HouseVillageThirdImportOrder')->where($where)->order('bind_id DESC')->find();
        if ($info && !is_array($info)) {
            $info = $info->toArray();
        }
        if (empty($info)) {
            return [];
        }
        $showArr = [];
        if (isset($info['charge_amount_on_account']) && $info['charge_amount_on_account']) {
            $showArr[] = [
                'title' => '挂账金额',
                'value' => $info['charge_amount_on_account'],
            ];
        } else {
            $showArr[] = [
                'title' => '挂账金额',
                'value' => 0,
            ];
        }
        if (isset($info['charge_amount_owed']) && $info['charge_amount_owed']) {
            $showArr[] = [
                'title' => '欠费金额',
                'value' => $info['charge_amount_owed'],
            ];
        } else {
            $showArr[] = [
                'title' => '欠费金额',
                'value' => 0,
            ];
        }
        if (isset($info['charge_received_amount']) && $info['charge_received_amount']) {
            $showArr[] = [
                'title' => '已收金额',
                'value' => $info['charge_received_amount'],
            ];
        } else {
            $showArr[] = [
                'title' => '已收金额',
                'value' => 0,
            ];
        }
        if (isset($info['charge_actual_receivable']) && $info['charge_actual_receivable']) {
            $showArr[] = [
                'title' => '实际应收金额',
                'value' => $info['charge_actual_receivable'],
            ];
        } else {
            $showArr[] = [
                'title' => '实际应收金额',
                'value' => 0,
            ];
        }
        if (isset($info['charge_amount_receivable']) && $info['charge_amount_receivable']) {
            $showArr[] = [
                'title' => '应收金额',
                'value' => $info['charge_amount_receivable'],
            ];
        } else {
            $showArr[] = [
                'title' => '应收金额',
                'value' => 0,
            ];
        }
        $arr = [];
        $arr['showArr'] = $showArr;
        return $arr;
    }



    /**
     * 获取  证件类型  对应描述
     * @param int $card_type
     * @return int
     */
    public function getCardTypeTxt(int $card_type) {
        $card_type_txt = '居民身份证';
        switch ($card_type) {
            case self::CARD_TYPE_RESIDENT_ID_CARD:
                $card_type_txt = '居民身份证';
                break;
            case self::CARD_TYPE_TEMPORARY_ID_CARD:
                $card_type_txt = '临时身份证';
                break;
            case self::CARD_TYPE_RESIDENCE_BOOKLET:
                $card_type_txt = '户口簿';
                break;
            case self::CARD_TYPE_RESIDENT_ID_CARD_TAIWAN:
                $card_type_txt = '居民身份证（台）';
                break;
            case self::CARD_TYPE_CERTIFICATE_OF_OFFICERS:
                $card_type_txt = '军官证';
                break;
            case self::CARD_TYPE_POLICE_OFFICER_CERTIFICAT:
                $card_type_txt = '警官证';
                break;
            case self::CARD_TYPE_SOLDIER_ID:
                $card_type_txt = '士兵证';
                break;
            case self::CARD_TYPE_MILITARY_ACADEMY_CERTIFICATE:
                $card_type_txt = '军事学院证';
                break;
            case self::CARD_TYPE_OFFICER_RETIREMENT_CERTIFICATE:
                $card_type_txt = '军官退休证';
                break;
            case self::CARD_TYPE_CIVILIAN_CADRE_CERTIFICATE:
                $card_type_txt = '文职干部证';
                break;
            case self::CARD_TYPE_RETIREMENT_CERTIFICATE_FOR_CIVILIAN_CADRES:
                $card_type_txt = '文职干部退休证';
                break;
            case self::CARD_TYPE_HONORARY_CERTIFICATE_FOR_RETIRED_CADRES:
                $card_type_txt = '离休干部荣誉证';
                break;
            case self::CARD_TYPE_ARMED_POLICE_OFFICER_CERTIFICATE:
                $card_type_txt = '武警警官证';
                break;
            case self::CARD_TYPE_MILITARY_STUDENT_CERTIFICATE:
                $card_type_txt = '军队学员证';
                break;
            case self::CARD_TYPE_MILITARY_CIVILIAN_CADRE_CERTIFICATE:
                $card_type_txt = '军队文职干部证';
                break;
            case self::CARD_TYPE_MILITARY_RETIRED_CADRE_CERTIFICATE_AND_MILITARY_STAFF_CERTIFICATE:
                $card_type_txt = '军队离退休干部证和军队职工证';
                break;
            case self::CARD_TYPE_PASSPORT:
                $card_type_txt = '护照';
                break;
            case self::CARD_TYPE_TRAVEL_PERMIT_FOR_COMPATRIOTS_FROM_HONG_KONG_MACAO_AND_TAIWAN:
                $card_type_txt = '港澳台同胞来往通行证';
                break;
            case self::CARD_TYPE_HOME_VISITING_CERTIFICATE_FOR_HONG_KONG_AND_MACAO_COMPATRIOTS:
                $card_type_txt = '港澳同胞回乡证';
                break;
            case self::CARD_TYPE_MAINLAND_TRAVEL_PERMIT_FOR_HONG_KONG_AND_MACAO_RESIDENTS:
                $card_type_txt = '港澳居民来往内地通行证';
                break;
            case self::CARD_TYPE_PERMIT_OF_THE_PEOPLES_REPUBLIC_OF_CHINA_TO_HONG_KONG_AND_MACAO:
                $card_type_txt = '中华人民共和国来往港澳通行证';
                break;
            case self::CARD_TYPE_TRAVEL_PASSES_FOR_TAIWAN_RESIDENTS_TO_ENTER_OR_LEAVE_THE_MAINLAND:
                $card_type_txt = '台湾居民来往大陆通行证';
                break;
            case self::CARD_TYPE_MAINLAND_RESIDENTS_TRAVEL_PERMIT_TO_TAIWAN:
                $card_type_txt = '大陆居民往来台湾通行证';
                break;
            case self::CARD_TYPE_FRONTIER_EXIT_ENTRY_PERMIT:
                $card_type_txt = '边民出入境通行证';
                break;
            case self::CARD_TYPE_FOREIGNERS_PERMANENT_RESIDENCE_PERMIT:
                $card_type_txt = '外国人永久居留证';
                break;
            case self::CARD_TYPE_ALIEN_RESIDENCE_PERMIT:
                $card_type_txt = '外国人居留证';
                break;
            case self::CARD_TYPE_ALIEN_EXIT_ENTRY_PERMIT:
                $card_type_txt = '外国人出入境证';
                break;
            case self::CARD_TYPE_DIPLOMATIC_CERTIFICATE:
                $card_type_txt = '外交官证';
                break;
            case self::CARD_TYPE_CONSULATE_CARD:
                $card_type_txt = '领事馆证';
                break;
            case self::CARD_TYPE_SEAMANS_CARD:
                $card_type_txt = '海员证';
                break;
            case self::CARD_TYPE_OTHER:
                $card_type_txt = '其他';
                break;
        }
        return $card_type_txt;
    }

    /**
     * 获取对应 性别 描述
     * @param int $sex
     * @return int
     */
    public function getMemberSexTxt(int $sex) {
        $member_sex = self::MEMBER_SEX_UNKNOWN;
        switch ($sex) {
            case self::MEMBER_SEX_UNKNOWN:
                $member_sex = '未知';
                break;
            case self::MEMBER_SEX_MALE:
                $member_sex = '男';
                break;
            case self::MEMBER_SEX_FEMALE:
                $member_sex = '女';
                break;
        }
        return $member_sex;
    }
    
    /**
     * 获取对应 房产状态 描述
     * @param int $status
     * @return int
     */
    public function getHouseStatusTxt(int $status) {
        $statusTxt = '';
        switch ($status) {
            case self::HOUSE_STATUS_SELL:
                $statusTxt = '出售';
                break;
            case self::HOUSE_STATUS_NOT_LIVE:
                $statusTxt = '空关';
                break;
            case self::HOUSE_STATUS_LIVE:
                $statusTxt = '入住';
                break;
            case self::HOUSE_STATUS_VACANT:
                $statusTxt = '空置';
                break;
            case self::HOUSE_STATUS_LEASE:
                $statusTxt = '出租';
                break;
            case self::HOUSE_STATUS_STOP:
                $statusTxt = '停用';
                break;
            case self::HOUSE_STATUS_RENOVATION:
                $statusTxt = '装修';
                break;
            case self::HOUSE_STATUS_UNCHECKED_ROOM:
                $statusTxt = '未收房';
                break;
        }
        return $statusTxt;
    }



    /**
     * 获取对应 房产类型 描述
     * @param int $type
     * @return int
     */
    public function getHouseTypeTxt(int $type) {
        $typeTxt = '';
        switch ($type) {
            case self::HOUSE_TYPE_NOT_SET:
                $typeTxt = '未设置';
                break;
            case self::HOUSE_TYPE_MULTI_STOREY:
                $typeTxt = '多层';
                break;
            case self::HOUSE_TYPE_SMALL_HEIGHT_RISE:
                $typeTxt = '小高层';
                break;
            case self::HOUSE_TYPE_HEIGHT_RISE:
                $typeTxt = '高层';
                break;
            case self::HOUSE_TYPE_VILLA:
                $typeTxt = '别墅';
                break;
            case self::HOUSE_TYPE_TOWNHOUSE:
                $typeTxt = '排屋';
                break;
            case self::HOUSE_TYPE_STOREROOM:
                $typeTxt = '储藏室';
                break;
            case self::HOUSE_TYPE_BICYCLE_PARKING:
                $typeTxt = '自行车库';
                break;
            case self::HOUSE_TYPE_OFFICE_BUILDING:
                $typeTxt = '写字楼';
                break;
            case self::HOUSE_TYPE_SHOPS:
                $typeTxt = '商铺';
                break;
            case self::HOUSE_TYPE_MARKET:
                $typeTxt = '商场';
                break;
            case self::HOUSE_TYPE_CLUB:
                $typeTxt = '会所';
                break;
            case self::HOUSE_TYPE_OFFICE_SPACE:
                $typeTxt = '办公用房';
                break;
            case self::HOUSE_TYPE_NANNY_ROOM:
                $typeTxt = '保姆房';
                break;
            case self::HOUSE_TYPE_HOTEL:
                $typeTxt = '酒店';
                break;
            case self::HOUSE_TYPE_OTHER:
                $typeTxt = '其它';
                break;
            case self::HOUSE_TYPE_GARAGE:
                $typeTxt = '车库';
                break;
            case self::HOUSE_TYPE_PARKING_LOT:
                $typeTxt = '车位';
                break;
            case self::HOUSE_TYPE_ADSENSE:
                $typeTxt = '广告位';
                break;
        }
        return $typeTxt;
    }



    /**
     * 获取对应 	房产性质 描述
     * @param int $nature
     * @return int
     */
    public function getHouseNatureTxt(int $nature) {
        $natureTxt = '';
        switch ($nature) {
            case self::HOUSE_TYPE_COMMERCIAL_HOUSING:
                $natureTxt = '商品房';
                break;
            case self::HOUSE_TYPE_AFFORDABLE_HOUSING:
                $natureTxt = '经济适用房';
                break;
            case self::HOUSE_TYPE_HOUSING_REFORM:
                $natureTxt = '房改房';
                break;
        }
        return $natureTxt;
    }
    
}