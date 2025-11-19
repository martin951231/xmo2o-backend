<include file="Publics:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<style>
    .page-content .btn-info { border: none;}
    .page-content .clearfix.form-actions {text-align: -webkit-center;}
</style>
<div class="page-content" style="background-color: white;">
        <div class="row" style="background-color: white;">
				<div class="col-xs-12">
					<form id="add_info" class="form-horizontal" method="post" action="__SELF__">
						<input  name="id" type="hidden"  value="{pigcms{$_GET['id']}"/>
						<div class="tab-content">
							<div class="form-group">
								<label class="col-sm-1"><label for="housesize">排序：</label></label>
								<input class="col-sm-2" size="20" name="sort" id="sort" placeholder="0,越大越靠前" type="text" value="{pigcms{$res.sort}"/>
							</div>
							<div class="form-group">
								<label class="col-sm-1"><label for="layer_room">类型：</label></label>

								<select class="col-sm-2" name="type" id="type" >


									<if condition="$res['type'] == 1">
									<option value="1">小区部门</option>
									</if>
									<if condition="$res['type'] == 2">
										<option value="2">物业部门</option>
									</if>
									<if condition="$res['type'] == 0">
										<option value="0">小区</option>
									</if>
								</select>

							</div>
							<div class="form-group">
								<label class="col-sm-1"><label for="layer_room">部门类型：</label></label>

								<select class="col-sm-2" name="section_type" id="section_type" >

									<option <if condition="$res['section_status'] == 8">selected="selected"</if> value="8-管理决策">管理决策</option>
									<option <if condition="$res['section_status'] == 7">selected="selected"</if>  value="7-人力资源">人力资源</option>
									<option <if condition="$res['section_status'] == 6">selected="selected"</if>  value="6-财务">财务</option>
									<option <if condition="$res['section_status'] == 5">selected="selected"</if>  value="5-招商开发">招商开发</option>
									<option <if condition="$res['section_status'] == 4">selected="selected"</if> value="4-安保">安保</option>
									<option <if condition="$res['section_status'] == 3">selected="selected"</if>  value="3-保洁">保洁</option>
									<option <if condition="$res['section_status'] == 1">selected="selected"</if>  value="1-维修">维修</option>
									<option <if condition="$res['section_status'] == 0">selected="selected"</if>  value="0-客服">客服</option>
									<option <if condition="$res['section_status'] == 9">selected="selected"</if> value="9-后勤">后勤</option>
									<option <if condition="$res['section_status'] == 99">selected="selected"</if> value="99-其他">其他</option>
								</select>

							</div>
							<if condition="$res['type'] == 0">
								<div class="form-group village_id">
									<label class="col-sm-1"><label for="layer_room">小区名称：</label></label>
									<input class="col-sm-2" size="20" name="house_village" id="house_village" type="text" value="pigcms{$res['name']}"/>
								</div>
							</if>
							<if condition="$res['type'] == 1 || $res['type'] == 2">
								<div class="form-group group_name">
									<label class="col-sm-1"><label for="phone">部门名称：</label></label>
									<input class="col-sm-2" size="20" name="group_name" id="name" type="text" value="{pigcms{$res['name']}"/>

								</div>
                            </if>
                            <if condition="$qy_txt neq ''">
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="">企业微信同步状态：</label></label>
                                    <span style="color: {pigcms{$qy_color}">{pigcms{$qy_txt}</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="">企业微信同步时间：</label></label>
                                    <span style="color: {pigcms{$qy_color}">{pigcms{$qy_time|default='暂无'}</span>
                                </div>
                            </if>
						</div>
						<div class="space"></div>
							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
									<button class="btn btn-info" type="submit">
										<i class="ace-icon fa fa-check bigger-110"></i>
										修改
									</button>
								</div>
							</div>
						</div>
					</form>
				</div>
    </div>

<script type="text/javascript">


$('#add_info').submit(function(){
    $.post($('#add_info').attr('action'),$('#add_info').serialize(),function(result){
        console.log('result', result);
        if (result && result.status==1) {
            layer.open({
                content: '编辑成功！添加数据不能实时显示，如需显示请刷新页面查看！',
                shadeClose: false,
                closeBtn: 0
                ,btn: ['确定']
                ,yes:function(index){
                    window.parent.close_all_page(result);
                }
            })
        } else if (result.info) {
            layer.open({
                content: result.info,
                shadeClose: false,
                closeBtn: 0
                ,btn: ['知道了']
            })
        } else {
            layer.open({content: '编辑失败'});
        }
    });
    return false;
});

$(document).ready(function (){


//	var village_id = "{pigcms{$_GET['village_id']}"
//	if(village_id){
////		$(".section_type").hide();
////		$(".group_name").hide();
//
//		$(".village_id").hide();
//	}else{
//		$(".section_type").hide();
//		$(".group_name").hide();
//	}
});


//$('#type').change(function(){
//	if($(this).children('option:selected').val() == 0){
//		$(".group_name").hide();
//		$(".village_id").show();
//		$(".section_type").hide();
//	}else if($(this).children('option:selected').val() == 1){
//		$(".group_name").show();
//		$(".village_id").hide();
//		$(".section_type").show();
//	}else if($(this).children('option:selected').val() == 2){
//		$(".group_name").show();
//		$(".village_id").hide();
//		$(".section_type").show();
//	}
//})
</script>
<include file="Publics:footer"/>
