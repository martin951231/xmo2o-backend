<include file="Public:header"/>
<form id="myform" method="post" action="{pigcms{:U('House/village_add')}" frame="true" refresh="true">
	<input type="hidden" name="cat_id" value="{pigcms{$now_category.cat_id}"/>
	<input type="hidden" name="property_id" value="{pigcms{$_GET['property_id']}"/>
	<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
		<tr>
			<th width="150">{pigcms{$config['house_name']}名称</th>
			<td><input type="text" class="input fl" name="village_name" size="30" placeholder="请输入{pigcms{$config['house_name']}名称" validate="maxlength:20,required:true"/></td>
		</tr>
		<tr >
			<th width="150">所在区域</th>
<!--			<td id="choose_cityarea" province_id="{pigcms{$village.province_id}" city_id="{pigcms{$village.city_id}" area_id="{pigcms{$village.area_id}" circle_id="-1" circle="-1"></td>-->

            <td id="choose_street" province_idss="{pigcms{$village.province_id}" city_idss="{pigcms{$village.city_id}" area_id="{pigcms{$village.area_id}" street_idss="{pigcms{$village.street_id}" community_idss="{pigcms{$village.community_id}" style="display:inline"></td>
        </tr>
		<tr>
			<th width="150">{pigcms{$config['house_name']}地址</th>
			<td><input type="text" class="input fl" name="village_address" size="30" placeholder="请输入{pigcms{$config['house_name']}地址" validate="maxlength:50,required:true"/></td>
		</tr>

        <tr>
            <th width="150">该小区楼栋是否超过100栋</th>
            <td>
                <span class="cb-enable"><label class="cb-enable"><span>是</span><input type="radio" name="village_single_support_digit" value="3"/></label></span>
                <span class="cb-disable"><label class="cb-disable selected"><span>否</span><input type="radio" name="village_single_support_digit" value="2" checked="checked"/></label></span>
                <em tips="选择是，楼栋号即可支持三位， 添加后不可变更" class="notice_tips"></em>
            </td>
        </tr>
        <tr>
            <th width="100">{pigcms{$config['house_name']}排序</th>
            <td><input type="text" class="input fl" name="village_sort" size="30" placeholder="请输入{pigcms{$config['house_name']}排序" /><span> 提示：排序值越大展示越靠前</span> </td>
        </tr>
        <if condition="$supplyStationArr && $supplyStationArr[0]">
            <tr>
                <th width="100">绑定供暖站</th>
                <td>
                    <div class="form-group">
                        <select class="col-xs-4 col-sm-4 col-md-2" name="supply_bind_village_id" id="supply_bind_village_id" style="width: 145px;">
                            <option style="display: none;">请选择绑定供暖站</option>
                            <volist name="supplyStationArr" id="vo">
                                <option value="{pigcms{$vo.village_id}">{pigcms{$vo['village_name']} [{pigcms{$vo.facilities_type_text}]</option>
                            </volist>
                        </select>
                    </div>
                </td>
            </tr>
        </if>
        <if condition="$developerArr && $developerArr[0]">
            <tr>
                <th width="100">绑定开发商</th>
                <td>
                    <div class="form-group">
                        <select class="col-xs-4 col-sm-4 col-md-2" name="developer_bind_village_id" id="developer_bind_village_id" style="width: 145px;">
                            <option style="display: none;">请选择绑定开发商</option>
                            <volist name="developerArr" id="vo">
                                <option value="{pigcms{$vo.village_id}">{pigcms{$vo['village_name']} [{pigcms{$vo.facilities_type_text}]</option>
                            </volist>
                        </select>
                    </div>
                </td>
            </tr>
        </if>
	</table>
	<div class="btn hidden">
		<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
		<input type="reset" value="取消" class="button" />
	</div>
</form>
<script type="text/javascript" src="{pigcms{$static_public}js/area_street.js?t=06"></script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script>
$(".cb-enable-integral").on('click',function(){
	$(".class-village-pay-integral").show(300);
});

$(".cb-disable-integral").on('click',function(){
	$(".class-village-pay-integral").hide(200);
});

$(".cb-enable-use-integral").on('click',function(){
	$(".class-village-pay-use-integral").show(300);
});

$(".cb-disable-use-integral").on('click',function(){
	$(".class-village-pay-use-integral").hide(200);
});


function addLink(domid,iskeyword){
	art.dialog.data('domid', domid);
	art.dialog.open('?g=Admin&c=Link&a=insert&iskeyword='+iskeyword,{lock:true,title:'插入链接或关键词',width:800,height:500,yesText:'关闭',background: '#000',opacity: 0.45});
}
</script>
<include file="Public:footer"/>