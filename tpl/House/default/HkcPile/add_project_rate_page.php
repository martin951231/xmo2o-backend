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
         margin-top: 30px;
    }
</style>
<div class="page-content">
    <div class="page-content-area">
        <style>
            .ace-file-input a {display:none;}
        </style>
        <div class="row">
            <div class="col-xs-12">
                <form  class="form-horizontal" id="haokuaichong_rate_form" method="post" onSubmit="return check_submit()">
                    <div class="tab-content">
                        <div id="basicinfo" class="tab-pane active">
                            <div class="form-group">
                                <label class="col-sm-2"><label for="rate_name">费率套餐名称</label></label>
                                <input class="col-sm-5"  name="rate_name" id="rate_name" type="text" value="{pigcms{$hkcrate['rate_name']}" required/>
                                <span style="margin-left: 20px;">必填项</span>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2"><label for="rate_type">费率模式</label></label>
                                 <select id="choose_rate_type" name="rate_type" class="col-sm-3" required>
                                    <option value="B1">计时（按充电时长计算费用）</option>
                                    <option value="B2"  <if condition="$hkcrate['rate_type'] eq 'B2' "> selected="selected" </if> >计量（按充电电量计算费用）</option>
                                    </select>
                            </div>
                            <div class="form-group" id="bbbbb2" <if condition="$hkcrate['rate_type'] neq 'B2' "> style="display:none;"  </if> >
                                <label class="col-sm-2"><label for="standard">计量费用</label></label>
                                <input class="col-sm-3"  name="standard" id="standard" type="number" placeholder="请输入金额，支持2位小数" min="0" step="0.01" pattern="[0-9.]" value="{pigcms{$hkcrate['standard']}"  /> 元/度
                                <span style="margin-left: 20px;">必填项，充一度电多少钱</span>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2"><label for="is_fund">是否退费</label></label>
                                 <select id="choose_is_fund" name="is_fund" class="col-sm-3">
                                    <option value="0">不退费</option>
                                    <option value="1"  <if condition="$hkcrate['is_fund'] eq '1' "> selected="selected" </if> >退费</option>
                                    </select>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2"><label for="remarks">备注</label></label>
                                <input class="col-sm-8"  name="remarks" id="remarks" type="text" value="{pigcms{$hkcrate['remarks']}" />
                            </div>
                            <div class="form-group" id="bbbbb1" <if condition="$hkcrate AND $hkcrate['rate_type'] AND $hkcrate['rate_type'] neq 'B1' "> style="display:none;"  </if> >
                                    <label class="col-sm-10" style="color: red;font-size: 15px;">充电档位设置，注意事项：可以设置多档，档位之间必须连贯，最小功率必须从0开始，最后一档 最小功率 和 最大功率 必须相等</label>
                                    <table class="layui-table">
                                        <colgroup>
                                            <col width="180">
                                            <col width="250">
                                            <col width="250">
                                            <col>
                                        </colgroup>
                                        <thead>
                                        <tr>
                                            <th>序号</th>
                                            <th>最小功率（瓦）</th>
                                            <th>最大功率（瓦）</th>
                                            <th>计费金额（元/小时）</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                         <tr>
                                            <td>第一项设置</td>
                                            <td><input class="col-sm-8" size="20" name="itemset[0][minPower]" type="number" pattern="[0-9]" min="0" step="1" value="0" readonly="true" oninput="value=value.replace(/[^\d]/g,'')" /><span  style="margin: 8px 0px 0px 10px;display: inline-block;" >瓦</span></td>
                                            <td><input class="col-sm-8" size="20" name="itemset[0][maxPower]" type="number" min="0" step="1" pattern="[0-9]" value="{pigcms{$rate_detail['0']['maxPower']}"  oninput="value=value.replace(/[^\d]/g,'')" /><span style="margin: 8px 0px 0px 10px;display: inline-block;">瓦</span></td>
                                            <td><input class="col-sm-8" size="20" name="itemset[0][standard]" type="number" min="0" step="0.01" pattern="[0-9.]" value="{pigcms{$rate_detail['0']['standard']}"  /><span  style="margin: 8px 0px 0px 10px;display: inline-block;" >元/小时</span></td>
                                        </tr>
                                        <tr>
                                          <td>第二项设置</td>
                                            <td><input class="col-sm-8" size="20" name="itemset[1][minPower]" type="number" pattern="[0-9]" min="0" step="1" value="{pigcms{$rate_detail['1']['minPower']}" oninput="value=value.replace(/[^\d]/g,'')"  /><span  style="margin: 8px 0px 0px 10px;display: inline-block;" >瓦</span></td>
                                            <td><input class="col-sm-8" size="20" name="itemset[1][maxPower]" type="number" min="0" step="1" pattern="[0-9]" value="{pigcms{$rate_detail['1']['maxPower']}"  oninput="value=value.replace(/[^\d]/g,'')"  /><span style="margin: 8px 0px 0px 10px;display: inline-block;">瓦</span></td>
                                            <td><input class="col-sm-8" size="20" name="itemset[1][standard]" type="number" min="0" step="0.01" pattern="[0-9.]" value="{pigcms{$rate_detail['1']['standard']}" /><span  style="margin: 8px 0px 0px 10px;display: inline-block;" >元/小时</span></td>   
                                        </tr>
                                        <tr>
                                            <td>第三项设置</td>
                                            <td><input class="col-sm-8" size="20" name="itemset[2][minPower]" type="number" pattern="[0-9]" min="0" step="1" value="{pigcms{$rate_detail['2']['minPower']}" oninput="value=value.replace(/[^\d]/g,'')"  /><span  style="margin: 8px 0px 0px 10px;display: inline-block;" >瓦</span></td>
                                            <td><input class="col-sm-8" size="20" name="itemset[2][maxPower]" type="number" min="0" step="1" pattern="[0-9]" value="{pigcms{$rate_detail['2']['maxPower']}" oninput="value=value.replace(/[^\d]/g,'')"  /><span style="margin: 8px 0px 0px 10px;display: inline-block;">瓦</span></td>
                                            <td><input class="col-sm-8" size="20" name="itemset[2][standard]" type="number" min="0" step="0.01" pattern="[0-9.]" value="{pigcms{$rate_detail['2']['standard']}"   /><span  style="margin: 8px 0px 0px 10px;display: inline-block;" >元/小时</span></td>
                                        </tr>
                                        <tr>
                                           <td>第四项设置</td>
                                            <td><input class="col-sm-8" size="20" name="itemset[3][minPower]" type="number" pattern="[0-9]" min="0" step="1" value="{pigcms{$rate_detail['3']['minPower']}" oninput="value=value.replace(/[^\d]/g,'')"  /><span  style="margin: 8px 0px 0px 10px;display: inline-block;" >瓦</span></td>
                                            <td><input class="col-sm-8" size="20" name="itemset[3][maxPower]" type="number" min="0" step="1" pattern="[0-9]" value="{pigcms{$rate_detail['3']['maxPower']}" oninput="value=value.replace(/[^\d]/g,'')"  /><span style="margin: 8px 0px 0px 10px;display: inline-block;">瓦</span></td>
                                            <td><input class="col-sm-8" size="20" name="itemset[3][standard]" type="number" min="0" step="0.01" pattern="[0-9.]" value="{pigcms{$rate_detail['3']['standard']}"  /><span  style="margin: 8px 0px 0px 10px;display: inline-block;" >元/小时</span></td>
                                        </tr>
                                          <tr>
                                            <td>第五项设置</td>
                                            <td><input class="col-sm-8" size="20" name="itemset[4][minPower]" type="number" pattern="[0-9]" min="0" step="1" value="{pigcms{$rate_detail['4']['minPower']}" oninput="value=value.replace(/[^\d]/g,'')"  /><span  style="margin: 8px 0px 0px 10px;display: inline-block;" >瓦</span></td>
                                            <td><input class="col-sm-8" size="20" name="itemset[4][maxPower]" type="number" min="0" step="1" pattern="[0-9]" value="{pigcms{$rate_detail['4']['maxPower']}" oninput="value=value.replace(/[^\d]/g,'')"  /><span style="margin: 8px 0px 0px 10px;display: inline-block;">瓦</span></td>
                                            <td><input class="col-sm-8" size="20" name="itemset[4][standard]" type="number" min="0" step="0.01" pattern="[0-9.]" value="{pigcms{$rate_detail['4']['standard']}"  /><span  style="margin: 8px 0px 0px 10px;display: inline-block;" >元/小时</span></td>
                                        </tr>
                                        <tr>
                                            <td>第六项设置</td>
                                            <td><input class="col-sm-8" size="20" name="itemset[5][minPower]" type="number" pattern="[0-9]" min="0" step="1" value="{pigcms{$rate_detail['5']['minPower']}" oninput="value=value.replace(/[^\d]/g,'')"  /><span  style="margin: 8px 0px 0px 10px;display: inline-block;" >瓦</span></td>
                                            <td><input class="col-sm-8" size="20" name="itemset[5][maxPower]" type="number" min="0" step="1" pattern="[0-9]" value="{pigcms{$rate_detail['5']['maxPower']}" oninput="value=value.replace(/[^\d]/g,'')"  /><span style="margin: 8px 0px 0px 10px;display: inline-block;">瓦</span></td>
                                            <td><input class="col-sm-8" size="20" name="itemset[5][standard]"  type="number" min="0" step="0.01" pattern="[0-9.]" value="{pigcms{$rate_detail['5']['standard']}" /><span  style="margin: 8px 0px 0px 10px;display: inline-block;" >元/小时</span></td>
                                        </tr>
                                         <tr>
                                            <td>第七项设置</td>
                                            <td><input class="col-sm-8" size="20" name="itemset[6][minPower]" placeholder="请输入一个正整数" type="number" pattern="[0-9]" min="0" step="1" value="{pigcms{$rate_detail['6']['minPower']}" oninput="value=value.replace(/[^\d]/g,'')"  /><span  style="margin: 8px 0px 0px 10px;display: inline-block;" >瓦</span></td>
                                            <td><input class="col-sm-8" size="20" name="itemset[6][maxPower]" placeholder="请输入一个正整数" type="number" min="0" step="1" pattern="[0-9]" value="{pigcms{$rate_detail['6']['maxPower']}" oninput="value=value.replace(/[^\d]/g,'')"  /><span style="margin: 8px 0px 0px 10px;display: inline-block;">瓦</span></td>
                                            <td><input class="col-sm-8" size="20" name="itemset[6][standard]" type="number" placeholder="请输入金额，支持2位小数" min="0" step="0.01" pattern="[0-9.]" value="{pigcms{$rate_detail['6']['standard']}" /><span  style="margin: 8px 0px 0px 10px;display: inline-block;" >元/小时</span></td>
                                        </tr>
                                        </tbody>
                                    </table>
                            </div>
                        </div>
                    </div>
                    <div class="space"></div>
                    <input  name="project_id" id="project_id" type="hidden"  value="{pigcms{$project_id}"/>
                    <input  name="idd" id="idd" type="hidden"  value="{pigcms{$idd}"/>
                    <div class="clearfix form-actions">
                        <div class="col-md-offset-1 col-md-9">
                            <a class="btn btn-info"  onclick="check_submit()">
                                <i class="ace-icon fa fa-check bigger-110"></i>
                                保存
                            </a>
                        </div>
                    </div>
            </div>
            </form>
        </div>
    </div>
</div>
</div>
<script type="text/javascript">
    $('#choose_rate_type').change(function(){
       var rate_type= $(this).val()||'';
       rate_type=$.trim(rate_type);
       if(rate_type=='B1'){
           $('#bbbbb1').show();
           $('#bbbbb2').hide();
       }else if(rate_type=='B2'){
           $('#bbbbb1').hide();
           $('#bbbbb2').show();
       }
    });
    function check_submit(){
        var params = $('#haokuaichong_rate_form').serialize();
        $.post("{pigcms{:U('HkcPile/add_project_rate')}", params, function(data) {
            if(data.err_code == 0){
                layer.open({
                    content: data.err_msg,
                    shadeClose: false,
                    closeBtn: 0
                    ,btn: ['确定']
                    ,yes:function(index){
                        parent.location.reload();
                    }
                })
            }else{
                layer.open({
                    content:  data.err_msg,
                    shadeClose: false,
                    closeBtn: 0
                    ,btn: ['确定']
                    ,yes:function(index){
                        layer.closeAll();
                    }
                })
            }
        },'JSON');
        return false;
    }

</script>

<include file="Public:footer"/>