<!DOCTYPE html>
<html>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no" />
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}my/css/reset.css" />
<script src="{pigcms{:C('JQUERY_FILE')}"></script>
<title>关注有礼</title>
<style>

    .activation_code_box {
        padding: 20px 14px;
        display: none;
    }
    .activation_code_box li {
        padding: 20px;
    }
    .dialog {
        position: fixed;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        padding: 0 20px;
        z-index: 2000;
        -webkit-box-sizing: border-box;
        display: -webkit-box;
        -webkit-box-pack: center;
        -webkit-box-align: center;
    }
    .dialog.mask {
        background-color: rgba(0, 0, 0, .2);
    }
    .dialog_wrap {
        position: relative;
        width: 100%;
        max-width: 640px;
        border-radius: 5px;
        overflow: hidden;
        -webkit-animation: bounceIn .5s .2s ease both;
    }
    .dialog_tt {
        height: 42px;
        line-height: 42px;
        color: #fff;
        text-align: center;
        font-size: 16px;
        border-radius: 5px 5px 0 0;
    }
    .dialog_tt {
        background-image: -webkit-gradient(linear, 0 0, 100% 0, from(#ea6e4c), to(#ea381e));
        background-image: -webkit-linear-gradient(0, #ea6e4c, #ea381e);
    }
    .dialog_scroller {
        position: relative;
        float: left;
        width: 100%;
        overflow: hidden;
        background-color: #fff;
        border-radius: 0 0 5px 5px;
    }
    .txt {
        -webkit-appearance: none;
        width: 94%;
        line-height: 1.4;
        min-height: 2.66667rem;
        resize: none;
        padding: 4px 9px;
        border-width: 1px;
        border-radius: 3px;
    }
    .activation_code_box .btns_wrap {
        color: white;
        width: 88%;
        display: flex;
    }
    .activation_code_box li.btns_wrap div {
        width: 50%;
        padding: 0 4px;
    }
    .comm_btn.higher {
        height: 37px;
        line-height: 37px;
    }
    .activation_code_box .comm_btn {
        width: 80%;
        text-align: center;
    }
    .disabled {
        background-color: #b6b6b6 !important;
        cursor: default;
    }
    .comm_btn {
        display: inline-block;
        height: 31px;
        line-height: 31px;
        padding: 0 15px;
        font-size: 14px;
        color: #fff;
        background-color: #ff5f32;
        border-radius: 3px;
        white-space: nowrap;
    }
    .activation_code_box .ico_arrow {
        width: 8px;
        height: 13px;
        background-position: -14px -25px;
        display: inline-block;
        background: url({pigcms{$static_path}my/img/s.png) no-repeat;
        background-size: 150px auto;
        width: 8px;
        height: 13px;
        background-position: -14px -25px;
    }
    #li_delivery strong{
        color: black;
        font-size: 14px;
    }
    .btn {
        text-align: -webkit-center;
        padding: 20px 0 65px 0;
    }
    .btn_div{
        color: black;
        font-size: 1.4rem;
        width: 80%;
        text-align: center;
        background-color: #FFDF00;
        padding: 5px;
        border-radius: 10px;
    }
</style>
<body>
<div class="bg1">
    <h2 class="title">领取礼包</h2>
    <!--  -->

    <div style="padding: 0 10px 10px;" class="link_url" data-url="{pigcms{$user_level_gift_info['gift_url']}">
        <img src="{pigcms{$user_level_gift_info['gift_img']}" style="width: 100%">
    </div>
    <div class="yhq_conbg">

        <div class="yhq">
            <img src="{pigcms{$static_path}my/img/top_yhq.png" style="width: 100%" class="top_yhq">
        </div>

        <div class="yhq_block_bb">
            <h2>恭喜您获得【{pigcms{$user_level_gift_info['gift_name']}】大礼包一份</h2>
            <p>点击领取，填写收货地址即可完成礼包领取</p>
        </div>

        <a class="btn">
            <div class="btn_div">立即领取</div>
        </a>
    </div>

    <div class="bt_bg">
        <img src="{pigcms{$static_path}my/img/bg2.png" style="width: 100%">
    </div>

</div>


<div class="activation_code_box" id="codeBox">
    <ul>
        <li id="li_delivery">
            <a style="display: flex;" href="{pigcms{:U('My/adress',array('type' => 'receive_level_gift', 'current_id'=>$user_adress['adress_id']))}">
                <strong>
                    <span id="showAddres"><if condition="$user_adress['adress_id']">{pigcms{$user_adress['province_txt']} {pigcms{$user_adress['city_txt']} {pigcms{$user_adress['area_txt']} {pigcms{$user_adress['adress']} {pigcms{$user_adress['detail']}
                            <else/>
                            请点击添加送货地址
                            <php>if($address_count > 0){</php>
                                <br/>
                                <span style="font-size: 8px;color: #bbb5b5">已有地址均不在配送范围内</span>
                            <php>}</php>
                        </if>
                    </span><br>
                    <span id="showName">{pigcms{$user_adress['name']}</span>
                    <span id="showTel">{pigcms{$user_adress['phone']}</span>
                </strong>
                <div style="line-height: 42px;padding-left: 10px;"><i class="ico_arrow"></i></div>
            </a>
        </li>
        <li><textarea class="txt max" placeholder="请填写备注" id="userCode"></textarea></li>
        <li class="btns_wrap">
            <div><a href="javascript:void(0);" class="comm_btn higher disabled" id="cancleRemark">取消</a></div>
            <div><a href="javascript:void(0);" class="comm_btn higher" id="saveRemark">确认</a></div>
        </li>
    </ul>
</div>
<input name="order_id" type="hidden" id="order_id" value="{pigcms{$user_level_gift_order_info['order_id']}">
<input name="adress_id" type="hidden" id="adress_id" value="{pigcms{$user_adress['adress_id']}">

</body>

<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/dialog.js?t=001" charset="utf-8"></script>
<script>
    $(document).ready(function () {
        var current_id = "{pigcms{$_GET['current_id']}";
        var adress_id = "{pigcms{$_GET['adress_id']}";
        if ((current_id && parseInt(current_id)>0) || (adress_id && parseInt(adress_id)>0)) {
            $('#userCode').val('');
            $('#codeBox').dialog({title: '填写相关信息'});
        }

        // 添加备注
        $('.btn_div').bind('click', function(){
            console.log("====");
            $('#userCode').val('');
            $('#codeBox').dialog({title: '填写相关信息'});
        });

        $('#cancleRemark').bind('click', function(){
            $('#codeBox').dialog('close');
        });

        // 点击图片跳转
        $('.link_url').click(function () {
           var url = $(this).data('url');
           if (url) {
               window.location.href = url;
           }
        });



        $('#saveRemark').bind('click', function(){
            // $('#remarkTxt').text($('#userCode').val());
            console.log("userCode", $('#userCode').val());
            var get_remark = $('#userCode').val();
            var showAddres = $('#showAddres').html();
            var showName = $('#showName').html();
            var showTel = $('#showTel').html();
            var order_id = $('#order_id').val();
            var adress_id = $('#adress_id').val();
            if (!showAddres) {
                layer.open({
                    content: '请选择地址！'
                    ,skin: 'msg'
                    ,time: 1 //1秒后自动关闭
                });
                return false;
            }
            $.post("{pigcms{$config.site_url}{pigcms{:U('My/receive_gift')}", {'adress_id':adress_id,'get_remark':get_remark, 'get_name': showName, 'get_address': showAddres, 'get_phone': showTel, 'order_id': order_id }, function(res) {
                console.log('res', res);
                layer.closeAll();
                if (res.status == 0 && !res.url) {
                    layer.open({
                        content: res.info
                        ,skin: 'msg'
                        ,time: 2 //2秒后自动关闭
                    });
                } else if(res.status == 0 && res.url) {
                    window.location.href = res.url;
                } else {
                    layer.open({
                        content: res.info
                        ,skin: 'msg'
                        ,time: 2 //2秒后自动关闭
                    });
                    setTimeout(function () {
                        window.location.href="{pigcms{$config.site_url}{pigcms{:U('My/levelUpdate')}";
                    },2000);
                }
            }, 'json');
        });

    });
</script>
</html>