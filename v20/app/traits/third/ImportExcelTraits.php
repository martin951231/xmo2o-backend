<?php
	/**
	 * +----------------------------------------------------------------------
	 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
	 * +----------------------------------------------------------------------
	 * @author    合肥快鲸科技有限公司
	 * @copyright 合肥快鲸科技有限公司
	 * @link      https://www.kuaijing.com.cn
	 * @Desc      代码功能描述 亚茹队列和一些公用方法
	 */

	namespace app\traits\third;

 
	use app\consts\thirdImportDataConst;
    use think\facade\Queue;

	trait ImportExcelTraits
	{
		/**
		 * 队列名称
		 * @return string
		 */
		public function queueNameThirdImport()
		{
			return 'third-import-excel';
		}

        public function roomDataImportQueue($queuData)
        {
            $queueId = Queue::push('app\job\third\ImportExcelJob@roomDataImportJob',$queuData,$this->queueNameThirdImport());

            file_put_contents('duilie.log','压入队列成功：[ '.$this->queueNameThirdImport().' ]---> 压入的数据 ----- > '.print_r($queuData,true). PHP_EOL, 8);
            return $queueId;
        }

        public function userDataImportQueue($queuData)
        {
            $queueId = Queue::push('app\job\third\ImportExcelJob@userDataImportJob',$queuData,$this->queueNameThirdImport());

            file_put_contents('duilie.log','压入队列成功：[ '.$this->queueNameThirdImport().' ]---> 压入的数据 ----- > '.print_r($queuData,true). PHP_EOL, 8);
            return $queueId;
        }

        public function chargeDataImportQueue($queuData)
        {
            $queueId = Queue::push('app\job\third\ImportExcelJob@chargeDataImportJob',$queuData,$this->queueNameThirdImport());

            file_put_contents('duilie.log','压入队列成功：[ '.$this->queueNameThirdImport().' ]---> 压入的数据 ----- > '.print_r($queuData,true). PHP_EOL, 8);
            return $queueId;
        }

        /**
         * 获取  证件类型  对应描述
         * @param int $card_type
         * @return int
         */
        public function getCardTypeTxt(int $card_type) {
            $card_type_txt = thirdImportDataConst::CARD_TYPE_RESIDENT_ID_CARD;
            switch ($card_type) {
                case thirdImportDataConst::CARD_TYPE_RESIDENT_ID_CARD:
                    $card_type_txt = '居民身份证';
                    break;
                case thirdImportDataConst::CARD_TYPE_TEMPORARY_ID_CARD:
                    $card_type_txt = '临时身份证';
                    break;
                case thirdImportDataConst::CARD_TYPE_RESIDENCE_BOOKLET:
                    $card_type_txt = '户口簿';
                    break;
                case thirdImportDataConst::CARD_TYPE_RESIDENT_ID_CARD_TAIWAN:
                    $card_type_txt = '居民身份证（台）';
                    break;
                case thirdImportDataConst::CARD_TYPE_CERTIFICATE_OF_OFFICERS:
                    $card_type_txt = '军官证';
                    break;
                case thirdImportDataConst::CARD_TYPE_POLICE_OFFICER_CERTIFICAT:
                    $card_type_txt = '警官证';
                    break;
                case thirdImportDataConst::CARD_TYPE_SOLDIER_ID:
                    $card_type_txt = '士兵证';
                    break;
                case thirdImportDataConst::CARD_TYPE_MILITARY_ACADEMY_CERTIFICATE:
                    $card_type_txt = '军事学院证';
                    break;
                case thirdImportDataConst::CARD_TYPE_OFFICER_RETIREMENT_CERTIFICATE:
                    $card_type_txt = '军官退休证';
                    break;
                case thirdImportDataConst::CARD_TYPE_CIVILIAN_CADRE_CERTIFICATE:
                    $card_type_txt = '文职干部证';
                    break;
                case thirdImportDataConst::CARD_TYPE_RETIREMENT_CERTIFICATE_FOR_CIVILIAN_CADRES:
                    $card_type_txt = '文职干部退休证';
                    break;
                case thirdImportDataConst::CARD_TYPE_HONORARY_CERTIFICATE_FOR_RETIRED_CADRES:
                    $card_type_txt = '离休干部荣誉证';
                    break;
                case thirdImportDataConst::CARD_TYPE_ARMED_POLICE_OFFICER_CERTIFICATE:
                    $card_type_txt = '武警警官证';
                    break;
                case thirdImportDataConst::CARD_TYPE_MILITARY_STUDENT_CERTIFICATE:
                    $card_type_txt = '军队学员证';
                    break;
                case thirdImportDataConst::CARD_TYPE_MILITARY_CIVILIAN_CADRE_CERTIFICATE:
                    $card_type_txt = '军队文职干部证';
                    break;
                case thirdImportDataConst::CARD_TYPE_MILITARY_RETIRED_CADRE_CERTIFICATE_AND_MILITARY_STAFF_CERTIFICATE:
                    $card_type_txt = '军队离退休干部证和军队职工证';
                    break;
                case thirdImportDataConst::CARD_TYPE_PASSPORT:
                    $card_type_txt = '护照';
                    break;
                case thirdImportDataConst::CARD_TYPE_TRAVEL_PERMIT_FOR_COMPATRIOTS_FROM_HONG_KONG_MACAO_AND_TAIWAN:
                    $card_type_txt = '港澳台同胞来往通行证';
                    break;
                case thirdImportDataConst::CARD_TYPE_HOME_VISITING_CERTIFICATE_FOR_HONG_KONG_AND_MACAO_COMPATRIOTS:
                    $card_type_txt = '港澳同胞回乡证';
                    break;
                case thirdImportDataConst::CARD_TYPE_MAINLAND_TRAVEL_PERMIT_FOR_HONG_KONG_AND_MACAO_RESIDENTS:
                    $card_type_txt = '港澳居民来往内地通行证';
                    break;
                case thirdImportDataConst::CARD_TYPE_PERMIT_OF_THE_PEOPLES_REPUBLIC_OF_CHINA_TO_HONG_KONG_AND_MACAO:
                    $card_type_txt = '中华人民共和国来往港澳通行证';
                    break;
                case thirdImportDataConst::CARD_TYPE_TRAVEL_PASSES_FOR_TAIWAN_RESIDENTS_TO_ENTER_OR_LEAVE_THE_MAINLAND:
                    $card_type_txt = '台湾居民来往大陆通行证';
                    break;
                case thirdImportDataConst::CARD_TYPE_MAINLAND_RESIDENTS_TRAVEL_PERMIT_TO_TAIWAN:
                    $card_type_txt = '大陆居民往来台湾通行证';
                    break;
                case thirdImportDataConst::CARD_TYPE_FRONTIER_EXIT_ENTRY_PERMIT:
                    $card_type_txt = '边民出入境通行证';
                    break;
                case thirdImportDataConst::CARD_TYPE_FOREIGNERS_PERMANENT_RESIDENCE_PERMIT:
                    $card_type_txt = '外国人永久居留证';
                    break;
                case thirdImportDataConst::CARD_TYPE_ALIEN_RESIDENCE_PERMIT:
                    $card_type_txt = '外国人居留证';
                    break;
                case thirdImportDataConst::CARD_TYPE_ALIEN_EXIT_ENTRY_PERMIT:
                    $card_type_txt = '外国人出入境证';
                    break;
                case thirdImportDataConst::CARD_TYPE_DIPLOMATIC_CERTIFICATE:
                    $card_type_txt = '外交官证';
                    break;
                case thirdImportDataConst::CARD_TYPE_CONSULATE_CARD:
                    $card_type_txt = '领事馆证';
                    break;
                case thirdImportDataConst::CARD_TYPE_SEAMANS_CARD:
                    $card_type_txt = '海员证';
                    break;
                case thirdImportDataConst::CARD_TYPE_OTHER:
                    $card_type_txt = '其他';
                    break;
            }
            return $card_type_txt;
        }

        /**
         * 获取  证件类型  对应值
         * @param string $member_card_type
         * @return int
         */
        public function getCardType(string $member_card_type) {
            $card_type = thirdImportDataConst::CARD_TYPE_NOTHING;
            switch ($member_card_type) {
                case '居民身份证':
                    $card_type = thirdImportDataConst::CARD_TYPE_RESIDENT_ID_CARD;
                    break;
                case '临时身份证':
                    $card_type = thirdImportDataConst::CARD_TYPE_TEMPORARY_ID_CARD;
                    break;
                case '户口簿':
                    $card_type = thirdImportDataConst::CARD_TYPE_RESIDENCE_BOOKLET;
                    break;
                case '居民身份证（台）':
                    $card_type = thirdImportDataConst::CARD_TYPE_RESIDENT_ID_CARD_TAIWAN;
                    break;
                case '军官证':
                    $card_type = thirdImportDataConst::CARD_TYPE_CERTIFICATE_OF_OFFICERS;
                    break;
                case '警官证':
                    $card_type = thirdImportDataConst::CARD_TYPE_POLICE_OFFICER_CERTIFICAT;
                    break;
                case '士兵证':
                    $card_type = thirdImportDataConst::CARD_TYPE_SOLDIER_ID;
                    break;
                case '军事学院证':
                    $card_type = thirdImportDataConst::CARD_TYPE_MILITARY_ACADEMY_CERTIFICATE;
                    break;
                case '军官退休证':
                    $card_type = thirdImportDataConst::CARD_TYPE_OFFICER_RETIREMENT_CERTIFICATE;
                    break;
                case '文职干部证':
                    $card_type = thirdImportDataConst::CARD_TYPE_CIVILIAN_CADRE_CERTIFICATE;
                    break;
                case '文职干部退休证':
                    $card_type = thirdImportDataConst::CARD_TYPE_RETIREMENT_CERTIFICATE_FOR_CIVILIAN_CADRES;
                    break;
                case '离休干部荣誉证':
                    $card_type = thirdImportDataConst::CARD_TYPE_HONORARY_CERTIFICATE_FOR_RETIRED_CADRES;
                    break;
                case '武警警官证':
                    $card_type = thirdImportDataConst::CARD_TYPE_ARMED_POLICE_OFFICER_CERTIFICATE;
                    break;
                case '军队学员证':
                    $card_type = thirdImportDataConst::CARD_TYPE_MILITARY_STUDENT_CERTIFICATE;
                    break;
                case '军队文职干部证':
                    $card_type = thirdImportDataConst::CARD_TYPE_MILITARY_CIVILIAN_CADRE_CERTIFICATE;
                    break;
                case '军队离退休干部证和军队职工证':
                    $card_type = thirdImportDataConst::CARD_TYPE_MILITARY_RETIRED_CADRE_CERTIFICATE_AND_MILITARY_STAFF_CERTIFICATE;
                    break;
                case '护照':
                    $card_type = thirdImportDataConst::CARD_TYPE_PASSPORT;
                    break;
                case '港澳台同胞来往通行证':
                    $card_type = thirdImportDataConst::CARD_TYPE_TRAVEL_PERMIT_FOR_COMPATRIOTS_FROM_HONG_KONG_MACAO_AND_TAIWAN;
                    break;
                case '港澳同胞回乡证':
                    $card_type = thirdImportDataConst::CARD_TYPE_HOME_VISITING_CERTIFICATE_FOR_HONG_KONG_AND_MACAO_COMPATRIOTS;
                    break;
                case '港澳居民来往内地通行证':
                    $card_type = thirdImportDataConst::CARD_TYPE_MAINLAND_TRAVEL_PERMIT_FOR_HONG_KONG_AND_MACAO_RESIDENTS;
                    break;
                case '中华人民共和国来往港澳通行证':
                    $card_type = thirdImportDataConst::CARD_TYPE_PERMIT_OF_THE_PEOPLES_REPUBLIC_OF_CHINA_TO_HONG_KONG_AND_MACAO;
                    break;
                case '台湾居民来往大陆通行证':
                    $card_type = thirdImportDataConst::CARD_TYPE_TRAVEL_PASSES_FOR_TAIWAN_RESIDENTS_TO_ENTER_OR_LEAVE_THE_MAINLAND;
                    break;
                case '大陆居民往来台湾通行证':
                    $card_type = thirdImportDataConst::CARD_TYPE_MAINLAND_RESIDENTS_TRAVEL_PERMIT_TO_TAIWAN;
                    break;
                case '边民出入境通行证':
                    $card_type = thirdImportDataConst::CARD_TYPE_FRONTIER_EXIT_ENTRY_PERMIT;
                    break;
                case '外国人永久居留证':
                    $card_type = thirdImportDataConst::CARD_TYPE_FOREIGNERS_PERMANENT_RESIDENCE_PERMIT;
                    break;
                case '外国人居留证':
                    $card_type = thirdImportDataConst::CARD_TYPE_ALIEN_RESIDENCE_PERMIT;
                    break;
                case '外国人出入境证':
                    $card_type = thirdImportDataConst::CARD_TYPE_ALIEN_EXIT_ENTRY_PERMIT;
                    break;
                case '外交官证':
                    $card_type = thirdImportDataConst::CARD_TYPE_DIPLOMATIC_CERTIFICATE;
                    break;
                case '领事馆证':
                    $card_type = thirdImportDataConst::CARD_TYPE_CONSULATE_CARD;
                    break;
                case '海员证':
                    $card_type = thirdImportDataConst::CARD_TYPE_SEAMANS_CARD;
                    break;
                case '其他':
                    $card_type = thirdImportDataConst::CARD_TYPE_OTHER;
                    break;
            }
            return $card_type;
        }

        /**
         * 获取对应 房屋销售状态 值
         * @param string $memberRelationTxt
         * @return int
         */
        public function getMemberRelationType(string $memberRelationTxt) {
            $relation_type = 1;
            switch ($memberRelationTxt) {
                case '业主':
                    $relation_type = thirdImportDataConst::HOUSEHOLDER_RELATIONSHIP_OWNER;
                    break;
                case '家属':
                    $relation_type = thirdImportDataConst::HOUSEHOLDER_RELATIONSHIP_FAMILY;
                    break;
                case '租客':
                    $relation_type = thirdImportDataConst::HOUSEHOLDER_RELATIONSHIP_TENANT;
                    break;
                case '配偶':
                    $relation_type = thirdImportDataConst::HOUSEHOLDER_RELATIONSHIP_SPOUSE;
                    break;
                case '父母':
                    $relation_type = thirdImportDataConst::HOUSEHOLDER_RELATIONSHIP_PARENT;
                    break;
                case '子女':
                    $relation_type = thirdImportDataConst::HOUSEHOLDER_RELATIONSHIP_CHILDREN;
                    break;
                case '亲朋好友':
                    $relation_type = thirdImportDataConst::HOUSEHOLDER_RELATIONSHIP_FRIENDS;
                    break;
            }
            return $relation_type;
        }
        
        public function getUserBindType($member_relation_type, $otherUserBind = []) {
            switch ($member_relation_type) {
                case thirdImportDataConst::HOUSEHOLDER_RELATIONSHIP_TENANT:
                    $otherUserBind['type'] = 2;
                    break;
                case thirdImportDataConst::HOUSEHOLDER_RELATIONSHIP_SPOUSE:
                    $otherUserBind['type']           = 1;
                    $otherUserBind['relatives_type'] = 1;
                    break;
                case thirdImportDataConst::HOUSEHOLDER_RELATIONSHIP_PARENT:
                    $otherUserBind['type']           = 1;
                    $otherUserBind['relatives_type'] = 2;
                    break;
                case thirdImportDataConst::HOUSEHOLDER_RELATIONSHIP_CHILDREN:
                    $otherUserBind['type']           = 1;
                    $otherUserBind['relatives_type'] = 3;
                    break;
                case thirdImportDataConst::HOUSEHOLDER_RELATIONSHIP_FRIENDS:
                    $otherUserBind['type']           = 1;
                    $otherUserBind['relatives_type'] = 4;
                    break;
                case thirdImportDataConst::HOUSEHOLDER_RELATIONSHIP_FAMILY:
                default:
                    $otherUserBind['type'] = 1;
                    break;
            }
            return $otherUserBind;
        }

        /**
         * 获取对应 性别 描述
         * @param int $sex
         * @return int
         */
        public function getMemberSexTxt(int $sex) {
            $member_sex = thirdImportDataConst::MEMBER_SEX_UNKNOWN;
            switch ($sex) {
                case thirdImportDataConst::MEMBER_SEX_UNKNOWN:
                    $member_sex = '未知';
                    break;
                case thirdImportDataConst::MEMBER_SEX_MALE:
                    $member_sex = '男';
                    break;
                case thirdImportDataConst::MEMBER_SEX_FEMALE:
                    $member_sex = '女';
                    break;
            }
            return $member_sex;
        }

        /**
         * 获取对应 性别 值
         * @param string $member_sex
         * @return int
         */
        public function getMemberSex(string $member_sex) {
            $sex = thirdImportDataConst::MEMBER_SEX_UNKNOWN;
            switch ($member_sex) {
                case '男':
                    $sex = thirdImportDataConst::MEMBER_SEX_MALE;
                    break;
                case '女':
                    $sex = thirdImportDataConst::MEMBER_SEX_FEMALE;
                    break;
            }
            return $sex;
        }


        /**
         * 获取对应 房产状态 值
         * @param string $statusTxt
         * @return int
         */
        public function getHouseStatus(string $statusTxt) {
            $status = 0;
            switch ($statusTxt) {
                case '出售':
                    $status = thirdImportDataConst::HOUSE_STATUS_SELL;
                    break;
                case '空关':
                    $status = thirdImportDataConst::HOUSE_STATUS_NOT_LIVE;
                    break;
                case '入住':
                    $status = thirdImportDataConst::HOUSE_STATUS_LIVE;
                    break;
                case '空置':
                    $status = thirdImportDataConst::HOUSE_STATUS_VACANT;
                    break;
                case '出租':
                    $status = thirdImportDataConst::HOUSE_STATUS_LEASE;
                    break;
                case '停用':
                    $status = thirdImportDataConst::HOUSE_STATUS_STOP;
                    break;
                case '装修':
                    $status = thirdImportDataConst::HOUSE_STATUS_RENOVATION;
                    break;
                case '未收房':
                    $status = thirdImportDataConst::HOUSE_STATUS_UNCHECKED_ROOM;
                    break;
            }
            return $status;
        }

        /**
         * 获取对应 房产状态 描述
         * @param int $status
         * @return int
         */
        public function getHouseStatusTxt(int $status) {
            $statusTxt = '';
            switch ($status) {
                case thirdImportDataConst::HOUSE_STATUS_SELL:
                    $statusTxt = '出售';
                    break;
                case thirdImportDataConst::HOUSE_STATUS_NOT_LIVE:
                    $statusTxt = '空关';
                    break;
                case thirdImportDataConst::HOUSE_STATUS_LIVE:
                    $statusTxt = '入住';
                    break;
                case thirdImportDataConst::HOUSE_STATUS_VACANT:
                    $statusTxt = '空置';
                    break;
                case thirdImportDataConst::HOUSE_STATUS_LEASE:
                    $statusTxt = '出租';
                    break;
                case thirdImportDataConst::HOUSE_STATUS_STOP:
                    $statusTxt = '停用';
                    break;
                case thirdImportDataConst::HOUSE_STATUS_RENOVATION:
                    $statusTxt = '装修';
                    break;
                case thirdImportDataConst::HOUSE_STATUS_UNCHECKED_ROOM:
                    $statusTxt = '未收房';
                    break;
            }
            return $statusTxt;
        }


        /**
         * 获取对应 房屋销售状态 值
         * @param string $statusTxt
         * @return int
         */
        public function getUserStatus(string $statusTxt) {
            $status = 0;
            switch ($statusTxt) {
                case '出租':
                    $status = thirdImportDataConst::USER_STATUS_LEASE;
                    break;
                case '停用':
                    $status = thirdImportDataConst::USER_STATUS_STOP;
                    break;
                case '入住':
                    $status = thirdImportDataConst::USER_STATUS_LIVE;
                    break;
                case '出售':
                    $status = thirdImportDataConst::USER_STATUS_SELL;
                    break;
                case '空关':
                    $status = thirdImportDataConst::USER_STATUS_NOT_LIVE;
                    break;
                case '空置':
                    $status = thirdImportDataConst::USER_STATUS_VACANT;
                    break;
                case '装修':
                    $status = thirdImportDataConst::USER_STATUS_RENOVATION;
                    break;
                case '未收房':
                    $status = thirdImportDataConst::USER_STATUS_UNCHECKED_ROOM;
                    break;
            }
            return $status;
        }

        /**
         * 获取对应 房产类型 值
         * @param string $typeTxt
         * @return int
         */
        public function getHouseType(string $typeTxt) {
            $type = 0;
            switch ($typeTxt) {
                case '未设置':
                    $type = thirdImportDataConst::HOUSE_TYPE_NOT_SET;
                    break;
                case '多层':
                    $type = thirdImportDataConst::HOUSE_TYPE_MULTI_STOREY;
                    break;
                case '小高层':
                    $type = thirdImportDataConst::HOUSE_TYPE_SMALL_HEIGHT_RISE;
                    break;
                case '高层':
                    $type = thirdImportDataConst::HOUSE_TYPE_HEIGHT_RISE;
                    break;
                case '别墅':
                    $type = thirdImportDataConst::HOUSE_TYPE_VILLA;
                    break;
                case '排屋':
                    $type = thirdImportDataConst::HOUSE_TYPE_TOWNHOUSE;
                    break;
                case '储藏室':
                    $type = thirdImportDataConst::HOUSE_TYPE_STOREROOM;
                    break;
                case '自行车库':
                    $type = thirdImportDataConst::HOUSE_TYPE_BICYCLE_PARKING;
                    break;
                case '写字楼':
                    $type = thirdImportDataConst::HOUSE_TYPE_OFFICE_BUILDING;
                    break;
                case '商铺':
                    $type = thirdImportDataConst::HOUSE_TYPE_SHOPS;
                    break;
                case '商场':
                    $type = thirdImportDataConst::HOUSE_TYPE_MARKET;
                    break;
                case '会所':
                    $type = thirdImportDataConst::HOUSE_TYPE_CLUB;
                    break;
                case '办公用房':
                    $type = thirdImportDataConst::HOUSE_TYPE_OFFICE_SPACE;
                    break;
                case '保姆房':
                    $type = thirdImportDataConst::HOUSE_TYPE_NANNY_ROOM;
                    break;
                case '酒店':
                    $type = thirdImportDataConst::HOUSE_TYPE_HOTEL;
                    break;
                case '其它':
                    $type = thirdImportDataConst::HOUSE_TYPE_OTHER;
                    break;
                case '车库':
                    $type = thirdImportDataConst::HOUSE_TYPE_GARAGE;
                    break;
                case '车位':
                    $type = thirdImportDataConst::HOUSE_TYPE_PARKING_LOT;
                    break;
                case '广告位':
                    $type = thirdImportDataConst::HOUSE_TYPE_ADSENSE;
                    break;
            }
            return $type;
        }
        
        /**
         * 获取对应 房产类型 描述
         * @param int $type
         * @return int
         */
        public function getHouseTypeTxt(int $type) {
            $typeTxt = '';
            switch ($type) {
                case thirdImportDataConst::HOUSE_TYPE_NOT_SET:
                    $typeTxt = '未设置';
                    break;
                case thirdImportDataConst::HOUSE_TYPE_MULTI_STOREY:
                    $typeTxt = '多层';
                    break;
                case thirdImportDataConst::HOUSE_TYPE_SMALL_HEIGHT_RISE:
                    $typeTxt = '小高层';
                    break;
                case thirdImportDataConst::HOUSE_TYPE_HEIGHT_RISE:
                    $typeTxt = '高层';
                    break;
                case thirdImportDataConst::HOUSE_TYPE_VILLA:
                    $typeTxt = '别墅';
                    break;
                case thirdImportDataConst::HOUSE_TYPE_TOWNHOUSE:
                    $typeTxt = '排屋';
                    break;
                case thirdImportDataConst::HOUSE_TYPE_STOREROOM:
                    $typeTxt = '储藏室';
                    break;
                case thirdImportDataConst::HOUSE_TYPE_BICYCLE_PARKING:
                    $typeTxt = '自行车库';
                    break;
                case thirdImportDataConst::HOUSE_TYPE_OFFICE_BUILDING:
                    $typeTxt = '写字楼';
                    break;
                case thirdImportDataConst::HOUSE_TYPE_SHOPS:
                    $typeTxt = '商铺';
                    break;
                case thirdImportDataConst::HOUSE_TYPE_MARKET:
                    $typeTxt = '商场';
                    break;
                case thirdImportDataConst::HOUSE_TYPE_CLUB:
                    $typeTxt = '会所';
                    break;
                case thirdImportDataConst::HOUSE_TYPE_OFFICE_SPACE:
                    $typeTxt = '办公用房';
                    break;
                case thirdImportDataConst::HOUSE_TYPE_NANNY_ROOM:
                    $typeTxt = '保姆房';
                    break;
                case thirdImportDataConst::HOUSE_TYPE_HOTEL:
                    $typeTxt = '酒店';
                    break;
                case thirdImportDataConst::HOUSE_TYPE_OTHER:
                    $typeTxt = '其它';
                    break;
                case thirdImportDataConst::HOUSE_TYPE_GARAGE:
                    $typeTxt = '车库';
                    break;
                case thirdImportDataConst::HOUSE_TYPE_PARKING_LOT:
                    $typeTxt = '车位';
                    break;
                case thirdImportDataConst::HOUSE_TYPE_ADSENSE:
                    $typeTxt = '广告位';
                    break;
            }
            return $typeTxt;
        }

        /**
         * 获取对应 	房产性质 值
         * @param string $natureTxt
         * @return int
         */
        public function getHouseNature(string $natureTxt) {
            $nature = 0;
            switch ($natureTxt) {
                case '商品房':
                    $nature = thirdImportDataConst::HOUSE_TYPE_COMMERCIAL_HOUSING;
                    break;
                case '经济适用房':
                    $nature = thirdImportDataConst::HOUSE_TYPE_AFFORDABLE_HOUSING;
                    break;
                case '房改房':
                    $nature = thirdImportDataConst::HOUSE_TYPE_HOUSING_REFORM;
                    break;
            }
            return $nature;
        }
        
        /**
         * 获取对应 	房产性质 描述
         * @param int $nature
         * @return int
         */
        public function getHouseNatureTxt(int $nature) {
            $natureTxt = '';
            switch ($nature) {
                case thirdImportDataConst::HOUSE_TYPE_COMMERCIAL_HOUSING:
                    $natureTxt = '商品房';
                    break;
                case thirdImportDataConst::HOUSE_TYPE_AFFORDABLE_HOUSING:
                    $natureTxt = '经济适用房';
                    break;
                case thirdImportDataConst::HOUSE_TYPE_HOUSING_REFORM:
                    $natureTxt = '房改房';
                    break;
            }
            return $natureTxt;
        }
	}