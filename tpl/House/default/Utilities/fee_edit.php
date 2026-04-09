<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<a href="{pigcms{:U('Utilities/fee_index')}">能耗记录</a>
			</li>
			<li class="active">添加记录</li>
		</ul>
	</div>
	<!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <div class="row">
                <div class="col-xs-12">
                    <form class="form-horizontal" method="post" onSubmit="return check_submit();" action="__SELF__">
                        <div class="tab-content">
                            <!-- 基础信息标签页 -->
                            <div id="basicinfo" class="tab-pane active">
                                <!-- 原有：分类名称 -->
<!--                                <div class="form-group">-->
<!--                                    <label class="col-sm-2"><label for="name">分类名称：</label></label>-->
<!--                                    <input style="padding-left:12px;" class="col-sm-2" size="20" name="name" id="name" type="text" value=""/>-->
<!--                                </div>-->

                                <!-- 新增：年月下拉框（渲染pigcms的monthList） -->
                                <div class="form-group">
                                    <label class="col-sm-2"><label for="year_month">年月：</label></label>
                                    <select class="col-sm-2" name="year_month" id="year_month">
                                        <option value="">请选择年月</option>
                                        <foreach name="monthList" item="vo">
                                            <option <if condition="$category_info['add_date'] eq $vo['value']">selected="selected"</if> value="{pigcms{$vo.value}">
                                                {pigcms{$vo.label}
                                            </option>
                                        </foreach>
                                    </select>
                                </div>

                                <!-- 新增：联动分类选择（渲染一级分类 + 二级分类联动） -->
                                <div class="form-group">
                                    <label class="col-sm-2"><label for="parent_id">所属一级分类：</label></label>
                                    <select class="col-sm-2" name="parent_id" id="parent_id">
                                        <option value="">请选择一级分类</option>
                                        <!-- 渲染后端传递的一级分类数据 -->
                                        <foreach name="newCategoryList" item="cateVo">
                                            <if condition="$cateVo.parent_id eq 0">
                                                <option value="{pigcms{$cateVo.id}"> <!-- 补全闭合大括号，避免value为空 -->
                                                    {pigcms{$cateVo.name}
                                                </option>
                                            </if>
                                        </foreach>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-2"><label for="second_category">所属二级分类：</label></label>
                                    <select class="col-sm-2" name="second_category" id="second_category">
                                        <option value="">请先选一级分类</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-2"><label for="unit">用量：</label></label>
                                    <input style="padding-left:12px;"
                                           class="col-sm-2"
                                           size="20"
                                           name="consumption"
                                           id="consumption"
                                           type="text"
                                           value="{pigcms{$category_info['consumption']}"
                                           oninput="limitTwoDecimal(this)"
                                           onblur="formatTwoDecimal(this)"
                                           placeholder="请输入数字（最多两位小数）"
                                    />
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-2"><label for="unit">费用：</label></label>
                                    <input style="padding-left:12px;"
                                           class="col-sm-2"
                                           size="20"
                                           name="rate"
                                           id="rate"
                                           type="text"
                                           value="{pigcms{$category_info['rate']}"
                                           oninput="limitTwoDecimal(this)"
                                           onblur="formatTwoDecimal(this)"
                                           placeholder="请输入数字（最多两位小数）"
                                    />
                                </div>

                                <!-- 原有：状态单选框 -->
                                <div class="form-group">
                                    <label class="col-sm-2"><label for="water_price">状态</label></label>
                                    <label><input name="status" <if condition="$category_info['status'] eq 1">checked</if> type="radio" value="1" checked/>&nbsp;&nbsp;正常</label>
                                    &nbsp;&nbsp;&nbsp;
                                    <label><input name="status" <if condition="$category_info['status'] eq 0">checked</if> type="radio" value="0"/>&nbsp;&nbsp;禁止</label>
                                </div>
                            </div>

                            <!-- 保存按钮 -->
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

</div>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript">

    const allCategoryList = JSON.parse(`{pigcms{$newCategoryList|json_encode}`);
    // 页面加载完成后初始化
    $(function () {
        // 监听一级分类下拉框变化
        $("#parent_id").change(function() {
            // 获取选中的一级分类ID
            const firstCateId = $(this).val();
            // 清空二级分类下拉框
            const $secondSelect = $("#second_category");
            $secondSelect.empty();

            // 如果未选择一级分类，显示提示
            if (!firstCateId) {
                $secondSelect.append('<option value="">请先选一级分类</option>');
                return;
            }
            // 3. 筛选newCategoryList中parent_id等于一级分类ID的二级分类
            const secondCateList = allCategoryList.filter(item => {
                // 匹配parent_id（注意字段名和后端一致：parent_id）
                return item.parent_id == firstCateId;
            });

            // 4. 渲染二级分类下拉框
            if (secondCateList.length === 0) {
                $secondSelect.append('<option value="">暂无二级分类</option>');
            } else {
                // 先加默认提示
                $secondSelect.append('<option value="">请选择二级分类</option>');
                // 循环渲染二级分类选项
                secondCateList.forEach(cate => {
                    $secondSelect.append(`
                    <option value="${cate.id}">${cate.name}</option>
                `);
                });
            }
        });
    });

    function limitTwoDecimal(obj) {
        // 清除非数字和小数点的字符
        obj.value = obj.value.replace(/[^\d.]/g, '');
        // 只保留第一个小数点
        obj.value = obj.value.replace(/\.{2,}/g, '.');
        // 小数点后最多两位
        obj.value = obj.value.replace('.', '$#$').replace(/\./g, '').replace('$#$', '.');
        obj.value = obj.value.replace(/^(\-)*(\d+)\.(\d\d).*$/, '$1$2.$3');
        // 禁止以小数点开头（如.12 → 0.12）
        if (obj.value.indexOf('.') === 0) {
            obj.value = '0' + obj.value;
        }
        // 禁止开头为0且后面跟数字（如012.34 → 12.34）
        if (obj.value.indexOf('0') === 0 && obj.value.length > 1 && obj.value.charAt(1) !== '.') {
            obj.value = obj.value.substring(1);
        }
    }

    // 2. 失去焦点自动补两位小数（如输入12 → 12.00，输入12.3 → 12.30）
    function formatTwoDecimal(obj) {
        if (!obj.value) {
            obj.value = '';
            return;
        }
        // 转成数字后保留两位小数
        let num = parseFloat(obj.value);
        if (isNaN(num)) {
            obj.value = '';
            return;
        }
        obj.value = num.toFixed(2);
    }

    // 保留原有提交校验函数
    function check_submit() {
        // 可添加表单校验逻辑，比如必填项检查
        var date = $("#year_month").val().trim();
        var name = $("#parent_id").val().trim();
        var secondName = $("#second_category").val().trim();
        var consumption = $("#consumption").val().trim();
        var rate = $("#rate").val().trim();
        if (!name) {
            alert("一级分类不能为空！");
            return false;
        }
        if (!secondName) {
            alert("二级分类不能为空！");
            return false;
        }
        if (!date) {
            alert("年月不能为空！");
            return false;
        }
        if (!consumption) {
            alert("用量不能为空！");
            return false;
        }
        if (!rate) {
            alert("费用不能为空！");
            return false;
        }
        return true;
    }
</script>




<include file="Public:footer"/>