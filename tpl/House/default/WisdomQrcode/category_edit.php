<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
    <div class="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <a href="{pigcms{:U('WisdomQrcode/category_index')}">分类管理</a>
            </li>
            <li class="active"> 编辑分类</li>
        </ul>
    </div>
	<!-- 内容头部 -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<form class="form-horizontal" method="post" onSubmit="return check_submit();" action="__SELF__">
						<div class="tab-content">
							<input type="hidden" value="{pigcms{$category['id']}" name="id">
<!--							<input type="hidden" value="{pigcms{$_GET['cate_id']}" name="cate_id">-->

                            <div id="basicinfo" class="tab-pane active">
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="name">分类名称：</label></label>
                                    <input class="col-sm-2" size="20" name="name" id="name" type="text" value="{pigcms{$category['name']}"/>
                                </div>
                            </div>
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1"><label for="status">状态</label></label>
									<label><input name="status" <if condition="$category.status eq 1">checked="checked" </if> type="radio" value="1"/>&nbsp;&nbsp;正常</label>
									&nbsp;&nbsp;&nbsp;
									<label><input name="status" <if condition="$category.status eq 0">checked="checked" </if> type="radio" value="0"/>&nbsp;&nbsp;禁止</label>
								</div>
							</div>

							<div class="space"></div>
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
<script>
function check_submit(){
	if($('#name').val()==''){
		alert('名称不能为空！');
		return false;
	}
}
</script>




<include file="Public:footer"/>