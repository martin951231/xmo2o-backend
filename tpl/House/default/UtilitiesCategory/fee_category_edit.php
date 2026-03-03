<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <a href="{pigcms{:U('UtilitiesCategory/fee_category_index')}">分类管理</a>
            </li>
            <li class="active">添加分类</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <div class="row">
                <div class="col-xs-12">
                    <form class="form-horizontal" method="post" onSubmit="return check_submit();" action="__SELF__">
                        <input type="hidden" value="{pigcms{$category_info.id}" name="id">
                        <div class="tab-content">
                            <!-- 基础信息标签页 -->
                            <div id="basicinfo" class="tab-pane active">
                                <!-- 原有：分类名称 -->
                                <div class="form-group">
                                    <label class="col-sm-2"><label for="name">分类名称：</label></label>
                                    <input style="padding-left:12px;" class="col-sm-2" size="20" name="name" id="name" type="text" value="{pigcms{$category_info['name']}"/>
                                </div>

                                <!-- 新增：联动分类选择（渲染一级分类 + 二级分类联动） -->
                                <div class="form-group">
                                    <label class="col-sm-2"><label for="parent_id">所属一级分类：</label></label>
                                    <select class="col-sm-2" name="parent_id" id="parent_id">
                                        <option value="">请选择一级分类</option>
                                        <!-- 渲染后端传递的一级分类数据 -->
                                        <foreach name="newCategoryList" item="cateVo">
                                            <if condition="$cateVo.parent_id eq 0">
                                                <option <if condition="$category_info['parent_id'] eq $cateVo['id']">selected="selected"</if> value="{pigcms{$cateVo.id}"> <!-- 补全闭合大括号，避免value为空 -->
                                                    {pigcms{$cateVo.name}
                                                </option>
                                            </if>
                                        </foreach>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-2"><label for="unit">单位：</label></label>
                                    <input style="padding-left:12px;" class="col-sm-2" size="20" name="unit" id="unit" type="text" value="{pigcms{$category_info['unit']}"/>
                                </div>

                                <!-- 原有：状态单选框 -->
                                <div class="form-group">
                                    <label class="col-sm-2"><label for="status">状态</label></label>
                                    <label><input name="status" <if condition="$category_info['status'] eq 1">checked</if> type="radio" value="1"/>&nbsp;&nbsp;正常</label>
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
        $("#first_category").change(function() {
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


    // 保留原有提交校验函数
    function check_submit() {
        // 可添加表单校验逻辑，比如必填项检查
        var name = $("#name").val().trim();
        var unit = $("#unit").val().trim();
        if (!name) {
            alert("分类不能为空！");
            return false;
        }
        if (!unit) {
            alert("单位不能为空！");
            return false;
        }
        return true;
    }
</script>




<include file="Public:footer"/>