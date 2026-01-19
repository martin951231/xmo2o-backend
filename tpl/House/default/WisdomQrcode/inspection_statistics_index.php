<include file="Public:header"/>
<style>
    .pagination li a {
        width: 35px;
        height: 35px;
        line-height: 35px;
        padding: 0;
        display: inline-block;
        text-align: center;
        align-content: center;
    }

    /* 切换区域整体样式 */
    .period-controls {
        margin: 15px 0;
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: center;
    }

    /* 快捷按钮样式 */
    .quick-switch {
        display: flex;
        gap: 10px;
    }

    .period-btn {
        padding: 6px 16px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background: #f5f5f5;
        cursor: pointer;
        transition: all 0.2s;
    }

    .period-btn:hover {
        background: #eee;
    }

    .period-btn.active {
        background: #409EFF;
        color: white;
        border-color: #409EFF;
    }

    /* 日期选择器样式（适配原生input[type=date]） */
    .date-range-picker {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .date-input {
        padding: 6px 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        width: 200px;
        height: 40px;
        font-family: inherit;
        /* 保持字体一致 */
    }

    /* 原生日期选择器箭头样式优化 */
    .date-input::-webkit-calendar-picker-indicator {
        cursor: pointer;
        padding: 2px;
    }

    .apply-btn {
        padding: 6px 16px;
        background: #409EFF;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        height: 32px;
    }

    .apply-btn:hover {
        background: #3390e0;
    }

    /* 表格样式 */
    .inspection-table-container {
        margin: 10px 0 20px;
        border-radius: 8px;
        overflow-x: scroll;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
        border: 1px solid #f0f0f0;
        height: calc(100% - 160px);
    }

    .inspection-matrix {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
    }

    .inspection-matrix thead {
        background: #f5f7fa;
    }

    .inspection-matrix th {
        padding: 12px 8px;
        text-align: center;
        font-weight: 500;
        color: #4e5969;
        border-bottom: 1px solid #e5e6eb;
        min-width: 65px;
    }

    .inspection-matrix thead th:not(:first-child) {
        border-right: 1px solid #e5e6eb;
    }

    .text-ellipsis {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        width: 200px;
        display: table-cell;
    }

    .inspection-matrix .project-column {
        background: #fafafa;
        text-align: left;
        padding: 12px 15px;
        font-weight: 500;
        border-right: 1px solid #e5e6eb;
        position: sticky;
        left: 0;
        z-index: 2;
    }

    .inspection-matrix td {
        padding: 15px 8px;
        text-align: center;
        border-bottom: 1px solid #e5e6eb;
        border-right: 1px solid #e5e6eb;
        transition: all 0.2s;
    }

    .inspection-matrix td:last-child {
        border-right: none;
    }

    .inspection-matrix tbody tr:hover td {
        background: #f9fafb;
    }

    .status-icon {
        display: inline-block;
        width: 30px;
        height: 30px;
        line-height: 30px;
        border-radius: 50%;
        font-size: 16px;
        align-content: center;
        cursor: pointer;
    }

    .status-completed {
        background: #f0f9f0;
        color: #52c41a;
    }

    .status-missed {
        background: #fff2f0;
        color: #f5222d;
    }

    /* 响应式适配 */
    @media (max-width: 768px) {
        .period-controls {
            flex-direction: column;
            align-items: flex-start;
        }

        .inspection-table-container {
            overflow-x: auto;
        }
    }

    /* 悬浮提示框 */
    .project-tooltip {
        position: absolute;
        left: 100%;
        top: 50%;
        transform: translateY(-50%);
        margin-left: 10px;
        padding: 6px 10px;
        background: #333;
        color: white;
        border-radius: 4px;
        font-size: 13px;
        white-space: nowrap;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        visibility: hidden;
        opacity: 0;
        transition: opacity 0.2s;
        z-index: 100;
    }

    .project-column:hover .project-tooltip {
        visibility: visible;
        opacity: 1;
    }

    .inspection-rate-column {
        background: #f0f7ff;
        font-weight: 500;
        position: sticky;
        right: 0;
        z-index: 2;
        min-width: 100px;
    }

    /* 弹窗内全屏加载样式（核心新增） */
    .modal-loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.9);
        z-index: 999;
        /* 确保在弹窗内容之上 */
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        transition: opacity 0.3s;
    }

    .modal-loading-overlay.hidden {
        opacity: 0;
        pointer-events: none;
    }

    .modal-loading-icon {
        font-size: 40px;
        color: #409EFF;
        margin-bottom: 15px;
        animation: spin 1s linear infinite;
    }

    .modal-loading-text {
        color: #666;
        font-size: 16px;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    /* 确保弹窗容器为相对定位，使加载层能正确定位 */
    .page-content-area {
        position: relative;
    }

    /* 标签页基础样式 */
    .tab-container {
        width: 95%;
        margin: 20px auto;
    }

    .tab-nav {
        display: flex;
        gap: 8px;
        border-bottom: 1px solid #dee2e6;
        padding-bottom: 0;
        margin-bottom: 15px;
    }

    .tab-item {
        padding: 10px 25px;
        cursor: pointer;
        border: 1px solid transparent;
        border-bottom: none;
        border-radius: 6px 6px 0 0;
        background: #f8f9fa;
        font-weight: 600;
        transition: all 0.2s;
    }

    .tab-item.active {
        background: #fff;
        border-color: #dee2e6;
        color: #2563eb;
    }

    .tab-content {
        overflow: scroll;
        display: none;
        padding: 10px;
        border: 1px solid #dee2e6;
        border-radius: 0 6px 6px 6px;
    }

    .tab-content.active {
        display: block;
    }

    /* 表格核心样式 */
    .inspection-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
        table-layout: fixed;
    }

    .inspection-table th,
    .inspection-table td {
        border: 1px solid #dee2e6;
        padding: 10px 8px;
        text-align: center;
        white-space: nowrap;
    }

    .inspection-table th:first-child {
        width: 200px;
    }

    /* 修改后：选中.inspection-table下第二个th */
    .inspection-table th:nth-child(2) {
        width: 100px !important;
    }

    .inspection-table th {
        width: 120px;
        background: #f8f9fa;
        font-weight: 600;
        position: sticky;
        top: 0;
        z-index: 2;
    }

    /* 巡检项目列样式 */
    .project-col {
        width: 180px;
        text-align: left !important;
        padding-left: 10px !important;
        white-space: normal; /* 多行需设为normal，单行是nowrap */
        /*display: -webkit-box;*/
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2; /* 显示2行，改数字可控制行数 */
        overflow: hidden;
        text-overflow: ellipsis;
        word-break: break-all;
    }

    /* 完成率列样式 */
    .rate-col {
        width: 120px;
    }

    /* 当前项高亮（月标签页当前月、日标签页当日） */
    .current-item {
        background: #fff3cd !important;
        color: #856404 !important;
    }

    /* 奇偶行样式 */
    .tr-odd {
        background: #f9f9f9;
    }

    .tr-even {
        background: #fff;
    }
    #tab-week th{
        width: 200px;
    }
    .modal-content{
        padding: 20px;
    }
    .inspection_field_item:hover{
        background: #ffb2aa;
    }
    /*.log-item:hover{*/
    /*    background: #e3f2fd;*/
    /*    border: 2px solid #e3f2fd !important;*/
    /*}*/
    .qrcode-item:hover{
        background: #f1f1f1 !important;
    }
</style>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="page-content" style="overflow:hidden;height: auto !important;">
        <div class="page-content-area">
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <!-- 切换控制区 -->
                        <div class="period-controls">
                            <div class="date-range-picker">
                                <!-- 改用HTML原生日期选择器 -->
                                <input type="date" id="startDate" class="date-input" placeholder="开始日期">
                                <span>至</span>
                                <input type="date" id="endDate" class="date-input" placeholder="结束日期">
                                <button class="apply-btn" id="applyDateBtn" onclick="searchDate()">确定</button>
                            </div>
                        </div>

                        <!--                        日纬度表格-->
                        <div id="tableContainer">
                            <!-- 标签页导航 -->
                            <div class="tab-nav">
                                <div class="tab-item" data-tab="tab-day" type="day">日计划</div>
                                <div class="tab-item" data-tab="tab-week" type="week">周计划</div>
                                <div class="tab-item" data-tab="tab-month" type="month">月计划</div>
                            </div>
                            <div class="tab-content active" id="tab-day">
                                <table class="inspection-table">
                                    <thead>
                                    <tr>
                                        <th class="project-col">巡检项目</th>
                                        <th class="rate-col">完成率</th>
                                        <volist name="currentHeaderList" id="headerDay">
                                            <th>
                                            {pigcms{$headerDay.fullDate}
                                            </th>
                                        </volist>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <if condition="$dataListNum gt 0">
                                        <volist name="dataList" id="vo" key="i">
                                            <tr class="<if condition='$i%2 eq 0'>tr-odd<else/>tr-even</if>">
                                                <td class="project-col">{pigcms{$vo.cate_name}</td>
                                                <td class="rate-col">{pigcms{$vo.inspectin_rate|default=0}%</td>
                                                <volist name="vo.date_list" id="cellDay">
                                                    <td>
                                                        <if condition="$cellDay.isInspection eq 1">
                    <span onclick='getInspectionDetail({pigcms{$vo.id|json_encode},{pigcms{$cellDay.fullDate|json_encode})' title="点击查看详情" class="status-icon status-completed">
                        <i class="fa fa-check"></i>
                    </span>
                                                            <else/>
                                                            <span onclick='getInspectionDetail({pigcms{$vo.id|json_encode},{pigcms{$cellDay.fullDate|json_encode})' title="点击查看详情" class="status-icon status-missed">
                        <i class="fa fa-times"></i>
                    </span>
                                                        </if>
                                                    </td>
                                                </volist>
                                            </tr>
                                        </volist>
                                        <else/>
                                        <tr>
                                            <php>
                                                $colspan = count($currentHeaderList)+2;
                                            </php>
                                            <td colspan="{pigcms{$colspan}" class="empty-state">
                                                暂无巡检数据
                                            </td>
                                        </tr>
                                    </if>
                                    </tbody>
                                </table>
                            </div>
                            <div class="tab-content" id="tab-week">
                                <table class="inspection-table">
                                    <thead>
                                    <tr>
                                        <th class="project-col">巡检项目</th>
                                        <th class="rate-col">完成率</th>
                                        <volist name="currentHeaderList" id="headerDay">
                                            <th>
                                                {pigcms{$headerDay.weekRange}
                                            </th>
                                        </volist>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <if condition="$dataListNum gt 0">
                                        <volist name="dataList" id="vo" key="i">
                                            <tr class="<if condition='$i%2 eq 0'>tr-odd<else/>tr-even</if>">
                                                <td class="project-col">{pigcms{$vo.cate_name}</td>
                                                <td class="rate-col">{pigcms{$vo.inspectin_rate|default=0}%</td>
                                                <volist name="vo.date_list" id="cellDay">
                                                    <td>
                                                        <if condition="$cellDay.isInspection eq 1">
                    <span onclick='getInspectionDetail({pigcms{$vo.id|json_encode},{pigcms{$cellDay.weekRangeArr|json_encode},{pigcms{$cellDay.weekRange|json_encode})' title="点击查看详情" class="status-icon status-completed">
                        <i class="fa fa-check"></i>
                    </span>
                                                            <else/>
                    <span onclick='getInspectionDetail({pigcms{$vo.id|json_encode},{pigcms{$cellDay.weekRangeArr|json_encode},{pigcms{$cellDay.weekRange|json_encode})' title="点击查看详情" class="status-icon status-missed">
                        <i class="fa fa-times"></i>
                    </span>
                                                        </if>
                                                    </td>
                                                </volist>
                                            </tr>
                                        </volist>
                                        <else/>
                                        <tr>
                                            <php>
                                                $colspan = count($currentHeaderList)+2;
                                            </php>
                                            <td colspan="{pigcms{$colspan}" class="empty-state">
                                                暂无巡检数据
                                            </td>
                                        </tr>
                                    </if>
                                    </tbody>
                                </table>
                            </div>
                            <div class="tab-content" id="tab-month">
                                <table class="inspection-table">
                                    <thead>
                                    <tr>
                                        <th class="project-col">巡检项目</th>
                                        <th class="rate-col">完成率</th>
                                        <volist name="currentHeaderList" id="headerDay">
                                            <th>
                                                {pigcms{$headerDay.monthName}
                                            </th>
                                        </volist>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <if condition="$dataListNum gt 0">
                                        <volist name="dataList" id="vo" key="i">
                                            <tr class="<if condition='$i%2 eq 0'>tr-odd<else/>tr-even</if>">
                                                <td class="project-col">{pigcms{$vo.cate_name}</td>
                                                <td class="rate-col">{pigcms{$vo.inspectin_rate|default=0}%</td>
                                                <volist name="vo.date_list" id="cellDay">
                                                    <td>
                                                        <if condition="$cellDay.isInspection eq 1">
                    <span onclick='getInspectionDetail({pigcms{$vo.id|json_encode},{pigcms{$cellDay.fullMonth|json_encode})' title="点击查看详情" class="status-icon status-completed">
                        <i class="fa fa-check"></i>
                    </span>
                                                            <else/>
                                                            <span onclick='getInspectionDetail({pigcms{$vo.id|json_encode},{pigcms{$cellDay.fullMonth|json_encode})' title="点击查看详情" class="status-icon status-missed">
                        <i class="fa fa-times"></i>
                    </span>
                                                        </if>
                                                    </td>
                                                </volist>
                                            </tr>
                                        </volist>
                                        <else/>
                                        <tr>
                                            <php>
                                                $colspan = count($currentHeaderList)+2;
                                            </php>
                                            <td colspan="{pigcms{$colspan}" class="empty-state">
                                                暂无巡检数据
                                            </td>
                                        </tr>
                                    </if>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!-- 分页容器 -->
                        <div id="paginationContainer">{pigcms{$pagebar}</div>
                    </div>
                </div>
            </div>
        </div>
        <div id="modal-table" class="modal fade map-modal" tabindex="-1" role="dialog" aria-hidden="true"  >
            <div class="modal-dialog" style="width:80%;">
                <div class="modal-content">
                    <div class="modal-header no-padding">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title table-title">巡检异常详情表</h4>
                    </div>
                    <div class="modal-body no-padding">
                        <form id="map-search" style="margin:10px;">
                            <div></div>
                        </form>
                        <!-- 用于渲染巡检详情的容器 -->
                        <div id="inspection_container" style="padding:10px;">

                        </div>
                    </div>
                    <div class="modal-footer no-margin-top">
                        <button class="btn btn-default" type="button" data-dismiss="modal">
                            关闭
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<include file="Public:footer"/>

<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js" charset="utf-8"></script>
<link href="{pigcms{$static_path}css/bootstrap.min.css" rel="stylesheet" type="text/css"/>
<script type="text/javascript" src="{pigcms{$static_path}js/bootstrap.min.js"></script>
<link rel="stylesheet" href="{pigcms{$static_path}css/font-awesome.min.css">
<!--<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>-->
<!--<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>-->
<script>
    const tabItems = document.querySelectorAll('.tab-item');
    const tabContents = document.querySelectorAll('.tab-content');
    const currentActive = "{pigcms{$currentActive}";
    const startDate = "{pigcms{$startDate}";
    const endDate = "{pigcms{$endDate}";

    $(function(){
        $('#tableContainer .tab-nav [type="' + currentActive + '"]').addClass('active');
        tabContents.forEach(tc => tc.classList.remove('active'));
        $('#tableContainer .tab-content[id="tab-' + currentActive + '"]').addClass('active');
        $('#startDate').val(startDate)
        $('#endDate').val(endDate)
    })
    tabItems.forEach(item => {
        item.addEventListener('click', (e) => {
            // 移除所有active类
            tabItems.forEach(ti => ti.classList.remove('active'));
            tabContents.forEach(tc => tc.classList.remove('active'));
            // 激活当前标签和对应内容
            item.classList.add('active');
            const targetTab = item.getAttribute('data-tab');
            document.getElementById(targetTab).classList.add('active');
            //查询页面
            getDate(e.target.getAttribute('type'))
        });
    });
    function getDate(type)
    {
        var url = "{pigcms{:U('inspection_statistics_index')}";
        window.location.href = url + '&type='+type+'&startDate=' + $('#startDate').val() + '&endDate=' + $('#endDate').val();
    }

    function searchDate()
    {
        var url = "{pigcms{:U('inspection_statistics_index')}";
        var $targetElement = $('#tableContainer .tab-nav [class*="active"]');
        var typeValue = $targetElement.length > 0 ? $targetElement.attr('type') : '';
        window.location.href = url + '&type='+typeValue+'&startDate=' + $('#startDate').val() + '&endDate=' + $('#endDate').val();
    }

    function getInspectionDetail(cateId, day, dayStr = '')
    {
        var url = "{pigcms{:U('get_inspection_detail')}";
        var $targetElement = $('#tableContainer .tab-nav [class*="active"]');
        var typeValue = $targetElement.length > 0 ? $targetElement.attr('type') : '';
        $.ajax({
            url: url, // PigCMS 模板变量拼接接口地址
            type: 'POST', // 请求方式：POST/GET
            dataType: 'json', // 预期接口返回 JSON 格式
            data: { // 传递给PHP接口的参数
                type: typeValue,       // 从点击事件传入的vo.id
                fulldate: day,       // 从点击事件传入的vo.id
                cateId: cateId // 从点击事件传入的fullDate
            },
            // 请求成功回调
            success: function(res) {
                if (res.code === 0) {
                    // 接口返回成功：处理数据（如展示详情弹窗、填充内容）
                    console.log('请求成功：', res.data);
                    res.data.currentTime = typeValue == 'week' ? dayStr : day;
                    renderInspectionDetail(res.data)
                    $('#modal-table').modal('show');

                } else {
                    // 业务逻辑错误
                    alert('请求失败：' + res.msg);
                }
            },
            // 请求失败回调（网络/HTTP错误）
            error: function(xhr, status, error) {
                alert('网络异常或接口错误：' + error);
                console.error('请求错误：', xhr, status, error);
            },
            // 请求完成（成功/失败都会执行）
            complete: function() {
                // 恢复点击状态
                $(this).attr('disabled', false).text('点击查看详情');
            }
        });
    }
    // 核心渲染函数：接收完整数据，渲染到inspection_container
    function renderInspectionDetail(fullData) {
        const $container = $('#inspection_container');
        // 清空容器旧数据
        $container.html('');

        // ===================== 1. 解析绑定的二维码数据（bind_qrcode）=====================
        let bindQrcodeList = [];
        try {
            // 解析转义后的JSON字符串（处理&amp;quot;等转义）
            const bindQrcodeStr = fullData.bind_qrcode.replace(/&quot;/g, '"');
            bindQrcodeList = JSON.parse(bindQrcodeStr) || [];
        } catch (e) {
            bindQrcodeList = [];
            console.error('解析bind_qrcode失败：', e);
        }

        // ===================== 2. 拼接基础信息HTML =====================
        let html = `
    <div class="inspection-base-info" style="margin-bottom: 20px; padding: 15px; border: 1px solid #eee; border-radius: 4px; background: #f8f9fa;">
        <h4 style="margin: 0 0 15px 0; color: #333; font-size: 16px;">
            <i class="fa fa-list-alt" style="color: #007bff;"></i> 巡检分类基础信息
        </h4>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 10px;">
            <div><strong>巡检计划名称：</strong>${fullData.cate_name || '无'}</div>
            <div><strong>计划创建时间：</strong>${fullData.add_time ? formatTimestamp(fullData.add_time) : '无'}</div>
            <div><strong>当前查询时间：</strong>${fullData.currentTime ? fullData.currentTime : '无'}</div>
            <div><strong>开始时间：</strong>${fullData.start_time || '无'}</div>
            <div><strong>结束时间：</strong>${fullData.end_time || '无'}</div>
            <div><strong>提醒时间：</strong>${fullData.remind_time || '无'}</div>
        </div>
    </div>
`;

        // ===================== 4. 渲染qrcode_list中的巡检日志 =====================
        const qrcodeList = fullData.qrcode_list || [];
        html += `
    <div class="inspection-log-list" style="padding: 15px; border: 1px solid #eee; border-radius: 4px; background: #f8f9fa;">
        <h4 style="margin: 0 0 15px 0; color: #333; font-size: 16px;">
            <i class="fa fa-history" style="color: #ffc107;"></i> 二维码巡检日志（共${qrcodeList.length}个二维码）
        </h4>
`;
        if (qrcodeList.length > 0) {
            qrcodeList.forEach((qItem, qIndex) => {
                // 解析当前二维码的巡检日志（log_list）- 新数据结构是数组
                const logList = qItem.log_list || [];
                html += `
            <div class="qrcode-item" style="margin-bottom: 15px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: #fff;">
                <div style="margin-bottom: 10px; font-weight: 600; color: #333;background: #ffc107;padding: 5px 10px;">
                    ${qItem.qrcode_name || '未知'}（ID：${qItem.qrcode_id || '无'}）
                </div>
        `;
                // 渲染当前二维码的巡检日志 - 适配新数据结构
                if (logList.length > 0) {
                    logList.forEach((log, lIndex) => {
                        // 解析test中的字段配置（新路径：entication_field_list.test）
                        let fieldHtml = '';
                        // 从entication_field_list中获取巡检字段配置
                        const enticationFieldList = log.entication_field_list || {};
                        const testList = enticationFieldList.test || [];

                        if (testList.length > 0) {
                            const testItem = testList[0];
                            const fieldConfigList = testItem.record_cate_field_config || [];

                            fieldConfigList.forEach((field, fIndex) => {
                                // 处理可选值和填写值的转义/换行
                                const useField = Array.isArray(field.use_field)
                                    ? field.use_field.map(item => item.replace(/\r|\n/g, '').trim()).join(' / ')
                                    : '无';
                                const fieldValue = (field.value || '').replace(/\r|\n/g, '').trim() || '未填写';
                                // 字段类型名称转换（兼容字符串类型的type，如"2"）
                                const fieldTypeName = getFieldTypeName(Number(field.type));

                                fieldHtml += `
                            <tr class="inspection_field_item" style="border-bottom: 1px solid #f5f5f5;">
                                <td style="padding: 6px; text-align: left;">${fIndex + 1}</td>
                                <td style="padding: 6px; text-align: left;">${field.title || '未知字段'}</td>
                                <td style="padding: 6px; text-align: left;">${fieldValue}</td>
                            </tr>
                        `;
                            });
                        }

                        // 单条日志HTML（适配新字段）
                        html += `
                    <div class="log-item" style="margin-bottom: 10px; padding: 8px; border: 1px solid #eee; border-radius: 4px;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 8px; margin-bottom: 8px; font-size: 13px;">
                            <div><strong>操作人：</strong>${log.name || '无'}</div>
                            <div><strong>操作人电话：</strong>${log.phone || '无'}</div>
                            <div><strong>巡检名称：</strong>${testList?.[0]?.record_name || '无'}</div>
                            <div><strong>巡检添加时间：</strong>${log.add_time ? formatTimestamp(log.add_time) : '无'}</div>
                        </div>
                        <!-- 字段配置表格 -->
                        <div style="font-size: 13px;">
                            <p style="margin: 0 0 5px 0; font-weight: 600;">巡检字段配置：</p>
                            <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                                <thead>
                                    <tr style="background: #e9e9e9; color: #333;">
                                        <th style="padding: 6px; text-align: left; width: 8%;">序号</th>
                                        <th style="padding: 6px; text-align: left; width: 20%;">字段名称</th>
                                        <th style="padding: 6px; text-align: center; width: 72%;">填写值</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${fieldHtml || '<tr><td colspan="7" style="padding: 6px; text-align: center; color: #999;">无字段配置</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
                    });
                } else {
                    html += `<div style="padding: 8px; color: #666; text-align: center;">暂无巡检日志</div>`;
                }
                html += `</div>`;
            });
        } else {
            html += `<div style="padding: 10px; color: #666; text-align: center;">暂无二维码巡检日志</div>`;
        }
        html += `</div>`;

        // ===================== 5. 渲染到容器 =====================
        $container.html(html);
    }

    // 辅助函数1：时间戳格式化（秒级）
    function formatTimestamp(timestamp) {
        if (!timestamp) return '无';
        const date = new Date(timestamp * 1000);
        return `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}-${date.getDate().toString().padStart(2, '0')} ${date.getHours().toString().padStart(2, '0')}:${date.getMinutes().toString().padStart(2, '0')}:${date.getSeconds().toString().padStart(2, '0')}`;
    }

    // 辅助函数2：字段类型转换（数字→名称）- 兼容字符串类型
    function getFieldTypeName(type) {
        const typeMap = {
            2: '文本框',
            3: '单选框',
            1: '输入框',
            4: '多选框',
            5: '下拉框'
        };
        return typeMap[type] || `未知(${type})`;
    }
</script>


