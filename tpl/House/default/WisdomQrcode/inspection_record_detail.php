<!-- 引入必要资源（仅保留字体图标） -->
<!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css"> -->

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
    }

    .status-completed {
        background: #f0f9f0;
        color: #52c41a;
    }

    .status-missed {
        background: #fff2f0;
        color: #f5222d;
    }

    .empty-state {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100%;
        text-align: center;
        padding: 40px 0;
        color: #86909c;
        background: #fafafa;
    }

    .empty-state i {
        font-size: 40px;
        margin-bottom: 10px;
        color: #d9d9d9;
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
</style>

<include file="Public:header" />
<div class="main-content">
    <!-- 内容头部 -->
    <div class="page-content" style="overflow:hidden;height: auto !important;">
        <div class="page-content-area">
            <style type="text/css">
                .ace-file-input a {
                    display: none;
                }
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <!-- 切换控制区 -->
                        <div class="period-controls">
                            <div class="quick-switch">
                                <div class="period-btn" data-period="lastWeek">上周</div>
                                <div class="period-btn" data-period="lastMonth">上月</div>
                            </div>
                            <div class="date-range-picker">
                                <!-- 改用HTML原生日期选择器 -->
                                <input type="date" id="startDate" class="date-input" placeholder="开始日期">
                                <span>至</span>
                                <input type="date" id="endDate" class="date-input" placeholder="结束日期">
                                <button class="apply-btn" id="applyDateBtn">确定</button>
                            </div>
                        </div>

                        <!-- 表格容器 -->
                        <div class="inspection-table-container" id="tableContainer">
                            <!-- 初始加载提示 -->
                            <div class="empty-state" id="loadingState">
                                <i class="fa fa-spinner fa-spin"></i>
                                <div>加载中...</div>
                            </div>
                        </div>
                        <!-- 分页容器 -->
                        <div id="paginationContainer">{pigcms{$pagebar}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script>
    const dataList = <?php echo json_encode($dataList ?? []); ?>;
    let currentPeriod = null;
    let startDate = null;
    let endDate = null;
    let isPaginationLoading = false; // 分页加载状态标记

    // 1. URL参数解析（不变）
    function getUrlParams() {
        const params = {};
        const search = window.location.search.substring(1);
        if (!search) return params;
        search.split('&').forEach(item => {
            const [key, value] = item.split('=');
            if (key) params[key] = decodeURIComponent(value || '');
        });
        return params;
    }

    // 2. 分页链接更新（不变）
    function updatePageLinks() {
        const params = getUrlParams();
        delete params.period;
        delete params.start;
        delete params.end;

        if (currentPeriod) {
            params.period = currentPeriod;
        } else if (startDate && endDate) {
            params.start = startDate;
            params.end = endDate;
        }

        const newParams = new URLSearchParams(params).toString();
        const baseUrl = window.location.href.split('?')[0];

        const pageLinks = document.querySelectorAll('#paginationContainer a[href*="page="]');
        pageLinks.forEach(link => {
            const pageParam = link.href.split('?')[1]?.split('&').find(p => p.startsWith('page=')) || '';
            link.href = `${baseUrl}?${newParams}${newParams && pageParam ? '&' : ''}${pageParam}`;
        });

        // 绑定分页点击事件（核心新增）
        bindPaginationClick();
    }

    // 3. 绑定分页点击事件（核心新增）
    function bindPaginationClick() {
        const pageLinks = document.querySelectorAll('#paginationContainer a[href*="page="]');
        pageLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                // 避免重复点击
                if (isPaginationLoading) {
                    e.preventDefault();
                    return;
                }

                // 显示加载状态
                showLoading();
                isPaginationLoading = true;
                // 给分页容器添加加载样式（禁用点击+半透明）
                document.getElementById('paginationContainer').classList.add('pagination-loading');

                // 延迟跳转（模拟加载过程，可根据实际接口响应时间调整）
                setTimeout(() => {
                    // 移除加载样式
                    document.getElementById('paginationContainer').classList.remove('pagination-loading');
                    isPaginationLoading = false;
                    // 允许跳转（如果是AJAX分页，这里替换为接口请求逻辑）
                    window.location.href = this.href;
                }, 500);

                // 如果是AJAX无刷新分页，取消默认跳转，改为接口请求
                // e.preventDefault();
                // 这里添加AJAX请求逻辑，请求成功后渲染表格并更新分页
            });
        });
    }

    // 4. 初始化页面（不变，新增分页事件绑定）
    document.addEventListener('DOMContentLoaded', function() {
        const startDateEl = document.getElementById('startDate');
        const endDateEl = document.getElementById('endDate');

        startDateEl.addEventListener('change', function() {
            startDate = this.value;
            if (startDate) {
                endDateEl.min = startDate;
                if (endDate && endDate < startDate) {
                    endDate = '';
                    endDateEl.value = '';
                }
            } else {
                endDateEl.min = '';
            }
        });

        endDateEl.addEventListener('change', function() {
            endDate = this.value;
        });

        setTimeout(() => {
            const params = getUrlParams();
            if (params.period) {
                currentPeriod = params.period;
                document.querySelector(`.period-btn[data-period="${currentPeriod}"]`)?.classList.add('active');
                renderTableByPeriod(currentPeriod);
            } else if (params.start && params.end) {
                startDate = params.start;
                endDate = params.end;
                startDateEl.value = startDate;
                endDateEl.value = endDate;
                endDateEl.min = startDate;
                renderTableByDateRange(new Date(startDate), new Date(endDate));
            } else {
                renderTableByPeriod('currentMonth');
            }

            bindEvents();
            // 初始化分页点击事件
            setTimeout(() => {
                updatePageLinks();
                bindPaginationClick();
            }, 100);
        }, 0);
    });

    // 5. 事件绑定（不变）
    function bindEvents() {
        document.querySelectorAll('.period-btn').forEach(btn => {
            btn.addEventListener('click', debounce(function() {
                currentPeriod = this.getAttribute('data-period');
                startDate = endDate = null;
                document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                const startDateEl = document.getElementById('startDate');
                const endDateEl = document.getElementById('endDate');
                startDateEl.value = '';
                endDateEl.value = '';
                endDateEl.min = '';
                renderTableByPeriod(currentPeriod);
                updatePageLinks();
            }, 100));
        });

        document.getElementById('applyDateBtn').addEventListener('click', debounce(function() {
            const startDateEl = document.getElementById('startDate');
            const endDateEl = document.getElementById('endDate');
            const start = startDateEl.value;
            const end = endDateEl.value;

            if (!start || !end) {
                alert('请选择开始日期和结束日期');
                return;
            }

            const startDateObj = new Date(start);
            const endDateObj = new Date(end);

            if (startDateObj > endDateObj) {
                alert('结束日期不能早于开始日期，请重新选择');
                endDateEl.value = '';
                endDate = '';
                return;
            }

            const maxDays = 31;
            if ((endDateObj - startDateObj) / (1000 * 60 * 60 * 24) > maxDays) {
                alert(`日期范围不能超过${maxDays}天`);
                return;
            }

            currentPeriod = null;
            startDate = start;
            endDate = end;
            document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
            renderTableByDateRange(startDateObj, endDateObj);
            updatePageLinks();
        }, 100));
    }

    // 6. 日期列表生成（不变）
    function getDateListByPeriod(period) {
        const today = new Date();
        const year = today.getFullYear();
        let startDate, endDate;

        switch (period) {
            case 'lastWeek':
                const lastWeekStart = new Date(today);
                lastWeekStart.setDate(today.getDate() - (today.getDay() || 7) - 6);
                endDate = new Date(lastWeekStart);
                endDate.setDate(lastWeekStart.getDate() + 6);
                startDate = lastWeekStart;
                break;
            case 'lastMonth':
                const lastMonth = today.getMonth() - 1;
                startDate = new Date(year, lastMonth, 1);
                endDate = new Date(year, lastMonth + 1, 0);
                break;
            case 'currentMonth':
            default:
                startDate = new Date(year, today.getMonth(), 1);
                endDate = today;
                break;
        }

        return generateDateList(startDate, endDate);
    }

    function generateDateList(startDate, endDate) {
        const dateList = [];
        const maxDays = 31;
        let dayCount = 0;
        for (let d = new Date(startDate); d <= endDate && dayCount < maxDays; d.setDate(d.getDate() + 1)) {
            const label = `${d.getMonth() + 1}-${d.getDate()}`;
            const fullDate = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
            dateList.push({
                label,
                fullDate
            });
            dayCount++;
        }
        return dateList;
    }

    // 7. 表格渲染（不变）
    function renderTableByPeriod(period) {
        showLoading();
        const dateList = getDateListByPeriod(period);
        setTimeout(() => {
            renderTable(dateList);
            hideLoading();
        }, 0);
    }

    function renderTableByDateRange(startDate, endDate) {
        showLoading();
        const dateList = generateDateList(startDate, endDate);
        setTimeout(() => {
            renderTable(dateList);
            hideLoading();
        }, 0);
    }

    function renderTable(dateList) {
        const tableContainer = document.getElementById('tableContainer');
        const fragment = document.createDocumentFragment();
        const table = document.createElement('table');
        table.className = 'inspection-matrix';

        // 构建表头（包含巡检率列）
        let theadHtml = `<tr>
        <th class="project-column text-ellipsis">巡检项目</th>`;
        dateList.forEach(date => {
            theadHtml += `<th>${date.label}</th>`;
        });
        theadHtml += `<th class="inspection-rate-column">巡检达成率</th></tr>`;
        table.innerHTML = `<thead>${theadHtml}</thead><tbody></tbody>`;
        const tbody = table.querySelector('tbody');

        const dateMap = new Map(dateList.map(date => [date.fullDate, true]));
        const totalDays = dateList.length; // 总天数
        const totalProjects = dataList.length; // 总项目数

        // ========== 新增：每日巡检达成率固定行（核心修改） ==========
        const today = new Date();
        const todayFullDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

        const dailyRateSummaryTr = document.createElement('tr');
        dailyRateSummaryTr.style.position = 'sticky';
        dailyRateSummaryTr.style.top = '0';
        dailyRateSummaryTr.style.zIndex = '3';
        dailyRateSummaryTr.style.background = '#e8f4ff';
        dailyRateSummaryTr.style.fontWeight = '600';

        let dailyRateHtml = `<td class="project-column">每日巡检达成率</td>`;
        dateList.forEach(date => {
            const currentDateFull = date.fullDate;
            let inspectedCount = 0;

            // 统计当前日期已巡检项目数
            dataList.forEach(project => {
                if (project.detailDataList && project.detailDataList.length > 0) {
                    const hasInspection = project.detailDataList.some(record => {
                        const recordDate = new Date(record.add_time * 1000);
                        const recordFullDate = `${recordDate.getFullYear()}-${String(recordDate.getMonth() + 1).padStart(2, '0')}-${String(recordDate.getDate()).padStart(2, '0')}`;
                        return recordFullDate === currentDateFull;
                    });
                    if (hasInspection) inspectedCount++;
                }
            });

            // 计算并渲染达成率
            const rate = totalProjects > 0 ? (inspectedCount / totalProjects) * 100 : 0;
            const rateText = rate.toFixed(2) + '%';

            // 当前日期标黄突出
            if (currentDateFull === todayFullDate) {
                dailyRateHtml += `<td style="background: #fff3cd; color: #856404;">${rateText}</td>`;
            } else {
                dailyRateHtml += `<td>${rateText}</td>`;
            }
        });

        // 最后一列（巡检率列）显示“-”（无意义，仅占位）
        dailyRateHtml += `<td class="inspection-rate-column">-</td>`;
        dailyRateSummaryTr.innerHTML = dailyRateHtml;
        tbody.insertBefore(dailyRateSummaryTr, tbody.firstChild);
        // ========== 固定行结束 ==========

        if (dataList.length > 0) {
            dataList.forEach(project => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                <td class="project-column">
                    <span class="text-ellipsis">${project.cate_name || '未知项目'}</span>
                    <span class="project-tooltip">${project.cate_name || '未知项目'}</span>
                </td>
            `;

                // 计算该项目已巡检天数
                const inspectedDates = new Set();
                if (project.detailDataList && project.detailDataList.length > 0) {
                    project.detailDataList.forEach(record => {
                        if (record.add_time) {
                            const recordDate = new Date(record.add_time * 1000);
                            const recordFullDate = `${recordDate.getFullYear()}-${String(recordDate.getMonth() + 1).padStart(2, '0')}-${String(recordDate.getDate()).padStart(2, '0')}`;
                            if (dateMap.has(recordFullDate)) {
                                inspectedDates.add(recordFullDate);
                            }
                        }
                    });
                }
                const inspectedDays = inspectedDates.size;

                // 生成项目的每日巡检状态
                dateList.forEach(date => {
                    const isInspected = inspectedDates.has(date.fullDate);
                    const td = document.createElement('td');
                    td.innerHTML = `
                    <span class="status-icon ${isInspected ? 'status-completed' : 'status-missed'}">
                        <i class="fa ${isInspected ? 'fa-check' : 'fa-times'}"></i>
                    </span>
                `;
                    tr.appendChild(td);
                });

                // 项目的整体巡检率（已巡检天数/总天数）
                const rateTd = document.createElement('td');
                rateTd.className = 'inspection-rate-column';
                const rate = totalDays > 0 ? (inspectedDays / totalDays) * 100 : 0;
                rateTd.textContent = rate.toFixed(2) + '%';
                tr.appendChild(rateTd);

                tbody.appendChild(tr);
            });
        } else {
            // 空状态（适配固定行和巡检率列）
            const emptyTr = document.createElement('tr');
            emptyTr.innerHTML = `
            <td colspan="${2 + dateList.length}" class="empty-state">
                <i class="fa fa-calendar-o"></i>
                <div>暂无巡检项目数据</div>
            </td>
        `;
            tbody.appendChild(emptyTr);
        }

        fragment.appendChild(table);
        tableContainer.innerHTML = '';
        tableContainer.appendChild(fragment);
    }

    // 8. 加载状态（不变）
    function showLoading() {
        const tableContainer = document.getElementById('tableContainer');
        tableContainer.innerHTML = `
            <div class="empty-state">
                <i class="fa fa-spinner fa-spin"></i>
                <div>加载中...</div>
            </div>
        `;
    }

    function hideLoading() {
        // 由renderTable接管
    }

    // 9. 防抖函数（不变）
    function debounce(fn, delay) {
        let timer = null;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(timer);
            timer = setTimeout(() => {
                fn.apply(context, args);
            }, delay);
        };
    }
</script>

<include file="Public:footer" />