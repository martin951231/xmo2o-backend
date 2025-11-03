<include file="NewStreetPublic:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-tablet"></i>
				<a href="{pigcms{:U('NewsSreet/index',['iframe'=> $_GET['iframe']])}">新闻列表</a>
			</li>
			<li class="active">内容发布</li>
		</ul>
	</div>
	<!-- 内容头部 -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
                    <a href="javascript:;" onclick="window.history.go(-1);" class="btn btn-info" style="margin: 3px;">返回上一页</a>
					<form enctype="multipart/form-data" class="form-horizontal" method="post" id="edit_form" action="{pigcms{:U('NewsSreet/news_edit_do',['iframe'=> $_GET['iframe']])}">
					   <input  name="news_id" type="hidden" value="{pigcms{$news_info['news_id']}"/>
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1"><label for="title">标题</label></label>
									<input class="col-sm-2" size="80" name="title" id="title" type="text" value="{pigcms{$news_info['title']}" <if condition="$is_edit" >disabled</if>/>
								</div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="child_title">副标题</label></label>
                                    <input class="col-sm-2" size="80" name="child_title" id="child_title" type="text" value="{pigcms{$news_info['child_title']}"/>
                                </div>
								<div class="form-group">
									<label class="col-sm-1"><label for="title">选择分类</label></label>
									<select name='cat_id' <if condition="$is_edit" >disabled</if>>
									<volist name='news_categorys' id='cate'>
										<option  value='{pigcms{$cate.cat_id}' <if condition="$news_info['cat_id'] eq $cate['cat_id']" >selected</if> >{pigcms{$cate.cat_name}</option>
									</volist>
									</select>
								</div>

                                <if condition="$is_edit" >
                                    <div class="form-group" style="display: flex">
                                        <span class="col-sm-1" style="float: left;display: inline-block">发布内容</span>
                                        <div style="float: right;padding-right: 20px;display: inline-block;width: 92%">
                                            {pigcms{:htmlspecialchars_decode(replace_file_domain_content($news_info['content']))}
                                        </div>
                                    </div>
                                    <else/>
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="sort">发布内容</label></label>
                                        <textarea id="description" name="description"  placeholder="写上一些想要发布的内容">{pigcms{:replace_file_domain_content($news_info['content'])}</textarea>
                                </if>
                            </div>
                                </if>

								<div class="form-group title_img_box">
									<label class="col-sm-1"><label for="layer_room">新闻封面图：</label></label>
									<if condition="($_GET['look'] neq 1) && !($is_edit)">
										<img src="{pigcms{$static_path}area_street/img/add.png" id="input_file">
									</if>
									<img  src="{pigcms{$news_info['title_img']}" id="input_file_view" <if condition="$news_info['title_img'] eq ''">style="display:none;"</if>>
									<input type="hidden" value="{pigcms{$news_info['title_img']}" name="title_img" id="title_img" style="min-width: 20rem;height: 25px" placeholder="新闻封面图" tips="建议尺寸200*200">
								</div>
								<div class="form-group">
									<label class="col-sm-1">是否热门</label>
									<label><input <if condition="$is_edit" >disabled</if> value="1" name="is_hot" type="radio" <if condition="$news_info['is_hot'] eq 1"> checked="checked" </if> />&nbsp;&nbsp;是</label>
									&nbsp;&nbsp;&nbsp;
									<label><input <if condition="$is_edit" >disabled</if> value="0" name="is_hot" type="radio" <if condition="$news_info['is_hot'] neq 1"> checked="checked" </if> />&nbsp;&nbsp;否</label>
									<span class="form_tips">若设置为热门，则会靠前显示</span>
								</div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="title">展示模式</label></label>
                                    <label><input  value="1" name="show_mode" type="radio" <if condition="$news_info['show_mode'] eq 1"> checked="checked" </if> />&nbsp;&nbsp;纯文字</label>&nbsp;&nbsp;&nbsp;
                                    <label><input  value="2" name="show_mode" type="radio" <if condition="$news_info['show_mode'] eq 2"> checked="checked" </if> />&nbsp;&nbsp;单图模式</label>&nbsp;&nbsp;&nbsp;
                                    <label><input  value="3" name="show_mode" type="radio" <if condition="$news_info['show_mode'] eq 3"> checked="checked" </if> />&nbsp;&nbsp;多图模式</label>&nbsp;&nbsp;&nbsp;
                                </div>
<!--								<div class="form-group">-->
<!--									<label class="col-sm-1">是否重要通知</label>-->
<!--									<label><input  value="1" name="is_important_notice" type="radio" <if condition="$news_info['is_important_notice'] eq 1"> checked="checked" </if> />&nbsp;&nbsp;是</label>-->
<!--									&nbsp;&nbsp;&nbsp;-->
<!--									<label><input  value="0" name="is_important_notice" type="radio" <if condition="$news_info['is_important_notice'] neq 1"> checked="checked" </if> />&nbsp;&nbsp;否</label>-->
<!--									<span class="form_tips">若设置为重要通知，则会在弹窗显示</span>-->
<!--								</div>-->


<!--                                <div class="form-group">-->
<!--                                    <label class="col-sm-1"><label for="floor_txt">发送对象：</label></label>-->
<!--                                    <select class="col-sm-2"  name="floor_txt" id="floor_txt">-->
<!--                                        <option value="0">全部单元</option>-->
<!--                                        <volist name='floor_list' id='floor_info'>-->
<!--                                            <option value="{pigcms{$floor_info.floor_id}">{pigcms{$floor_info.floor_layer}&nbsp;&nbsp;--&nbsp;&nbsp;{pigcms{$floor_info.floor_name}</option>-->
<!--                                        </volist>-->
<!--                                    </select>-->
<!--                                </div>-->
                                <div class="form-group" style="display: none;">
                                    <label class="col-sm-1"><label for="floor_txt">发送对象：</label></label>
                                    <button class="btn btn-info" type="button"  onclick="chooseFloor()">
                                        点击选择发送对象
                                    </button>
                                </div>
                                <div class="form-group" style="display: none;">
                                    <label class="col-sm-1"><label for="floor_txt">所选单元：</label></label>
                                    <label>
                                    <div class="floor_txt_box">
                                        <div style="">
                                            <if condition="$choose_data">
                                                <volist name="choose_data" id="item">
                                                    <span>{pigcms{$item['floor_layer']}-{pigcms{$item['floor_name']}</span>；<input type="hidden" name="floor_txt_arr[]" value="{pigcms{$item['floor_id']}"/>&nbsp;&nbsp;&nbsp;
                                                </volist>
                                                <else/>
                                                <span>全部单元</span>
                                            </if>
                                        </div>
                                    </div>
                                    </label>
                                </div>

								<div class="form-group">
									<label class="col-sm-1">状态</label>
									<label><input  <if condition="$is_edit" >disabled</if> value="1" name="status" type="radio" <if condition="$news_info['status'] eq 1"> checked="checked" </if> />&nbsp;&nbsp;显示</label>
									&nbsp;&nbsp;&nbsp;
									<label><input  <if condition="$is_edit" >disabled</if> value="0" name="status" type="radio" <if condition="$news_info['status'] neq 1"> checked="checked" </if> />&nbsp;&nbsp;关闭</label>
								</div>
								<div class="space"></div>
								<div class="clearfix form-actions" <if condition="$is_edit" >style="display: none"</if>>
									<div class="col-md-offset-3 col-md-9">
										<button class="btn btn-info" type="submit" onclick="$(this).attr('type','text')">
											<i class="ace-icon fa fa-check bigger-110"></i>
											保存
										</button>
									</div>
								</div>
							</div>
						</div>
					</form>
			</div>
		</div>
	 </div>
   </div>
</div>
<style type="text/css">
.ke-dialog-body .ke-input-text{height: 30px;}
.title_img_box img {
	width: 100px;
	height: 100px;
}
.title_img_box #input_file_view {
	padding: 10px;
}
</style>
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script type="text/javascript">
KindEditor.ready(function(K){
			kind_editor = K.create("#description",{
				width:'400px',
				height:'400px',
				resizeType : 1,
				allowPreviewEmoticons:false,
				allowImageUpload : true,
				filterMode: true,
				cssData: 'img{max-width:100%!important;vertical-align:top!important;}',
				items : [
					'source', '|', 'fontname', 'fontsize', '|', 'forecolor', 'hilitecolor', 'bold', 'italic', 'underline',
					'removeformat', '|', 'justifyleft', 'justifycenter', 'justifyright', 'insertorderedlist',
					'insertunorderedlist', '|', 'emoticons', 'image', 'link'
				],
				emoticonsPath : './static/emoticons/',
				uploadJson : "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=street/news"
			});
			K('#input_file').click(function() {
				kind_editor.uploadJson = "{pigcms{:U('ajax_upload_pic')}";
				kind_editor.loadPlugin('image', function() {
					kind_editor.plugin.imageDialog({
						showRemote : false,
						imageUrl : K('#url3').val(),
						clickFn : function(url, title, width, height, border, align) {
							var img = K('#input_file_view');
							img.attr("src",url);
							img.show();
							K("#title_img").val(url);
							kind_editor.hideDialog();
						}
					});
				});
			});
		});
var selecteds = "{pigcms{$selecteds}";
function chooseFloor() {
    localStorage.removeItem('choose_floor');
    var url = "{pigcms{:U('NewsSreet/floor_list')}";
    var href = url + '&selecteds='+selecteds;
    layer.open({
        type: 2,
        title:'选择单元',
        content: href,
        area: ['750px', '485px'],
        maxmin: false
    });
}


function close_all_page(info) {
    console.log('info', info);
    if (info) {
        $.post("{pigcms{:U('NewsSreet/choose_floor')}",{selecteds:info},function(res){
            console.log(res)
            if (res.data) {
                var html = '';
                for (var j in res.data) {
                    if (res.data[j]) {
                        html += '<span>'+res.data[j]['floor_layer']+'-'+res.data[j]['floor_name']+'</span>；<input type="hidden" name="floor_txt_arr[]" value="'+res.data[j]['floor_id']+'">&nbsp;&nbsp;&nbsp;';
                    }
                }
                $('.floor_txt_box div').html(html);
            }
            if (res.selecteds) {
                selecteds = res.selecteds;
            }
        },'json');
    }


    layer.closeAll();
}


</script>

<include file="NewStreetPublic:footer"/>
