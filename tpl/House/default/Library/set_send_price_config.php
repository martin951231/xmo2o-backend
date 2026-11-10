<style>
    .date-input {
        padding: 6px 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        width: 140px;
        height: 32px;
        font-family: inherit;
        /* 保持字体一致 */
    }

    /* 原生日期选择器箭头样式优化 */
    .date-input::-webkit-calendar-picker-indicator {
        cursor: pointer;
        padding: 2px;
    }
    .clear_btn{
        cursor: pointer;
    }
</style>
<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-gear gear-icon"></i>
                <a href="{pigcms{:U('Library/set_send_price_config')}">代送基本设置</a>
            </li>
            <li class="active">基本信息设置</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {
                    display: none;
                }
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <form id="edit_form" method="post" class="form-horizontal"
                          action="{pigcms{:U('Library/set_send_price_config')}" enctype="multipart/form-data">
                        <div class="tab-content">
                            <div class="form-group express_send_price">
                                <label class="col-sm-2"><label for="express_send_price">代发价格</label></label>
                                <input class="col-sm-1" size="10" name="express_send_price" id="express_send_price"
                                       type="text" placeholder="请设置代发价格"
                                       value="{pigcms{$village_info.express_send_price}"/>
                                <span class="form_tips" style="line-height: 30px;">请设置代发价格</span>
                            </div>
                            <div class="form-group express_person_average_entrust">
                                <label class="col-sm-2"><label
                                            for="express_person_average_entrust">快递代取人均日委托数</label></label>
                                <input class="col-sm-2" size="10" name="express_person_average_entrust"
                                       id="express_person_average_entrust" type="number"
                                       placeholder="请输入户均日委托数"
                                       value="{pigcms{$village_info.express_person_average_entrust}"/>
                                <span class="form_tips" style="line-height: 30px;">请输入户均日委托数, 0或空则不限制</span>
                            </div>
                            <div class="form-group express_project_daily_entrust">
                                <label class="col-sm-2"><label
                                            for="express_project_daily_entrust">快递代取项目日委托数</label></label>
                                <input class="col-sm-2" size="10" name="express_project_daily_entrust"
                                       id="express_project_daily_entrust" type="number"
                                       placeholder="请输入项目日委托数"
                                       value="{pigcms{$village_info.express_project_daily_entrust}"/>
                                <span class="form_tips" style="line-height: 30px;">请输入项目日委托数, 0或空则不限制, 户均日委托数不得大于项目日委托数</span>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2"><label for="express_project_startDate">代取快递申请时间限制</label></label>
                                 <input type="time" name="express_project_startDate" id="express_project_startDate" value="{pigcms{$village_info.express_project_startDate}" class="date-input" placeholder="开始日期">
                                <span>&nbsp;&nbsp;至&nbsp;&nbsp;</span>
                                <input type="time" name="express_project_endDate" id="express_project_endDate" value="{pigcms{$village_info.express_project_endDate}" class="date-input" placeholder="结束日期">
                                <span class="form_tips clear_btn" onclick="document.getElementById('express_project_startDate').value = '';document.getElementById('express_project_endDate').value = ''" >清空</span>
                                <span class="form_tips" style="line-height: 30px;">结束时间不要早于开始时间, 留空则不限制, 此时间段内将不允许提交申请</span>

                            </div>
                            <div class="clearfix form-actions">
                                <div class="col-md-offset-3 col-md-9">
                                    <if condition="in_array(3,$house_session['menus'])">
                                        <button type="submit" class="btn btn-info">
                                            <i class="ace-icon fa fa-check bigger-110"></i>
                                            保存
                                        </button>
                                        <else/>
                                        <button type="button" class="btn btn-info disabled" disabled="disabled"
                                                style="cursor: not-allowed;">
                                            <i class="ace-icon fa fa-check bigger-110"></i>
                                            保存
                                        </button>
                                    </if>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<script>

    $('#edit_form').submit(function () {

        var express_send_price = $("#express_send_price").val();
        if (express_send_price < 0) {
            alert('请设置不小于0的代发价格！');
            return false;
        }

        var express_person_average_entrust = Number( $("#express_person_average_entrust").val() || 0);
        var express_project_daily_entrust = Number( $("#express_project_daily_entrust").val() || 0);

        if (express_person_average_entrust > 0 && express_project_daily_entrust > 0 && express_person_average_entrust > express_project_daily_entrust) {
            alert('快递代取人均日委托数不能大于快递代取项目日委托数！');
            return false;
        }

        var startDate = $('#startDate').val();
        var endDate = $('#endDate').val();
        if (startDate > endDate) {
            alert('开始时间不能晚于结束时间！');
            return false;
        }

        $('#edit_form button[type="submit"]').prop('disabled', true).html('保存中...');
        if (lock = 1) {
            return;
        }
        lock = 1;


        $.post("{pigcms{:U('Library/set_send_price_config')}", $('#edit_form').serialize(), function (result) {
            lock = 0;
            if (result.status == 1) {
                window.location.href = "{pigcms{:U('Library/set_send_price_config')}";
            } else {
                $('#edit_form button[type="submit"]').prop('disabled', false).html('<i class="ace-icon fa fa-check bigger-110"></i>保存');
                alert(result.info);
            }
        })
        return false;
    });
</script>

<include file="Public:footer"/>