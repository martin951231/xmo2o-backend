<include file="Public:header"/>
<div class="main-content">
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear"></i>
				<a href="{pigcms{:U('Repair/index')}">报修管理</a>
			</li>
			<li class="active">添加报修</li>
		</ul>
	</div>
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
                <div class="tabbable">
                    <ul id="myTab" class="nav nav-tabs">
                        <li>
                            <a href="{pigcms{:U('Repair/index',$_GET)}">在线报修列表</a>
                        </li>
                        <li class="active">
                            <a href="javascript:;" data-toggle="tab">在线报修配置</a>
                        </li>
                    </ul>
                </div>

				<div class="col-xs-12">
					<form  class="form-horizontal" method="post">
						<div class="tab-content">
							<div class="form-group">
								<label class="col-sm-2"><label for="phone">在线报修可能会存在收费</label></label>
								<label><input name="repair_config" <if condition="$house_village['repair_config'] eq 1">checked="checked"</if>  type="radio" value="1"/>&nbsp;&nbsp;是</label>
								&nbsp;&nbsp;&nbsp;
								<label><input name="repair_config" <if condition="$house_village['repair_config'] eq 0">checked="checked"</if> type="radio" value="0"/>&nbsp;&nbsp;否</label>
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


<include file="Public:footer"/>