<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <title><if condition="$type eq 'money'">余额兑换升级<else/>{pigcms{$config.score_name}兑换升级</if></title>
    <meta name="viewport" content="initial-scale=1, width=device-width, maximum-scale=1, user-scalable=no">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name='apple-touch-fullscreen' content='yes'>
	<meta name="apple-mobile-web-app-status-bar-style" content="black">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="address=no">
    <link href="{pigcms{$static_path}css/eve.7c92a906.css" rel="stylesheet"/>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}my/css/mylevel.css">
     <link rel="stylesheet" href="{pigcms{$static_path}css/swiper.min.css">
	<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
	<script type="text/javascript" src="{pigcms{$static_path}js/flexible.js"></script>
    <style>

    	body{padding-bottom: 1.2rem;}

	    button{outline: none;border: none;position: fixed;bottom: 0;left: 0;right: 0;width: 100%;height: 1.5rem;z-index: 99;}

        .item{border-radius: 10px;border:1px solid #e3e3e3;background:#fff;color: #999;}
	    .item.active{border:1px solid #dcb87f;background:#f5e9d7;color: #bb9967;}
        ul li{cursor: pointer;}
	</style>
</head>

<body class="color_0 bg_f">
	<div class="pd10">
      <ul>
          <volist name="nextlevels" id="nextlevel" key="k">
              <li class="flex flex_justify bg_f5e pd15 mg_b10 item <if condition='$k eq 1'> active </if>" data-level="{pigcms{$nextlevel['level']}" data-money="{pigcms{$nextlevel['use_money']}" data-score="{pigcms{$nextlevel['integral']}">
                  <div>
                      <div class="font_b font_size24 mg_b10">{pigcms{$nextlevel['lname']}</div>
                      <div class="font_size16">指定商品<if condition="$nextlevel['type'] eq 1">{pigcms{$nextlevel['boon']/10}折<else/>立减{pigcms{$nextlevel['boon']}{pigcms{$config['Currency_txt']}</if></div>
                  </div>
                  <div>
                      <div class="font_size16 mg_b10 color_bb9">
                          <if condition="$type eq 'money'">
                              {pigcms{$config['Currency_symbol']}<span class="font_b font_size30">{pigcms{$nextlevel['use_money']}</span>
                          <else/>
                              <span class="font_b font_size30">{pigcms{$nextlevel['integral']}</span>{pigcms{$config.score_name}
                          </if>
                      </div>
                      <div class="font_size14 align_right"><if condition="$nextlevel['validity'] egt 1">有效期{pigcms{$nextlevel['validity']}天<else/>永久有效</if></div>
                  </div>
              </li>
          </volist>
      </ul>   
    </div>
    <form id="recharge" action="{pigcms{:U('recharge')}" method="post" style="display:none">
        <input type="hidden" id="money" name="money" value="">
        <input type="hidden" id="label" name="label" value="wap_level_">
        <input type="hidden" id="label_level" name="label_level" value="0">
    </form>
    <button class="w100 color_f pd10 font_size16 font_b" id="openfire" style="background:#d7ac79;height: 1.2rem">立即开通</button>
</body>
	<script src="https://apps.bdimg.com/libs/jquery/1.7.0/jquery.min.js"></script>
    <script>
        var View={
            init:function(){
                 
                this.initClick();
          
            },
            setting:{
                is_chccked:false
            },
            initClick:function(){
            },
        }
        window.View=View;
        View.init();
    </script>
    <script>
        $(document).ready(function () {
            var type = "{pigcms{$type}";
            var now_money = "{pigcms{$now_user['now_money']}";
            var now_score = "{pigcms{$now_user['score_count']}";
            $(document).on('click','ul li',function(){
                $('ul li').removeClass('active');
                $(this).addClass('active');
            });
            $('#openfire').click(function(){
                if(type=='money'){
                    levelBuyupdate(now_money,$("li.active").data('money'),$("li.active").data('level'));
                }else{
                    levelToupdate(now_score,$("li.active").data('score'),$("li.active").data('level'));
                }
            });
            /*****等级升级******/
            var levelToupdateUrl="{pigcms{$config['site_url']}/index.php?g=User&c=Level&a=levelUpdate"
            function levelToupdate(currentscore,needscore,level){

                if(currentscore==0){
                    layer.open({
                        content:"您当前没有{pigcms{$config['score_name']}！",
                    });
                    return false;
                }
                currentscore=parseInt(currentscore);
                needscore=parseInt(needscore);
                if(currentscore>0 && needscore>0){

                    if(currentscore<needscore){
                        layer.open({
                            content:"您当前的{pigcms{$config['score_name']}不够升级！",
                        });
                        return false;
                    }


                    layer.open({
                        content:"升级会扣除您"+needscore+"{pigcms{$config['score_name']}\n您确认要升级吗？",
                        btn: ['确定','取消'],
                        yes:function(){
                            $.post(levelToupdateUrl,{use_score:1,nextlevel:level},function(ret){
                                if (ret && ret['error']==0) {
                                    layer.open({
                                        content:"恭喜您，升级成功！",
                                        btn: ['确定'],
                                        yes:function(){
                                            sessionStorage.setItem('levelUpdate_refresh', 1);
                                            history.back();
                                        }
                                    });
                                } else {
                                    alert(ret.msg);
                                    window.location.reload();
                                }
                            },'JSON');
                            return false;
                        }
                    });

                }
            }
            function levelBuyupdate(now_money,need_money,level){
				if(now_money < need_money){
					var recharge_money = need_money - now_money;
					var num = recharge_money;
					$('#money').val(num);
					$('#label').val($('#label').val()+num)
                    $('#label_level').val(level);
					$("#recharge").submit();

					return false;
				}

				layer.open({
					content:"升级会扣除您"+need_money+"{pigcms{$config['Currency_txt']}余额\n您确认要升级吗？",
					btn: ['确定','取消'],
					yes:function(){
						$.post(levelToupdateUrl,{use_money:1,nextlevel:level},function(ret){
							if (ret && ret['error']==0) {
								layer.open({
									content:"恭喜您，升级成功！",
									btn: ['确定'],
									yes:function(){
									    if (ret['gift']['gift_id'] > 0) {
                                            sessionStorage.setItem('levelUpdate_refresh', 1);
                                            sessionStorage.setItem('gift_level', 1);
                                        }
										history.back();
									}
								});
							} else {
                                alert(ret.msg);
								window.location.reload();
							}

						},'JSON');
						return false;
					}
				});
            }
        })
    </script>
</html>