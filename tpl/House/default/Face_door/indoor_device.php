<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">

            <style>
                .ace-file-input a {display:none;}
                .msg_div{
                    margin-bottom: 15px;
                }
                .msg_span{
                    background-color: #dff0d8;
                    color: #3c763d;
                    padding: 8px;
                    border: 1px solid #d6e9c6;
                    display: inline-block;
                }
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <div class="msg_div">
                        <span class="msg_span">注意：室内机仅限于 A185智能门禁配合使用</span>
                    </div>
                    <button class="btn btn-success" onclick="add()">添加室内机</button>
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th width="5%">ID</th>
                                <th>室内机设备号</th>
                                <th>状态</th>
                                <th class="am-text-middle">操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            <if condition="$list['list']">
                                <volist name="list['list']" id="vo">
                                    <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                            <td><div class="tagDiv">{pigcms{$vo.id}</div></td>
                            <td><div class="tagDiv"><a >{pigcms{$vo.device_sn}</a></div></td>
                            <td>
                                <div class="tagDiv">
                                    <if condition='$vo["status"] eq 0'>
                                        <span class="red">关闭</span>
                                        <else />
                                        <span class="green">开启</span>
                                    </if>
                                </div>
                            </td>
                            <td class="am-text-middle">
                                <div class="tpl-table-black-operation">
                                    <a  onclick="edit({pigcms{$vo['id']})">编辑</a>
                                    <if condition="in_array(235,$house_session['menus'])">
                                        &nbsp;<a title="删除" class="item-delete tpl-table-black-operation-del"  onclick="del({pigcms{$vo['id']})">删除</a>
                                    </if>
                                </div>
                            </td>
                            </tr>
                            </volist>
                            <else/>
                            <tr class="odd"><td class="button-column" colspan="8" >没有任何数据。</td></tr>
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

<script type="text/javascript">
    function add(){
        var url = "{pigcms{:U('Face_door/indoor_device_add')}";
        layer.open({
            type: 2,
            title:'添加室内机',
            content: url,
            area: ['50%', '50%'],
            maxmin: true
        });
    }

    function edit(id){
        var url = "{pigcms{:U('Face_door/indoor_device_edit',['id'=>'"+id+"'])}";
        layer.open({
            type: 2,
            title:'编辑室内机',
            content: url,
            area: ['50%', '50%'],
            maxmin: true
        });
    }

    function del(id) {
        layer.confirm('确定删除？', {
            btn: ['确定', '取消']
        }, function(index, layero){
            $.post("{pigcms{:U('indoor_device_del')}",{'id':id},function(data){
                if(data.status == 1){
                    layer.msg(data.info,{icon: 1,time : 1000},function(){
                        window.parent.location.reload();//刷新父页面
                    });
                }
                if(data.status == 0){
                    layer.msg(data.info,{icon: 2});
                }
            },'json');

        }, function(index){

        });

    }


</script>
<include file="Public:footer"/>