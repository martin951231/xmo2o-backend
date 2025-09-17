<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8"/>
		<title>{pigcms{$merchant.name}餐费充值</title>
		<meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
		<meta http-equiv="pragma" content="no-cache"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
        <link rel="stylesheet" href="{pigcms{$static_path}company_campus/css/reset.css"> 
        <link rel="stylesheet" href="{pigcms{$static_path}company_campus/js/weui/weui.min.css">
        <link rel="stylesheet" href="{pigcms{$static_path}company_campus/css/recharge.css" /> 
        <script src="{pigcms{$static_path}company_campus/js/jquery-1.8.3.min.js"></script>  
        <script src="{pigcms{$static_path}js/common.js"></script>
        <script src="{pigcms{$static_path}company_campus/js/weui/weui.min.js"></script> 
        <script src="{pigcms{$static_path}js/layer.m.js"></script>
        <style>
            .weui-cells:before{
                border-top:none
            }
        </style>
        <script>
            var path='{pigcms{$static_path}company_campus'
        </script>
	</head>
	<body>
        <div id="app">
          <!-- <header class="header">
              <div class="goleft" onclick="JavaScript:history.back(-1)"></div>
              <span class="com_til">合肥市五十中餐费充值</span> 
           </header>  -->
            <div class="weui-cells weui-cells_form">
                <div class="weui-cell">
                    <div class="weui-cell__hd"><label class="weui-label">姓名</label></div>
                    <div class="weui-cell__bd">
                        <input class="weui-input" type="text" id="name"  placeholder="本人或他人">
                    </div>
                </div>
                <div class="weui-cell">
                    <div class="weui-cell__hd"><label class="weui-label">ID</label></div>
                    <div class="weui-cell__bd">
                        <input class="weui-input" type="text" id="card_id"  placeholder="卡号/学号/工号">
                    </div>
                 </div>
                <div class="weui-cell">
                    <div class="weui-cell__hd"><label class="weui-label">充值金额</label></div>
                    <div class="weui-cell__bd">
                        <input class="weui-input" type="number" id="money" onKeyUp="amount(this)"   placeholder="充值金额">
                    </div>
                </div> 
            </div>
         <!-- onBlur="overFormat(this)" -->
            <label for="weuiAgree" class="weui-agree">
                <input id="weuiAgree" type="checkbox" class="weui-agree__checkbox">
                <span class="weui-agree__text">
                    阅读并同意<a href="javascript:void(0);" class="btn_agree" style="color:#0f9cd6">《餐卡充值服务协议》</a>
                </span>
            </label> 
            <a href="javascript:;" class="weui-btn btn_recharge">充值</a> 
        </div>
        <script src="{pigcms{$static_path}/js/common.js"></script> 
        <script type="text/javascript">
            $(".btn_recharge").click(function (argument) {
                if($('#weuiAgree').is(":checked") == false){
                    weui.toast('您没同意协议！',1000);
                    return false
                } 

                var name = $('#name').val();
                var card_id = $('#card_id').val();
                var money = $('#money').val();
                var mer_id = {pigcms{$_GET['mer_id']|intval}

                if (name=='') {
                    weui.toast('{pigcms{:L_("请输入姓名")}',1000);
                    return false;
                }

                if (card_id=='') {
                    weui.toast('{pigcms{:L_("请输入卡号/学号/工号")}',1000);
                    return false;
                }

                if (money=='') {
                    weui.toast('{pigcms{:L_("请输入正确的充值金额")}',1000);
                    return false;
                }

                $.post("{pigcms{:U('get_card_info')}",{name:name,card_id:card_id,money:money,mer_id:mer_id},function(res){
                    if (res.err_code) {
                        layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:res.msg,btn:['好的'],shadeClose:false,yes:function(){
                            if(res.url!='' && typeof(res.url) != 'undefined'){
                                window.location.href= res.url;
                            }
                            layer.closeAll();
                        }});
                    }else{
                        content = '<div><p>组别：'+res.data.group_name+'</p><p>性名：'+res.data.name+'</p><p>ID：'+res.data.physical_id+'</p><p>余额：'+res.data.money_total+'</p><p style="margin-top:10px">充值金额：'+money+'</p></div>';

                        layer.open({
                            title:['确认信息','background-color:#8DCE16;color:#fff;'],
                            content:content, 
                            style: 'width:100%',
                            btn:['确认','取消'],
                            shadeClose:false,
                            yes:function(){
                                $.post("{pigcms{:U('do_recharge')}",{name:name,card_id:card_id,money:money,mer_id:mer_id},function(res){
                                    if (res.err_code) {
                                        layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:res.msg,btn:['好的'],shadeClose:false,yes:function(){
                                            if(res.url!='' && typeof(res.url) != 'undefined'){
                                                window.location.href= res.url;
                                            }
                                            layer.closeAll();
                                        }});
                                    }else{
                                        window.location.href= res.url;
                                           
                                    }
                                },'json')
                        }});
                    }
                },'json');


                
            })

            /**
            * 实时动态强制更改用户录入
            * arg1 inputObject
            **/
            function amount(th){
                var regStrs = [
                    ['^0(\\d+)$', '$1'], //禁止录入整数部分两位以上，但首位为0
                    ['[^\\d\\.]+$', ''], //禁止录入任何非数字和点
                    ['\\.(\\d?)\\.+', '.$1'], //禁止录入两个以上的点
                    ['^(\\d+\\.\\d{2}).+', '$1'] //禁止录入小数点后两位以上
                ];
                for(var i=0; i<regStrs.length; i++){
                    var reg = new RegExp(regStrs[i][0]);
                    th.value = th.value.replace(reg, regStrs[i][1]);
                }
            }

            $(document).ready(function(){
                $('.btn_agree').click(function(){  
                    var merchant_recharge_agreement = '{pigcms{$merchant_recharge_agreement}';
                    weui.alert('<div style="height:400px; overflow-y:scroll">'+merchant_recharge_agreement+'</div>',{
                        title: '餐卡充值服务协议',
                    });
                }) 
               
                
            })
        </script>
	</body>
</html>
