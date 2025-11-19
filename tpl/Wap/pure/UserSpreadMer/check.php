<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<meta http-equiv="Expires" content="-1">
<meta http-equiv="Cache-Control" content="no-cache">
<meta http-equiv="Pragma" content="no-cache">
<meta charset="utf-8">
<title>提交申请为商家推广者</title>
<link href="{pigcms{$this->config['site_url']}/tpl/Wap/pure/static/css/spread_hb.css" rel="stylesheet"/>
<script src="/static/js/jquery-1.7.min.js"></script>
	<script type="text/javascript" src="http://www.group.com/tpl/Wap/pure/static/layer/layer.m.js" charset="utf-8"></script>
<style>


.code_n .pd15 {
	padding: 20px 50px;
}
p{
	font-size: 22px;
    padding: 13px;
}
.code_n .trade span {
	padding:0px;
}

.layermcont p{
  font-size:14px;
}
</style>
</head>
<body style="background: #f0efed;">
    <section class="code_n">
		<p>申请开通代理商,提高您的收入</p>
        <div class="img_info" style="min-height: 600px;width: calc(100% - 10px);padding:0 5px;">
            {pigcms{$check_html}
        </div>
        <if condition="$can_c2b_spread eq '0'">
        <div class="trade" id="trade" style="margin: 0px 10px;">
            <span>提交</span>
        </div>
        <elseif condition="$can_c2b_spread eq '1'" />
        <div class="trade" style="margin: 0px 10px;background: #9D9D9D;">
            <span>审核中</span>
        </div>
        <elseif condition="$can_c2b_spread eq '3'" />
        <div class="trade" style="margin: 0px 10px;background: #9D9D9D;">
            <span>审核未通过</span>
        </div>
        </if>
    </section>
</body>
</html>

<script>
    // 计算高度 减去 170
    var height_info = $(window).height() - 170;
    //$('.img_info').css({'height': height_info + 'px'});
	$(function(){
		$('.agent_rule').click(function(){
			layer.open({
				content:$('#agent_rule_html').html(),
				style:' height:400px; width:300px;overflow: auto; '
			});
		})
		$('#trade').click(function(){
			window.location.href="/wap.php?g=Wap&c=UserSpreadMer&a=reply";
		})
	})
</script>