<include file="Public:header"/>
<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('User/import')}">导入用户</a>
            <a href="{pigcms{:U('User/importlist')}">导入用户列表</a>
            <a href="{pigcms{:U('User/importmoney')}">客户余额导入</a>
            <a href="{pigcms{:U('User/importmoneylist')}" class="on">导入余额列表</a>
        </ul>
    </div>
    <table class="search_table" width="100%">
        <tr>
            <td>
                <form action="{pigcms{:U('User/importmoneylist')}" method="get">
                    <input type="hidden" name="c" value="User"/>
                    <input type="hidden" name="a" value="importmoneylist"/>
                    筛选:
                    <font color="#000">手机号：</font>
                    <input type="text" name="phone" class="input-text" value="{pigcms{$_GET['phone']}"/>
                    <font color="#000">审核状态：</font>
                    <select name="status">
                        <option value="0" <if condition="$_GET['status'] eq 0">selected="selected"</if>>全部</option>
                        <option value="1" <if condition="$_GET['status'] eq 1">selected="selected"</if>>未审核</option>
                        <option value="2" <if condition="$_GET['status'] eq 2">selected="selected"</if>>审核通过</option>
                        <option value="3" <if condition="$_GET['status'] eq 3">selected="selected"</if>>审核驳回</option>
                    </select>
                    <font color="#000">导入日期：</font>
                    <input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                    <input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                    <input type="submit" value="查询" class="button"/>
                    <input type="button" value="批量审核通过" id="agree" class="button"/>
                    <input type="button" value="批量审核驳回" id="refuse" class="button"/>
                </form>
            </td>
        </tr>
    </table>
    <form name="myform" id="myform" action="" method="post">
        <div class="table-list">
            <table width="100%" cellspacing="0">
                <colgroup>
                    <col/>
                    <col/>
                    <col/>
                    <col/>
                    <col/>
                    <col/>
                    <col/>
                    <col/>
                    <col/>
                    <col/>
                    <col/>
                    <col width="180" align="center"/>
                </colgroup>
                <thead>
                <tr>
                    <th><input type="checkbox" id="all"></th>
                    <th>ID</th>
                    <th>手机号</th>
                    <th>余额</th>
                    <th>{pigcms{$config['score_name']}</th>
                    <th>状态</th>
                    <th>操作人名称</th>
                    <th>备注</th>
                    <th>审核备注</th>
                    <th>审核时间</th>
                    <th>导入时间</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <if condition="is_array($userimport)">
                    <volist name="userimport" id="vo">
                        <tr>
                            <td>
                                <if condition="$vo['status'] == 1">
                                    <input type="checkbox" name="ids[]" value="{pigcms{$vo.id}">
                                    <else />
                                    <input type="checkbox" name="checkdisabled" disabled value="{pigcms{$vo.id}">
                                </if>
                            </td>
                            <td>{pigcms{$vo.id}</td>
                            <td>{pigcms{$vo.phone}
                                <if condition="$vo['is_register'] == 0">
                                    <font style="color:red">未注册</font>
                                </if>
                            </td>
                            <td>{pigcms{$vo.money} {pigcms{$config['Currency_txt']}</td>
                            <td>{pigcms{$vo.score}</td>
                            <td>
                                <if condition="$vo['status'] == 1">
                                    <font color="red">未审核</font>
                                    <elseif condition="$vo['status'] == 2" />
                                    <font color="green">审核通过</font>
                                    <elseif condition="$vo['status'] == 3" />
                                    <font color="blue">审核驳回</font>
                                </if>
                            </td>
                            <td>{pigcms{$vo.verify_name}</td>
                            <td>{pigcms{$vo.remark}</td>
                            <td>{pigcms{$vo.verify_remark}</td>
                            <td>
                                <if condition="$vo['verify_time']">
                                    {pigcms{$vo.verify_time|date='Y-m-d H:i:s',###}
                                    <else />
                                    无
                                </if>
                            </td>
                            <td>{pigcms{$vo.create_time|date='Y-m-d H:i:s',###}</td>
                            <td>
                                <if condition="$vo['status'] == 1">
                                    <if condition="$vo['is_register'] == 1">
                                        <a href="javascript:void(0);" onclick="agreeone({pigcms{$vo.id})" data="{pigcms{$vo.id}" id="agreeone">审核通过</a> |
                                    </if>
                                    <a href="javascript:void(0);" onclick="agreeone({pigcms{$vo.id})" data="{pigcms{$vo.id}" id="refuseone">审核驳回</a>
                                </if>
                            </td>
                        </tr>
                    </volist>
                    <tr><td class="textcenter pagebar" colspan="14">{pigcms{$pagebar}</td></tr>
                    <else/>
                    <tr><td class="textcenter red" colspan="14">列表为空！</td></tr>
                </if>
                </tbody>
            </table>
        </div>
    </form>
</div>

<script>
    $(document).ready(function(){
        layer.config({extend: 'extend/layer.ext.js'});
        $('#all').click(function(){
            if ($(this).is(':checked')) {
                $('input[type="checkbox"]').attr('checked', true);
            } else {
                $('input[type="checkbox"]').attr('checked', false);
            }
            $('input[name="checkdisabled"]').attr('checked', false);
        });
        $('#agree').click(function(){
            var ids = [];
            $ ("input['name=ids[]']:checked").each(function() {
                ids.push($(this).val());
            });
            if(ids.length < 1){
                layer.msg('请先选择');
                return false;
            }
            layer.prompt({
                formType: 2,
                value: '无',
                title: '请输入审核备注',
            }, function(value, index, elem){
                $.post('{pigcms{:U("User/verifyagree")}',{'ids': ids, 'verify_remark': value},function(res){
                    layer.msg(res.msg);
                    setTimeout(function () {window.location.reload() }, 1000);
                },'json');
                layer.close(index);
            });
            // layer.confirm('确定审核通过吗?', {icon: 3, title:'提示',offset: ['100px', '400px'],}, function(index){
            //     $.post('{pigcms{:U("User/verifyagree")}',{'ids':ids},function(res){
            //         layer.msg(res.msg);
            //         setTimeout(function () {window.location.reload() }, 1000);
            //     },'json');
            //     layer.close(index);
            // });
        });
        $('#refuse').click(function(){
            var ids = [];
            $ ("input['name=ids[]']:checked").each(function() {
                ids.push($(this).val());
            });
            if(ids.length < 1){
                layer.msg('请先选择');
                return false;
            }
            layer.prompt({
                formType: 2,
                value: '无',
                title: '请输入审核备注',
            }, function(value, index, elem){
                $.post('{pigcms{:U("User/verifyrefuse")}',{'ids': ids, 'verify_remark': value},function(res){
                    layer.msg(res.msg);
                    setTimeout(function () {window.location.reload() }, 1000);
                },'json');
                layer.close(index);
            });
        });
    });
    function agreeone(ids){
        var ids = [ids];
        layer.prompt({
            formType: 2,
            value: '无',
            title: '请输入审核备注',
        }, function(value, index, elem){
            $.post('{pigcms{:U("User/verifyagree")}',{'ids': ids, 'verify_remark': value},function(res){
                layer.msg(res.msg);
                setTimeout(function () {window.location.reload() }, 1000);
            },'json');
            layer.close(index);
        });
    };
    function refuseone(ids){
        var ids = [ids];
        layer.prompt({
            formType: 2,
            value: '无',
            title: '请输入审核备注',
        }, function(value, index, elem){
            $.post('{pigcms{:U("User/verifyrefuse")}',{'ids': ids, 'verify_remark': value},function(res){
                layer.msg(res.msg);
                setTimeout(function () {window.location.reload() }, 1000);
            },'json');
            layer.close(index);
        });
    };

</script>
<include file="Public:footer"/>