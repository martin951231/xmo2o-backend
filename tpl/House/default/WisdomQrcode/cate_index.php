<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
<!--			<li>-->
<!--				<i class="ace-icon fa fa-shopping-cart gear-icon"></i>-->
<!--				功能库-->
<!--			</li>-->
<!--			<li><a href="{pigcms{:U('Openphone/phone')}">常用电话</a></li>-->
			<!-- <li class="active"><a href="{pigcms{:U('Openphone/phone',array('cat_id'=>$now_cat['cat_id']))}">电话列表</a></li> -->
		</ul>
	</div>
	<!-- 内容头部 -->
	<div class="page-content">
        <div class="page-content-area">
        	
<!--                <button class="btn btn-success" style="margin-bottom: 10px" onclick="location.href='{pigcms{:U('index')}'" <if condition="!in_array(199,$house_session['menus'])">disabled="disabled"</if>>分类管理</button> &nbsp;&nbsp;-->

            <if condition="in_array(541,$house_session['menus'])">
                <button class="btn btn-success" style="margin-bottom: 10px" onclick="cate_add()">添加计划</button>
                <else/>
                <button class="btn btn-success" style="margin-bottom: 10px"  disabled>添加计划</button>
            </if>&nbsp;&nbsp;
            <if condition="in_array(548,$house_session['menus'])">
                <button class="btn btn-success" style="margin-bottom: 10px" onclick="location.href='{pigcms{:U('qrcode_record_index')}'" >二维码记录</button>
                <else/>
                <button class="btn btn-success" style="margin-bottom: 10px" disabled>二维码记录</button>
            </if>&nbsp;&nbsp;
            <if condition="in_array(548,$house_session['menus'])">
                <button class="btn btn-success" style="margin-bottom: 10px" onclick="inspection_record_detail('{pigcms{$vo}')" >巡检统计</button>
                <else/>
                <button class="btn btn-success" style="margin-bottom: 10px" disabled>巡检统计</button>
            </if>
            <div class="row">

                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="15%">计划名称</th>
<!--                                    <th width="15%">设备介绍字段管理</th>-->
<!--                                    <th width="10%">二维码管理</th>-->
<!--                                    <th width="10%">记录内容管理</th>-->
                                    <th width="10%">负责人管理</th>
                                    <th width="15%">巡检定位记录</th>
<!--                                    <th width="15%">添加时间</th>-->
                                    <!-- <th width="15%">巡检统计</th> -->
                                    <th class="button-column" width="15%">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$dataList">
                                    <volist name="dataList" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td><div class="tagDiv">{pigcms{$vo.cate_name}</div></td>
<!--                                            <td>-->
<!--                                                <if condition="isset($vo['from_type']) AND $vo['from_type'] eq 1">-->
<!---->
<!--                                                <elseif condition="in_array(544,$house_session['menus'])" />-->
<!--                                                    <div class="tagDiv"><a style="color:lightseagreen;" href="{pigcms{:U('WisdomQrcode/cate_field_index',array('cate_id'=>$vo['id']))}">管理</a></div>-->
<!--                                                    <else />-->
<!--                                                    暂无此权限-->
<!--                                                </if>-->
<!--                                            </td>-->
<!--                                            <td>-->
<!--                                                <if condition="in_array(545,$house_session['menus'])">-->
<!--                                                    <div class="tagDiv"><a style="color:lightseagreen;" href="{pigcms{:U('WisdomQrcode/qrcode_index',array('cate_id'=>$vo['id']))}">管理</a></div>-->
<!--                                                    <else/>-->
<!--                                                    暂无此权限-->
<!--                                                </if>-->
<!--                                            </td>-->
<!--                                           	<td>-->
<!--                                                <if condition="isset($vo['from_type']) AND $vo['from_type'] eq 1">-->
<!---->
<!--                                                <elseif condition="in_array(546,$house_session['menus'])" />-->
<!--                                                    <div class="tagDiv"><a style="color:lightseagreen;" href="{pigcms{:U('WisdomQrcode/record_cate_index',array('cate_id'=>$vo['id']))}">管理</a></div>-->
<!--                                                    <else />-->
<!--                                                    暂无此权限-->
<!--                                                </if>-->
<!--                                            </td>-->
                                            <td>
                                                <if condition="isset($vo['from_type']) AND $vo['from_type'] eq 1">
                                                机器人：{pigcms{$vo.robot_name}<br/>
                                                机器人编码：{pigcms{$vo.robot_code}
                                                <elseif condition="in_array(547,$house_session['menus'])" />
                                                    <div class="tagDiv"><a style="color:lightseagreen;" href="{pigcms{:U('WisdomQrcode/perpon_index',array('cate_id'=>$vo['id']))}">管理</a></div>
                                                    <else/>
                                                    暂无此权限
                                                </if>
                                            </td>
                                <td>
                                    <span style="color:lightseagreen; cursor:pointer" onclick="position_record('{pigcms{$vo.id}','{pigcms{$vo.cate_name}')">查看</span>
                                </td>
<!--                                           	<td><div class="tagDiv">{pigcms{$vo.add_time|date="Y-m-d H:i:s",###}</div></td>-->
<!--                                巡检记录-->
                                <!-- <td>
                                    <span style="color:lightseagreen; cursor:pointer" onclick="inspection_record_detail('{pigcms{$vo}')">查看</span>
                                </td> -->
                                            <td class="button-column">
                                             <if condition="!isset($vo['from_type']) OR $vo['from_type'] neq 1">

<!--                                             <a style="width: 100px;" class="label label-sm label-info" title="同步记录内容" href="{pigcms{:U('qrcode_synchronize_recorded',array('id'=>$vo['id']))}">同步记录内容</a>&nbsp;-->
                                             
												<a style="width: 60px;" class="label label-sm label-info" title="修改" href="{pigcms{:U('cate_edit',array('id'=>$vo['id']))}">修改</a>&nbsp;

                                                <a style="width: 60px;" class="label label-sm label-info" title="删除" href="javascript:void(0)" onClick="if(confirm('确认删除该内容？')){location.href='{pigcms{:U('cate_del',array('id'=>$vo['id']))}'}">删除</a>
                                                </if>
                                           </td>
                                 
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="6" >没有任何数据。</td></tr>
                                </if>
                            </tbody>
                        </table>
                        {pigcms{$pagebar}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
function cate_add(){
	var url = "{pigcms{:U('cate_add')}";
	window.location.href = url;
}

function position_record(cate_id,title) {
    var url = "{pigcms{:U('WisdomQrcode/position_record_list')}"+"&cate_id=" + cate_id;
    layer.open({
        type: 2,
        title:'查看【'+title+'】记录',
        content: url,
        area: ['70%', '65%'],
        maxmin: false
    });
}

function inspection_record_detail(cate_id) {
    var url = "{pigcms{:U('WisdomQrcode/inspection_record_detail')}"+"&cate_id=" + cate_id;
    layer.open({
        type: 2,
        title:'查看巡检记录',
        content: url,
        area: ['80%', '90%'],
        maxmin: false
    });
}

</script>

<include file="Public:footer"/>