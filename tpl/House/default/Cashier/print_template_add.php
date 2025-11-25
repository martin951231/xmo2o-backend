<include file="Public:header"/>

<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-tablet"></i>
				<a href="{pigcms{:U('print_template_list')}">打印模板设置</a>
			</li>
			<li class="active">添加打印模板</li>
		</ul>
	</div>
	<!-- 内容头部 -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
        .page_top{
            background-color: #e6f7ff;
            display: inline-block;
            width: 100%;
            padding: 20px 20px;
            margin-bottom: 20px;
            color: #666666;
        }

			</style>
			<div class="row">
				<span class="page_top">
              <span class="notice">
                  注意：<br/>
								1、填写打印标题时，设置模板的预览区中的标题，展示打印标题的内容，不填则默认展示模板名称<br/>
								2、模板打印标题不填，打印时则不展示标题<br/>
								3、一行列数设置仅支持页眉页脚，且列数越大，会导致每列间距变小，请适当调配
              </span>
          </span>

				<div class="col-xs-12">
					<form  class="form-horizontal" >
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1" style="margin-left: 30%"><label for="title" style="width: 65px;"><font style="color: red;">*</font>模板名称</label></label>
									<input class="col-sm-2" size="20" name="title" id="title" type="text"  value="{pigcms{$print_template.title}" />
								</div>
								<div class="form-group">
									<label class="col-sm-1" style="margin-left: 30%"><label for="top_title" style="width: 65px;">打印标题</label></label>
									<input class="col-sm-2" size="20" name="top_title" id="top_title" type="text"  value="{pigcms{$print_template.top_title}" />
								</div>
								<div class="form-group">
									<label class="col-sm-1" style="margin-left: 30%"><label for="col_num" style="width: 65px;">一行列数</label></label>
									<select id="col_num" class="col-sm-1" value="{pigcms{$print_template.col_num}">
										<option value="3">3</option>
										<option value="4">4</option>
										<option value="5">5</option>
									</select>
								</div>
								<div class="form-group">
									<label class="col-sm-1" style="margin-left: 30%"><label for="desc">说明</label></label>
									<!-- <input class="col-sm-2" size="20" name="desc" id="desc" type="text"  value="{pigcms{$print_template.desc}" /> -->
									<textarea name="desc" id="desc"  style="width:400px; height:150px">{pigcms{$print_template.desc}</textarea>
								</div>
							</div>
						</div>
						<div class="space"></div>
							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
                                    <if condition="in_array(87,$house_session['menus']) || in_array(88,$house_session['menus'])">
									<button class="btn btn-info" onclick="check_submit();return false;" style="margin-left: 25%;">
										<i class="ace-icon fa fa-check bigger-110"></i>
										下一步
									</button> &nbsp;&nbsp;
									<else/>
									<button class="btn btn-info" disabled="disabled" style="margin-left: 25%;">
										<i class="ace-icon fa fa-check bigger-110"></i>
										下一步
									</button>
									</if>
									<a href="{pigcms{:U('print_template_list')}" class="btn">
										返回
									</a>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
<script type="text/javascript">
	$(function (){
	  let col_num = '{pigcms{$print_template.col_num}'
		$("#col_num").val(col_num)
	})
function check_submit(){
	var title = $('#title').val();
	var top_title = $('#top_title').val();
	var col_num = $('#col_num').val();
	var desc = $('#desc').val();
	if(title == ''){
		alert('模板名称不可以为空');
		return false;
	}
	$.post("{pigcms{:U('print_template_save',array('template_id'=>$template_id))}",{title:title,desc:desc,top_title:top_title,col_num:col_num},function(data){
		var url='{pigcms{:U('print_template_custom')}';
		if(data.status==0){
			window.location.href=url+"&template_id="+data.data.template_id;
		}else{
			alert(data.msg);return false;
		}
	},'json')

}
</script>

<include file="Public:footer"/>