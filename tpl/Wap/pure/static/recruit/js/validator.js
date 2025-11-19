/*
 * User: 唐金涛
 * Date: 2019/02/21
 */
var Validator = {
    /**
     * 校验通用
     * @param reg
     * @param str
     * @returns
     */
    regex: function (reg, str) {
        if (Validator.isNull(reg)) {
            return false;
        } else if (Validator.isNull(str)) {
            return false;
        }
        return reg.test(str);
    },
    /**
     * 必填
     * @param str
     * @returns
     */
    required: function(str){
        return !Validator.isNull(str);
    },
    /**
     * 必选
     * @param str
     * @returns
     */
    checked: function(str, selector){
        return $(selector).prop('checked');
    },
    /**
     * 值是否相同（密码是否相同校验）
     * @param str
     * @param selector zepto选择器
     * @returns
     */
    isEqualTo: function(str, selector){
        return str == $(selector).val();
    },
    /**
     * 空的校验
     * @param param
     * @returns {Boolean}
     */
    isNull: function (param) {



        if (typeof(param) == "undefined" || param == null || param == "null" || param == "") {
            return true;
        } else {
            /*var result=param.replace(/(^\s+)|(\s+$)/g,"");
            if(result==''){
                return true;
            }else{
                return false
            }*/
            return false
        }
    },
    /**
     * 整数或者小数
     * @param str
     * @returns
     */
    isIntOrFloat: function (str) {
        var reg = /^[0-9]+\.{0,1}[0-9]{0,2}$/;
        return Validator.regex(reg, str);
    },
    /**
     * 只能是数字
     * @param str
     * @returns
     */
    isInt: function (str) {
        var reg = /^[0-9]*$/;
        return Validator.regex(reg, str);
    },
    /**
     * 大于n的数字
     * @param str
     * @returns
     */
    isIntGreater : function (str, n) {
        return parseInt(str, 10) >= n;
    },
    /**
     * 银行卡号
     * @param str
     * @returns
     */
    isBankCard : function (str) {
        return /^(\d{16}|\d{19})$/.test(str);
    },
    /**
     * 只能输入n位的数字
     * @param str
     * @param n
     * @returns
     */
    isIntLength: function (str, n) {
        if (Validator.isNull(n)) {
            return false;
        }
        var reg = new RegExp('^\\d{' + n + '}$');
        return Validator.regex(reg, str);
    },
    /**
     * 至少n位数字
     * @param str
     * @param n
     * @returns
     */
    isIntLessLength: function (str, n) {
        if (Validator.isNull(n)) {
            return false;
        }
        var reg = new RegExp('^\\d{' + n + ',}$');
        return Validator.regex(reg, str);
    },
    /**
     * n到m位数字
     * @param str
     * @param n
     * @param m
     * @returns
     */
    isIntLengthRange: function (str, n, m) {
        if (Validator.isNull(n) || Validator.isNull(m)) {
            return false;
        }
        var reg = new RegExp('^\\d{' + n + ',' + m + '}$');
        return Validator.regex(reg, str);
    },
    /**
     * 只能输入非零开头的数字
     * @param str
     * @returns
     */
    isNoZeroStart: function (str) {
        var reg = /^([1-9][0-9]*)$/;
        return Validator.regex(reg, str);
    },
    /**
     * 只能输入有n位小数的正实数
     * @param str
     * @param n
     * @returns
     */
    isDecimalLength: function (str, n) {
        var reg = new RegExp('^[0-9]+(.[0-9]{' + n + '})?$');
        return Validator.regex(reg, str);
    },
    /**
     * 只能输入有n~m位小数的正实数
     * @param str
     * @param n
     * @param m
     * @returns
     */
    isDecimalLengthRange: function (str, n, m) {
        var reg = new RegExp('^[0-9]+(.[0-9]{' + n + ',' + m + '})?$');
        return Validator.regex(reg, str);
    },

    /**
     * 长度为n的字符串
     * @param str
     * @returns
     */
    isStringLength: function (str, n) {
        var reg = new RegExp("^.{" + n + "}$");
        return Validator.regex(reg, str);
    },
    /**
     * 由26个英文字母组成的字符串
     * @param str
     * @param aorA,大写或小写类型，A表示大写，a表示小写，不指定或其他置顶表示不限制大小写
     * @returns
     */
    isStringLetter: function (str, aorA) {
        var reg;
        if (Validator.isNull(aorA)) {
            reg = /^[A-Za-z]+$/;
        } else if (aorA == "A") {
            reg = /^[A-Z]+$/;
        } else if (aorA == "a") {
            reg = /^[a-z]+$/;
        } else {
            reg = /^[A-Za-z]+$/;
        }
        return Validator.regex(reg, str);
    },
    /**
     * 由数字、26个英文字母或者下划线组成的字符串
     * @param str
     * @returns
     */
    isStringLetterDefault: function (str) {
        var reg = /^\w+$/;
        return Validator.regex(reg, str);
    },
    /**
     * 验证是否含有^%&',;=?$\"等特殊字符
     * @param str
     * @returns
     */
    isSpecialStr: function (str) {
        var reg = /[^%&',;=?$\x22]+/;
        return Validator.regex(reg, str);
    },
    /**
     * 只能输入汉字
     * @param str
     * @returns
     */
    isChinese: function (str) {
        var reg = /^[\u4e00-\u9fa5]{0,}$/;
        return Validator.regex(reg, str);
    },
    /**
     * 验证Email地址
     * @param str
     * @returns
     */
    isEmail: function (str) {
        var reg = /^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/;
        return Validator.regex(reg, str);
    },
    /**
     * 验证InternetURL地址
     * @param str
     * @returns
     */
    isHttpUrl: function (str) {
        var reg = /^http:\/\/([\w-]+\.)+[\w-]+(\/[\w-./?%&=]*)?$/;
        return Validator.regex(reg, str);
    },
    /**
     * 验证电话号码,正确格式为："XXX-XXXXXXX"、"XXXX-XXXXXXXX"、"XXX-XXXXXXX"、"XXX-XXXXXXXX"、"XXXXXXX"和"XXXXXXXX"
     * @param str
     * @returns
     */
    isTel: function (str) {
        var reg = /^((\d{3,4}-)|\d{3.4}-)?\d{7,8}$/;
        return Validator.regex(reg, str);
    },
    /**
     * 匹配手机号
     * @param str
     * @returns
     */
    isMobile: function (str) {
        var reg = /^1\d{10}$/;
        return Validator.regex(reg, str);
    },
    /**
     * 验证身份证号（15位或18位数字）
     * @param str
     * @returns
     */
    isIdCard: function (str) {
        var reg = /^\d{15}|\d{18}$/;
        return Validator.regex(reg, str);
    },
    /**
     * 货币输入校验
     * @param str
     * @returns
     */
    isMoney: function (str) {
        var reg = /^\d+\.\d{2}$/;
        return Validator.regex(reg, str);
    },
    /**
     * 验证一年的12月，正确格式为："01"～"09"和"1"～"12"。
     * @param str
     * @returns
     */
    isMonth: function (str) {
        var reg = /^(0?[1-9]|1[0-2])$/;
        return Validator.regex(reg, str);
    },
    /**
     * 验证一个月的31天,正确格式为；"01"～"09"和"1"～"31"。
     * @param str
     * @returns
     */
    isDay: function (str) {
        var reg = /^((0?[1-9])|((1|2)[0-9])|30|31)$/;
        return Validator.regex(reg, str);
    },
    /**
     * 匹配html标签的正则表达式
     * @param str
     * @returns
     */
    isHtml: function (str) {
        var reg = /<(.*)>(.*)<\/(.*)>|<(.*)\/>/;
        return Validator.regex(reg, str);
    },
    /**
     * 匹配空行的正则表达式
     * @param str
     * @returns
     */
    isSpace: function (str) {
        var reg = /\n[\s| ]*\r/;
        return Validator.regex(reg, str);
    },
    /**
     * 匹配QQ号
     * @param str
     * @returns
     */
    isQQ: function (str) {
        var reg = /^[1-9][0-9]{4,}$/;
        return Validator.regex(reg, str);
    },
    /**
     * 匹配邮编
     * @param str
     * @returns
     */
    isZip: function (str) {
        var reg = /^[\d]{6}/;
        return Validator.regex(reg, str);
    },
    /**
     * 匹配双字节字符(包括汉字在内)
     * @param str
     */
    isDoubleByte: function (str) {
        var reg = /[^\x00-\xff]/;
        return Validator.regex(reg, str);
    },

    /**
     * 类似java中的trim函数
     */
    trim: function (str) {
        return str.replace(/(^\\s*)|(\\s*$)/g, "");
    }
};

/**
 * 获取表单数据并验证
 */
Validator.getData = function(ignoreValidate, container){
    var $container = $(container || 'body'),
        $fields = $container.find('[data-rules]').not('.ignore'),
        len = $fields.size(),
        i = 0,
        data;

    for(; i < len; i++){
        var $field = $fields.eq(i),
            rules = $field.attr('data-rules'),
            messages = $field.attr('data-messages'),
            name = $field.attr('name'),
            isRadioOrCheck = $field.is('[type=radio],[type=checkbox]'),
            $checked = $container.find('[name="'+ name +'"]').filter(':checked'),
            value = isRadioOrCheck ? $checked.val() : $field.val(),
            addValue = function(){
                if(name){
                    data = data || {};
                    data[name] = value;

                    if(isRadioOrCheck && $checked.size() > 1){
                        data[name] = [];

                        $checked.each(function(){
                            data[name].push( $(this).val() );
                        });
                    }
                }
            };

        if(ignoreValidate){
            addValue();
        }else{
            if(rules){
                try{
                    rules = JSON.parse(rules);
                    messages = JSON.parse(messages);
                }catch(e){
                    throw new Error('the element "name='+ name +'", data-rules or data-messages is invalid JSON string！');
                }

                for(var attr in rules){
                    if(Validator.hasOwnProperty(attr)){
                        var ruleValue = rules[attr];

                        if(Validator[attr](value, ruleValue)){
                            addValue();
                        }else{
                            messages.hasOwnProperty(attr) && $.toptip(messages[attr], 1000, 'warning');
                            return false;
                        }
                    }
                }
            }else{
                addValue();
            }
        }
    }

    return data;
};
