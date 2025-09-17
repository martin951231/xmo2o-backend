<include file="Publics:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}layer/css/layui.css"  media="all">
<style>
    .page-content .btn-info { border: none;}
    .page-content .clearfix.form-actions {text-align: -webkit-center;}
    .page-content {
        background-color: #fff;
        position: relative;
        margin: 0;
        padding: 0px 20px;
    }
</style>
<div class="page-content">
    <div class="page-content-area">
        <style>
            .ace-file-input a {display:none;}
        </style>
        <div class="row">
            <div class="col-xs-12">
                <form  class="form-horizontal" method="post" onSubmit="return check_submit()">
                    <div class="tab-content">
                        <div id="basicinfo" class="tab-pane active">
                            <div class="form-group">
                                <label class="col-sm-2"><label for="standard_name">自定义标准名称</label></label>
                                <input class="col-sm-2" size="20" name="standard_name" id="standard_name" type="text" value=""/>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2"><label for="standard_des">自定义标准描述</label></label>
                                <input class="col-sm-2" size="20" name="standard_des" id="standard_des" type="text" value=""/>
                            </div>

                            <div class="form-group">
                                <if condition="$attribute_id eq 1">
                                    <table class="layui-table">
                                        <colgroup>
                                            <col width="150">
                                            <col width="150">
                                            <col>
                                        </colgroup>
                                        <thead>
                                        <tr>
                                            <th>字段名称</th>
                                            <th>设定范围</th>
                                            <th>设定值</th>
                                            <th>单位</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td>单价</td>
                                            <td>0~500</td>
                                            <td><input class="col-sm-8" size="20" name="A" id="A" type="text" value="1" style="width: 300px;" /><div style="color:red; margin-left: 1px;float: left;">温馨提示：单价和服务费一起组成了充电费用，加在一起不能超过500</div></td>
                                            <td>元/CNY</td>
                                        </tr>
                                        <tr>
                                            <td>服务费</td>
                                            <td>0~500</td>
                                            <td><input class="col-sm-8" size="20" name="F" id="F" type="text" value="0" style="width: 300px;" /><div style="color:red; margin-left: 1px;float: left;">温馨提示：服务费可以不设置。单价和服务费一起组成了充电费用，加在一起不能超过500</div></td>
                                            <td>元/CNY</td>
                                        </tr>
                                        <tr>
                                            <td>时间</td>
                                            <td>0~100</td>
                                            <td><input class="col-sm-8" size="20" name="B" id="B" type="text" value=""/></td>
                                            <td>小时/h</td>
                                        </tr>
                                        <tr>
                                            <td>电量</td>
                                            <td>0~100</td>
                                            <td><input class="col-sm-8" size="20" name="C" id="C" type="text" value=""/></td>
                                            <td>度/kw.h</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                    <elseif condition="$attribute_id eq 2"/>
                                    <table class="layui-table">
                                        <colgroup>
                                            <col width="150">
                                            <col width="150">
                                            <col>
                                        </colgroup>
                                        <thead>
                                        <tr>
                                            <th>字段名称</th>
                                            <th>设定范围</th>
                                            <th>设定值</th>
                                            <th>单位</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td>标准功率</td>
                                            <td>1~1000</td>
                                            <td><input class="col-sm-8" size="20" name="A" id="A" type="text" value=""/></td>
                                            <td>瓦/W</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                    <elseif condition="$attribute_id eq 3"/>
                                    <table class="layui-table">
                                        <colgroup>
                                            <col width="150">
                                            <col width="150">
                                            <col>
                                        </colgroup>
                                        <thead>
                                        <tr>
                                            <th>字段名称</th>
                                            <th>设定范围</th>
                                            <th>设定值</th>
                                            <th>单位</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td>功率变化阀值</td>
                                            <td>0~100</td>
                                            <td><input class="col-sm-8" size="20" name="A" id="A" type="text" value=""/></td>
                                            <td>瓦/W</td>
                                        </tr>
                                        <tr>
                                            <td>最小上报间隔</td>
                                            <td>0~255</td>
                                            <td><input class="col-sm-8" size="20" name="B" id="B" type="text" value=""/></td>
                                            <td>秒/sec</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                    <elseif condition="$attribute_id eq 4"/>
                                    <table class="layui-table">
                                        <colgroup>
                                            <col width="150">
                                            <col width="150">
                                            <col>
                                        </colgroup>
                                        <thead>
                                        <tr>
                                            <th>字段名称</th>
                                            <th>设定范围</th>
                                            <th>设定值</th>
                                            <th>单位</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td>极限功率</td>
                                            <td>10~3000</td>
                                            <td><input class="col-sm-8" size="20" name="A" id="A" type="text" value=""/></td>
                                            <td>瓦/W</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                    <elseif condition="$attribute_id eq 5"/>
                                    <table class="layui-table">
                                        <colgroup>
                                            <col width="150">
                                            <col width="150">
                                            <col>
                                        </colgroup>
                                        <thead>
                                        <tr>
                                            <th>字段名称</th>
                                            <th>设定范围</th>
                                            <th>设定值</th>
                                            <th>单位</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td>最大充电时长</td>
                                            <td>1~172800</td>
                                            <td><input class="col-sm-8" size="20" name="A" id="A" type="text" value=""/></td>
                                            <td>秒/sec</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                    <elseif condition="$attribute_id eq 6"/>
                                    <table class="layui-table">
                                        <colgroup>
                                            <col width="150">
                                            <col width="150">
                                            <col>
                                        </colgroup>
                                        <thead>
                                        <tr>
                                            <th>字段名称</th>
                                            <th>设定范围</th>
                                            <th>设定值</th>
                                            <th>单位</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td>时长限制</td>
                                            <td>0~36000</td>
                                            <td><input class="col-sm-8" size="20" name="A" id="A" type="text" value=""/></td>
                                            <td>秒/sec</td>
                                        </tr>
                                        <tr>
                                            <td>功率限制</td>
                                            <td>0~1000</td>
                                            <td><input class="col-sm-8" size="20" name="B" id="B" type="text" value=""/></td>
                                            <td>瓦/W</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                    <elseif condition="$attribute_id eq 7"/>
                                    <table class="layui-table">
                                        <colgroup>
                                            <col width="150">
                                            <col width="150">
                                            <col>
                                        </colgroup>
                                        <thead>
                                        <tr>
                                            <th>字段名称</th>
                                            <th>设定范围</th>
                                            <th>设定值</th>
                                            <th>单位</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td>时长</td>
                                            <td>0~36000</td>
                                            <td><input class="col-sm-8" size="20" name="A" id="A" type="text" value=""/></td>
                                            <td>秒/sec</td>
                                        </tr>
                                        <tr>
                                            <td>功率</td>
                                            <td>0~200</td>
                                            <td><input class="col-sm-8" size="20" name="B" id="B" type="text" value=""/></td>
                                            <td>瓦/W</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                    <else />
                                    <table class="layui-table">
                                        <colgroup>
                                            <col width="150">
                                            <col width="150">
                                            <col>
                                        </colgroup>
                                        <thead>
                                        <tr>
                                            <th>字段名称</th>
                                            <th>设定范围</th>
                                            <th>设定值</th>
                                            <th>单位</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td>最低温度</td>
                                            <td>-40</td>
                                            <td><input class="col-sm-8" size="20" name="A" id="A" type="text" value=""/></td>
                                            <td>摄氏度/℃</td>
                                        </tr>
                                        <tr>
                                            <td>最高温度</td>
                                            <td>40~100</td>
                                            <td><input class="col-sm-8" size="20" name="B" id="B" type="text" value=""/></td>
                                            <td>摄氏度/℃</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </if>
                            </div>
                        </div>
                    </div>
                    <div class="space"></div>
                    <input  name="attribute_id" id="attribute_id" type="hidden"  value="{pigcms{$attribute_id}"/>
                    <div class="clearfix form-actions">
                        <div class="col-md-offset-3 col-md-9">
                            <button class="btn btn-info" type="submit">
                                <i class="ace-icon fa fa-check bigger-110"></i>
                                保存
                            </button>
                        </div>
                    </div>
            </div>
            </form>
        </div>
    </div>
</div>
</div>
<script type="text/javascript">

    function check_submit(){
        var params = {
            'standard_name'	: $('#standard_name').val(),
            'standard_des'	: $('#standard_des').val(),
            'A'	: $('#A').val()||0,
            'B'	: $('#B').val()||0,
            'C'	: $('#C').val()||0,
            'F'	: $('#F').val()||0,
            'attribute_id':$('#attribute_id').val()||0,
        };
        if($('#standard_name').val() == ''){
            alert('请输入自定义消费标准名称');
            return false;
        }
        $.post("{pigcms{:U('add_standard')}", params, function(data) {
            data = JSON.parse(data);
            console.log('lijie',data);
            if(data.status == 1){
                layer.open({
                    content: '操作成功！',
                    shadeClose: false,
                    closeBtn: 0
                    ,btn: ['确定']
                    ,yes:function(index){
                        parent.location.reload();
                    }
                })
            }else{
                layer.open({
                    content: '请填写正确的范围！',
                    shadeClose: false,
                    closeBtn: 0
                    ,btn: ['确定']
                    ,yes:function(index){
                        //parent.location.reload();
                        layer.closeAll();
                    }
                })
            }
        });
        return false;
    }

</script>

<include file="Public:footer"/>