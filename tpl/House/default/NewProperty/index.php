<include file="NewPublic:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('NewProperty/index',['iframe' => $_GET['iframe']])}">物业管理</a>
            </li>
            <li class="active">物业中心</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <if condition="$limit_info['room_limit_num'] gt 0">
                <div style="background-color: #dff0d8;padding: 10px;margin-bottom: 10px;color: red;">
                    可导入<?php echo (D('House_village')->getHouseAlias('room_name')?:'房间')?>数量为{pigcms{$limit_info['room_limit_num']},已导入{pigcms{$limit_info['room_num']}，剩余导入数量{pigcms{$limit_info['surplus_room_num']}
                </div>
            </if>
        	<div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
				<form method="get" action="{pigcms{:U('NewProperty/index')}" id="find-form">
                    <input type="hidden" name="c" value="NewProperty"/>
                    <input type="hidden" name="a" value="index"/>
                    <input type="hidden" name="iframe" value="{pigcms{$_GET['iframe']}"/>

                    <div  id="choose_street" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" street_idss="{pigcms{$_GET.street_idss}" community_idss="{pigcms{$_GET.community_idss}" style="display:inline"></div>
<!--                    <div id="choose_pca" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" style="display:inline"></div>-->
<!--					<span id="choose_cityarea" class=''></span>-->
					<input value="{pigcms{$village_name}" class="col-sm-2" name="village_name" id="" type="text" placeholder="请输入<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>名称" style="margin-right:10px;font-size:16px;height:30px;"/>
					&nbsp;&nbsp;
					<input class="btn btn-success" type="submit" id="" value="查询" />&nbsp;
					<a class="btn btn-success" onclick="location.href='{pigcms{:U('NewProperty/index',[\'iframe\' => $_GET[\'iframe\']])}'">重置</a>
				</form>

			</div>


            <if condition="$property['login_identify'] == 1">
			<button class="btn btn-success" onclick="addProperty({pigcms{$_GET['iframe']})">添加<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?></button>&nbsp;
            </if>

            
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover" style="position:relative;">
                            <thead>
                                <tr>
                                    <th width="3%">小区ID</th>
                                    <th width="5%"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>名称</th>
                                    <th width="5%">管理入口</th>
                                    <th width="5%">最后登录时间</th>
                                    <th width="10%"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>地址</th>
                                    <th width="5%">状态</th>
                                    <if condition="$property['login_identify'] == 1">
                                         <th width="5%">操作</th>
                                     </if>
                                </tr>
                            </thead>
                            <tbody class="class-checkbox">
                                <if condition="$dataList">
                                    <volist name="dataList" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                           <td>{pigcms{$vo.village_id}</td>
                                            <td>
                                                <div class="tagDiv">
                                                    {pigcms{$vo.village_name}
                                                    <if condition="$vo['facilities_type_text'] && $vo['facilities_type_text'] neq ''">
                                                        <span style="color:#2681f3;">[{pigcms{$vo.facilities_type_text}]</span>
                                                    </if>
                                                </div>
                                            </td>
											<td>
												<div class="tagDiv">
                                                    <if condition="$vo['status'] neq 2">
                                                        <a href="{pigcms{:U('NewProperty/village_login',array('village_id'=>$vo['village_id']))}" target="_blank" style="color:red;">管理入口</a>
                                                        <else/>
                                                        禁止状态
                                                    </if>
                                                </div>
											</td>
                                <if condition="$vo['last_time'] neq 0">
                                    <td><div class="tagDiv">{pigcms{$vo.last_time|date='Y-m-d H:i:s',###}</div></td>
                                    <else/>
                                    <td><div class="tagDiv">暂无登录</div></td>
                                </if>
                                            <td><div class="tagDiv">{pigcms{$vo.village_address}</div></td>
                                            <td>
                                                <if condition="$vo['status'] eq 1">
                                                    <div class="tagDiv">正常</div>
												<elseif condition="$vo['status'] eq 0"/>
													<div class="tagDiv">待完善信息</div>
												<else/>
													<div  class="tagDiv">禁止</div>
                                                </if>
                                            </td>

                                             <if condition="$property['login_identify'] == 1">
                                                <td><div class="tagDiv"> <a href="{pigcms{:U('NewProperty/edit_village',array('village_id'=>$vo['village_id']))}&iframe=true">编辑<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?></a>&nbsp;</div></td>
                                            </if>
                                             
                                        </tr>
                                    </volist>

                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="14" >没有任何数据。</td></tr>
                                </if>
                            </tbody>
                           
                        </table>
                        <if condition="$dataList">
                            <div style="float: right; padding-left: 5px; ">
                                <if condition="$totalPage gt 1">
                                    <input style="width: 50px;" type="text" name="page" id="page" value="{pigcms{$_GET['page']}">
                                    <button onclick='checkPage()' style="background-color: #3a87ad!important; color: #fff; width: 50px; height: 30px;">跳转</button>
                                </if>
                                <script>
                                    function checkPage(){
                                        var page = $("#page").val();
                                        if(page == ''){
                                            return false;
                                        }
                                        location.href = "{pigcms{:U('index')}"+"&page="+page;
                                    }
                                </script>
                            </div>
                            {pigcms{$pagebar}
                        </if>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="{pigcms{$static_path}js/area.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/area_street.js?t=08"></script>
<script>

	var static_path = "{pigcms{$static_path}";
//	var choose_province="{pigcms{:U('Area/ajax_province')}",choose_city="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}",choose_circle="";
    var choose_provincess="{pigcms{:U('Area/ajax_province')}",choose_cityss="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}",choose_circle="";
    var choose_street="{pigcms{:U('Area/ajax_street')}",choose_community="{pigcms{:U('Area/ajax_community')}";
    var ajax_check_village_add = "{pigcms{:U('NewProperty/ajax_check_village_add')}"
    var property_ids = "{pigcms{$property_id}"
</script>


<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript" language="javascript">

function addProperty(iframe){
    $.post(ajax_check_village_add,{property_id:property_ids},function(result){

        result = $.parseJSON(result);
          if(result.error == 0){
               alert(result.msg)
               return false;
           }else if(result.error == 1){
              if(!iframe)
              {
                  iframe = 0;
              }
               window.location.href = "{pigcms{:U('NewProperty/add')}"+'&iframe='+iframe;
               return false;
           }

    })

}
$(".checkbox_all").on('click',function(){
	
	if($(this).is(':checked')){
		$("input[name='delCheckbox[]']").prop("checked",true);  
	}else{
		$("input[name='delCheckbox[]']").prop("checked",false);  	
	}	
		
});
</script>
<include file="NewPublic:footer"/>
