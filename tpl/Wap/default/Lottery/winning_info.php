<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

    <meta name="viewport"
          content="width=device-width,height=device-height,inital-scale=1.0,maximum-scale=1.0,user-scalable=no;">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="format-detection" content="telephone=no">
    <meta name="description" content="">

    <title>{pigcms{$Dazpan.title}</title>
    <link href="{pigcms{$static_path}css/activity-style.css" rel="stylesheet" type="text/css">
    <if condition="$Dazpan['backpicurl'] neq ''">
        <style>
            .activity-lottery-winning {
                background: url("{pigcms{$Dazpan['backpicurl']}");
                overflow-x: hidden;
            }
        </style>
    </if>
</head>
<body class="activity-lottery-winning">
<!--main start-->
<div class="main">
    <script src="{pigcms{$static_path}js/jquery.js" type="text/javascript"></script>
    <script src="{pigcms{$static_path}js/alert.js" type="text/javascript"></script>
    <style type="text/css">

        .window {
            width: 290px;
            position: absolute;
            display: none;
            bottom: 30px;
            left: 50%;
            z-index: 9999;
            margin: -50px auto 0 -145px;
            padding: 2px;
            border-radius: 0.6em;
            -webkit-border-radius: 0.6em;
            -moz-border-radius: 0.6em;
            background-color: #ffffff;
            -webkit-box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            -moz-box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            -o-box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            font: 14px/1.5 Microsoft YaHei, Helvitica, Verdana, Arial, san-serif;
        }

        .window .title {

            background-color: #A3A2A1;
            line-height: 26px;
            padding: 5px 5px 5px 10px;
            color: #ffffff;
            font-size: 16px;
            border-radius: 0.5em 0.5em 0 0;
            -webkit-border-radius: 0.5em 0.5em 0 0;
            -moz-border-radius: 0.5em 0.5em 0 0;
            background-image: -webkit-gradient(linear, left top, left bottom, from(#585858), to(#565656)); /* Saf4+, Chrome */
            background-image: -webkit-linear-gradient(#585858, #565656); /* Chrome 10+, Saf5.1+ */
            background-image: -moz-linear-gradient(#585858, #565656); /* FF3.6 */
            background-image: -ms-linear-gradient(#585858, #565656); /* IE10 */
            background-image: -o-linear-gradient(#585858, #565656); /* Opera 11.10+ */
            background-image: linear-gradient(#585858, #565656);

        }

        .window .content {
            /*min-height:100px;*/
            overflow: auto;
            padding: 10px;
            background: linear-gradient(#FBFBFB, #EEEEEE) repeat scroll 0 0 #FFF9DF;
            color: #222222;
            text-shadow: 0 1px 0 #FFFFFF;
            border-radius: 0 0 0.6em 0.6em;
            -webkit-border-radius: 0 0 0.6em 0.6em;
            -moz-border-radius: 0 0 0.6em 0.6em;
        }

        .window #txt {
            min-height: 30px;
            font-size: 16px;
            line-height: 22px;
        }

        .window .txtbtn {

            background: #f1f1f1;
            background-image: -webkit-gradient(linear, left top, left bottom, from(#DCDCDC), to(#f1f1f1)); /* Saf4+, Chrome */
            background-image: -webkit-linear-gradient(#ffffff, #DCDCDC); /* Chrome 10+, Saf5.1+ */
            background-image: -moz-linear-gradient(#ffffff, #DCDCDC); /* FF3.6 */
            background-image: -ms-linear-gradient(#ffffff, #DCDCDC); /* IE10 */
            background-image: -o-linear-gradient(#ffffff, #DCDCDC); /* Opera 11.10+ */
            background-image: linear-gradient(#ffffff, #DCDCDC);
            border: 1px solid #CCCCCC;
            border-bottom: 1px solid #B4B4B4;
            color: #555555;
            font-weight: bold;
            text-shadow: 0 1px 0 #FFFFFF;
            border-radius: 0.6em 0.6em 0.6em 0.6em;
            display: block;
            width: 100%;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
            text-overflow: ellipsis;
            white-space: nowrap;
            cursor: pointer;
            text-align: windowcenter;
            font-weight: bold;
            font-size: 18px;
            padding: 6px;
            margin: 10px 0 0 0;
        }

        .window .txtbtn:visited {
            background-image: -webkit-gradient(linear, left top, left bottom, from(#ffffff), to(#cccccc)); /* Saf4+, Chrome */
            background-image: -webkit-linear-gradient(#ffffff, #cccccc); /* Chrome 10+, Saf5.1+ */
            background-image: -moz-linear-gradient(#ffffff, #cccccc); /* FF3.6 */
            background-image: -ms-linear-gradient(#ffffff, #cccccc); /* IE10 */
            background-image: -o-linear-gradient(#ffffff, #cccccc); /* Opera 11.10+ */
            background-image: linear-gradient(#ffffff, #cccccc);
        }

        .window .txtbtn:hover {
            background-image: -webkit-gradient(linear, left top, left bottom, from(#ffffff), to(#cccccc)); /* Saf4+, Chrome */
            background-image: -webkit-linear-gradient(#ffffff, #cccccc); /* Chrome 10+, Saf5.1+ */
            background-image: -moz-linear-gradient(#ffffff, #cccccc); /* FF3.6 */
            background-image: -ms-linear-gradient(#ffffff, #cccccc); /* IE10 */
            background-image: -o-linear-gradient(#ffffff, #cccccc); /* Opera 11.10+ */
            background-image: linear-gradient(#ffffff, #cccccc);
        }

        .window .txtbtn:active {
            background-image: -webkit-gradient(linear, left top, left bottom, from(#cccccc), to(#ffffff)); /* Saf4+, Chrome */
            background-image: -webkit-linear-gradient(#cccccc, #ffffff); /* Chrome 10+, Saf5.1+ */
            background-image: -moz-linear-gradient(#cccccc, #ffffff); /* FF3.6 */
            background-image: -ms-linear-gradient(#cccccc, #ffffff); /* IE10 */
            background-image: -o-linear-gradient(#cccccc, #ffffff); /* Opera 11.10+ */
            background-image: linear-gradient(#cccccc, #ffffff);
            border: 1px solid #C9C9C9;
            border-top: 1px solid #B4B4B4;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.3) inset;
        }

        .window .title .close {
            float: right;
            background-image: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABoAAAAaCAYAAACpSkzOAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAACTSURBVEhL7dNtCoAgDAZgb60nsGN1tPLVCVNHmg76kQ8E1mwv+GG27cestQ4PvTZ69SFocBGpWa8+zHt/Up+IN+MhgLlUmnIE1CpBQB2COZibfpnXhHFaIZkYph0SOeeK/QJ8o7KOek84fkCWSBtfL+Ny2MPpCkPFMH6PWEhWhKncIyEk69VfiUuVhqJefds+YcwNbEwxGqGIFWYAAAAASUVORK5CYII=");
            width: 26px;
            height: 26px;
            display: block;
        }
    </style>

    <if condition="$lottery['end'] eq 1">
        <div class="activity-lottery-end">
            <div class="main">
                <div class="banner"><img
                            src="<php>if(!$Dazpan['endpicurl']){</php>{pigcms{$static_path}images/activity-lottery-end2.jpg<php>}else{</php>{pigcms{$Dazpan.endpicurl}<php>}</php>"/>
                </div>
                <div class="content" style=" margin-top:10px">
                    <div class="boxcontent boxyellow">
                        <div class="box">
                            <div class="title-red">活动结束说明：</div>
                            <div class="Detail">
                                <p> {pigcms{$lottery.endinfo}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </if>


    <!--content start-->
    <div class="content">
        <if condition="$winning">
            <volist name="winning" id="item">
                <div class="boxcontent boxyellow">
                    <div class="box">
                        <div class="title-orange"><span>恭喜您中奖了</span></div>
                        <div class="Detail">
                            <p>你中了：<span class="red" id="prizetype">{pigcms{$item.prize_info}等奖</span></p>
                            <p>兑奖{pigcms{$item.renamesn}：<span class="red" id="sncode_{pigcms{$key}">{pigcms{$item.sn}</span></p>
                            <p class="red" id="red">{pigcms{$item.sttxt}</p>
                            <if condition="$item.phone eq ''">
                                <p>
                                    <input name="" class="px" id="tel_{pigcms{$key}" value="{pigcms{$item['tel']}" type="text" placeholder="请输入您的{pigcms{$item.renametel}">
                                    <!-- <input name="" class="px" id="wechaname_{pigcms{$key}" value="{pigcms{$item['uname']}" type="text" placeholder="请输入您的微信号"> -->
                                    <input name="" id="wechaid_{pigcms{$key}" value="{pigcms{$item.wecha_id}" type="hidden">
                                    <input name="" id="lid_{pigcms{$key}" value="{pigcms{$item.lid}" type="hidden">
                                    <input name="" id="winprize_{pigcms{$key}" value="{pigcms{$item.prize}" type="hidden">
                                    <input name="" id="rid_{pigcms{$key}" value="{pigcms{$item.rid}" type="hidden">
                                </p>
                                <p>
                                    <input class="pxbtn save-btn" name="提 交" data-key="{pigcms{$key}" id="" type="button" value="用户提交">
                                </p>
                                <else/>
                                {pigcms{$item.phone}
                            </if>
                            <if condition="$item.sendstutas eq 0">
                                <p><input name="" class="px" id="parssword_{pigcms{$key}" value="" placeholder="商家输入兑奖密码" type="password"></p>
                                <p><input class="pxbtn save-btnn" name="提 交" data-key="{pigcms{$key}" id="" value="商家提交" type="button"></p>
                                <else/>
                                <p style="text-align: -webkit-center;padding: 10px 0 0;">已于{pigcms{$item.sendtime|date="Y-m-d",###}兑奖</p>
                            </if>
                        </div>
                    </div>
                </div>
            </volist>
            <else/>
            <div class="boxcontent boxyellow">
                <div class="box">
                    <div class="title-orange" style="margin: 10px 0 10px 33%;"><span>暂无中奖信息</span></div>
                </div>
            </div>

        </if>

</div>
<!--main end-->
<!--footer start-->
<style>
    .footFix {
        width: 100%;
        text-align: center;
        position: fixed;
        left: 0;
        bottom: 0;
        z-index: 99;
    }

    #footReturn a, #footReturn2 a {
        display: block;
        line-height: 41px;
        color: #fff;
        text-shadow: 1px 1px #282828;
        font-size: 14px;
        font-weight: bold;
    }

    #footReturn, #footReturn2 {
        z-index: 89;
        display: inline-block;
        text-align: center;
        text-decoration: none;
        vertical-align: middle;
        cursor: pointer;
        width: 100%;
        outline: 0 none;
        overflow: visible;
        Unknown property name .
        -moz-box-sizing: border-box;
        box-sizing: border-box;
        padding: 0;
        height: 41px;
        opacity: .95;
        border-top: 1px solid #181818;
        box-shadow: inset 0 1px 2px #b6b6b6;
        background-color: #515151;
        Invalid property value .
        background-image: -ms-linear-gradient(top, #838383, #202020);
        background-image: -webkit-linear-gradient(top, #838383, #202020);
        Invalid property value .
        background-image: -moz-linear-gradient(top, #838383, #202020);
        Invalid property value .
        background-image: -o-linear-gradient(top, #838383, #202020);
        background-image: -webkit-gradient(linear, 0% 0, 0% 100%, from(#838383), to(#202020));
        Invalid property value .
        filter: progid:DXImageTransform.Microsoft.gradient(GradientType=0, startColorstr='#838383', endColorstr='#202020');
        Unknown property name .
        -ms-filter: progid:DXImageTransform.Microsoft.gradient(GradientType=0, startColorstr='#838383', endColorstr='#202020');
    }

</style>
<!--<div class="footFix" id="footReturn" style="display: none1">-->
<!--    <if condition="$_GET['token'] gt 0">-->
<!--        <a href="javascript:void(0)"-->
<!--           onClick="location.href='{pigcms{:U('Wap/Index/index',array('token'=>$_GET['token']))}';"><span>返回商家首页</span></a>-->
<!--        <else/>-->
<!--        <a href="javascript:void(0)" onClick='location.href="{pigcms{$config.site_url}/wap.php"'><span>返回系统首页</span></a>-->
<!--    </if>-->
<!--</div>-->
<!--footer end-->

<script type="text/javascript">


    //中奖提交
    $(".save-btn").bind("click", function () {
        var btn = $(this);
        var key =  btn.data("key");
        console.log('key---', key);
        var tel = $("#tel_"+key).val();
        var wxname = $("#wechaname_"+key).val();
        var wechaid = $("#wechaid_"+key).val();
        var lid = $("#lid_"+key).val();
        var prizetype = $("#winprize_"+key).val();
        if (tel == '') {
            alert("请认真输入{pigcms{$Dazpan.renametel}");
            return
        }

        if (wxname == '') {
            alert("请认真输入微信号");
            return
        }

        var submitData = {
            lid: lid,
            sncode: $("#sncode_"+key).text(),
            tel: tel,
            wxname: wxname,
            wechaid: wechaid,
            rid: $("#rid_"+key).val(),
            action: "add"
        };
        $.post('wap.php?g=Wap&c=Lottery&a=add', submitData, function (data) {
            if (data.success == true) {
                alert(data.msg);
                $("#result").hide("slow");
                setTimeout("window.location.href = location.href", 2000);
                return
            } else {
            }
        }, "json")
    });

    $(".save-btnn").bind("click", function () {
        var btn = $(this);
        var key =  btn.data("key");
        var lid = $("#lid_"+key).val();
        var rid = $("#rid_"+key).val();
        var submitData = {
            id: lid,
            rid: rid,
            parssword: $("#parssword_"+key).val()
        };
        $.post('wap.php?g=Wap&c=Lottery&a=exchange', submitData, function (data) {
            if (data.success == true) {
                alert(data.msg);
                if (data.changed == true) {
                    setTimeout("window.location.href = location.href", 2000);
                }
                return
            } else {
                alert(data.msg);
            }
        }, "json")
    });

</script>
<script type="text/javascript">
    window.shareData = {
        "moduleName": "Lottery",
        "moduleID": "{pigcms{$lottery.id}",
        "imgUrl": '<if condition="substr($lottery['starpicurl'],0,4) eq 'http'">{pigcms{$lottery.starpicurl}<else/>{pigcms{$config.site_url}{pigcms{$lottery.starpicurl}</if>',
        "sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Lottery/index',array('token'=>$lottery['token'],'id'=>$lottery['id'],'type'=>1))}",
        "tTitle": "{pigcms{$lottery.title}",
        "tContent": "{pigcms{$lottery.info}"
    };
</script>
{pigcms{$shareScript}
<div style="display:none;">{pigcms{$config.wap_site_footer}</div>
</body>
</html>