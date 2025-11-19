<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="javascript:void(0);">快递柜记录</a>
            </li>
            <li class="active">快递柜单体记录表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>

            <div class="row">
                <table class="search_table" width="100%">
                    <tr>
                        <td>
                            <form action="{pigcms{:U('express_num_log_list')}" method="get">
                                <input type="hidden" name="c" value="Intelligence_express"/>
                                <input type="hidden" name="a" value="express_num_log_list"/>
                                类型: <select name="type" style="height:42px">
                                    <option value="0" <if condition="$_GET['type'] eq ''">selected="selected"</if>>所有</option>
                                    <option value="1" <if condition="$_GET['type'] eq '1'">selected="selected"</if>>取件</option>
                                    <option value="2" <if condition="$_GET['type'] eq '2'">selected="selected"</if>>存件</option>
                                </select>
                                &nbsp;&nbsp;&nbsp;
                                手机号: <input type="text" name="phone" class="input-text" value="{pigcms{$_GET['phone']}"  style="height:42px"/>&nbsp;&nbsp;
                                &nbsp;&nbsp;&nbsp;

                                筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"  style="height:42px"/>&nbsp;&nbsp;
                                <select name="searchtype"  style="height:42px">
                                    <option value="pickup_code" <if condition="$_GET['searchtype'] eq 'pickup_code'">selected="selected"</if>>取件码</option>
                                    <option value="express_odd_numbers" <if condition="$_GET['searchtype'] eq 'express_odd_numbers'">selected="selected"</if>>快递单号</option>
                                    </select>
                                    &nbsp;&nbsp;&nbsp;

                                操作时间筛选：
                                <input type="text" name="begin_time" class="input-text" value="{pigcms{$_GET['begin_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:true,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="开始时间"/>&nbsp;&nbsp;-&nbsp;&nbsp;
                                <input type="text" name="end_time" class="input-text" value="{pigcms{$_GET['end_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:true,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="结束时间"/>&nbsp;&nbsp;
                                <button class="btn btn-success" type="submit">查询</button>&nbsp;&nbsp;
                                <button class="btn btn-success" type="button" onclick="location.href='{pigcms{:U('express_num_log_list')}'">重置</button>
                            </form>
                        </td>
                    </tr>
                </table>
                <div class="col-xs-12">
                        <div class="tab-pane active" id="txtstore">
                            <div id="" class="grid-view">
                                <table class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th width="5%">编号</th>
                                            <th width="5%">类型</th>
                                            <th width="5%">取件码</th>
                                            <th width="10%">快递单号</th>
                                            <th width="8%">手机号</th>
                                            <th width="5%">操作时间</th>

                                        </tr>
                                    </thead>
                                    <tbody>
                                        <if condition="$dataList">
                                            <volist name="dataList" id="vo">
                                                <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                                    <if condition="$vo.type eq 1">
                                                        <td> 取件 </td>
                                                    <elseif condition="$vo.type eq 2">
                                                        <td> 存件 </td>
                                                    </if>
                                                    <td>{pigcms{$vo.pickup_code}</td>
                                                    <td>{pigcms{$vo.express_odd_numbers}</td>
                                                    <td>{pigcms{$vo.phone}</td>
                                                    <td>{pigcms{$vo.create_time|date='Y-m-d H:i:s',###}</td>


                                                </tr>
                                            </volist>

                                            <tr class="odd">
                                                <td colspan="16" id="show_count"></td>
                                            </tr>
                                            <tr><td class="textcenter pagebar" colspan="12">{pigcms{$pagebar}</td></tr>
                                        <else />
                                            <tr class="odd"><td class="button-column" colspan="15" >没有任何数据。</td></tr>
                                        </if>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div style="font-size: 30px; padding: 15px;"></div>
<input type="hidden" name="checkedmoney" value="0">
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script>

</script>
<include file="Public:footer"/>
