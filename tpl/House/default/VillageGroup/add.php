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
                    <form  id="add_info" class="form-horizontal" method="post" action="__SELF__">
						<input  name="fid" type="hidden"  value="{pigcms{$_GET['id']}"/>
						<input  name="village_id" type="hidden"  value="{pigcms{$_GET['village_id']}"/>
						<input  name="property_id" type="hidden"  value="{pigcms{$now_property['id']}"/>
						<div class="tab-content">
							<div class="form-group">
								<label class="col-sm-1"><label for="housesize">排序：</label></label>
								<input class="col-sm-2" size="20" name="sort" id="sort" placeholder="0,越大越靠前" type="text" value=""/>
							</div>
							<div class="form-group">
								<label class="col-sm-1"><label for="layer_room">类型：</label></label>

								<select class="col-sm-2" name="type" id="type" >


									<if condition="$village != undefined">
									<option value="1">小区部门</option>
									<else/>
										<option value="2">物业部门</option>
									</if>
									<if condition="$village == undefined">
										<option value="0">小区</option>
									</if>
								</select>

							</div>
							<div class="form-group section_type">
								<label class="col-sm-1"><label for="layer_room">部门类型：</label></label>

								<select class="col-sm-2" name="section_type" id="section_type" >
									<option value="8-管理决策">管理决策</option>
									<option value="7-人力资源">人力资源</option>
									<option value="6-财务">财务</option>
									<option value="5-招商开发">招商开发</option>
									<option value="4-安保">安保</option>
									<option value="3-保洁">保洁</option>
									<option value="1-维修">维修</option>
									<option value="0-客服">客服</option>
									<option value="9-后勤">后勤</option>
									<option value="99-其他">其他</option>
								</select>

							</div>
							<div class="form-group village_id">
								<label class="col-sm-1"><label for="layer_room">小区名称：</label></label>

								<select class="col-sm-2" name="village_name" id="name" >
									<volist name="house_village" id="vo">
									   <option value="{pigcms{$vo.village_id}-{pigcms{$vo.village_name}">{pigcms{$vo.village_name}</option>
									</volist>
								</select>

							</div>

							<div class="form-group group_name">
								<label class="col-sm-1"><label for="phone">部门名称：</label></label>
								<input class="col-sm-2" size="20" name="group_name" id="name" type="text" value=""/>

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


<script type="text/javascript">


function check_submit(){

//	if($('#village_name').val()==''){
//		alert('名称不能为空！');
//		return false;
//	}

}

$(document).ready(function (){


    $('#add_info').submit(function(){
        $.post($('#add_info').attr('action'),$('#add_info').serialize(),function(result){
            console.log('result', result);
            if (result && result.status==1) {
                layer.open({
                    content: '添加成功！添加数据实时显示但只是填充显示，如需查看完善数据显示请刷新页面查看！',
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
                layer.open({content: '添加失败'});
            }
        });
        return false;
    });

	var village_id = "{pigcms{$_GET['village_id']}"
	if(village_id){
//		$(".section_type").hide();
//		$(".group_name").hide();

		$(".village_id").hide();
	}else{
		$(".section_type").hide();
		$(".group_name").hide();
	}
});


$('#type').change(function(){
	if($(this).children('option:selected').val() == 0){
		$(".group_name").hide();
		$(".village_id").show();
		$(".section_type").hide();
	}else if($(this).children('option:selected').val() == 1){
		$(".group_name").show();
		$(".village_id").hide();
		$(".section_type").show();
	}else if($(this).children('option:selected').val() == 2){
		$(".group_name").show();
		$(".village_id").hide();
		$(".section_type").show();
	}
})
</script>
<include file="Publics:footer"/>
