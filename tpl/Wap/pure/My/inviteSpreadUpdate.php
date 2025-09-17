<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta http-equiv="Expires" content="-1">
    <meta http-equiv="Cache-Control" content="no-cache">
    <meta http-equiv="Pragma" content="no-cache">
    <meta charset="utf-8">
    <title>推广海报</title>
    <link href="{pigcms{$static_path}css/spread_hb.css" rel="stylesheet"/>
    <script src="{pigcms{:C('JQUERY_FILE')}"></script>
    <!--[if lte IE 9]>
    <script src="scripts/html5shiv.min.js"></script>
    <![endif]-->
</head>
<body style="background: #f0efed;">
<section class="code">
    <div class="code_top">
        <div style="position: absolute;left: 29.5%;top: 31px;text-align: center">
            <img src="{pigcms{$userinfo.avatar}" style="width: 45px;height: 45px;border-radius: 45px">
            <p>{pigcms{$userinfo.nickname}</p>
            <h3 style="color: #FF0000;margin-top: 1rem">升级推广商，你也能赚</h3>
        </div>

        <div class="code_input clr" style="width: 90%;height: auto;background: #FFFFFF;border-radius: 5px;margin-left: 5%;margin-top: 48px;padding-top: 66px">
            <img id="qrcode" style="width: 60%;margin-left: 20%;margin-top: 30px;margin-bottom: 25px;" src=""/>
        </div>
        <p class="remind" style="background: url(tpl/Wap/pure/static/images/light.png) left center no-repeat;">长按保存上图二维码后发送好友~</p>
    </div>
    <div class="code_end">
        <ul class="clr">
            <li>
                <span style="background: url(/tpl/Wap/default/static/images/my_07.png) center  no-repeat; background-size: 24px;"></span>
                <p class="text">分享推广二维码给小伙伴</p>
            </li>
            <li>
                <span style="background: url(/tpl/Wap/default/static/images/my_09.png) center  no-repeat; background-size: 24px;"></span>
                <p class="text">小伙伴就与你绑定推广关系</p>
            </li>
            <li>
                <span style="background: url(/tpl/Wap/default/static/images/my_11.png) center  no-repeat; background-size: 24px;"></span>
                <p class="text">获得小伙伴升级推广商直推奖金</p>
            </li>
        </ul>
    </div>
    <div class="ul_list">
        <ul>
            <volist name="settings" id="s">
                <li>
                    <div class="ul_top"><php>echo $s['type']==1?'直推vip推广商奖励比例':'直推城市推广商奖励比例';</php></div>
                    <div class="ul_end clr">
                        <div class="jdt fl" data-num="100">
                            <div class="jdt_n" data-num="{pigcms{$s.spread_percent|getFormatNumber}"></div>
                    </div>
                    <span class="bfb fr"><i>{pigcms{$s.spread_percent|getFormatNumber}</i>%</span>
                    </div>
                </li>
            </volist>
            <if condition="$spread['is_city_spread']">
                <li>
                    <div class="ul_top">推荐商家获得佣金比例</div>
                    <div class="ul_end clr">
                        <div class="jdt fl" data-num="100">
                            <div class="jdt_n" data-num="{pigcms{$spread.commission|getFormatNumber}"></div>
                        </div>
                        <span class="bfb fr"><i>{pigcms{$spread.commission|getFormatNumber}</i>%</span>
                    </div>
                </li>
            </if>

    </ul>
    </div>
</section>
</body>
{pigcms{$hideScript}
</html>

<script type="text/javascript">
    var spreadUrl = '{pigcms{$spreadUrl}';
    var uid = '{pigcms{$uid}';

    get_code(spreadUrl,uid);


    function get_code(url,uid){
        $.post("{pigcms{:U('My/get_spread_qrcode')}", {url:url,uid:uid}, function(data) {
           console.log(data);
           var rs = $.parseJSON(data);

            if(!rs.error_code){
                $('#qrcode').attr('src',rs.msg.ticket);
            }else{
                alert(data.msg);
            }
        });
    }

    $(".ul_end").each(function () {
        var jdt = $(this).find(".jdt");
        var num1 = jdt.data("num");
        var w = jdt.width();
        var jdt_n = $(this).find(".jdt_n");
        var num2 = jdt_n.data("num");
        jdt_n.width(w * (num2 / num1));
    })
</script>



