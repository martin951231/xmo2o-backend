<include file="Public:header"/>
<style>
	.frame_form .input{
		height:30px;
	}
    .btn-success{
        background-color: #87b87f!important;
        border-color: #87b87f;
        color: #FFF!important;
    }
</style>
<if condition="$_GET['row_type'] neq single && $yingshan AND $jsdhwy_new_wap_index lt 1">
	<div style="margin-left: 10px;color: #9a9999">
		<span> 说明：</span>
		<p>1.角标最多只能输入四位</p>
	</div>
</if>
	<form id="myform" method="post" action="{pigcms{:U('House/nav_modify_new')}" enctype="multipart/form-data">
		<input type="text" style="display: none" name="row_type" value="{pigcms{$_GET['row_type']}"/>
        <input type="hidden"  name="block_type_id" value="{pigcms{$block_type_id}"/>
        <input type="hidden"  name="is_community_platform" value="{pigcms{$is_community_platform}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">名称</th>
				<td><input type="text" class="input fl" style="height:20px" name="name" size="20" placeholder="请输入名称" validate="maxlength:20,required:true"/></td>
			</tr>
            <if condition="$_GET['source'] neq 1">
			<tr >
				<th width="15%">发布范围</th>
                <td><input  style="margin-left: 0px;width: 85px;" onclick="chooseFloor()" value="选择发布<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>" class="button" />
                 <input  style="margin-left: 0px;width: 85px;display: none" id="village_arr" name="village_arr" value="" type="text" /></td>
			</tr>
                <else/>
                <input type="hidden" name="source" value="1">
            </if>
			<tr>
				<th width="80">图标</th>
				<td><input type="file" class="input fl" name="img" style="width:200px;height:20px;" placeholder="请上传图片" validate="required:true"/></td>

			</tr>
			<tr>
				<th width="80">链接地址</th>
				<td>
					<input type="text" class="input fl" name="url" id="url" style="width:200px;height:20px;" placeholder="请填写链接地址" validate="maxlength:200,required:true,url:true"/>
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
				<td><input type="text" class="input fl" name="wxapp_page" id="wxapp_page" style="width:200px;height:25px;" value=""  placeholder="请输入小程序页面路径" tips="即打开另一个小程序时进入的页面路径，如果为空则打开首页；另一个小程序的页面路径请联系该小程序的技术人员询要；目前仅支持用户在平台小程序首页和{pigcms{$config.shop_alias_name}首页中打开其他小程序。"/></td>
			</tr>
			<tr>
				<th width="80">排序</th>
				<td><input type="text" class="input fl" name="sort" style="width:80px;height:20px;" value="0" validate="maxlength:10,required:true,number:true"/></td>
			</tr>
			<tr>
				<th width="80">是否开启</th>
				<td>
					<span class="cb-enable"><label class="cb-enable selected"><span>开启</span><input type="radio" name="status" value="1" checked="checked" /></label></span>
					<span class="cb-disable"><label class="cb-disable"><span>关闭</span><input type="radio" name="status" value="0" /></label></span>
				</td>
			</tr>

			<if condition="$_GET['row_type'] neq single AND $yingshan AND $jsdhwy_new_wap_index lt 1">
				<tr>
					<th width="80">角标名称</th>
					<td><input type="text" class="input fl" name="mark_name" style="width:120px;height:20px;" onblur="if(value.length>4) value=value.slice(0,4)" size="20" placeholder="请输入名称"/></td>
				</tr>

				<tr>
					<th width="80">角标背景颜色</th>
					<td>
						<input type="text" readonly class="input fl" name="mark_bg" id="mark_bg" style="width:120px;height:20px;" placeholder="不可填写" />&nbsp;&nbsp;
						<a href="javascript:void(0);" id="mark_bg_color" style="line-height:28px;">点击选择颜色</a>
					</td>
				</tr>
			</if>

		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>


<script type="text/javascript" src="{pigcms{$static_public}js/area_street.js?t=02"></script>
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/upyun.js"></script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script>

	function addLink(domid, iskeyword, type) {
		art.dialog.data('domid', domid);
    <if condition="$_GET['source'] neq 1">
		if (type == 1) {
			art.dialog.open('?g=Admin&c=LinkPC&a=insert&iskeyword=' + iskeyword, {
				lock: true,
				title: '插入链接或关键词',
				width: 800,
				height: 500,
				yesText: '关闭',
				background: '#000',
				opacity: 0.45
			});
		} else {
			art.dialog.open('?g=Admin&c=Link&a=insert&iskeyword=' + iskeyword, {
				lock: true,
				title: '插入链接或关键词',
				width: 800,
				height: 500,
				yesText: '关闭',
				background: '#000',
				opacity: 0.45
			});
		}
    <else/>

        if (type == 1) {
            art.dialog.open('?g=Admin&c=LinkPC&a=visualize&iskeyword=' + iskeyword, {
                lock: true,
                title: '插入链接或关键词',
                width: 800,
                height: 500,
                yesText: '关闭',
                background: '#000',
                opacity: 0.45
            });
        } else {
            art.dialog.open('?g=Admin&c=Link&a=visualize&iskeyword=' + iskeyword, {
                lock: true,
                title: '插入链接或关键词',
                width: 800,
                height: 500,
                yesText: '关闭',
                background: '#000',
                opacity: 0.45
            });
        }
    </if>
	}

    function chooseFloor() {
        var url = "{pigcms{:U('House/house_nav_village_list')}";
        // var href = url + '&selecteds='+selecteds;
        var village_id= $('#village_arr').val();
        var href = url+'&type=1';
       var index= layer.open({
            type: 2,
            title:'发布'+"<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>",
           content: href+'&village_id='+village_id,
            area: ['95%', '95%'],
            maxmin: false,
            cancel: function (index, layero) {
                var body = layer.getChildFrame('body', index); //得到iframe页的body内容
                var BDDM = body.find("#village_id").val();
                $('#village_arr').val(BDDM);

               console.log(BDDM);
                layer.close(index);
            }

        });
    }
$("#wxapp_id").change(function(){
	if ($("#wxapp_id").val()) {
		$(".wxapp_page").show();
	}else{
		$("#wxapp_page").val('');
		$(".wxapp_page").hide();
	}
})
</script>
<script>
    $(".ant-modal-root",parent.document).css("pointer-events","none")

    $('#mark_bg_color').click(function(){
      window.showTopColorPanel('Openadd',$(this).offset().top,$(this).height(),$(this).offset().left,'mark_bg',function(color){
        $("#mark_bg").val(color);
        var cssText = $("#mark_bg").attr("style") + ";background-color:"+ color +" !important;";
        $("#mark_bg").css("cssText",cssText);
      })
    });
</script>
<include file="Public:footer"/>