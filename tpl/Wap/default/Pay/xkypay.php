<!doctype html>
<html xmlns="http://www.w3.org/1999/html">
<head>
    <title>xkypay</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="content-language" content="zh-CN">
    <meta name="keywords" content="" />
    <meta name="description" content="" />
    <meta http-equiv="Cache-Control" content="max-age=1700">
    <meta name="viewport" content="initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="no">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="format-detection" content="telephone=no">
    <meta content="yes" name="apple-touch-fullscreen">
    <meta http-equiv="cleartype" content="on">
    <style>
        .group{
            margin:10px 0;
            line-height:25px;
        }
        .group span{
            margin-right:10px
        }
    </style>
</head>
<body>
<!-- <div  id="div_center">
    <p>本示例提供内嵌在新空云app中调起微信或支付宝付款页面示例，仅在新空云app中有效</p>
    <div id="welcome"></div>
    <div class="group">
        <span>支付金额</span><input id="total_fee" value='' /><span style="margin-left:10px;">元</span>
    </div>
    <div class="group">
        <span>支付方式</span>
        <input type="radio" name="payType" value="0"><span>微信</span>
        <input type="radio" name="payType" value="1"><span>支付宝</span>
        <input type="radio" name="payType" value="-1" checked><span>均可</span>
    </div>
    <button onclick="goAppreciate()">支付</button>

</div> -->
<script>
    var p_goods_name = '{pigcms{$goods_name}';
    var p_total_fee = '{pigcms{$total_fee}';
    var p_pay_type = '{pigcms{$pay_type}';
    var p_otherOrderNo = '{pigcms{$order_no}';
    var p_callBackUrl = '{pigcms{$callback_url}';
    var globalData = {}
    function goAppreciate(){
        if(!globalData.uid){
            //uid不存在，需触发登录
            login();
            return
        }
        var textInfo = encodeURIComponent(p_goods_name);//不能为空。商品描述交易字段，格式根据不同的应用场景按照以下格式：新空云-测试，最大不超过60个字符，需要对内容进行encodeURIComponent转义；
        var callBackUrl = encodeURIComponent(p_callBackUrl);//第三方接收支付结果的url，接收方法为post，此url不能携带任何参数,需要对内容进行encodeURIComponent转义；
        var pay_type = p_pay_type;//支付渠道，0：微信，1：支付宝，-1：不指定支付渠道由app用户手动在选择
        var otherOrderNo = p_otherOrderNo;//第三方自己的订单编号，此编号应该由第三方自己的服务器生成
        // var total_fee = document.querySelector("#total_fee").value;//支付金额
        
        var total_fee = p_total_fee*100;
        //签名字段，使用SHA1加密对相关参数进行签名。签名规则如下：sign = SHA1(sid + uid + textInfo + total_fee + pay_type + otherOrderNo + callBackUrl); 将各字段拼接起来，不需要中间有加号。
        var sign = sha1(globalData.sid + globalData.uid + textInfo + total_fee + pay_type + otherOrderNo + callBackUrl);
        if(!total_fee || (total_fee+"").indexOf(".")>=0 || isNaN(parseInt(total_fee)) || total_fee<=0){

            alert("交易金额不合法")
            return
        }
        window.location.href="goappreciate://?sid="+globalData.sid+"&textInfo="+textInfo+"&total_fee="+total_fee+"&uid="+globalData.uid+"&pay_type="+pay_type+"&otherOrderNo="+otherOrderNo+"&callBackUrl="+callBackUrl+"&sign="+sign;
    }
    //支付结果回调函数onWxPayResult:: {"success":true,"msg":"支付成功"}
    function appreciateResult(params) {
        var jsonParams = eval("(" + params + ")");
        if(typeof jsonParams.success != 'undefined' && jsonParams.success == '0'){
            window.history.go(-1)
            return false;
        }
        // document.write("{pigcms{$redirect_url}");
        window.location.href = "{pigcms{$redirect_url}";        
    }


    //登录注册成功后回调函数
    /* —— 各参数含义：
* jsonParams.sid:新空云租户ID（唯一）
* jsonParams.userID:新空云的用户ID（唯一）
* jsonParams.password：密文密码（明文密码进行md5后的密码）
* jsonParams.nickName: 新空云的用户昵称
* jsonParams.userPhone: 新空云的用户手机号
* jsonParams.loginName: 新空云用户登录名（手机号或邮箱地址）
* jsonParams.openID: 新空云的第三方登录openID(uType不为0才有值)
* jsonParams.faceUrl: 新空云的用户头像地址
* jsonParams.uType: 用户登录类型uType：登录类型：0手机号登录；1微博登录；2QQ登录；3微信登录
*/
    function postUserInfo(params){
        var jsonParams = eval("(" + params + ")");
        globalData.uid = jsonParams.userID
        globalData.sid = jsonParams.sid

        // var msg = "Hi," + jsonParams.nickName;
        // document.getElementById("welcome").innerText = msg;
        //登录成功，调起支付
        goAppreciate();
    }

    //触发登录事件
    function login(){
        var $agent = navigator.userAgent;
        //检测是否在新空云APP内打开
        var iMyApp = $agent.indexOf('xkyApp') > -1;
        if(iMyApp){
            window.location.href = "checkuserlogin:///";
        }
        else{
            alert("请在APP中打开。");
        }
    }

    function encodeUTF8(s) {
        var i, r = [], c, x;
        for (i = 0; i < s.length; i++)
            if ((c = s.charCodeAt(i)) < 0x80) r.push(c);
            else if (c < 0x800) r.push(0xC0 + (c >> 6 & 0x1F), 0x80 + (c & 0x3F));
            else {
                if ((x = c ^ 0xD800) >> 10 == 0) //对四字节UTF-16转换为Unicode
                    c = (x << 10) + (s.charCodeAt(++i) ^ 0xDC00) + 0x10000,
                        r.push(0xF0 + (c >> 18 & 0x7), 0x80 + (c >> 12 & 0x3F));
                else r.push(0xE0 + (c >> 12 & 0xF));
                r.push(0x80 + (c >> 6 & 0x3F), 0x80 + (c & 0x3F));
            };
        return r;
    }

    // 字符串加密成 hex 字符串
    function sha1(s) {
        var data = new Uint8Array(encodeUTF8(s))
        var i, j, t;
        var l = ((data.length + 8) >>> 6 << 4) + 16, s = new Uint8Array(l << 2);
        s.set(new Uint8Array(data.buffer)), s = new Uint32Array(s.buffer);
        for (t = new DataView(s.buffer), i = 0; i < l; i++)s[i] = t.getUint32(i << 2);
        s[data.length >> 2] |= 0x80 << (24 - (data.length & 3) * 8);
        s[l - 1] = data.length << 3;
        var w = [], f = [
                function () { return m[1] & m[2] | ~m[1] & m[3]; },
                function () { return m[1] ^ m[2] ^ m[3]; },
                function () { return m[1] & m[2] | m[1] & m[3] | m[2] & m[3]; },
                function () { return m[1] ^ m[2] ^ m[3]; }
            ], rol = function (n, c) { return n << c | n >>> (32 - c); },
            k = [1518500249, 1859775393, -1894007588, -899497514],
            m = [1732584193, -271733879, null, null, -1009589776];
        m[2] = ~m[0], m[3] = ~m[1];
        for (i = 0; i < s.length; i += 16) {
            var o = m.slice(0);
            for (j = 0; j < 80; j++)
                w[j] = j < 16 ? s[i + j] : rol(w[j - 3] ^ w[j - 8] ^ w[j - 14] ^ w[j - 16], 1),
                    t = rol(m[0], 5) + f[j / 20 | 0]() + m[4] + w[j] + k[j / 20 | 0] | 0,
                    m[1] = rol(m[1], 30), m.pop(), m.unshift(t);
            for (j = 0; j < 5; j++)m[j] = m[j] + o[j] | 0;
        };
        t = new DataView(new Uint32Array(m).buffer);
        for (var i = 0; i < 5; i++)m[i] = t.getUint32(i << 2);

        var hex = Array.prototype.map.call(new Uint8Array(new Uint32Array(m).buffer), function (e) {
            return (e < 16 ? "0" : "") + e.toString(16);
        }).join("");
        return hex;
    }

    goAppreciate();//去支付
</script>
</body>
</html>