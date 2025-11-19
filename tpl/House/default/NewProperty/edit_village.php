<include file="NewPublic:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" >
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('NewProperty/index',array('iframe'=>true))}"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>管理</a>
            </li>
            <li class="active">编辑<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?></li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <style>
		.line-span { line-height:34px;}
		.none { display:none}
        /*.title_right { text-align: right;}*/
	</style>
    <div class="page-content" style="background-color: white;">
        <div class="row" style="background-color: white;">
				<div class="col-xs-12">
					<form  class="form-horizontal" method="post" onSubmit="return check_submit()" action="__SELF__">
                    	<input class="col-sm-2" size="20" name="village_id" id="village_id" type="hidden" value="{pigcms{$villageinfo.village_id}"/>
						<div class="tab-content">
							编辑<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>
						</div>
						<div class="tab-content">
                            
							<div class="form-group">
								<label class="col-sm-2 title_right"><label for="housesize"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>名称：</label></label>
								<input class="col-sm-2" size="20" name="village_name" id="village_name" type="text" value="{pigcms{$villageinfo.village_name}"/>
							</div>
							<div class="form-group">
								<label class="col-sm-2 title_right"><label><?php echo (D('House_village')->getHouseAlias('house_name') ? :'小区').L_("地址").'：'?></label></label>
								<fieldset id="choose_street" province_idss="{pigcms{$villageinfo.province_id}" city_idss="{pigcms{$villageinfo.city_id}" area_id="{pigcms{$villageinfo.area_id}" street_idss="{pigcms{$villageinfo.street_id}" community_idss="{pigcms{$villageinfo.community_id}" style="display:inline"></fieldset>

							</div>
							<div class="form-group">
								<label class="col-sm-2 title_right"><label for="phone"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>详细地址：</label></label>
								<input class="col-sm-2" size="20" name="village_address" id="village_address" type="text" value="{pigcms{$villageinfo.village_address}"/>
							</div>

                            <div class="form-group">
                                <label class="col-sm-2 title_right"><label for="phone">该<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区').(D('House_village')->getHouseAlias('single_name')?:'楼栋')?>是否超过100<?php echo (D('House_village')->getHouseAlias('single_name')?:'栋')?>：</label></label>
                                <label style="padding-left:0px;padding-right:20px;"><input type="radio" class="ace" value="3" name="village_single_support_digit" disabled="disabled" <if condition="$villageinfo['village_single_support_digit'] eq 3"> checked="checked" </if> ><span style="z-index: 1" class="lbl"> 是 </span></label>
                                <label style="padding-left:0px;"><input type="radio" class="ace" value="2" name="village_single_support_digit" disabled="disabled" <if condition="$villageinfo['village_single_support_digit'] neq 3"> checked="checked" </if> ><span style="z-index: 1" class="lbl"> 否 </span></label>
                                <span class="form_tips" style="color: gray;margin-left:5px;">选择是，<?php echo (D('House_village')->getHouseAlias('single_name')?:'楼栋')?>号即可支持三位， 添加后不可变更</span>
                            </div>

                            <if condition="$house_desensitization_tel gt -1">
                            <div class="form-group">
                                <label class="col-sm-2 title_right"><label>手机号脱敏：</label></label>
                                <label style="padding-left:0px;padding-right:20px;"><input type="radio" class="ace" value="1" name="desensitization_tel" <if condition="$villageinfo['desensitization_tel'] eq 1"> checked="checked" </if> ><span style="z-index: 1" class="lbl"> 是 </span></label>
                                <label style="padding-left:0px;"><input type="radio" class="ace" value="0" name="desensitization_tel" <if condition="$villageinfo['desensitization_tel'] neq 1"> checked="checked" </if> ><span style="z-index: 1" class="lbl"> 否 </span></label>
                                <span class="form_tips" style="color: gray;margin-left:25px;">当系统后台 <span style="color:green;">社区配置=》手机号脱敏，开启时</span>，此处的开启设置才用效，否则无效。目前 <span style="color:green;">社区配置=》手机号脱敏 <span style="color: black;font-weight: bold;"><if condition="$house_desensitization_tel gt 0">已开启<else />未开启</if></span></span></span>
                            </div>
                            </if>

                            <if condition="$house_desensitization_idnum gt -1">
                            <div class="form-group">
                                <label class="col-sm-2 title_right"><label>身份证号脱敏：</label></label>
                                <label style="padding-left:0px;padding-right:20px;"><input type="radio" class="ace" value="1" name="desensitization_idnum"  <if condition="$villageinfo['desensitization_idnum'] eq 1"> checked="checked" </if> ><span style="z-index: 1" class="lbl"> 是 </span></label>
                                <label style="padding-left:0px;"><input type="radio" class="ace" value="0" name="desensitization_idnum" <if condition="$villageinfo['desensitization_idnum'] neq 1"> checked="checked" </if> ><span style="z-index: 1" class="lbl"> 否 </span></label>
                               <span class="form_tips" style="color: gray;margin-left:25px;">当系统后台 <span style="color:green;">社区配置=》身份证号脱敏，开启时</span>，此处的开启设置才用效，否则无效。目前 <span style="color:green;">社区配置=》身份证号脱敏 <span style="color: black;font-weight: bold;"><if condition="$house_desensitization_idnum gt 0">已开启<else />未开启</if></span></span></span>
                            </div>
                            </if>

							<div class="form-group">
								<label class="col-sm-2 title_right"><label for="plot_area"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>占地面积：</label></label>
								<input class="col-sm-2" size="20" name="plot_area" value="{pigcms{$villageinfo.plot_area}" type="text" style=""/>
								<span class="form_tips" style="color: gray;margin-left:5px;">单位：㎡(平方米)</span>
							</div>
							<div class="form-group">
								<label class="col-sm-2 title_right"><label for="building_area"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>建筑面积：</label></label>
								<input class="col-sm-2" size="20" name="building_area" value="{pigcms{$villageinfo.building_area}" type="text" style=""/>
								<span class="form_tips" style="color: gray;margin-left:5px;">单位：㎡(平方米)</span>
							</div>
							<div class="form-group">
								<label class="col-sm-2 title_right"><label for="public_area"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>公共场所面积：</label></label>
								<input class="col-sm-2" size="20" name="public_area" value="{pigcms{$villageinfo['public_area']}" type="text" style=""/>
								<span class="form_tips" style="color: gray;margin-left:5px;">单位：㎡(平方米)</span>
							</div>
							<div class="form-group">
								<label class="col-sm-2 title_right"><label for="green_area"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>绿化面积：</label></label>
								<input class="col-sm-2" size="20" name="green_area" value="{pigcms{$villageinfo['green_area']}" type="text" style=""/>
								<span class="form_tips" style="color: gray;margin-left:5px;">单位：㎡(平方米)</span>
							</div>
							<div class="form-group">
								<label class="col-sm-2 title_right"><label for="garage_area"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>车库面积：</label></label>
								<input class="col-sm-2" size="20" name="garage_area" value="{pigcms{$villageinfo['garage_area']}" type="text" style=""/>
								<span class="form_tips" style="color: gray;margin-left:5px;">单位：㎡(平方米)</span>
							</div>
							<div class="form-group">
								<label class="col-sm-2 title_right"><label for="design_parking_area"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>设计车位面积：</label></label>
								<input class="col-sm-2" size="20" name="design_parking_area" value="{pigcms{$villageinfo['design_parking_area']}" type="text" style=""/>
								<span class="form_tips" style="color: gray;margin-left:5px;">单位：㎡(平方米)</span>
							</div>
							<div class="form-group">
								<label class="col-sm-2 title_right"><label for="cars_park_num">车位数：</label></label>
								<input class="col-sm-2" size="20" name="cars_park_num" value="{pigcms{$villageinfo['cars_park_num']}" type="text" style=""/>
							</div>
							<div class="form-group">
								<label class="col-sm-2 title_right"><label for="remark">备注：</label></label>
								<textarea class="col-sm-2" size="20" name="remark" placeholder="请填写备注">{pigcms{$villageinfo['remark']}</textarea>
							</div>
						</div>
						<div class="space"></div>
							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
									<button class="btn btn-info" type="submit">
										<i class="ace-icon fa fa-check bigger-110"></i>
										保存
									</button>
								</div>
							</div>
						</div>
					</form>
				</div>
    </div>
</div>
<!--<script type="text/javascript" src="{pigcms{$static_path}js/area.js"></script>-->
<script>
	var choose_provincess="{pigcms{:U('Area/ajax_province')}",choose_cityss="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}",choose_circle="";
	var choose_street="{pigcms{:U('Area/ajax_street')}",choose_community="{pigcms{:U('Area/ajax_community')}";
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/area_street.js?t=13"></script>
<script type="text/javascript">

	var static_public="{pigcms{$static_public}",static_path="{pigcms{$static_path}",merchant_index="{pigcms{:U('Index/index')}",choose_province="{pigcms{:U('Area/ajax_province')}",choose_city="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}",choose_circle="";

 let submit = false;
function check_submit(){
	if($('#village_name').val()==''){
		alert("<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>"+'名称不能为空！');
		return false;
	}
	if($('#village_address').val()==''){
		alert("<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>"+'详细不能为空！');
		return false;
	}
    if (!submit){
        submit = true;
        return  true;
    }
    return  false;
}
</script>
<include file="NewPublic:footer"/>
