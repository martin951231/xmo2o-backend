<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<meta http-equiv="Expires" content="-1">
<meta http-equiv="Cache-Control" content="no-cache">
<meta http-equiv="Pragma" content="no-cache">
<meta charset="utf-8">
<title>商家会员卡</title>
<link href="{pigcms{$static_path}css/css_whir.css" rel="stylesheet"/>
<script src="{pigcms{$static_path}js/jquery.min1.8.js"></script>
<!--[if lte IE 9]>
<script src="scripts/html5shiv.min.js"></script>
<![endif]-->
</head>
 <style type="text/css">
  .Disable{background:url({pigcms{$static_path}images/ssbj_03.png);  width: 100%; height: 100% ;border-radius: 10px; position: absolute; top: 10px; left:20px;
    padding: 8px 10px; text-align: center; font-size: 18px; color:#fff; font-weight: bold;  }
  .no-deals{padding-top:.6rem;padding-bottom:1.25rem;font-size:1.32rem;text-align:center;margin:0}
  body{background:white;}
  .Membership li .Membership_n{border-radius:10px;padding:5% 15px 10% 15px;background:-webkit-linear-gradient(top, #fe6299 35%,#f3527b 100%);color:#fff;box-shadow:0px 5px 20px 3px #d5d5d5;}
   .Mcard_end{line-height:24px;margin-top:15%;}
   .Membership .Member_top img{border-radius:100%;}
   .Mcard_end .fl{font-family:"Arial";}
 </style>

<body>

  <section class="Membership">
    <ul>
		<if condition="$card_list">
			<volist name="card_list" id="vo">
			  <li>
			  <a href="{pigcms{:U('My_card/merchant_card',array('mer_id'=>$vo['mer_id']))}">
				<div class="Membership_n" style="background: url({pigcms{:replace_file_domain($vo['bg'])}) center; background-size: 100%">
				  <div class="Member_top">
                    <div class="fl">
                        <if condition="$vo['merchant_logo']">
                            <img src="{pigcms{:thumb_img($vo['merchant_logo'],60)}" style="border:none;"/>
                        <elseif condition="$vo['mer_pic']"/>
                            <img src="{pigcms{:file_domain()}/upload/merchant/{pigcms{$vo.mer_pic}" style="border:none;"/>
                        </if>
                        <h2 style="color:{pigcms{$vo.numbercolor};">{pigcms{$vo.name}</h2>
                    </div>
				  </div>
				  <dd class="clr Mcard_end">
                    <div class="fl" style="color:{pigcms{$vo.numbercolor}"><if condition="$vo.wx_card_code neq ''">{pigcms{$vo.wx_card_code}<else />{pigcms{$vo.id}</if></div>
					<if condition="$vo.discount neq 0 AND $vo.discount neq 10"><div class="fr" style="color:{pigcms{$vo.numbercolor};">{pigcms{$vo.discount}折</div></if>
                   </dd>
				</div>
				<if condition="$vo.usercard_status eq 0 OR $vo.card_status eq 0 ">
					<div class="Disable" >此会员卡已禁用！</div>
				</if>
			  </a>  
			  </li>
			</volist>
        <else/>
            <div class="no-deals">您还没有领取过会员卡呢</div>
		</if>
    </ul>
  </section>
  
  {pigcms{$hideScript}
</body>

<script>
	$('.Membership_n').css({
	// height: ($(window).width() - 40) * 630 / 1052 + 'px',
	'height': '156px',
	'padding': 0,
	'position': 'relative',
	'background-size': '100% 100%',
	'background-repeat': 'no-repeat'});
	$('.Membership_n .Member_top').css({'padding-top':$('.Membership_n').height()*58/630+'px','width':'100%'});
    $('.Membership_n .Member_top .fl').css({'margin-left':$('.Membership_n').height()*58/630+'px'});
    $('.Membership_n .Member_top .fr').css({'margin-right':$('.Membership_n').height()*80/630+'px'});
    
    $('.Membership_n .Mcard_end').css({'bottom':$('.Membership_n').height()*70/630+'px','position':'absolute','width':'100%'});
    $('.Membership_n .Mcard_end .fl').css({'margin-left':$('.Membership_n').height()*58/630+'px','font-size':'20px'});
    $('.Membership_n .Mcard_end .fr').css({'margin-right':$('.Membership_n').height()*58/630+'px'});
		
	$(".Disable").each(function(){
		var w=$(".Membership_n").width();
		var h=$(".Membership_n").height();
		$(this).height(h).width(w).css({"line-height":+h+"px"})
	});
	
	// $(".Membership_n").each(function(i,item){
  //       var memcard = $(item).find('.Mcard_end .fl').html();
	// 	memcard = memcard.replace(/(.{4})/g, "$1 ");
	// 	$(item).find('.Mcard_end .fl').html(memcard);
	// });
</script>
</html>