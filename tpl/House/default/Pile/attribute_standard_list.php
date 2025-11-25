<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('attribute_standard_list')}">消费标准管理</a>
            </li>
            <li class="active">消费标准列表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <!--<button class="btn btn-success" onclick="add('{pigcms{$attribute_id}')">新增</button>-->
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th width="10%">编号</th>
                                <th width="20%">自定义标准名称</th>
                                <th width="20%">自定义标准描述</th>
                                <th width="20%">添加时间</th>
                                <th width="10%">状态</th>
                                <th class="button-column" width="20%">操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            <if condition="$list">
                                <volist name="list" id="vo">
                                    <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                            <td><div class="tagDiv">{pigcms{$vo.id}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.standard_name}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.standard_des}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</div></td>
                            <td><div class="tagDiv"> <if condition="$vo.status eq 1 "><input name="switch-field-1" data-id="{pigcms{$vo['id']}" class="ace ace-switch ace-switch-888" type="checkbox" checked="true" data-status="OPEN" /><else/> <input name="switch-field-1" data-id="{pigcms{$vo['id']}" class="ace ace-switch ace-switch-888" type="checkbox" checked="false"  data-status='CLOSED' /> </if><span class="lbl" style="margin-left:10px;"></span></div></td>
                            <td class="am-text-middle">
                                <div class="tpl-table-black-operation">
                                    <!--<a title="编辑" href="{pigcms{:U('add_standard',array('id'=>$vo['id'],'attribute_id'=>$attribute_id))}">编辑</a>
                                    <a title="删除" href="{pigcms{:U('del_standard',array('id'=>$vo['id'],'attribute_id'=>$attribute_id))}">删除</a>-->
                                    <a title="删除" href="{pigcms{:U('bind_standard',array('id'=>$vo['id'],'attribute_id'=>$attribute_id))}">绑定设备</a>
                                </div>
                            </td>
                            </tr>
                            </volist>
                            <else/>
                            <tr class="odd"><td class="button-column" colspan="12" >没有消费标准。</td></tr>
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
    $(function(){
        updateStatus(".ace-switch", "OPEN", "CLOSED");
    });
    function updateStatus(dom, status1, status2){
        $(dom).each(function(){
            if($(this).attr("data-status")==status1){
                $(this).attr("checked",true);
            }else{
                $(this).attr("checked",false);
            }
        }).click(function(){
            var _this = $(this), type = 'open', id = $(this).attr("data-id");
            if(_this.attr("checked")){	//开启
                type = 'open';
            }else{		//关闭
                type = 'close';
            }
            $.ajax({
                url:"{pigcms{:U('change_status')}",
                type:"post",
                data:{"type":type,"id":id},
                success:function(data){
                    data = JSON.parse(data);
                    if(data.status == 1){
                        if(type=='open'){
                            _this.attr("checked",true);
                        }else{
                            _this.attr("checked",false);
                        }
                    }
                }
            });
        });
    }
    function add(id){
        let title = '';
        switch (id) {
            case '1':
                title = '添加/编辑消费标准';
                break;
            case '2':
                title = '添加/编辑标准功率';
                break;
            case '3':
                title = '添加/编辑上报功率参数';
                break;
            case '4':
                title = '添加/编辑极限功率';
                break;
            case '5':
                title = '添加/编辑最大充电时长';
                break;
            case '6':
                title = '添加/编辑充满断电参数';
                break;
            case '7':
                title = '添加/编辑空载断电参数';
                break;
            case '8':
                title = '添加/编辑上报温度阀值';
                break;
            default:
                title = '添加/编辑消费标准';
        }
        var content = "{pigcms{:U('add_standard',array('attribute_id'=>$attribute_id,'id'=>0))}";
        layer.open({
            type: 2,
            title:title,
            content: content,
            area: ['950px', '555px'],
            maxmin: false
        });
    }
</script>
<include file="Public:footer"/>
