<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('House/open_adver_amend')}" enctype="multipart/form-data">
		<input type="hidden" name="id" value="{pigcms{$now_adver.id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">广告名称</th>
				<td>

                    <if condition="!$config['house_advert_targeted_delivery'] || $config['house_advert_targeted_delivery'] neq 1">
                        <input type="text" class="input fl" name="name" value="{pigcms{$now_adver.name}" size="20" placeholder="请输入名称" validate="maxlength:20,required:true"/>
                        <else/>
                        <input type="text" class="input fl" name="name" value="{pigcms{$now_adver.name}" size="20" placeholder="请输入名称"  tips="建议尺寸715*278" validate="maxlength:20,required:true"/>
                    </if>
                </td>
			</tr>

			<tr>
				<th width="80">广告现图</th>
				<td><img src="{pigcms{$now_adver.pic}" style="width:260px;height:80px;" class="view_msg"/></td>
			</tr>
			<tr>
				<th width="80">广告图片</th>
				<td><input type="file" class="input fl" name="pic" style="width:200px;" placeholder="请上传图片" tips="建议尺寸750*988 不修改请不上传！上传新图片，老图片会被自动删除！"/></td>
			</tr>


            <tr>
                <th width="80">链接地址</th>
                <td>
                    <input type="text" class="input fl" name="url" id="url" value="{pigcms{$now_adver.url}" style="width:200px;" placeholder="请填写链接地址" validate="maxlength:200,required:true,url:true"/>
                    <?php if(!C('butt_open')){ ?>
                        <if condition="$now_category['cat_type'] neq 1">
                            <a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('url', 0, 0)" data-toggle="modal">从功能库选择</a>
                            <else />
                            <a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('url', 0, 1)" data-toggle="modal">从功能库选择</a>
                        </if>
                    <?php } ?>
                </td>
            </tr>


            <tr >
                <th width="15%">发布范围</th>
                <td><input  style="margin-left: 0px;width: 85px;" onclick="chooseFloor()" value="选择发布小区" class="button" />
                    <input  style="margin-left: 0px;width: 85px;display: none" id="village_arr" name="village_arr" value="{pigcms{$now_adver['village_arr']}" type="text" /></td>
            </tr>

            <if condition="$config['house_advert_targeted_delivery'] && $config['house_advert_targeted_delivery'] eq 1">
                <tr>
                    <th width="80">广告有效期：</th>
                    <td colspan="2">
                        <input type="text" class="input-text" name="effective_start_time"  style="width:120px;" value="{pigcms{$now_adver.effective_start_time|date='Y-m-d H:i',###}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})"/>
                        至&nbsp;
                        <input type="text" class="input-text" name="effective_end_time" style="width:120px;" value="{pigcms{$now_adver.effective_end_time|date='Y-m-d H:i',###}" tips="广告显示的起始时间和结束时间" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})"/>
                    </td>
                </tr>
                <tr>
                    <th width="80">播放时间段：</th>
                    <td colspan="2">
                        <input type="text" class="input-text" name="play_start_time" value="{pigcms{$now_adver['play_start_time_txt']}" style="width:120px;" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'HH:mm'})"/>
                        至&nbsp;
                        <input type="text" class="input-text" name="play_end_time" value="{pigcms{$now_adver['play_end_time_txt']}" style="width:120px;" tips="每天播放的时间段" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'HH:mm'})"/>
                    </td>
                </tr>
                <tr>
                    <th width="80">投放年龄范围：</th>
                    <td colspan="2">
                        <input type="number" min="0" class="input-text" name="lm_age_min" value="{pigcms{$now_adver.lm_age_min}" style="width:120px;"/>
                        至&nbsp;
                        <input type="number" min="0" class="input-text" name="lm_age_max" value="{pigcms{$now_adver.lm_age_max}"  style="width:120px;" tips="设置后，该广告将只给该年龄段的用户展示"/>
                    </td>
                </tr>

                <tr>
                    <th width="80">投放性别</th>
                    <td colspan="2">
                        <select name="lm_sex" id="lm_sex" value="{pigcms{$now_adver.lm_sex}" style="width: 126px;" tips="设置后，该广告将只给该性别的用户展示">
                            <option value="0" <if condition="$now_adver['lm_sex'] eq 0">selected="selected"</if>>不限</option>
                            <option value="1" <if condition="$now_adver['lm_sex'] eq 1">selected="selected"</if>>男</option>
                            <option value="2" <if condition="$now_adver['lm_sex'] eq 2">selected="selected"</if>>女</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th width="80">虚拟浏览量：</th>
                    <td colspan="2">
                        <input type="number" min="0" class="input-text" value="{pigcms{$now_adver.virtual_look_num}" name="virtual_look_num" style="width:120px;"/>
                    </td>
                </tr>
                <tr>
                    <th width="80">虚拟点击量：</th>
                    <td colspan="2">
                        <input type="number" min="0" class="input-text" name="virtual_click_num" style="width:120px;" value="{pigcms{$now_adver.virtual_click_num}"/>
                    </td>
                </tr>
                <tr>
                    <th width="80">每日浏览量统计上限：</th>
                    <td colspan="2">
                        <input type="number" min="0" class="input-text" value="{pigcms{$now_adver.lm_day_look_max}" name="lm_day_look_max" style="width:120px;" tips="设置后，超出上限的数量不进行统计，但业主仍然可以浏览和点击广告。"/>
                    </td>
                </tr>
                <tr>
                    <th width="80">每日点击量统计上限：</th>
                    <td colspan="2">
                        <input type="number" min="0" class="input-text" value="{pigcms{$now_adver.lm_day_click_max}" name="lm_day_click_max" style="width:120px;" tips="设置后，超出上限的数量不进行统计，但业主仍然可以浏览和点击广告。"/>
                    </td>
                </tr>
            </if>

            <tr>
                <th width="80">背景颜色</th>
                <td><input type="text" class="input fl" name="bg_color" value="{pigcms{$now_adver.bg_color}" id="choose_color" style="width:120px;background-color:{pigcms{$now_adver.bg_color}" placeholder="可不填写" tips="请点击右侧按钮选择颜色，用途为如果图片尺寸小于屏幕时，会被背景颜色扩充，主要为首页使用。"/>&nbsp;&nbsp;<a href="javascript:void(0);" id="choose_color_box" style="line-height:28px;">点击选择颜色</a></td>
            </tr>
			<tr>
				<th width="80">广告排序</th>
				<td><input type="text" class="input fl" name="sort" style="width:80px;" value="{pigcms{$now_adver.sort}" validate="maxlength:10,required:true,number:true"/></td>
			</tr>
			<tr>
				<th width="80">广告状态</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$now_adver['status'] eq 1">selected</if>"><span>启用</span><input type="radio" name="status" value="1" <if condition="$now_adver['status'] eq 1">checked="checked"</if>/></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$now_adver['status'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="status" value="0" <if condition="$now_adver['status'] eq 0">checked="checked"</if>/></label></span>
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
function addLink(domid, iskeyword, type){
	art.dialog.data('domid', domid);
	if (type == 1) {
		art.dialog.open('?g=Admin&c=LinkPC&a=insert&iskeyword='+iskeyword,{lock:true,title:'插入链接或关键词',width:800,height:500,yesText:'关闭',background: '#000',opacity: 0.45});
	} else {
		art.dialog.open('?g=Admin&c=Link&a=insert&iskeyword='+iskeyword,{lock:true,title:'插入链接或关键词',width:800,height:500,yesText:'关闭',background: '#000',opacity: 0.45});
	}
}

function chooseFloor() {
    var url = "{pigcms{:U('House/house_nav_village_list')}";
    // var href = url + '&selecteds='+selecteds;
    var village_id= $('#village_arr').val();
    var href = url+'&type=1';
    var index= layer.open({
        type: 2,
        title:'发布小区',
        content: href+'&village_id=,'+village_id,
        area: ['95%', '95%'],
        maxmin: false,
        cancel: function (index, layero) {
            var body = layer.getChildFrame('body', index); //得到iframe页的body内容
            var BDDM = body.find("#village_id").val();
            if(!BDDM){
                $("#village_arr").attr("value", "");
            }else{
                $("#village_arr").attr("value", BDDM);
              //   $('#village_arr').val(BDDM);
            }
            console.log('2222',BDDM)
            layer.close(index);
        }

    });
}
</script>
<script>

</script>
<script>
    $(".ant-modal-root",parent.document).css("pointer-events","none")
</script>
<include file="Public:footer"/>