<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('Sms/sms_template')}">短信模板设置</a>
            </li>
            <li class="active">短信模板列表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">

        <div class="tabbable">
            <ul id="myTab" class="nav nav-tabs">
                <li>
                    <a href="{pigcms{:U('Sms/index')}">短信群发</a>
                </li>
                <li  class="active">
                    <a>短信模板设置</a>
                </li>

            </ul>
        </div>
        <div class="page-content-area">
            <div class="row">
                <div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
                    <a class="btn btn-success" onclick="add()">添加</a>
                </div>
                
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="20%">短信模板标题</th>
                                    <th width="20%">内容</th>
                                    <th width="5%">状态</th>
                                    <th width="20%">最后修改时间</th>
                                    <th class="button-column" >操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$list">
                                    <volist name="list" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td>{pigcms{$vo.template_name}</td>
                                            <td>{pigcms{$vo.template_content}</td>
                                            <td>
                                                <if condition="$vo['status'] eq 0">
                                                    <span>开启</span>
                                                    <else/>
                                                    <span style="color: red;">禁止</span>
                                                </if>
                                            </td>
                                            <td>
                                                <if condition="$vo['last_time'] gt 0">
                                                    {pigcms{$vo.last_time|date='Y-m-d H:i:s',###}
                                                    <else/>
                                                    {pigcms{$vo.add_time|date='Y-m-d H:i:s',###}
                                                </if>
                                            </td>
                                            
                                            <td class="button-column">
                                                <a style="width: 60px;" class="label label-sm label-info" title="编辑" onclick="add('{pigcms{$vo.template_id}')">编辑</a>&nbsp;&nbsp;

                                                <if condition="in_array(198,$house_session['menus'])">
                                                <a style="width: 60px;" class="label label-sm label-info" title="删除" href="javascript:void(0)" onclick="if(confirm('确认删除该条模板信息？')){location.href=\'{pigcms{:U('template_del',array('template_id'=>$vo['template_id']))}\'}">删除</a>
                                                </if>
                                           </td>
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="5" >没有发送信息</td></tr>
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
<script type="text/javascript" language="javascript">

function add(template_id) {
    var url = "{pigcms{:U('Sms/sms_template_add')}";
    if (template_id && template_id>0) {
        url += '&template_id='+template_id;
    }
    layer.open({
        type: 2,
        title:'添加短信模板',
        content: url,
        area: ['900px', '520px'],
        maxmin: true
    });
}

function close_all_page() {
    console.log('关闭弹窗');
    layer.closeAll();
    window.location.reload();
}


function send_property_one(pigcms_id , usernum){
	if(confirm('确认发送微信消息？')){
		var url = "{pigcms{:U('User/send_property')}";
		$.post(url,{'pigcms_id':pigcms_id,'usernum':usernum},function(data){
			if(data['status']){
				alert(data['msg']);
			}
		},'json')
	}
}
</script>
<include file="Public:footer"/>
