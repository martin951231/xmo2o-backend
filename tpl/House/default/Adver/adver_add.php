<include file="Publics:header"/>
<style>
	.frame_form .input{
		height:30px;
	}
</style>
	<form id="myform" method="post" action="{pigcms{:U('Adver/adver_modify')}" enctype="multipart/form-data">
		<input type="hidden" name="cat_id" value="{pigcms{$now_category.cat_id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">广告名称</th>
				<td><input type="text" class="input fl" name="name" size="20" placeholder="请输入名称" validate="maxlength:20,required:true"/></td>
			</tr>
			<tr>
				<th width="80">广告副标题</th>
				<td><input type="text" class="input fl" name="sub_name" size="20" placeholder="请输入副标题" validate="required:true"/></td>
			</tr>
			

			<tr>
				<th width="80">广告图片</th>
				<td><input type="file" class="input fl" name="pic" style="width:200px;height:35px;" placeholder="请上传图片" validate="required:true"/></td>
				
			</tr>
			<tr>
				<th width="80">背景颜色</th>
				<td><input type="text" class="input fl" name="bg_color" id="choose_color_add" style="width:120px;height:35px;" placeholder="可不填写" />&nbsp;&nbsp;<a href="javascript:void(0);" id="choose_color_box_add" style="line-height:28px;">点击选择颜色</a></td>
			</tr>

			<!--<if condition="$yingshan">
				<tr>
					<th width="80">圆角半径</th>
					<td>
						<div id="slideTest1" style="width: 100px;display: inline-block"></div>
						<div style="display: inline-block;margin-left: 15px">
							<input type="number" class="input fl" id="fillet_radius" style="width: 50px;margin-right: 20px" name="fillet_radius" min="0" max="50" size="20" value="0"/>
							<span style="line-height: 30px">
								圆角半径默认为0,最大50,圆角半径值越大，圆角越圆
							</span>
						</div>
					</td>
				</tr>
			</if>-->


			<tr>
                    <th width="80">链接地址</th>
                    <td>
                        <input type="text" class="input fl" name="url" id="url" style="width:200px;" placeholder="请填写链接地址" validate="maxlength:200,required:true,url:true"/>
                       	<?php if(!C('butt_open')){ ?>
                            <if condition="$now_category['cat_type'] neq '1'">
                                <a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('url', 0, 0)" data-toggle="modal">从功能库选择</a>
                            <else />
                                <a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('url', 0, 1)" data-toggle="modal">从功能库选择</a>
                            </if>
                        <?php } ?>
                    </td>
                </tr>

              <tr class="wxapp_open_wxapp">
                <th width="80">打开其他小程序</th>
                <td>
                   	<select name="wxapp_id" id="wxapp_id" tips="请选择一个想要打开的其他小程序，目前仅支持用户在平台小程序首页和{pigcms{$config.shop_alias_name}首页中打开其他小程序。">
	                   	<option value="">选择小程序</option>
	                   	<volist name='wxapp_list' id='vo'>
	                   		<option value="{pigcms{$vo.appid}">{pigcms{$vo.name}</option>
	                   	</volist>
                   </select>
                   <span style="position: relative;top: 2px;">选填</span>
                </td>
            </tr>
			<tr style="display: none;" class="wxapp_open_wxapp wxapp_page">
				<th width="80">小程序页面</th>
				<td><input type="text" class="input fl" name="wxapp_page" id="wxapp_page" style="width:200px;" value=""  placeholder="请输入小程序页面路径" tips="即打开另一个小程序时进入的页面路径，如果为空则打开首页；另一个小程序的页面路径请联系该小程序的技术人员询要；目前仅支持用户在平台小程序首页和{pigcms{$config.shop_alias_name}首页中打开其他小程序。"/></td>
			</tr>

			<tr>
				<th width="80">广告排序</th>
				<td><input type="text" class="input fl" name="sort" style="width:80px;" value="0" validate="maxlength:10,required:true,number:true"/></td>
			</tr>
			<tr>
				<th width="80">广告状态</th>
				<td>
					<span class="cb-enable"><label class="cb-enable selected"><span>启用</span><input type="radio" name="status" value="1" checked="checked" /></label></span>
					<span class="cb-disable"><label class="cb-disable"><span>关闭</span><input type="radio" name="status" value="0" /></label></span>
				</td>
			</tr>

		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
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

  layui.use('slider', function(){
    var slider = layui.slider;

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

  });

    $("#yes").click(function () {
        $("#adver_region").hide();
    })

    $("#no").click(function () {
        $("#adver_region").show();
    })

$("#wxapp_id").change(function(){
	if ($("#wxapp_id").val()) {
		$(".wxapp_page").show();
	}else{
		$("#wxapp_page").val('');
		$(".wxapp_page").hide();
	}
})

    $('#choose_color_box_add').click(function(){
        window.showTopColorPanel('Openadd',$(this).offset().top,$(this).height(),$(this).offset().left,'choose_color_add',function(color){
            console.log('data', color);
            $("#choose_color_add").val(color);
            $("#choose_color_add").css("background-color",color);
        })
    });

//	var admin_area_id = {pigcms{$system_session.area_id};
//	if(admin_area_id > 0){
//		$("#adver_currency").hide();
//		$("#no").trigger('click');
//	}
</script>
<include file="Publics:footer"/>