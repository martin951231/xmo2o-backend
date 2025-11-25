<?php

namespace tools;
//收货地址解析，仅支持中国地址
class AddressParser
{
    //百家姓
    static $familyNames = array(
        '赵',
        '钱',
        '孙',
        '李',
        '周',
        '吴',
        '郑',
        '王',
        '冯',
        '陈',
        '褚',
        '卫',
        '蒋',
        '沈',
        '韩',
        '杨',
        '朱',
        '秦',
        '尤',
        '许',
        '何',
        '吕',
        '施',
        '张',
        '孔',
        '曹',
        '严',
        '华',
        '金',
        '魏',
        '陶',
        '姜',
        '戚',
        '谢',
        '邹',
        '喻',
        '柏',
        '水',
        '窦',
        '章',
        '云',
        '苏',
        '潘',
        '葛',
        '奚',
        '范',
        '彭',
        '郎',
        '鲁',
        '韦',
        '昌',
        '马',
        '苗',
        '凤',
        '花',
        '方',
        '任',
        '袁',
        '柳',
        '鲍',
        '史',
        '唐',
        '费',
        '薛',
        '雷',
        '贺',
        '倪',
        '汤',
        '滕',
        '殷',
        '罗',
        '毕',
        '郝',
        '安',
        '常',
        '傅',
        '卞',
        '齐',
        '元',
        '顾',
        '孟',
        '平',
        '黄',
        '穆',
        '萧',
        '尹',
        '姚',
        '邵',
        '湛',
        '汪',
        '祁',
        '毛',
        '狄',
        '米',
        '伏',
        '成',
        '戴',
        '谈',
        '宋',
        '茅',
        '庞',
        '熊',
        '纪',
        '舒',
        '屈',
        '项',
        '祝',
        '董',
        '梁',
        '杜',
        '阮',
        '蓝',
        '闵',
        '季',
        '贾',
        '路',
        '娄',
        '江',
        '童',
        '颜',
        '郭',
        '梅',
        '盛',
        '林',
        '钟',
        '徐',
        '邱',
        '骆',
        '高',
        '夏',
        '蔡',
        '田',
        '樊',
        '胡',
        '凌',
        '霍',
        '虞',
        '万',
        '支',
        '柯',
        '管',
        '卢',
        '莫',
        '柯',
        '房',
        '裘',
        '缪',
        '解',
        '应',
        '宗',
        '丁',
        '宣',
        '邓',
        '单',
        '杭',
        '洪',
        '包',
        '诸',
        '左',
        '石',
        '崔',
        '吉',
        '龚',
        '程',
        '嵇',
        '邢',
        '裴',
        '陆',
        '荣',
        '翁',
        '荀',
        '于',
        '惠',
        '甄',
        '曲',
        '封',
        '储',
        '仲',
        '伊',
        '宁',
        '仇',
        '甘',
        '武',
        '符',
        '刘',
        '景',
        '詹',
        '龙',
        '叶',
        '幸',
        '司',
        '黎',
        '溥',
        '印',
        '怀',
        '蒲',
        '邰',
        '从',
        '索',
        '赖',
        '卓',
        '屠',
        '池',
        '乔',
        '胥',
        '闻',
        '莘',
        '党',
        '翟',
        '谭',
        '贡',
        '劳',
        '逄',
        '姬',
        '申',
        '扶',
        '堵',
        '冉',
        '宰',
        '雍',
        '桑',
        '寿',
        '通',
        '燕',
        '浦',
        '尚',
        '农',
        '温',
        '别',
        '庄',
        '晏',
        '柴',
        '瞿',
        '阎',
        '连',
        '习',
        '容',
        '向',
        '古',
        '易',
        '廖',
        '庾',
        '终',
        '步',
        '都',
        '耿',
        '满',
        '弘',
        '匡',
        '国',
        '文',
        '寇',
        '广',
        '禄',
        '阙',
        '东',
        '欧',
        '利',
        '师',
        '巩',
        '聂',
        '关',
        '荆',
        '司马',
        '上官',
        '欧阳',
        '夏侯',
        '诸葛',
        '闻人',
        '东方',
        '赫连',
        '皇甫',
        '尉迟',
        '公羊',
        '澹台',
        '公冶',
        '宗政',
        '濮阳',
        '淳于',
        '单于',
        '太叔',
        '申屠',
        '公孙',
        '仲孙',
        '轩辕',
        '令狐',
        '徐离',
        '宇文',
        '长孙',
        '慕容',
        '司徒',
        '司空'
    );

    static $provinces = [
        '北京市',
        '上海市',
        '天津市',
        '重庆市',
        '河北省',
        '山西省',
        '辽宁省',
        '吉林省',
        '黑龙江省',
        '江苏省',
        '浙江省',
        '安徽省',
        '福建省',
        '江西省',
        '山东省',
        '河南省',
        '湖北省',
        '湖南省',
        '广东省',
        '海南省',
        '四川省',
        '贵州省',
        '云南省',
        '陕西省',
        '甘肃省',
        '青海省',
        '台湾省',
        '内蒙古自治区',
        '广西壮族自治区',
        '宁夏回族自治区',
        '新疆维吾尔自治区',
        '西藏自治区',
        '香港特别行政区',
        '澳门特别行政区'
    ];


    //生成姓名匹配正则表达式
    public static function buildFamilyNamePattern()
    {
        //按长度排序，复姓优先
        usort(self::$familyNames, function ($a, $b) {
            return mb_strlen($b) - mb_strlen($a);
        });

        // 构建正则表达式
        $pattern = '/^(' . implode('|', self::$familyNames) . ')[\p{Han}]{1,4}/u';
        return $pattern;
    }


    //地址解析
    public static function parse($address)
    {
        //地址存在逗号的，先替换掉
        $address = str_replace(['，', ',', '\n', '\r\n'], ' ', $address);

        $result = [
            'username' => '',       // 用户名
            'phone' => '',          // 手机号
            'province' => '',       // 省份
            'city' => '',           // 城市
            'district' => '',       // 区/县
            'detail' => ''          // 详细地址
        ];

        // 1. 提取手机号 (11位数字)
        if (preg_match('/1[3-9]\d{9}/', $address, $matches)) {
            $result['phone'] = $matches[0];
            // 从地址中移除已提取的手机号
            $strpos = strpos($address, $result['phone']);
            $address = trim(str_replace($matches[0], ' ', $address));
        }

        // 2. 提取用户名,用户名字符串长度不能超过7个汉字，切能匹配百家姓
        $arrs = array_filter(explode(" ", $address));
        foreach ($arrs as $item) {
            $item = trim($item);
            if (mb_strlen($item) < 7 && preg_match(self::buildFamilyNamePattern(), $item, $matches)) {
                $result['username'] = $matches[0];
                // 从地址中移除已提取的用户名
                $address = trim(str_replace($matches[0], '', $address));
                break;
            }
        }

        //3、提取省份
        foreach (self::$provinces as $province) {
            if (strpos($address, $province) !== false) {
                $result['province'] = $province;
                $address = trim(str_replace($province, ' ', $address));
                break;
            }
        }
        // 4、提取城市（匹配以市、自治州结尾的名称）
        if (preg_match('/[\p{Han}]+?(市|自治州|盟)/u', $address, $matches)) {
            $result['city'] = $matches[0];
            $address = trim(str_replace($matches[0], '', $address));
        }

        // 5、提取区/县（匹配以区、县、市、旗结尾的名称）
        if (preg_match('/[\p{Han}]+?(区|县|市|旗)/u', $address, $matches)) {
            $result['district'] = $matches[0];
            $address = trim(str_replace($matches[0], '', $address));
        }

        // 6. 提取详细地址（剩余部分）
        $result['detail'] = trim($address);

        return $result;
    }
}
