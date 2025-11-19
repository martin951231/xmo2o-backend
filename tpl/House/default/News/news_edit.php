<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" >
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-tablet"></i>
				<a href="{pigcms{:U('News/index')}">新闻列表</a>
			</li>
			<li class="active">内容发布</li>
		</ul>
	</div>
	<!-- 内容头部 -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<form enctype="multipart/form-data" class="form-horizontal" method="post" id="edit_form" action="{pigcms{:U('News/news_edit_do')}">
					   <input  name="news_id" type="hidden" value="{pigcms{$news_info['news_id']}"/>
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1"><label for="title">标题</label></label>
									<input class="col-sm-2" size="80" name="title" id="title" type="text" value="{pigcms{$news_info['title']}"/>
								</div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="child_title">副标题</label></label>
                                    <input class="col-sm-2" size="80" name="child_title" id="child_title" type="text" value="{pigcms{$news_info['child_title']}"/>
                                </div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="title">选择分类</label></label>
									<select name='cat_id'>
									<volist name='news_categorys' id='cate'>
										<option  value='{pigcms{$cate.cat_id}' <if condition="$news_info['cat_id'] eq $cate['cat_id']" >selected</if> >{pigcms{$cate.cat_name}</option>
									</volist>
									</select>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="sort">发布内容</label></label>
									<textarea id="description" name="description"  placeholder="写上一些想要发布的内容">{pigcms{:replace_file_domain_content($news_info['content'])}</textarea> 
								</div>
								<div class="form-group">
									<label class="col-sm-1">是否热门</label>
									<label><input value="1" name="is_hot" type="radio" <if condition="$news_info['is_hot'] eq 1"> checked="checked" </if> />&nbsp;&nbsp;是</label>
									&nbsp;&nbsp;&nbsp;
									<label><input value="0" name="is_hot" type="radio" <if condition="$news_info['is_hot'] neq 1"> checked="checked" </if> />&nbsp;&nbsp;否</label>
									<span class="form_tips">若设置为热门，则会靠前显示</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">是否重要通知</label>
									<label><input  value="1" name="is_important_notice" type="radio" <if condition="$news_info['is_important_notice'] eq 1"> checked="checked" </if> />&nbsp;&nbsp;是</label>
									&nbsp;&nbsp;&nbsp;
									<label><input  value="0" name="is_important_notice" type="radio" <if condition="$news_info['is_important_notice'] neq 1"> checked="checked" </if> />&nbsp;&nbsp;否</label>
									<span class="form_tips">若设置为重要通知，则会在弹窗显示 </span>
								</div>

                                <div class="form-group">
                                    <label class="col-sm-1">是否显示在门禁上</label>
                                    <label><input  value="1" name="is_device_show" type="radio" <if condition="$news_info['is_device_show'] eq 1"> checked="checked" </if> />&nbsp;&nbsp;是</label>
                                    &nbsp;&nbsp;&nbsp;
                                    <label><input  value="0" name="is_device_show" type="radio" <if condition="$news_info['is_device_show'] neq 1"> checked="checked" </if> />&nbsp;&nbsp;否</label>
                                    <span class="form_tips">只能设置一条新闻显示</span>
                                </div>


<!--                                <div class="form-group">-->
<!--                                    <label class="col-sm-1"><label for="floor_txt">发送对象：</label></label>-->
<!--                                    <select class="col-sm-2"  name="floor_txt" id="floor_txt">-->
<!--                                        <option value="0">全部单元</option>-->
<!--                                        <volist name='floor_list' id='floor_info'>-->
<!--                                            <option value="{pigcms{$floor_info.floor_id}">{pigcms{$floor_info.floor_layer}&nbsp;&nbsp;--&nbsp;&nbsp;{pigcms{$floor_info.floor_name}</option>-->
<!--                                        </volist>-->
<!--                                    </select>-->
<!--                                </div>-->
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="floor_txt">发送对象</label></label>
                                    <button class="btn btn-info" type="button"  onclick="chooseFloor1()">
                                        点击选择发送对象
                                    </button>
                                    <span class="form_tips" style="color: red">注意：不绑定房间时，默认给所有业主发送新闻通知；绑定房间时，只给绑定的房间发送新闻通知。</span>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-1"><label for="floor_txt">展示到期时间</label></label>
                                    <input type="text"
                                           name="end_time" class="input-text"
                                           value="{pigcms{$news_info.end_time}"
                                           style="height:30px;"
                                           onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm:ss'})"
                                           placeholder="展示到期时间"
                                    />

                                </div>

<!--                                <div class="form-group" style="display: flex;">-->
<!--                                    <label class="col-sm-1"><label for="floor_txt">所选单元</label></label>-->
<!--                                    <label>-->
<!--                                    <div class="floor_txt_box">-->
<!--                                        <div style="">-->
<!--                                            <if condition="$choose_data">-->
<!--                                                <volist name="choose_data" id="item">-->
<!--                                                    <span>{pigcms{$item['floor_layer']}-{pigcms{$item['floor_name']}</span>；<input type="hidden" name="floor_txt_arr[]" value="{pigcms{$item['floor_id']}"/>&nbsp;&nbsp;&nbsp;-->
<!--                                                </volist>-->
<!--                                                <else/>-->
<!--                                                <span>全部单元</span>-->
<!--                                            </if>-->
<!--                                        </div>-->
<!--                                    </div>-->
<!--                                    </label>-->
<!--                                </div>-->
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="title">展示模式</label></label>
                                    <label><input  value="1" name="show_mode" type="radio" <if condition="$news_info['show_mode'] eq 1"> checked="checked" </if> />&nbsp;&nbsp;纯文字</label>&nbsp;&nbsp;&nbsp;
                                    <label><input  value="2" name="show_mode" type="radio" <if condition="$news_info['show_mode'] eq 2"> checked="checked" </if> />&nbsp;&nbsp;单图模式</label>&nbsp;&nbsp;&nbsp;
                                    <label><input  value="3" name="show_mode" type="radio" <if condition="$news_info['show_mode'] eq 3"> checked="checked" </if> />&nbsp;&nbsp;多图模式</label>&nbsp;&nbsp;&nbsp;
                                </div>
								<div class="form-group">
									<label class="col-sm-1">状态</label>
									<label><input  value="1" name="status" type="radio" <if condition="$news_info['status'] eq 1"> checked="checked" </if> />&nbsp;&nbsp;显示</label>
									&nbsp;&nbsp;&nbsp;
									<label><input  value="0" name="status" type="radio" <if condition="$news_info['status'] neq 1"> checked="checked" </if> />&nbsp;&nbsp;关闭</label>
								</div>
								<div class="space"></div>
								<div class="clearfix form-actions">
									<div class="col-md-offset-3 col-md-9">
										<button class="btn btn-info" type="submit" onclick="$(this).attr('type','text')" <if condition="!in_array(159,$house_session['menus']) && !in_array(160,$house_session['menus'])">disabled="disabled"</if>>
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
    .no-skin .ke-dialog{
        left: 30% !important;top:18% !important;
    }
</style>
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script type="text/javascript">
KindEditor.ready(function(K){
			kind_editor = K.create("#description",{
				width:'89%',
				height:'400px',
				resizeType : 1,
				pasteType : 1,
				allowPreviewEmoticons:false,
				allowImageUpload : true,
				filterMode: true,
				items : [
					'source', '|', 'fontname', 'fontsize','formatblock','lineheight', '|', 'forecolor', 'hilitecolor', 'bold', 'italic', 'underline',
					'removeformat', '|', 'justifyleft', 'justifycenter', 'justifyright', 'insertorderedlist',
					'insertunorderedlist', '|', 'emoticons', 'image', 'wordpaste'
				],
				emoticonsPath : './static/emoticons/',
				uploadJson : "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=merchant/news"
			});
		});
var selecteds = "{pigcms{$selecteds}";

var news_id = "{pigcms{$_GET['news_id']}";

function chooseFloor1() {
    // localStorage.removeItem('choice_object');
    var url = "{pigcms{:U('News/choice_object')}";
    // var href = url + '&selecteds='+selecteds;
    var href = url + '&news_id='+news_id;
    layer.open({
        type: 2,
        title:'选择发送对象',
        content: href,
        area: ['75%', '82%'],
        maxmin: false
    });
}




function close_all_page(info) {
    console.log('info', info);
    // if (info) {
    //     $.post("{pigcms{:U('News/choose_floor')}",{selecteds:info},function(res){
    //         console.log(res)
    //         if (res.data) {
    //             var html = '';
    //             for (var j in res.data) {
    //                 if (res.data[j]) {
    //                     html += '<span>'+res.data[j]['floor_layer']+'-'+res.data[j]['floor_name']+'</span>；<input type="hidden" name="floor_txt_arr[]" value="'+res.data[j]['floor_id']+'">&nbsp;&nbsp;&nbsp;';
    //                 }
    //             }
    //             $('.floor_txt_box div').html(html);
    //         }
    //         if (res.selecteds) {
    //             selecteds = res.selecteds;
    //         }
    //     },'json');
    // }


    layer.closeAll();
}


</script>

<include file="Public:footer"/>
