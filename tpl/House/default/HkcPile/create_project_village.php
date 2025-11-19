<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('HkcPile/charg_area_list')}">智能充电桩(好快充)</a>
            </li>
            <li class="active">充电区列表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <form  class="form-horizontal" method="post" onSubmit="return check_submit()"  action="{pigcms{:U('opt_create_project_village')}">
                        <div class="tab-content">
                            <input  type="hidden" name='id' value="{pigcms{$vinfo['id']}"/>

                            <div class="form-group">
                                <label class="col-sm-1"><label for="project_name">小区名称</label></label>
                                <input class="col-sm-2" size="20" name="project_name" id="project_name" type="text" placeholder="请输入小区名称" value="{pigcms{$vinfo['project_name']}" required/>
                                <span style="margin-left: 20px;">必填项</span>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-1"><label for="phone_name">小区联系人名称</label></label>
                                <input class="col-sm-2" size="20" name="phone_name" id="phone_name" placeholder="请输入小区联系人名称" type="text" value="{pigcms{$vinfo['phone_name']}" required/>
                                <span style="margin-left: 20px;">必填项</span>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-1"><label for="phone">小区联系人电话</label></label>
                                <input class="col-sm-2" size="20" name="phone" id="phone" placeholder="请输入小区联系人电话号码" type="number" value="{pigcms{$vinfo['phone']}" required/>
                               <span style="margin-left: 20px;">必填项，请正确填写！</span>
                            </div>

                            <div class="form-group">
                                <input type="hidden" value="" id="choose_province_name" name="province_name">
                                <input type="hidden" value="" id="choose_city_name" name="city_name">
                                <input type="hidden" value="" id="choose_area_name" name="area_name">
                                <label class="col-sm-1"><label>请选择省市区</label></label>
                                    <select id="choose_province_code" name="province_code"  class="col-sm-1" style="margin-right:10px;width:180px;" required>
                                    <option value="" data-region_name="" >请选择省</option>
                                     <volist name="province_data" id="pd">
                                            <option value="{pigcms{$pd.code}" data-region_name="{pigcms{$pd.name}">{pigcms{$pd.name}</option>
                                     </volist>
                                    </select>
                                    
                                    <select id="choose_citys_code" name="city_code" class="col-sm-1" style="margin-right:10px;width:180px;" onchange="choose_citys_code()" required>
                                    <option value="" data-region_name="">请选择市</option></select>

                                    <select id="choose_areas_code" name="area_code" class="col-sm-1" style="margin-right:10px;width:180px;" onchange="choose_areas_code()" required ><option value="">请选择区县</option></select>
                                    <span style="margin-left: 20px;">必选项，省市区都要选</span>
                            </div>
  
                            <div class="form-group">
                                <label class="col-sm-1"><label for="address">小区具体地址</label></label>
                                <input class="col-sm-2" size="20" name="address" id="address" placeholder="请输入小区具体地址" type="text" value="{pigcms{$vinfo['address']}" required/>
                                 <span style="margin-left: 20px;">必填项，填写除 选择的省市区外的地址</span>
                            </div>

                        </div>
                        <div class="space"></div>
                        <div class="clearfix form-actions">
                            <div class="col-md-offset-3 col-md-9">
                                <button class="btn btn-info" type="submit">
                                    <i class="ace-icon fa fa-check bigger-110"></i>
                                    确定
                                </button>
                            </div>
                        </div>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    function check_submit(){
        var  phone=$('#phone').val()||'';
        if(!phone || phone.length!=11){
            alert('请正确填写小区联系人电话！');
            return false;
        }
    }

   $('#choose_province_code').change(function(){
        var p_code=$(this).val()||'';
        var p_name=$('#choose_province_code option:selected').data('region_name')||'';
        $('#choose_province_name').val(p_name);
		show_citys_code(p_code,p_name,1);
    });
function choose_citys_code(){
   var c_code=$('#choose_citys_code').val()||'';
   var c_name=$('#choose_citys_code option:selected').data('region_name')||'';
   $('#choose_city_name').val(c_name);
	show_areas_code(c_code,c_name,2);
};
function choose_areas_code(){
   var a_code=$('#choose_areas_code').val()||'';
   var a_name=$('#choose_areas_code option:selected').data('region_name')||'';
   $('#choose_area_name').val(a_name);
};
//显示城市
function show_citys_code(p_code,p_name,type){
    if(p_code){
    	$.post("{pigcms{:U('HkcPile/get_work_unit_info')}",{region_code:p_code,region_name:p_name,region_type:type},function(result){
		if(result.region_data && result.region_data.length>0){

			var city_area_dom = '<select id="choose_citys_code" name="city_code" class="col-sm-1" style="margin-right:10px;width:180px;" onchange="window.choose_citys_code()" required > <option value="0" data-region_name="">请选择市</option>';
			$.each(result.region_data,function(i,item){
				city_area_dom+= '<option value="'+item.code+'" data-region_name="'+item.name+'">'+item.name+'</option>';
			});
			city_area_dom+= '</select>';
			    $('#choose_citys_code').replaceWith(city_area_dom);
			
			    show_areas_code('','',2);
		    }
	    },'json');
    }else{
         var city_area_dom = '<select id="choose_citys_code" name="city_code" class="col-sm-1" style="margin-right:10px;width:180px;" onchange="window.choose_citys_code()" required ><option value="0" data-region_name="">请选择市</option></select>';
         $('#choose_citys_code').replaceWith(city_area_dom);
         show_areas_code('','',2);
    }

}

//显示城市
function show_areas_code(c_code,c_name,type){
    if(c_code){
    	$.post("{pigcms{:U('HkcPile/get_work_unit_info')}",{region_code:c_code,region_name:c_name,region_type:type},function(result){
		if(result.region_data && result.region_data.length>0){

			var area_dom = '<select id="choose_areas_code" name="area_code" class="col-sm-1" style="margin-right:10px;width:180px;" onchange="window.choose_areas_code()" required > <option value="0" data-region_name="">请选择区县</option>';
			$.each(result.region_data,function(i,item){
				area_dom+= '<option value="'+item.code+'" data-region_name="'+item.name+'">'+item.name+'</option>';
			});
			area_dom+= '</select>';
		
			$('#choose_areas_code').replaceWith(area_dom);
		}
	    },'json');
    }else{
         var area_dom = '<select id="choose_areas_code" name="area_code" class="col-sm-1" style="margin-right:10px;width:180px;" onchange="window.choose_areas_code()" required ><option value="0" data-region_name="">请选择区县</option></select>';
         $('#choose_areas_code').replaceWith(area_dom);
    }

}

</script>

<include file="Public:footer"/>