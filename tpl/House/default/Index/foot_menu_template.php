<include file="Publics:header"/>
<style>
	.frame_form .input{
		height:30px;
	}
	.template {
			background-color: #eeeeee;
			margin-bottom: 20px;
			padding-top: 10px;
	}
	.template p {
      padding-bottom: 20px;
      padding-left: 20px;
			margin-top: 10px;
	}
	.template_radio {
      margin-left: 20px !important;
      margin-top: 0 !important;
      width: 15px;
      height: 15px;
      vertical-align: middle;
	}
	.template_span{
			display: inline-block;
      vertical-align: middle;
	}
</style>
	<form id="myform" method="post" enctype="multipart/form-data">
		<div style="padding-top: 5px;width: 415px;margin: auto;">
			<volist name="template" id="vo">
				<div class="template">
					<input name="template" style="cursor: pointer" class="template_radio" id="template_radio_{pigcms{$vo.id}" value="{pigcms{$vo.id}" type="radio" <if condition="$template_id eq $vo['id']" >checked</if> />
					<label for="template_radio_{pigcms{$vo.id}" style="cursor: pointer"><span class="template_span">{pigcms{$vo.template}</span></label>
					<p><img src="{pigcms{$vo.image}" /></p>
				</div>
			</volist>
		</div>
	</form>

<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/upyun.js"></script>
<link rel="stylesheet" href="{pigcms{$static_public}layui2/css/layui.css" media="all"/>
<script src="{pigcms{$static_public}layui2/layui.all.js" charset="utf-8"></script>
<script>
  $("input[name=template]").change(function(){
    var template = $(this).val();
    layer.confirm("是否保存底部导航模板设置?",{
      btn: ['确认', '取消']
    }, function () {
      var url ="{pigcms{:U('set_footer_template')}";
      $.post(url,{template: template,type: 0},function(data){
        layer.msg(data['msg'])
        setTimeout(function (){
          parent.location.reload()
          parent.layer.closeAll();
				},1000)
      },'json')
    }, function(){
      $("input[name=template][type=radio][value='{pigcms{$template_id}']").attr("checked",true);
    });
  });
</script>
<include file="Publics:footer"/>