<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('Unit/index')}"><?php echo (D('House_village')->getHouseAlias('single_name')?:'楼栋') ?>管理</a>
            </li>
			&nbsp;&nbsp;
            <li class="active"><?php echo (D('House_village')->getHouseAlias('single_name')?:'楼栋') ?>列表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
			<!-- 暂停手动添加的功能，统一使用导入
            <if condition="in_array(464,$house_session['menus'])">
				<button class="btn btn-success" onclick="importAdd()">添加楼栋</button>
            <else/>
				<button class="btn btn-success" disabled="disabled">添加楼栋</button>
            </if>
			-->
            <if condition="!$base_third_config && !$base_third_config['third_id'] && in_array(465,$house_session['menus'])">
                &nbsp;&nbsp;<button class="btn btn-success" onclick="importSingle()">导入<?php echo (D('House_village')->getHouseAlias('single_name')?:'楼栋') ?>/<?php echo (D('House_village')->getHouseAlias('floor_name')?:'单元') ?>/楼层</button>
            <else/>
                &nbsp;&nbsp;<button class="btn btn-success" title="{pigcms{$base_third_title}" disabled="disabled">导入<?php echo (D('House_village')->getHouseAlias('single_name')?:'楼栋') ?>/<?php echo (D('House_village')->getHouseAlias('floor_name')?:'单元') ?>/楼层</button>
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
                                    <th width="10%"><?php echo (D('House_village')->getHouseAlias('single_name')?:'楼栋') ?>名称</th>
                                    <th width="10%"><?php echo (D('House_village')->getHouseAlias('single_name')?:'楼栋') ?>编号</th>
                                    <th width="15%"><?php echo (D('House_village')->getHouseAlias('single_name')?:'楼栋') ?>层数</th>
                                    <th width="15%"><?php echo (D('House_village')->getHouseAlias('single_name')?:'楼栋') ?>面积(m²)</th>
                                    <th width="15%"><?php echo (D('House_village')->getHouseAlias('floor_name')?:'单元') ?>列表</th>
                                    <th width="10%">状态</th>
                                    <th class="button-column" width="25%">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$dataList">
                                    <volist name="dataList" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td><div class="tagDiv">{pigcms{$vo.sort}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.single_name}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.single_number|default='-'}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.layer_num}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.measure_area}</div></td>
                                            <td><div class="tagDiv"><a style="color:lightseagreen;" href="{pigcms{:U('Unit/index',array('single_id'=>$vo['id']))}">管理</a></div></td>

                                            <td><div class="tagDiv"><if condition='$vo["status"] eq 1'><span class="green">开启</span><else /><span class="red">关闭</span></if></div></td>
                                            <td class="button-column">
                                                <if condition="in_array(466,$house_session['menus'])">
                                                    <div class="tpl-table-black-operation">
                                                        <if condition="$is_cockpit eq '1'">
                                                        <a title="绘制区域" target="_blank"  onclick="goedit({pigcms{$vo['id']})" >绘制区域</a>&nbsp;
                                                        </if>
                                                        <if condition="!$base_third_config && !$base_third_config['third_id']">
                                                        <a title="编辑" href="{pigcms{:U('single_edit',array('id'=>$vo['id']))}">编辑</a>&nbsp;
                                                        <a class="item-delete tpl-table-black-operation-del" title="删除" onclick="delItemData('{pigcms{$vo.single_name}','{pigcms{$vo.id}')" href="javascript:;">删除</a>
                                                        </if>
                                                    </div>
                                                    <else/>
                                                    -
                                                </if>
                                           </td>
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="12" >没有任何<?php echo (D('House_village')->getHouseAlias('single_name')?:'楼栋') ?>。</td></tr>
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

    function goedit(id){
        sessionStorage.removeItem('is_edit');
		var is_edit=false;
		sessionStorage.setItem('is_edit', is_edit);
        window.open("/v20/public/platform/#/community/village/cockpit/hotZone?single_id="+id)
        return false;
    }
function importAdd(){
    window.location.href = "{pigcms{:U('single_add')}";
}
function importUnit(){
	window.location.href = "{pigcms{:U('unit_import')}";
}
function importSingle(){
    window.location.href = "{pigcms{:U('single_import')}";
}
 var single_delete_url= "{pigcms{:U('single_delete')}";
  function delItemData(single_name,idd){
      layer.confirm('确认删除'+"<?php echo (D('House_village')->getHouseAlias('single_name')?:'楼栋') ?>"+'是【'+single_name+'】这条数据？<br/>一旦删除无法恢复，请谨慎操作。', {
          btn: ['确定','取消'] //按钮
      }, function(){
          window.location.href=single_delete_url+'&id='+idd;
      });
  }
</script>
<include file="Public:footer"/>
