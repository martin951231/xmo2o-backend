<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <!--	<div class="breadcrumbs" >-->
    <!--		<ul class="breadcrumb">-->
    <!--			<li>-->
    <!--				<i class="ace-icon fa fa-gear gear-icon"></i>-->
    <!--				<a href="{pigcms{:U('WisdomQrcode/cate_index')}">智慧巡检码</a>-->
    <!--			</li>-->

    <!--			<li class="active">二维码管理</li>-->
    <!--		</ul>-->
    <!--	</div>-->
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <!--            下拉筛选框-->
            <div class="category-select-wrapper">
                <label class="category-select-label"><label for="category_id">所属分类：</label></label>
                <select name="categoryId" id="categoryId">
                    <option value="0">请选择分类</option>
                    <foreach name="categoryList" item="categoryList">
                        <option
                        <if condition="$cate['category_id'] eq $categoryList['id']">selected="selected"</if>
                        value="{pigcms{$categoryList.id}">
                        {pigcms{$categoryList.name}
                        </option>
                    </foreach>
                </select>
                <button class="btn btn-success" style="margin-left: 10px" onclick="clearCategory()">
                    清空
                </button>
                <button class="btn btn-success" style="margin-left: 10px" onclick="search()">查询</button>
            </div>

            <div class="row">

                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th width="5%">序号</th>
                                <th width="15%">巡检计划名称</th>
                                <th width="10%">二维码分类</th>
                                <th width="15%">二维码名称</th>
                                <th width="10%">工单名称</th>
                                <th width="5%">上报人</th>
                                <th width="8%">上报人电话</th>
                                <th width="8%">上报人部门</th>
                                <th width="8%">工单状态</th>
                                <th width="12%">添加时间</th>
                                <th class="button-column" width="15%">操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            <if condition="$dataList">
                                <volist name="dataList" id="vo">
                                    <tr class="<if condition=" $i%2 eq 0
                                    ">odd
                                    <else/>
                                    even
                            </if>
                            ">
                            <input type="hidden" id="currentItem{pigcms{$vo.id}" value="{pigcms{$vo.entication_field_arr|json_encode|htmlspecialchars}" >

                            <td>
                                <div class="tagDiv">{pigcms{$vo.id}</div>
                            </td>
                            <td>
                                <div class="tagDiv">{pigcms{$vo.plan_name}</div>
                            </td>
                            <td>
                                <div class="tagDiv">{pigcms{$vo.qrcode_category_name}</div>
                            </td>
                            <td>
                                <div class="tagDiv">{pigcms{$vo.qrcode_name}</div>
                            </td>
                            <td>
                                <div class="tagDiv">{pigcms{$vo.works_order_str}</div>
                            </td>
                            <td>
                                <div class="tagDiv">{pigcms{$vo.worker_name}</div>
                            </td>
                            <td>
                                <div class="tagDiv">{pigcms{$vo.phone}</div>
                            </td>
                            <td>
                                <div class="tagDiv">{pigcms{$vo.department_name}</div>
                            </td>
                            <td>
                                <div class="tagDiv">{pigcms{$vo.event_status_str}</div>
                            </td>
                            <td>
                                <div class="tagDiv">{pigcms{$vo.add_time|date="Y-m-d H:i:s",###}</div>
                            </td>
                            <td>
                                <div class="tagDiv">
                                    <a href="#modal-table" class="btn btn-sm btn-success" id="show_map_frame"
                                       data-toggle="modal" onclick="qrcode_detail(`{pigcms{$vo.id}`)">查看详情</a>
<!--                                    <a-->
<!--                                            href="?qrcode_id={pigcms{$vo.id}&spread=1&village_id={pigcms{$village_id}&name={pigcms{$vo.name}&wis_code=1&map_type=0&category_name={pigcms{$vo.category_name}"-->
<!--                                            class="see_qrcode">{pigcms{:L_("查看详情")}</a>-->
                                </div>
                            </td>
                            </tr>
                            </volist>
                            <else/>
                            <tr class="odd">
                                <td class="button-column" colspan="12">没有任何数据。</td>
                            </tr>
                            </if>
                            </tbody>
                        </table>
                        {pigcms{$pagebar}
                    </div>
                </div>
            </div>
        </div>
        <div id="modal-table" class="modal fade map-modal" tabindex="-1" style="display:block;">
            <div class="modal-dialog" style="width:80%;">
                <div class="modal-content" style="width:100%;">
                    <div class="modal-header no-padding" style="width:100%;">
                        <div class="table-title">
                            巡检异常详情表
                        </div>
                    </div>
                    <div class="modal-body no-padding" style="width:100%;">
                        <form id="map-search" style="margin:10px;">
<!--                            <div>-->
<!--                               -->
<!--                                <input-->
<!--                                        type="text"-->
<!--                                        placeholder="输入关键词筛选..."-->
<!--                                        id="filter-keyword">-->
<!--                                <span-->
<!--                                        id="clear-keyword"-->
<!--                                        title="清空输入">清空</span>-->
<!--                            </div>-->
                            <div>
                                <div id="abnormality_container">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer no-margin-top">
                        <button class="btn btn-close" type="button" data-dismiss="modal">
                            关闭
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
    $(function () {
        var searchCategoryId = "{pigcms{$searchCategoryId}";
        $('#categoryId').val(searchCategoryId)
    })

    function search() {
        // 1. 获取下拉框选中的值
        var categoryId = $('#categoryId').val();
        // 2. 先通过模板引擎获取基础URL（不带参数）
        var baseUrl = "{pigcms{:U('qrcode_abnormality_index')}";
        console.log( baseUrl )
        // 3. 用JS拼接参数（核心：避免模板引擎解析JS变量）
        var url = baseUrl + (categoryId ? '&categoryId=' + categoryId : '');
        // 4. 跳转
        window.location.href = url;
    }

    function clearCategory() {
        $('#categoryId').val('0')
        search()
    }
    function qrcode_detail2(id) {
        renderDataTable()
        console.log( id, currentVal )
    }
    function qrcode_detail(id) {
        var currentValJson = $('#currentItem'+id).val()
        var currentVal = JSON.parse(currentValJson)

        // 获取容器元素
        const container = document.getElementById('abnormality_container');
        if (!container) return;

        // 初始化表格HTML结构
        let tableHtml = `
<!--                <div class="table-title">巡检数据详情表</div>-->
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="30%">字段标题</th>
                            <th width="70%">当前值</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

        // 3. 多层遍历数据：test -> record_cate_field_config
        // 遍历test数组中的每一项
        currentVal.test.forEach(testItem => {
            // 遍历当前test项的record_cate_field_config数组（核心嵌套遍历）
            testItem.record_cate_field_config.forEach(fieldConfig => {
                // 处理可选选项（去除\r换行符，格式化为逗号分隔）
                const optionList = fieldConfig.use_field
                    .map(option => option.replace(/\r/g, '')) // 移除多余的\r字符
                    .join(', ');

                // 处理是否必填（转换为文字标注，添加样式类）
                const isMust = fieldConfig.is_must === 1
                    ? '<span class="must-flag">是</span>'
                    : '否';

                // 处理当前值（去除\r换行符，空值显示为"--"）
                const currentValue = fieldConfig.value.replace(/\r/g, '') || '--';

                // 拼接表格行（对应每一条fieldConfig数据）
                tableHtml += `
                        <tr>
                            <td class="text-ellipsis" title="${fieldConfig.title}">${fieldConfig.title}</td>
                            <td>${currentValue}</td>
                        </tr>
                    `;
            });
        });

        // 闭合表格HTML结构
        tableHtml += `
                    </tbody>
                </table>
            `;

        // 4. 将生成的表格HTML插入到容器中
        container.innerHTML = tableHtml;
    }
</script>
<style>
    .export_qrcode:focus {
        outline: none !important;
        outline-offset: 0 !important;
    }

    .category-select-wrapper {
        display: flex;
        align-items: center;
        margin: 10px 0;
        padding: 8px 0;
    }

    .category-select-label {
        width: 90px;
        text-align: left;
        padding-right: 15px;
        font-weight: 600;
        color: #333;
        font-size: 14px;
    }

    #categoryId {
        flex: 0 0 280px;
        height: 38px;
        padding: 6px 12px;
        font-size: 14px;
        line-height: 1.42857143;
        color: #555;
        background-color: #fff;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075);
        transition: border-color ease-in-out .15s, box-shadow ease-in-out .15s;
    }

    #categoryId:focus {
        border-color: #66afe9;
        outline: 0;
        box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075), 0 0 8px rgba(102, 175, 233, .6);
    }

    #categoryId option {
        padding: 5px 10px;
        font-size: 14px;
    }
    #abnormality_container {
        width: 100%;
        /*max-width: 1200px;*/
        /*margin: 5px auto;*/
        font-family: "Microsoft YaHei", Arial, sans-serif;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        border-radius: 8px;
        overflow: hidden;
    }

    /* 表格整体样式 */
    .data-table {
        width: 100%;
        border-collapse: collapse;
        background-color: #ffffff;
    }

    /* 表格头部样式 */
    .data-table thead tr {
        background-color: #2f5496;
        color: #ffffff;
    }

    .data-table thead th {
        padding: 16px 12px;
        text-align: left;
        font-weight: 500;
        font-size: 14px;
        border: none;
    }

    /* 表格主体样式 */
    .data-table tbody tr {
        border-bottom: 1px solid #f0f0f0;
        transition: background-color 0.3s ease;
    }

    /* 奇偶行隔行变色，提升可读性 */
    .data-table tbody tr:nth-child(even) {
        background-color: #f8f9fa;
    }

    /* 鼠标悬浮高亮 */
    .data-table tbody tr:hover {
        background-color: #eef2f7;
    }

    .data-table tbody td {
        padding: 14px 12px;
        font-size: 13px;
        color: #333333;
        border: none;
        vertical-align: middle;
    }

    /* 表格单元格内文本过长省略 */
    .data-table td.text-ellipsis {
        max-width: 200px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* 特殊字段（必填、选项）样式标注 */
    .must-flag {
        color: #dc3545;
        font-weight: bold;
    }

    .option-list {
        color: #28a745;
        font-size: 12px;
    }

    /* 表格标题样式 */
    .table-title {
        padding: 20px;
        font-size: 16px;
        font-weight: 600;
        color: #2f5496;
        background-color: #f5f7fa;
        border-bottom: 1px solid #e9ecef;
    }
    #shopList a{
        color: #428bca;
        text-decoration: none;
    }
</style>

<include file="Public:footer"/>