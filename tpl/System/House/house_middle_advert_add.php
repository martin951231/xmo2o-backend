<include file="Public:header"/>
<style>
    .btn-success{
        background-color: #87b87f!important;
        border-color: #87b87f;
        color: #FFF!important;
    }
</style>
	<form id="myform" method="post" action="{pigcms{:U('House/middle_adver_modify')}" enctype="multipart/form-data">
		<input type="hidden" name="cat_id" value="{pigcms{$now_category.cat_id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">广告名称</th>
				<td>
                    <if condition="!$config['house_advert_targeted_delivery'] || $config['house_advert_targeted_delivery'] neq 1">
                        <input type="text" class="input fl" name="name" size="20" placeholder="请输入名称" validate="maxlength:20,required:true"/>
                        <else/>
                        <input type="text" class="input fl" name="name" size="20" placeholder="请输入名称"  tips="建议尺寸715*206" validate="maxlength:20,required:true"/>
                    </if>
                </td>
			</tr>

			<tr>
				<th width="80">广告图片</th>
				<td><input type="file" class="input fl" name="pic" style="width:200px;" placeholder="请上传图片" validate="required:true"/></td>
			</tr>

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

            <if condition="$_GET['source'] neq 1">
            <tr >
                <th width="15%">发布范围</th>
                <td><input  style="margin-left: 0px;width: 85px;" onclick="chooseFloor()" value="选择发布小区" class="button" />
                    <input  style="margin-left: 0px;width: 85px;display: none" id="village_arr" name="village_arr" value="" type="text" /></td>
            </tr>
                <else/>
                <input type="hidden" name="source" value="1">
                <input type="hidden" name="is_platform" value="{pigcms{$_GET['is_platform']}">
            </if>
            <if condition="$config['house_visualize_switch'] eq 0 && $config['house_advert_targeted_delivery'] && $config['house_advert_targeted_delivery'] eq 1">
                <tr>
                    <th width="80">广告有效期：</th>
                    <td colspan="2">
                        <input type="text" class="input-text" name="effective_start_time" style="width:120px;" value="" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})"/>
                        至&nbsp;
                        <input type="text" class="input-text" name="effective_end_time" style="width:120px;" value="" tips="广告显示的起始时间和结束时间" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})"/>
                    </td>
                </tr>
                <tr>
                    <th width="80">播放时间段：</th>
                    <td colspan="2">
                        <input type="text" class="input-text" name="play_start_time" style="width:120px;" value="" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'HH:mm'})"/>
                        至&nbsp;
                        <input type="text" class="input-text" name="play_end_time" style="width:120px;" value="" tips="每天播放的时间段" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'HH:mm'})"/>
                    </td>
                </tr>
                <tr>
                    <th width="80">投放年龄范围：</th>
                    <td colspan="2">
                        <input type="number" min="0" class="input-text" name="lm_age_min" style="width:120px;" value=""/>
                        至&nbsp;
                        <input type="number" min="0" class="input-text" name="lm_age_max" style="width:120px;" value="" tips="设置后，该广告将只给该年龄段的用户展示"/>
                    </td>
                </tr>

                <tr>
                    <th width="80">投放性别</th>
                    <td colspan="2">
                        <select name="lm_sex" id="lm_sex" style="width: 126px;" tips="设置后，该广告将只给该性别的用户展示">
                            <option value="0">不限</option>
                            <option value="1">男</option>
                            <option value="2">女</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th width="80">虚拟浏览量：</th>
                    <td colspan="2">
                        <input type="number" min="0" class="input-text" name="virtual_look_num" style="width:120px;" value=""/>
                    </td>
                </tr>
                <tr>
                    <th width="80">虚拟点击量：</th>
                    <td colspan="2">
                        <input type="number" min="0" class="input-text" name="virtual_click_num" style="width:120px;" value=""/>
                    </td>
                </tr>
                <tr>
                    <th width="80">每日浏览量统计上限：</th>
                    <td colspan="2">
                        <input type="number" min="0" class="input-text" name="lm_day_look_max" style="width:120px;" tips="设置后，超出上限的数量不进行统计，但业主仍然可以浏览和点击广告。" value=""/>
                    </td>
                </tr>
                <tr>
                    <th width="80">每日点击量统计上限：</th>
                    <td colspan="2">
                        <input type="number" min="0" class="input-text" name="lm_day_click_max" style="width:120px;" tips="设置后，超出上限的数量不进行统计，但业主仍然可以浏览和点击广告。" value=""/>
                    </td>
                </tr>
            </if>

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
<script type="text/javascript" src="{pigcms{$static_public}js/area_street_village.js?t=04"></script>
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
            title:'发布小区',
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
</script>
<include file="Public:footer"/>