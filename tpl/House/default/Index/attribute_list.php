<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('attribute_list')}">业主属性设置</a>
            </li>
            <li class="active">主属性</li>
        </ul>
    </div>
	<div class="alert alert-info" style="margin:10px;">
        <button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>
		业主属性：添加业主属性后，添加/编辑业主可以标记不同的标签属性。<br/>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
        	<button class="btn btn-success" onclick="window.location.href = '{pigcms{:U("attribute_add")}';" <if condition="!in_array(281,$house_session['menus'])">disabled="disabled"</if>>添加业主属性</button>
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="10%">ID</th>
                                    <th width="20%">属性名</th>
                                    <th width="20%">添加时间</th>
                                    <th class="button-column" width="">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$attribute_list">
                                    <volist name="attribute_list" id="vo">
                                        <tr>
                                            
                                            <td><div class="tagDiv">{pigcms{$vo.pigcms_id}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.name}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.time|date='Y-m-d H:i:s',###}</div></td>
                                            
                                            <td class="button-column">
                                                <a style="width: 60px;" class="label label-sm label-info" title="编辑" href="{pigcms{:U('attribute_edit',array('id'=>$vo['pigcms_id']))}">编辑</a> &nbsp;
                                                <if condition="in_array(283,$house_session['menus'])">
                                                <a style="width: 60px;" class="label label-sm label-info" title="删除" href="javascript:void(0)" onClick="if(confirm('删除收费标准后，所有使用此标准的已收费数据，客户资料中的收费标准都将删除，确认要删除吗？')){location.href='{pigcms{:U('attribute_del',array('id'=>$vo['pigcms_id']))}'}">删除</a>
                                                </if>
                                           </td>
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="8" >没有任何业主属性。</td></tr>
                                </if>
                            </tbody>
                        </table>
                        {pigcms{$list.pagebar}
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
    	window.location.href = "{pigcms{:U('payment_standard_add',array('payment_id'=>$_GET['payment_id']))}";
    }
</script>
<include file="Public:footer"/>
