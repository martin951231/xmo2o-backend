<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-gear gear-icon"></i>
                <a href="{pigcms{:U('WisdomQrcode/cate_index')}">智慧巡检码</a>
            </li>
            <li class="active">添加类别</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <div class="row">
                <div class="col-xs-12">
                    <form class="form-horizontal" method="post" onSubmit="return check_submit();" action="__SELF__">
                        <div class="tab-content">
                            <input type="hidden" id="qrcodeCheckList" name="qrcodeCheckList">
                            <div id="basicinfo" class="tab-pane active">
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="name">类别名称：</label></label>
                                    <input class="col-sm-2" size="20" name="cate_name" id="cate_name" type="text"
                                           value=""/>
                                </div>
                            </div>
                            <div id="basicinfo" class="tab-pane active">
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="name">距离设置：</label></label>
                                    工作人员需距离扫码点<input style="width: 100px;" name="distance" type="text"
                                                               value=""/>
                                    米,才可扫码记录内容(不填默认不限距离)
                                </div>
                            </div>
                            <div id="basicinfo" class="tab-pane active">
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="type">负责人任务配置</label></label>
                                    <label class="col-sm-1"><input name="type" class="type" checked="checked"
                                                                   type="radio"
                                                                   value="1"/>&nbsp;&nbsp;按单个负责人</label>
                                    &nbsp;&nbsp;&nbsp;
                                    <label class="col-sm-1"><input name="type" class="type" type="radio" value="2"/>&nbsp;&nbsp;按所有负责人</label>
                                    &nbsp;&nbsp;<label class="col-sm-1" style="width: 70%"><font color="red">按所有负责人逻辑：类别名称（
                                            例：小区环境巡检 ）下有设置多个负责人巡检时，只有其中一个负责人巡检过所有的二维码点位，所有负责人都算已完成巡检；<br/>按单个负责人逻辑：类别名称（
                                            例：小区设备巡检 ）下有设置多个负责人巡检时，如果是其中一个负责人巡检完成，只有当前负责人算完成，其他负责人不算完成巡检。</font></label>
                                </div>
                            </div>
                            <div id="basicinfo" class="tab-pane active">
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="water_price">记录时间</label></label>
                                    <label><input name="record_type" class="record_type" checked="checked" type="radio"
                                                  value="1"/>&nbsp;&nbsp;每日</label>
                                    &nbsp;&nbsp;&nbsp;
                                    <label><input name="record_type" class="record_type" type="radio" value="2"/>&nbsp;&nbsp;每周</label>
                                    &nbsp;&nbsp;&nbsp;
                                    <label><input name="record_type" class="record_type" type="radio" value="3"/>&nbsp;&nbsp;每月</label>
                                </div>
                            </div>
                            <div id="basicinfo" class="tab-pane active_ri">
                                <div class="form-group" id="choose_child_info" style="padding-left: 8.333%;">
                                    <div id="time-row-container">
                                        <!-- 初始行（保留原有结构） -->
                                        <volist name="cate.record_time" id="time" key="i">
                                            <div class="time-row" style="margin-bottom: 8px;">
                                                <label style="margin-right:10px;min-width: 55px;">
                                                    <input class="Config_day_start_time h30" size="20"
                                                           name="day_start_time[]"
                                                           type="time"/>&nbsp;&nbsp;至&nbsp;&nbsp;
                                                    <input class="Config_day_end_time h30" size="20"
                                                           name="day_end_time[]"
                                                           type="time"/>
                                                    <i class="ace-icon fa fa-trash-o add-icon-ri delete-row"></i>
                                                </label>
                                                <span class="record_time_raw"
                                                      style="display:none;">{pigcms{$time}</span>
                                            </div>
                                        </volist>
                                    </div>
                                    <!-- 添加按钮 -->
                                    <button class="btn btn-info margin-top-10" type="button" id="add-time-row">
                                        <i class="ace-icon fa fa-plus bigger-110"></i>添加
                                    </button>
                                </div>
                            </div>

                            <div id="basicinfo" class="tab-pane active_zhou">

                                <div class="form-group" id="choose_child_info" style="padding-left: 8.333%;">

                                    <label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="1"
                                               id="group_category_1"/>
                                        <span class="lbl"><label for="group_category_1">周一</label></span>
                                    </label>
                                    <label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="2"
                                               id="group_category_2"/>
                                        <span class="lbl"><label for="group_category_2">周二</label></span>
                                    </label>
                                    <label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="3"
                                               id="group_category_3"/>
                                        <span class="lbl"><label for="group_category_3">周三</label></span>
                                    </label>
                                    <label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="4"
                                               id="group_category_4"/>
                                        <span class="lbl"><label for="group_category_4">周四</label></span>
                                    </label>
                                    <label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="5"
                                               id="group_category_5"/>
                                        <span class="lbl"><label for="group_category_5">周五</label></span>
                                    </label>
                                    <label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="6"
                                               id="group_category_6"/>
                                        <span class="lbl"><label for="group_category_6">周六</label></span>
                                    </label>
                                    <label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="7"
                                               id="group_category_7"/>
                                        <span class="lbl"><label for="group_category_7">周日</label></span>
                                    </label>
                                </div>
                            </div>

                            <div id="basicinfo" class="tab-pane active_yue">

                                <div class="form-group" id="choose_child_info" style="padding-left: 8.333%;">

                                    <label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="1"
                                               id="group_category_1"/>
                                        <span class="lbl"><label for="group_category_1">1号</label></span>
                                    </label>
                                    <label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="2"
                                               id="group_category_2"/>
                                        <span class="lbl"><label for="group_category_2">2号</label></span>
                                    </label>
                                    <label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="3"
                                               id="group_category_3"/>
                                        <span class="lbl"><label for="group_category_3">3号</label></span>
                                    </label>
                                    <label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="4"
                                               id="group_category_4"/>
                                        <span class="lbl"><label for="group_category_4">4号</label></span>
                                    </label>
                                    <label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="5"
                                               id="group_category_5"/>
                                        <span class="lbl"><label for="group_category_5">5号</label></span>
                                    </label>
                                    <label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="6"
                                               id="group_category_6"/>
                                        <span class="lbl"><label for="group_category_6">6号</label></span>
                                    </label>
                                    <label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="7"
                                               id="group_category_7"/>
                                        <span class="lbl"><label for="group_category_7">7号</label></span>
                                    </label>
                                    <label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="8"
                                               id="group_category_8"/>
                                        <span class="lbl"><label for="group_category_8">8号</label></span>
                                    </label>
                                    <label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="9"
                                               id="group_category_9"/>
                                        <span class="lbl"><label for="group_category_9">9号</label></span>
                                    </label>
                                    <label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="10"
                                               id="group_category_10"/>
                                        <span class="lbl"><label for="group_category_10">10号</label></span>
                                    </label>
                                    <label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="11"
                                               id="group_category_11"/>
                                        <span class="lbl"><label for="group_category_11">11号</label></span>
                                    </label>
                                    <label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="12"
                                               id="group_category_12"/>
                                        <span class="lbl"><label for="group_category_12">12号</label></span>
                                    </label>
                                    <label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="13"
                                               id="group_category_13"/>
                                        <span class="lbl"><label for="group_category_13">13号</label></span>
                                    </label>
                                    <label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="14"
                                               id="group_category_14"/>
                                        <span class="lbl"><label for="group_category_14">14号</label></span>
                                    </label><label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="15"
                                               id="group_category_15"/>
                                        <span class="lbl"><label for="group_category_15">15号</label></span>
                                    </label>
                                    <label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="16"
                                               id="group_category_16"/>
                                        <span class="lbl"><label for="group_category_16">16号</label></span>
                                    </label>
                                    <label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="17"
                                               id="group_category_17"/>
                                        <span class="lbl"><label for="group_category_17">17号</label></span>
                                    </label>
                                    <label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="18"
                                               id="group_category_18"/>
                                        <span class="lbl"><label for="group_category_18">18号</label></span>
                                    </label><label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="19"
                                               id="group_category_19"/>
                                        <span class="lbl"><label for="group_category_19">19号</label></span>
                                    </label>
                                    </label><label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="20"
                                               id="group_category_20"/>
                                        <span class="lbl"><label for="group_category_20">20号</label></span>
                                    </label>
                                    </label><label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="21"
                                               id="group_category_21"/>
                                        <span class="lbl"><label for="group_category_21">21号</label></span>
                                    </label>
                                    </label><label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="22"
                                               id="group_category_22"/>
                                        <span class="lbl"><label for="group_category_22">22号</label></span>
                                    </label>
                                    </label><label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="23"
                                               id="group_category_23"/>
                                        <span class="lbl"><label for="group_category_23">23号</label></span>
                                    </label>
                                    </label><label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="24"
                                               id="group_category_24"/>
                                        <span class="lbl"><label for="group_category_24">24号</label></span>
                                    </label>
                                    </label><label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="25"
                                               id="group_category_25"/>
                                        <span class="lbl"><label for="group_category_25">25号</label></span>
                                    </label>
                                    </label><label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="26"
                                               id="group_category_26"/>
                                        <span class="lbl"><label for="group_category_26">26号</label></span>
                                    </label>
                                    </label><label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="27"
                                               id="group_category_27"/>
                                        <span class="lbl"><label for="group_category_27">27号</label></span>
                                    </label>
                                    </label><label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="28"
                                               id="group_category_28"/>
                                        <span class="lbl"><label for="group_category_28">28号</label></span>
                                    </label>
                                    </label><label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="29"
                                               id="group_category_29"/>
                                        <span class="lbl"><label for="group_category_29">29号</label></span>
                                    </label>
                                    </label><label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="30"
                                               id="group_category_30"/>
                                        <span class="lbl"><label for="group_category_30">30号</label></span>
                                    </label>
                                    </label><label style="margin-right:10px;min-width: 55px;">
                                        <input class="ace" type="checkbox" name="record_time[]" value="31"
                                               id="group_category_31"/>
                                        <span class="lbl"><label for="group_category_31">31号</label></span>
                                    </label>
                                </div>
                            </div>
                            <div id="basicinfo" class="tab-pane tab-contents active start_end_time">
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="name">起止时间：</label></label>
                                    <input class="Config_shop_start_time" readonly size="20" name="start_time"
                                           id="start_time" type="text" value=""/>&nbsp;&nbsp;至&nbsp;&nbsp;
                                    <input class="Config_shop_start_time" readonly size="20" name="end_time"
                                           id="end_time" type="text" value=""/>
                                </div>
                            </div>
                            <div id="basicinfo" class="tab-pane tab-contents active remind_time">
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="name">提醒时间：</label></label>
                                    将在
                                    <input class="Config_shop_start_time" readonly size="20" name="remind_time"
                                           id="record_name" type="text" value=""/>&nbsp;&nbsp;发送模板消息给工作人员
                                </div>
                            </div>
                            <div class="space"></div>
                            <div id="basicinfo" class="tab-pane tab-contents active ">
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="name">二维码：</label></label>
                                    <a href="#modal-table" class="btn btn-sm btn-success" id="show_map_frame"
                                       data-toggle="modal">查看二维码</a>
                                </div>
                            </div>
                            <div class="clearfix form-actions">
                                <div class="col-md-offset-3 col-md-9">
                                    <button class="btn btn-info btn-submit-qrcode" type="submit">
                                        <i class="ace-icon fa fa-check bigger-110"></i>
                                        保存
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div id="modal-table" class="modal fade map-modal" tabindex="-1" style="display:block;">
                <div class="modal-dialog" style="width:80%;">
                    <div class="modal-content" style="width:100%;">
                        <div class="modal-header no-padding" style="width:100%;">
                            <div class="table-header">
                                查看/添加二维码
                            </div>
                        </div>
                        <div class="modal-body no-padding" style="width:100%;">
                            <form id="map-search" style="margin:10px;">
                                <div>
                                    <!--                                    筛选-->
                                    <input
                                            type="text"
                                            placeholder="输入关键词筛选..."
                                            id="filter-keyword">
                                    <span
                                            id="clear-keyword"
                                            title="清空输入">清空</span>
                                </div>
                                <div>
                                    <div id="checkbox-container">
                                        <!-- 循环生成的多选框会插入到这里 -->
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer no-margin-top">
                            <!--                            <button class="btn btn-info btn-success btn-submit-qrcode" type="button">-->
                            <!--                                保存-->
                            <!--                            </button>-->
                            <button class="btn btn-close" type="button" data-dismiss="modal">
                                关闭
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(function () {
            // ==============================================
            // 全局变量定义（集中管理，便于维护）
            // ==============================================
            let rowIndex = 1; // 时间行唯一ID计数器
            let checkedIds = new Set(); // 存储选中的二维码ID（核心状态载体）
            let qrcodeCheckList = []; // 存储选中的二维码详情（用于提交）

            // DOM元素缓存（避免重复获取，提升性能）
            const $addTimeBtn = $('#add-time-row');
            const $timeRowContainer = $('#time-row-container');
            const filterInput = document.getElementById('filter-keyword');
            const clearBtn = document.getElementById('clear-keyword');
            const checkboxContainer = document.getElementById('checkbox-container');
            const $qrcodeCheckList = $('#qrcodeCheckList');

            // 二维码分组数据（初始化）
            const checkboxStr = `{pigcms{$qrcodeList}`;
            const checkboxList = JSON.parse(checkboxStr);

            // ==============================================
            // 1. 时间区间行 增删功能
            // ==============================================
            /**
             * 新增时间区间行
             */
            function addTimeRow() {
                const newRow = `
            <div class="time-row" style="margin-bottom: 8px;">
                <label style="margin-right:10px;min-width: 55px;">
                    <input class="Config_day_start_time h30"  size="20" name="day_start_time[]"
                           id="start_time_${rowIndex}" type="time" value=""/>&nbsp;&nbsp;至&nbsp;&nbsp;
                    <input class="Config_day_end_time h30"  size="20" name="day_end_time[]"
                           id="end_time_${rowIndex}" type="time" value=""/>
                    <i class="ace-icon fa fa-trash-o add-icon-ri delete-row"></i>
                </label>
            </div>
        `;
                $timeRowContainer.append(newRow);
                rowIndex++; // 计数器自增，保证ID唯一
            }

            /**
             * 删除当前时间区间行
             * @param {EventTarget} target - 点击的删除图标元素
             */
            function deleteTimeRow(target) {
                if (confirm('确定要删除这一行吗？')) {
                    $(target).closest('.time-row').remove();
                }
            }

            /**
             * 初始化时间区间行（填充默认时间）
             */
            function initTimeRows() {
                document.querySelectorAll('.time-row').forEach(function (row) {
                    const raw = row.querySelector('.record_time_raw')?.innerText;
                    if (!raw) return; // 无原始时间则跳过

                    const times = raw.split('-'); // 拆分 ["05:18","10:18"]
                    row.querySelector('input[name="day_start_time[]"]').value = times[0] || '';
                    row.querySelector('input[name="day_end_time[]"]').value = times[1] || '';
                });
            }

            // ==============================================
            // 2. 二维码分类多选 核心功能
            // ==============================================
            /**
             * 渲染分组式自定义多选框（隐藏原生input，自定义选中样式）
             * @param {Array} groupedList - 分组格式的二维码列表
             */
            function renderCheckboxes(groupedList) {
                checkboxContainer.innerHTML = '';

                // 无匹配结果时显示提示
                if (groupedList.length === 0) {
                    const emptyTip = document.createElement('div');
                    emptyTip.className = 'empty-tip';
                    emptyTip.textContent = '暂无匹配的选项';
                    checkboxContainer.appendChild(emptyTip);
                    return;
                }

                // 遍历每个分类分组
                groupedList.forEach(groupItem => {
                    // 1. 创建分类标题栏（含批量多选框）
                    const categoryTitleWrap = document.createElement('div');
                    categoryTitleWrap.className = 'checkbox-category-title-wrap';

                    const categoryCheckbox = document.createElement('input');
                    categoryCheckbox.type = 'checkbox';
                    categoryCheckbox.id = `category_${groupItem.category_id}`;
                    categoryCheckbox.name = `category_checkbox_${groupItem.category_id}`;
                    categoryCheckbox.className = 'category-batch-checkbox';

                    const categoryTitle = document.createElement('label');
                    categoryTitle.htmlFor = `category_${groupItem.category_id}`;
                    categoryTitle.className = 'checkbox-category-title';
                    categoryTitle.textContent = groupItem.category_name;

                    categoryTitleWrap.appendChild(categoryCheckbox);
                    categoryTitleWrap.appendChild(categoryTitle);
                    checkboxContainer.appendChild(categoryTitleWrap);

                    // 2. 创建分类选项包裹容器
                    const categoryOptionWrap = document.createElement('div');
                    categoryOptionWrap.className = 'checkbox-category-option-wrap';
                    categoryOptionWrap.dataset.categoryId = groupItem.category_id;
                    checkboxContainer.appendChild(categoryOptionWrap);

                    // 3. 初始化分类多选框状态（全选/半选/全不选）
                    const childIds = groupItem.data.map(item => item.id);
                    const isCategoryChecked = childIds.every(id => checkedIds.has(id));
                    const isNoneChecked = childIds.every(id => !checkedIds.has(id));
                    categoryCheckbox.checked = isCategoryChecked;
                    categoryCheckbox.indeterminate = !isCategoryChecked && !isNoneChecked;

                    // 4. 遍历渲染该分类下的子选项
                    groupItem.data.forEach(item => {
                        // 选项外层容器（核心点击区域）
                        const itemDiv = document.createElement('div');
                        itemDiv.className = 'checkbox-item';
                        if( item.isChecked ){
                            itemDiv.className = 'checkbox-item notChecked';
                        }
                        itemDiv.dataset.label = item.label;
                        itemDiv.dataset.isChecked = item.isChecked;
                        itemDiv.dataset.categoryId = groupItem.category_id;
                        itemDiv.dataset.itemId = item.id;

                        // 隐藏的原生多选框（仅作为状态载体）
                        const checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.id = item.id;
                        checkbox.name = 'custom-checkbox';
                        checkbox.value = item.id;
                        checkbox.className = 'hidden-checkbox';
                        checkbox.checked = checkedIds.has(item.id);

                        // 选项内容容器（文字 + 选中对号）
                        const itemContent = document.createElement('div');
                        itemContent.className = 'checkbox-item-content';

                        const itemLabel = document.createElement('span');
                        itemLabel.className = 'checkbox-item-label';
                        itemLabel.textContent = item.label;

                        const checkIcon = document.createElement('div');
                        checkIcon.className = 'checkbox-check-icon';

                        // 组装选项
                        itemContent.appendChild(itemLabel);
                        itemContent.appendChild(checkIcon);
                        itemDiv.appendChild(checkbox);
                        itemDiv.appendChild(itemContent);

                        // 点击切换选中状态
                        itemDiv.addEventListener('click', (e) => {
                            e.stopPropagation();
                            toggleItemCheck(itemDiv, item.id, groupItem.category_id, item.isChecked);
                        });

                        // 初始选中样式
                        if (checkbox.checked) {
                            itemDiv.classList.add('checked');
                        }

                        categoryOptionWrap.appendChild(itemDiv);
                    });

                    // 5. 分类批量选中/取消事件
                    categoryCheckbox.addEventListener('change', (e) => {
                        const isChecked = e.target.checked;
                        const categoryId = groupItem.category_id;
                        const childItems = document.querySelectorAll(`.checkbox-item[data-category-id="${categoryId}"]`);

                        childItems.forEach(childItem => {
                            const childId = childItem.dataset.itemId;
                            const prevIsChecked = childItem.dataset.isChecked;

                            if( prevIsChecked == true || prevIsChecked == 'true' ){
                                return
                            }
                            childItem.classList.toggle('checked', isChecked);

                            // 更新全局选中ID集合
                            if (isChecked) {
                                checkedIds.add(childId);
                            } else {
                                checkedIds.delete(childId);
                            }
                        });
                    });
                });
            }

            /**
             * 切换单个选项的选中状态，并同步分类多选框
             * @param {HTMLElement} itemDiv - 选项外层容器
             * @param {string} itemId - 选项ID
             * @param {string} categoryId - 所属分类ID
             */
            function toggleItemCheck(itemDiv, itemId, categoryId, itemIsChecked) {
                if( itemIsChecked ){
                    return
                }
                const isChecked = !itemDiv.classList.contains('checked');

                // 更新选项样式
                itemDiv.classList.toggle('checked', isChecked);
                // 更新全局选中ID
                isChecked ? checkedIds.add(itemId) : checkedIds.delete(itemId);
                // 同步分类多选框状态
                syncCategoryCheckboxStatus(categoryId);
            }

            /**
             * 同步分类多选框的全选/半选/全不选状态
             * @param {string} categoryId - 分类ID
             */
            function syncCategoryCheckboxStatus(categoryId) {
                const categoryCheckbox = document.getElementById(`category_${categoryId}`);
                if (!categoryCheckbox) return;

                const childItems = document.querySelectorAll(`.checkbox-item[data-category-id="${categoryId}"]`);
                const childIds = Array.from(childItems).map(el => el.dataset.itemId);

                const isAllChecked = childIds.every(id => checkedIds.has(id));
                const isNoneChecked = childIds.every(id => !checkedIds.has(id));

                categoryCheckbox.checked = isAllChecked;
                categoryCheckbox.indeterminate = !isAllChecked && !isNoneChecked;
            }

            /**
             * 获取所有选中的二维码详情，并同步到隐藏输入框
             * @returns {Array} 选中的二维码详情列表
             */
            function getCheckedOptions() {
                const result = [];
                checkboxList.forEach(group => {
                    group.data.forEach(item => {
                        if (checkedIds.has(item.id)) {
                            result.push({
                                id: item.id,
                                label: item.label,
                                category_id: group.category_id,
                                category_name: group.category_name
                            });
                        }
                    });
                });

                // 同步全局变量和隐藏输入框
                qrcodeCheckList = result;
                $qrcodeCheckList.val(JSON.stringify(qrcodeCheckList));
                console.log('当前选中的二维码：', qrcodeCheckList);

                return result;
            }

            // ==============================================
            // 3. 筛选与清空功能
            // ==============================================
            /**
             * 二维码筛选逻辑（防抖处理）
             * @param {Event} e - 输入框输入事件
             */
            function filterQrcodes(e) {
                let debounceTimer;
                clearTimeout(debounceTimer);

                debounceTimer = setTimeout(() => {
                    const keyword = e.target.value.trim().toLowerCase();

                    // 关键词为空，渲染原始数据
                    if (!keyword) {
                        renderCheckboxes(checkboxList);
                        return;
                    }

                    // 筛选逻辑：分类匹配保留所有子选项，否则筛选子选项
                    const filteredGroupedList = checkboxList.map(group => {
                        const isCategoryMatched = group.category_name.toLowerCase().includes(keyword);

                        if (isCategoryMatched) {
                            return {...group, data: [...group.data]};
                        } else {
                            const filteredData = group.data.filter(item =>
                                item.label.toLowerCase().includes(keyword)
                            );
                            return filteredData.length > 0 ? {...group, data: filteredData} : null;
                        }
                    }).filter(Boolean);

                    renderCheckboxes(filteredGroupedList);
                }, 300); // 300ms防抖，优化性能
            }

            /**
             * 清空筛选条件，恢复显示所有二维码
             */
            function clearFilter() {
                filterInput.value = '';
                renderCheckboxes(checkboxList); // 直接渲染原始数据，无需定时器
                filterInput.focus(); // 可选：聚焦输入框
            }

            // ==============================================
            // 4. 事件绑定与页面初始化
            // ==============================================
            function initPage() {
                // 时间行事件绑定
                $addTimeBtn.on('click', addTimeRow);
                $timeRowContainer.on('click', '.delete-row', function () {
                    deleteTimeRow(this);
                });

                // 筛选与清空事件绑定
                if (filterInput) {
                    filterInput.addEventListener('input', filterQrcodes);
                }
                if (clearBtn) {
                    clearBtn.addEventListener('click', clearFilter);
                }

                // 全局点击事件：实时更新选中结果（可根据需求调整触发时机）
                document.addEventListener('click', getCheckedOptions);

                // 初始化渲染
                initTimeRows(); // 初始化时间行
                renderCheckboxes(checkboxList); // 初始化二维码多选框
            }

            // 页面加载完成后初始化
            initPage();
        });

        function check_submit() {
            if ($('#cate_name').val() == '') {
                alert('名称不能为空！');
                return false;
            }
            let validatePass = true;
            let errorMsg = '';

            // 遍历所有时间段行验证
            $('.time-row label').each(function (index) {
                const rowNum = index + 1; // 行号从1开始
                const startTime = $(this).find('.Config_day_start_time').val().trim();
                const endTime = $(this).find('.Config_day_end_time').val().trim();

                // 1. 验证是否为空
                if (!startTime || !endTime) {
                    validatePass = false;
                    errorMsg = `第${rowNum}行时间段未填写完整，请补充开始时间和结束时间！`;
                    return false; // 终止遍历，优先提示未填问题
                }

                // 2. 验证开始时间是否大于结束时间（转成分钟数比较）
                const startMinutes = timeToMinutes(startTime);
                const endMinutes = timeToMinutes(endTime);
                if (startMinutes > endMinutes) {
                    validatePass = false;
                    errorMsg = `第${rowNum}行时间段异常：开始时间(${startTime})不能大于结束时间(${endTime})！`;
                    return false; // 终止遍历，提示时间顺序问题
                }
            });

            // 验证失败处理
            if (!validatePass) {
                alert(errorMsg); // 弹窗提示具体错误
                return false;
            }

            function timeToMinutes(timeStr) {
                const [hours, minutes] = timeStr.split(':').map(Number);
                return hours * 60 + minutes;
            }
        }

        $(".tab-contents").on("focus", "#start_time", function () {
            $(this).timepicker($.extend($.datepicker.regional['zh-cn'], {
                'timeFormat': 'hh:mm',
                'hour': '10',
                'minute': '00',
                'second': '00'
            }));
        });
        $(".tab-contents").on("focus", "#end_time", function () {
            $(this).timepicker($.extend($.datepicker.regional['zh-cn'], {
                'timeFormat': 'hh:mm',
                'hour': '18',
                'minute': '00',
                'second': '00'
            }));
        });
        $(".tab-contents").on("focus", "#record_name", function () {
            $(this).timepicker($.extend($.datepicker.regional['zh-cn'], {
                'timeFormat': 'hh:mm',
                'hour': '9',
                'minute': '00',
                'second': '00'
            }));
        });

        $().ready(function () {
            $(".active_ri").show();
            $(".active_yue").hide();
            $(".active_zhou").hide();
            $(".start_end_time").hide();
            $(".remind_time").hide();
        });

        $(".record_type").click(function () {
            if ($("input[name='record_type']:checked").val() == 1) {
                $(".active_ri").show();
                $(".active_zhou").hide();
                $(".active_yue").hide();
                $(".start_end_time").hide();
                $(".remind_time").hide();
            } else if ($("input[name='record_type']:checked").val() == 2) {
                $(".active_ri").hide();
                $(".active_zhou").show();
                $(".active_yue").hide();
                $(".start_end_time").show();
                $(".remind_time").show();
            } else if ($("input[name='record_type']:checked").val() == 3) {
                $(".active_ri").hide();
                $(".active_zhou").hide();
                $(".active_yue").show();
                $(".start_end_time").show();
                $(".remind_time").show();
            }
        })
    </script>
    <style>
        .delete-row {
            cursor: pointer;
        }

        .add-icon-ri {
            font-size: 20px;
            line-height: 20px;
            margin-left: 10px;
        }

        .w120 {
            /*width: 120px;*/
        }

        .h30 {
            height: 30px;
        }

        /* 全局重置 & 基础样式 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Microsoft YaHei", Arial, sans-serif;
        }

        /* 表单容器 */
        #map-search {
            margin: 10px;
            padding: 20px;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e8e8e8;
        }

        /* 筛选标题 */
        #map-search > div:first-child {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f3f4f6;
        }

        /* 筛选头部（标题+输入框） */
        .filter-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f3f4f6;
        }

        /* 给你的筛选输入框设置placeholder样式 */
        #filter-keyword::placeholder {
            /* 字体颜色（匹配你截图的浅灰色） */
            color: #696868;
            /* 字体大小 */
            font-size: 14px;
            /* 字体家族（和页面整体风格统一） */
            font-family: "PingFang SC", "Helvetica Neue", Helvetica, Arial, sans-serif;
            /* 可选：字体样式（斜体/正常） */
            font-style: normal;
            /* 可选：行高 */
            line-height: 1.5;
            /* 修复Firefox默认半透明 */
            opacity: 1;
        }

        /* Firefox 旧版本兼容 */
        #filter-keyword:-moz-placeholder {
            color: #696868;
            font-size: 14px;
            font-family: "PingFang SC", "Helvetica Neue", Helvetica, Arial, sans-serif;
            opacity: 1;
        }

        /* 输入筛选框 */
        #filter-keyword { /* 关键：给输入框加id，和JS里的filterInput对应 */
            width: calc(100% - 40px);
            padding: 6px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 400;
            outline: none;
            transition: border-color 0.2s;
        }

        #filter-keyword:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
        }

        #clear-keyword {
            color: #696868;
            cursor: pointer;
            font-size: 14px;
            font-weight: 400;
            user-select: none;
            margin-left: 10px;
        }

        #clear-keyword:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
        }

        /* 容器基础样式 */
        .checkbox-container {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            max-width: 500px;
        }

        /* 分类标题栏样式 */
        .checkbox-category-title-wrap {
            display: flex;
            align-items: center;
            margin: 8px 5px;
            /*cursor: pointer;*/
            height: 36px;
        }

        .checkbox-category-title-wrap:first-child {
            margin-top: 0;
        }

        .category-batch-checkbox {
            margin-right: 10px;
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .checkbox-category-title {
            font-weight: 600;
            color: #212529;
            font-size: 16px;
            cursor: pointer;
            user-select: none;
            margin: 0 0 0 8px;
        }

        /* 分类选项包裹容器 */
        .checkbox-category-option-wrap {
            padding-left: 26px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
        }

        /* 选项盒子样式（核心） */
        .checkbox-item {
            position: relative;
            background: #ffffff;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 12px 15px;
            cursor: pointer;
            transition: all 0.2s ease;
            overflow: hidden;
        }

        .checkbox-item:hover {
            border-color: #adb5bd;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        /* 选中状态样式 */
        .checkbox-item.checked {
            border-color: #0d6efd;
            background: #e7f1ff;
            box-shadow: 0 2px 8px rgba(13, 110, 253, 0.15);
        }

        /* 隐藏原生多选框 */
        .hidden-checkbox {
            position: absolute;
            opacity: 0;
            pointer-events: none;
            z-index: -1;
        }

        /* 选项内容样式 */
        .checkbox-item-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .checkbox-item-label {
            color: #212529;
            font-size: 14px;
            user-select: none;
        }

        /* 选中对号图标（美观大气） */
        .checkbox-check-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #0d6efd;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transform: scale(0.8);
            transition: all 0.2s ease;
        }

        .checkbox-item.checked .checkbox-check-icon {
            opacity: 1;
            transform: scale(1);
        }

        .checkbox-check-icon::after {
            content: "";
            width: 6px;
            height: 12px;
            border: 2px solid #ffffff;
            border-left: none;
            border-top: none;
            transform: rotate(45deg);
        }

        /* 无结果提示 */
        .empty-tip {
            padding: 25px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }

        #confirmBtn:hover {
            background: #0b5ed7;
        }

        /* 响应式适配 */
        @media (max-width: 768px) {
            #checkbox-container {
                grid-template-columns: 1fr;
            }

            #map-search {
                padding: 16px;
            }
        }

        .btn-close {
            color: #4A4A4A !important;
            background-color: #F2F2F2 !important; /* 按钮原有浅灰 */
            border-color: #F2F2F2 !important; /* 边框与按钮背景一致 */
            border-radius: 4px !important;
            transition: all 0.2s ease !important;
            outline: none !important;
            box-shadow: 0 0 0 1px rgba(200, 200, 200, 0.3) !important; /* 极细描边，区分弹窗背景 */
        }

        .btn-close:focus, .btn-close:hover {
            color: #333333 !important;
            background-color: #E5E5E5 !important;
            border-color: #E5E5E5 !important;
            box-shadow: 0 0 0 1px rgba(180, 180, 180, 0.5), 0 0 0 2px rgba(190, 190, 190, 0.2) !important;
        }

        .modal-footer {
            background: #ffffff;
        }

        #clear-keyword:hover {
            color: #2563eb; /* 悬浮时变蓝色 */
        }

        #clear-keyword svg {
            transition: fill 0.2s;
        }

        #clear-keyword:hover svg {
            fill: #2563eb;
        }
        .notChecked{
            background-color: #e3e3e3;
            cursor: no-drop;
        }
        .notChecked checkbox-item-label{
            color: #686b6b;
        }
    </style>


    <include file="Public:footer"/>