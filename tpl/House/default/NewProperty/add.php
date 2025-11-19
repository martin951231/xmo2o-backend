<include file="NewPublic:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" >
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('NewProperty/index',array('iframe'=>true))}"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>管理</a>
            </li>
            <li class="active">添加<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?></li>
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
						<div class="tab-content">
							添加<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>
						</div>
						<div class="tab-content">
                            
							<div class="form-group">
								<label class="col-sm-2 title_right"><label for="housesize"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>名称：</label></label>
								<input class="col-sm-2" size="20" name="village_name" id="village_name" type="text" value=""/>
							</div>
							<div class="form-group">
								<label class="col-sm-2 title_right"><label><?php echo (D('House_village')->getHouseAlias('house_name') ? :'小区').L_("地址").'：'?></label></label>
<!--								<fieldset id="choose_cityarea"></fieldset>-->
								<fieldset id="choose_street" province_idss="{pigcms{$village.province_id}" city_idss="{pigcms{$village.city_id}" area_id="{pigcms{$village.area_id}" street_idss="{pigcms{$village.street_id}" community_idss="{pigcms{$village.community_id}" style="display:inline"></fieldset>

							</div>
							<div class="form-group">
								<label class="col-sm-2 title_right"><label for="phone"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>详细地址：</label></label>
								<input class="col-sm-2" size="20" name="village_address" id="village_address" type="text" value=""/>
							</div>

                            <div class="form-group">
                                <label class="col-sm-2 title_right"><label for="phone">该<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区').(D('House_village')->getHouseAlias('single_name')?:'楼栋')?>是否超过100<?php echo (D('House_village')->getHouseAlias('single_name')?:'栋')?>：</label></label>
                                <label style="padding-left:0px;padding-right:20px;"><input type="radio" class="ace" value="3" name="village_single_support_digit"><span style="z-index: 1" class="lbl">是</span></label>
                                <label style="padding-left:0px;"><input checked="" type="radio" class="ace" value="2" name="village_single_support_digit"><span style="z-index: 1" class="lbl">否</span></label>
                                <span class="form_tips" style="color: gray;margin-left:5px;">选择是，<?php echo (D('House_village')->getHouseAlias('single_name')?:'楼栋')?>号即可支持三位， 添加后不可变更</span>
                            </div>

							<div class="form-group">
								<label class="col-sm-2 title_right"><label for="plot_area"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>占地面积：</label></label>
								<input class="col-sm-2" size="20" name="plot_area" value="" type="text" style=""/>
								<span class="form_tips" style="color: gray;margin-left:5px;">单位：㎡(平方米)</span>
							</div>
							<div class="form-group">
								<label class="col-sm-2 title_right"><label for="building_area"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>建筑面积：</label></label>
								<input class="col-sm-2" size="20" name="building_area" value="" type="text" style=""/>
								<span class="form_tips" style="color: gray;margin-left:5px;">单位：㎡(平方米)</span>
							</div>
							<div class="form-group">
								<label class="col-sm-2 title_right"><label for="public_area"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>公共场所面积：</label></label>
								<input class="col-sm-2" size="20" name="public_area" value="" type="text" style=""/>
								<span class="form_tips" style="color: gray;margin-left:5px;">单位：㎡(平方米)</span>
							</div>
							<div class="form-group">
								<label class="col-sm-2 title_right"><label for="green_area"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>绿化面积：</label></label>
								<input class="col-sm-2" size="20" name="green_area" value="" type="text" style=""/>
								<span class="form_tips" style="color: gray;margin-left:5px;">单位：㎡(平方米)</span>
							</div>
							<div class="form-group">
								<label class="col-sm-2 title_right"><label for="garage_area"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>车库面积：</label></label>
								<input class="col-sm-2" size="20" name="garage_area" value="" type="text" style=""/>
								<span class="form_tips" style="color: gray;margin-left:5px;">单位：㎡(平方米)</span>
							</div>
							<div class="form-group">
								<label class="col-sm-2 title_right"><label for="design_parking_area"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>设计车位面积：</label></label>
								<input class="col-sm-2" size="20" name="design_parking_area" value="" type="text" style=""/>
								<span class="form_tips" style="color: gray;margin-left:5px;">单位：㎡(平方米)</span>
							</div>
							<div class="form-group">
								<label class="col-sm-2 title_right"><label for="cars_park_num">车位数：</label></label>
								<input class="col-sm-2" size="20" name="cars_park_num" value="" type="text" style=""/>
							</div>
							<div class="form-group">
								<label class="col-sm-2 title_right"><label for="remark">备注：</label></label>
								<textarea class="col-sm-2" size="20" name="remark" placeholder="请填写备注"></textarea>
							</div>
						</div>
						<div class="space"></div>
							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
									<button class="btn btn-info" type="submit">
										<i class="ace-icon fa fa-check bigger-110"></i>
										添加
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
