<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('Sms/index')}">短信群发</a>
            </li>
            <li class="active">短信群发列表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">

        <div class="tabbable">
            <ul id="myTab" class="nav nav-tabs">
                <li class="active">
                    <a>短信群发</a>
                </li>
                <li>
                    <a href="{pigcms{:U('Sms/sms_template')}">短信模板设置</a>
                </li>

            </ul>
        </div>
        <div class="page-content-area">
            <div class="row">
                <div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
                    <a class="btn btn-success" onclick="add()">添加群发</a>
                </div>
                
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="20%">短信模板标题</th>
                                    <th width="20%">发送类型</th>
                                    <th width="20%">群发时间</th>
                                    <th class="button-column" >查看用户</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$list">
                                    <volist name="list" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td>{pigcms{$vo.template_name}</td>
                                            <td><if condition="$vo['send_type'] eq 1">指定身份发送<else />指定号码发送</if></td>
                                            <td>{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</td>
                                            
                                            <td class="button-column">
                                                
                                                <a style="width: 60px;" class="label label-sm label-info" title="编辑" href="{pigcms{:U('Sms/sms_index',array('send_id'=>$vo['send_id']))}">查看</a>&nbsp;&nbsp;

                                           </td>
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="12" >没有发送信息</td></tr>
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


    function add() {
        var url = "{pigcms{:U('Sms/sms_add')}";
        layer.open({
            type: 2,
            title:'添加短信群发',
            content: url,
            area: ['750px', '400px'],
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
