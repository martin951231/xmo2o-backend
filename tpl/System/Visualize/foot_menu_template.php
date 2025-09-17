<include file="Publics:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-fonts.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-rtl.min.css">
<script src="./tpl/House/default/static/layer/layer.js"></script>
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
					<input name="template" style="cursor: pointer" class="template_radio" id="template_radio_{pigcms{$vo.id}" value="{pigcms{$vo.id}" <if condition="$template_floor eq $vo['id']">checked</if> type="radio" />
					<label for="template_radio_{pigcms{$vo.id}" style="cursor: pointer"><span class="template_span">{pigcms{$vo.template}</span></label>
					<p><img src="{pigcms{$vo.image}" /></p>
				</div>
			</volist>
		</div>
	</form>

<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<link rel="stylesheet" href="{pigcms{$static_public}layui2/css/layui.css" media="all"/>
<script src="{pigcms{$static_public}layui2/layui.all.js" charset="utf-8"></script>
<script>
  $("input[name=template]").change(function(){
    var template = $(this).val();
    layer.confirm("将会覆盖平台所有小区底部导航模板，是否继续保存底部导航模板设置?",{
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
    },function () {
      $("input[name=template][type=radio][value='{pigcms{$template_floor}']").attr("checked",true);
    });
  });
</script>
<include file="Publics:footer"/>