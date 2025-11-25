<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('index')}">智慧充电桩管理</a>
            </li>
            <li class="active">智慧充电桩列表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
        	<button class="btn btn-success" onclick="importAdd()">添加智慧充电桩</button>
            
            <a href="{pigcms{:U('import_smart_charge_add')}" class="btn btn-success fr">导入数据</a>
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="20%">编号</th>
                                    <th width="30%">二维码名称</th>
                                    <th width="10%">打开方式</th>
                                    <th width="10%">查看二维码</th>
                                    <th width="20%">添加时间</th>
                                    <th class="button-column" width="20%">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$list">
                                    <volist name="list" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td><div class="tagDiv">{pigcms{$vo.id}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.name}</div></td>
                                            
                                            <td><div class="tagDiv"><if condition='$vo["open_type"] eq 1'><span class="green">小程序</span><else /><span class="green">链接</span></if></div></td>
                                            <td>
                                            <div class="tagDiv">
                                                <a href="javascript:;" style="width: 60px;color:lightseagreen;" class="" title="" data-look-url="{pigcms{$vo['look_url']}" onclick="look('{pigcms{$vo['look_url']}')">点击查看</a>
                                            </div>
                                            </td>
                                            <td><div class="tagDiv">{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</div></td>
                                            <td class="am-text-middle">
												<div class="tpl-table-black-operation">
													<a title="编辑" href="{pigcms{:U('smart_add',array('id'=>$vo['id']))}">编辑</a>
												</div>
											</td>
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="12" >没有智慧充电桩。</td></tr>
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
	window.location.href = "{pigcms{:U('smart_add')}";
}
function importUserDetail(){
	window.location.href = "{pigcms{:U('User/detail_import')}";
}
function look(look_url) {
    $.post(look_url,{},function(data){
        console.log('data', data);
        if (data && data.look_img) {
            layer.open({
                type: 1,
                area: ['450px', '500px'],
                content: '<div style="text-align:center;"><img src="'+data['look_img']+'"/> </div>'
            });
        }
    },'json');
    console.log('look_url', look_url);
}
</script>
<include file="Public:footer"/>
