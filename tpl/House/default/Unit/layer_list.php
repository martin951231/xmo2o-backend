<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" >
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('Unit/single_list')}">楼栋管理</a>
            </li>
			&nbsp;&nbsp;
            <li class="active">楼层列表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
			<!-- 暂停手动添加的功能，统一使用导入
            <if condition="in_array(551,$house_session['menus'])">
                <button class="btn btn-success" onclick="importAdd()">添加楼层</button>
                <else/>
                <button class="btn btn-success" disabled="disabled">添加楼层</button>
            </if>
			-->
            <if condition="!$base_third_config && !$base_third_config['third_id'] && in_array(465,$house_session['menus'])">
                &nbsp;&nbsp;<button class="btn btn-success" onclick="importLayer()">导入楼栋/单元/楼层</button>
            <else/>
                &nbsp;&nbsp;<button class="btn btn-success" title="{pigcms{$base_third_title}" disabled="disabled">导入楼栋/单元/楼层</button>
            </if>
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">

                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="10%">排序</th>
                                    <th width="10%">楼层名称</th>
                                    <th width="10%">楼层编号</th>
                                    <th width="10%">楼栋名称</th>
                                    <th width="10%">单元名称</th>

                                    <th width="15%">状态</th>
                                    <th class="button-column" width="20%">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$dataList">
                                    <volist name="dataList" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td><div class="tagDiv">{pigcms{$vo.sort}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.layer_name}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.layer_number|default='-'}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.single_name}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.floor_name}</div></td>
                                            <td><div class="tagDiv"><if condition='$vo["status"] eq 1'><span class="green">开启</span><else /><span class="red">关闭</span></if></div></td>
                                            <td class="button-column">
                                                <if condition="!$base_third_config && !$base_third_config['third_id'] && in_array(552,$house_session['menus'])">
                                                    <div class="tpl-table-black-operation">
                                                        <a title="编辑" href="{pigcms{:U('layer_edit',array('id'=>$vo['id'],'single_id'=>$vo['single_id'],'floor_id'=>$vo['floor_id']))}">编辑</a>&nbsp;
                                                        <a class="item-delete tpl-table-black-operation-del" title="删除"  onclick="delItemData('{pigcms{$vo.layer_name}','{pigcms{$vo.id}','{pigcms{$vo.floor_id}','{pigcms{$vo.single_id}')" href="javascript:;">删除</a>
                                                    </div>
												<else/>
                                                    -
                                                </if>
                                           </td>
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="12" >没有任何楼层。</td></tr>
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
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script>
function importAdd(){
    window.location.href = "{pigcms{:U('layer_add',array('single_id'=>$_GET['single_id'],'floor_id'=>$_GET['floor_id']))}";
}
function importLayer(){
	window.location.href = "{pigcms{:U('layer_import')}";
}
var layer_delete_url= "{pigcms{:U('layer_delete')}";
function delItemData(layer_name,idd,floor_id,single_id){
    layer.confirm('确认删除楼层是【'+layer_name+'】这条数据？<br/>一旦删除无法恢复，请谨慎操作。', {
        btn: ['确定','取消'] //按钮
    }, function(){
        window.location.href=layer_delete_url+'&id='+idd+'&floor_id='+floor_id+'&single_id='+single_id;
    });
}
</script>
<include file="Public:footer"/>
