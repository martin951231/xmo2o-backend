<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('Unit/index')}">物业管理</a>
            </li>
            <li class="active">物业对帐</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row" style="margin: 20% 35%">
                <table class="search_table" width="100%">
                    <tr>
                        <td>
                            <form>
                                <div style="width: 100%;display: inline-flex;">
                                    <div style="width: 10rem;line-height: 42px;">统计表格选择:</div>
                                    <select id="searchtype"  style="height:42px">
                                        <option value="carry">物业月度收入结转统计</option>
                                        <option value="rate">物业费收缴率统计</option>
                                    </select>
                                </div>
                                <br><br>
                                <div id="carry" style="width: 100%;display: inline-flex;">
                                    <div style="width: 10rem;line-height: 42px;">选择日期：</div>
                                    <input type="text" id="time1" name="begin_time" class="input-text"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM'})" placeholder="选择日期"/>
                                </div>
                                <br><br>
                                <div style="display: none;width: 100%;" id="rate">
                                    <div style="width: 10rem ;line-height: 42px;">选择日期：</div>
                                    <input type="text" id="time2" name="begin_time" class="input-text"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM'})" placeholder="开始时间"/>&nbsp;&nbsp;-&nbsp;&nbsp;
                                    <input type="text" id="time3" name="end_time" class="input-text" style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM'})" placeholder="结束时间"/>&nbsp;&nbsp;
                                </div>
                                <br><br>
                                <button style="width: 65%" class="btn btn-success" type="button" onclick="export_fee()" <if condition="!in_array(34,$house_session['menus'])">disabled="disabled"</if>>EXCEL导出</button>&nbsp;&nbsp;
                            </form>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
<div style="font-size: 30px; padding: 15px;"></div>
<script>
    $(function () {
        var obj = document.getElementById("searchtype");
        obj.onchange = function(){
            var index = obj.selectedIndex;
            if(index == 0){
                $('#carry').css({"display":"inline-flex"});
                $('#rate').css({"display":"none"});
            }else{
                $('#carry').css({"display":"none"});
                $('#rate').css({"display":"inline-flex"});
            }
        }
    })

    function export_fee() {
        var  type = $('#searchtype').val();
        if(type == 'carry'){
            var time1 = $('#time1').val();
            console.log(time1)
            if(time1 == ''){
                alert('请选择日期');
            }
            location.href = "{pigcms{:U('export_fee')}"+"&type="+type+"&time1="+time1;
        }else{
            var time2 = $('#time2').val();
            var time3 = $('#time3').val();
            if(time2 == '' || time3 == ''){
                alert('请选择日期');
            }
            location.href = "{pigcms{:U('export_fee')}"+"&type="+type+"&time2="+time2+"&time3="+time3;
        }
    }
</script>
<include file="Public:footer"/>
