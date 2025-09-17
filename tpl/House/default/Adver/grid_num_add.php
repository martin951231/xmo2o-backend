<include file="Publics:header"/>
<style>
	.frame_form .input{
		height:30px;
	}
</style>
<div style="margin-bottom: 15px;background-color: #eef3f7;padding: 10px;">
	1、活动标题长度不超过4位<br/>
	2、排序越大，列表展示越靠前，移动端只展示排序最大的推荐活动
</div>
	<form id="myform" method="post" action="{pigcms{:U('Adver/grid_num_add')}" enctype="multipart/form-data">
		<input type="hidden" name="grid_num" value="{pigcms{$grid_num}"/>
		<input type="hidden" name="source" value="{pigcms{$_GET['source']}"/>

		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">活动标题</th>
				<td><input type="text" class="input fl" name="name" onblur="if(value.length>4) value=value.slice(0,4)" size="20" placeholder="请输入名称" validate="maxlength:20,required:true"/>
				</td>
			</tr>

			<if condition="$grid_num eq 3">
				<tr>
					<th width="80">背景图片</th>
					<td><input type="file" class="input fl" name="bg_image" style="width:200px;height:35px;" placeholder="请上传图片" validate="required:true"/></td>
				</tr>
			</if>

			<tr>
				<th width="80">活动类型</th>
				<td>
					<select name="type" class="input fl" validate="required:true">
						<option value="limited">秒杀活动</option>
						<option value="bargain">砍价活动</option>
						<option value="group">拼团活动</option>
					</select>
				</td>
			</tr>

			<tr>
				<th width="80">字体颜色</th>
				<td>
					<input type="text" readonly class="input fl" name="field_color" id="field_color_add" style="width:120px;height:35px;" placeholder="可不填写" validate="required:true" />&nbsp;&nbsp;
					<a href="javascript:void(0);" id="field_color_box_add" style="line-height:28px;">点击选择颜色</a>
				</td>
			</tr>

			<if condition="$grid_num eq 4">
				<tr>
					<th width="80">背景顶部颜色</th>
					<td>
						<input type="text" readonly class="input fl" name="bg_color" id="choose_color_add" style="width:120px;height:35px;" placeholder="可不填写" validate="required:true" />&nbsp;&nbsp;
						<a href="javascript:void(0);" id="choose_color_box_add" style="line-height:28px;">点击选择颜色</a>
					</td>
				</tr>

				<tr>
					<th width="80">背景底部颜色</th>
					<td>
						<input type="text" readonly class="input fl" name="bg_floor" id="choose_floor_color_add" style="width:120px;height:35px;" placeholder="可不填写" validate="required:true" />&nbsp;&nbsp;
						<a href="javascript:void(0);" id="choose_floor_color_box_add" style="line-height:28px;">点击选择颜色</a>
					</td>
				</tr>
			</if>

			<tr>
				<th width="80">图标</th>
				<td><input type="file" class="input fl" name="icon_image" style="width:200px;height:35px;" placeholder="请上传图标" validate="required:true"/></td>
			</tr>

			<tr>
				<th width="80">展示时间</th>
				<td><input type="text" class="input fl" name="start_time" id="start_time" style="width:260px;" placeholder="请选择时间范围" value="" validate="required:true"/></td>
			</tr>

			<tr>
				<th width="80">排序</th>
				<td><input type="text" class="input fl" name="sort" style="width:80px;" value="0" validate="maxlength:10,required:true,number:true"/></td>
			</tr>

		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>



<link rel="stylesheet" href="https://www.layuicdn.com/layui-v2.6.8/css/layui.css">
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/upyun.js"></script>
<link rel="stylesheet" href="{pigcms{$static_public}layui2/css/layui.css" media="all"/>
<script src="{pigcms{$static_public}layui2/layui.all.js" charset="utf-8"></script>
<script>

  layui.use(['slider','laydate'], function(){
    var slider = layui.slider;
    var laydate = layui.laydate;

    //渲染
    var ins1 = slider.render({
      elem: '#slideTest1',  //绑定元素
			min: 0,
      max: 50,
      change: function(value){
        $("#fillet_radius").val(value)
      }
    });

    $("#fillet_radius").on('keyup',function (e){
      ins1.setValue(e.delegateTarget.value)
		})

    //日期时间范围选择
    laydate.render({
      elem: '#start_time'
      ,type: 'datetime'
      ,range: true
    });

  });

    $("#yes").click(function () {
        $("#adver_region").hide();
    })

    $("#no").click(function () {
        $("#adver_region").show();
    })



    $('#choose_color_box_add').click(function(){
        window.showTopColorPanel('Openadd',$(this).offset().top,$(this).height(),$(this).offset().left,'choose_color_add',function(color){
            $("#choose_color_add").val(color);
            var cssText = $("#choose_color_add").attr("style") + ";background-color:"+ color +" !important;";
            $("#choose_color_add").css("cssText",cssText);
        })
    });

  $('#choose_floor_color_box_add').click(function(){
    window.showTopColorPanel('Openadd',$(this).offset().top,$(this).height(),$(this).offset().left,'choose_floor_color_add',function(color){
      $("#choose_floor_color_add").val(color);
      var cssText = $("#choose_floor_color_add").attr("style") + ";background-color:"+ color +" !important;";
      $("#choose_floor_color_add").css("cssText",cssText);
    })
  });

  $('#field_color_box_add').click(function(){
    window.showTopColorPanel('Openadd',$(this).offset().top,$(this).height(),$(this).offset().left,'field_color_add',function(color){
      $("#field_color_add").val(color);
      var cssText = $("#field_color_add").attr("style") + ";background-color:"+ color +" !important;";
      $("#field_color_add").css("cssText",cssText);
    })
  });
</script>
<include file="Publics:footer"/>