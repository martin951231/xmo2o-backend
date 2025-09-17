
<style>
	.fl{float: left;	}
	.fr{float: right;	}
	.clear{clear: both;}
	.ul_tab{
		border-bottom: 1px solid #ddd; 
		margin: 0 auto 30px auto;
	}
	.ul_tab li{
		float: left;
		padding: 10px 20px;
		font-size: 16px;
		cursor: pointer;
	}
	.ul_tab li.act{
		border-bottom: 2px solid #0099FC;
		text-decoration: none;
		color: #3283FA;
	}
	#container .tab{
		display: none;
	}
	.dl_common dd{
		width: 140px;
		float: left;
		text-align: left;
		padding-left: 20px; 
		font-size: 14px;
	}
	.dl_common dt{
		width: 600px;
		float: left;
		font-weight: normal;
		color:#666
	}
	.dl_common dt .border_com{
		border:1px solid #999;
	}
	 
</style>
<include file="Public:header"/> 
<div class="main-content">
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div class="col-xs-12"> 
				   <ul class="ul_tab" id="ul_tab">
					   <li <if condition="empty($_GET['selected']) or $_GET['selected'] eq '1'">class="act"</if> name="1">{pigcms{:L_("批量上传/更新图片")}</li>
					   <li <if condition="$_GET['selected'] eq '2'">class="act"</if> name="2">{pigcms{:L_("批量新增商品")}</li>
					   <li <if condition="$_GET['selected'] eq '3'">class="act"</if> name="3">{pigcms{:L_("批量更新商品")}</li>
					   <li <if condition="$_GET['selected'] eq '4'">class="act"</if> name="4">{pigcms{:L_("批量导出商品")}</li>
					   <div class="clear"></div>
				   </ul> 
				   <div id="container">
				    <section class="tab" <if condition="empty($_GET['selected']) or $_GET['selected'] eq '1'">style="display: block;"</if>> 
						<form id="myform" method="post" action="{pigcms{:U('Shop/uploadGoodsPicZip')}" enctype="multipart/form-data">
						    <input type="hidden" name="store_id" value="{pigcms{$storeId}"/>
						    <input type="hidden" name="act" value="{pigcms{$act}"/>
						    <table cellpadding="0" cellspacing="0" class="table table-striped table-bordered table-hover" width="100%">  
								<dl class="dl_common">
									<dd>压缩包</dd>
									<dt>
										<input type="file" class="input  border_com" name="file" style="width:200px;" placeholder="点击上传图片压缩包" accept=".zip,.rar"/>
									 </dt>
									<div class="clear"></div>
								</dl>
								<dl class="dl_common">
									<dd>上传要求</dd>
									<dt>
										<p>1.图片要求:600px*600px或者900px*500px,大小不得超过5M,格式为.jpg/.png</p>
										<p>2.请以商品名称命名图片, 同商品名称将使用同一张图片, 以最后上传的为准</p>
                                        <p>3.如果是更新图片，则替换同一商品中原来的主图</p>
										<p>4.请将所有图片直接压缩后上传，最多不能超过500张，最大不超过50M，仅支持zip格式</p>
										<p>5.以上规则有任意一项错误,图片将上传失败</p>
									</dt>
									<div class="clear"></div>
								</dl> 
								<dl class="dl_common">
									<dd>&nbsp;</dd>
									<dt>
										<button class="btn btn-info" type="submit">
										    <i class="ace-icon fa fa-check bigger-110"></i>
										    提交
										</button>
									</dt>
									<div class="clear"></div>
								</dl> 
						    </table> 
						</form> 
					</section>
				    <section class="tab" <if condition="$_GET['selected'] eq '2'">style="display: block;"</if>> 
						<form id="myform" method="post" action="{pigcms{:U('Shop/importGoods')}" enctype="multipart/form-data">
						    <input type="hidden" name="store_id" value="{pigcms{$storeId}"/>
						    <input type="hidden" name="act" value="add"/>
						    <table cellpadding="0" cellspacing="0" class="table table-striped table-bordered table-hover" width="100%">
                                <dl class="dl_common">
                                    <dd>上传类型</dd>
                                    <dt>
                                        <input type="radio" data-act="add" name="goods_type" value="1" checked/>计件类商品
                                        <input type="radio" data-act="add" name="goods_type" value="2"/>计重类商品
                                    </dt>
                                    <div class="clear"></div>
                                </dl>
								<dl class="dl_common">
									<dd>Excel导入</dd>
									<dt>
										<input type="file" class="input   border_com" name="file" style="width:200px;" placeholder="请上传excel表格" accept="application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"/>
									</dt>
									<div class="clear"></div>
								</dl>
								<dl class="dl_common">
									<dd>上传要求</dd>
									<dt>
                                        <p class="add_for_piece">请下载 <a target="_blank" href="{pigcms{$static_public}file/add_template.xlsx" class="button" style="margin-left:0px;">&nbsp;批量新增商品模板&nbsp;</a>  并严格按照 <a target="_blank" href="{pigcms{$static_public}file/add_instructions.xlsx" class="button" style="margin-left:0px;">&nbsp;批量新增填写指南&nbsp;</a>要求填写。每次上传限500件商品。</p>

                                        <p style="display: none" class="add_for_weight">请下载 <a target="_blank" href="{pigcms{$static_public}file/add_template_for_weight_goods.xlsx" class="button" style="margin-left:0px;">&nbsp;计重类商品-批量新增商品模板&nbsp;</a>  并严格按照 <a target="_blank" href="{pigcms{$static_public}file/add_instructions_for_weight_goods.xlsx" class="button" style="margin-left:0px;">&nbsp;计重类商品-批量新增填写指南&nbsp;</a>要求填写。每次上传限500件商品。</p>
									</dt>
									<div class="clear"></div>
								</dl>
								<dl class="dl_common">
									<dd>&nbsp;</dd>
									<dt>
										<button class="btn btn-info" type="submit">
										    <i class="ace-icon fa fa-check bigger-110"></i>
										    提交
										</button>
									</dt>
									<div class="clear"></div>
								</dl>  
						    </table> 
						</form> 
						
					</section>
				    <section class="tab" <if condition="$_GET['selected'] eq '3'">style="display: block;"</if>>
                        <form id="myform" method="post" action="{pigcms{:U('Shop/importGoods')}" enctype="multipart/form-data">
                            <input type="hidden" name="store_id" value="{pigcms{$storeId}"/>
                            <input type="hidden" name="act" value="update"/>
                            <table cellpadding="0" cellspacing="0" class="table table-striped table-bordered table-hover" width="100%">
                                <dl class="dl_common">
                                    <dd>上传类型</dd>
                                    <dt>
                                        <input type="radio" data-act="update" name="goods_type" value="1" checked/>计件类商品
                                        <input type="radio" data-act="update" name="goods_type" value="2"/>计重类商品
                                    </dt>
                                    <div class="clear"></div>
                                </dl>
                                <dl class="dl_common">
                                    <dd>Excel导入</dd>
                                    <dt>
                                        <input type="file" class="input   border_com" name="file" style="width:200px;" placeholder="请上传excel表格" accept="application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"/>
                                    </dt>
                                    <div class="clear"></div>
                                </dl>
                                <dl class="dl_common">
                                    <dd>上传要求</dd>
                                    <dt>
                                        <p class="update_for_piece">请下载 <a target="_blank" href="{pigcms{$static_public}file/update_template.xlsx" class="button" style="margin-left:0px;">&nbsp;批量更新模板&nbsp;</a>  并严格按照 <a target="_blank" href="{pigcms{$static_public}file/update_instructions.xlsx" class="button" style="margin-left:0px;">&nbsp;批量更新填写指南&nbsp;</a>要求填写。每次上传限500件商品。</p>

                                        <p style="display: none" class="update_for_weight">请下载 <a target="_blank" href="{pigcms{$static_public}file/update_template_for_weight_goods.xlsx" class="button" style="margin-left:0px;">&nbsp;计重类商品-批量更新模板&nbsp;</a>  并严格按照 <a target="_blank" href="{pigcms{$static_public}file/update_instructions_for_weight_goods.xlsx" class="button" style="margin-left:0px;">&nbsp;计重类商品-批量更新填写指南&nbsp;</a>要求填写。每次上传限500件商品。</p>
                                    </dt>
                                    <div class="clear"></div>
                                </dl>
                                <dl class="dl_common">
                                    <dd>&nbsp;</dd>
                                    <dt>
                                        <button class="btn btn-info" type="submit">
                                            <i class="ace-icon fa fa-check bigger-110"></i>
                                            提交
                                        </button>
                                    </dt>
                                    <div class="clear"></div>
                                </dl>
                            </table>
                        </form>
                    </section>
				    <section class="tab" <if condition="$_GET['selected'] eq '4'">style="display: block;"</if>>
                        <button class="btn btn-info" onclick="exportGoods(1)">
                            {pigcms{:L_("导出全部计件商品")}
                        </button>
                        <button class="btn btn-info" onclick="exportGoods(2)">
                            {pigcms{:L_("导出全部计重商品")}
                        </button>
					</section>
                       <div id="shopList" class="grid-view">
                        <button class="btn btn-info" style="float: right;margin: 20px 10px;" onclick="_update();">刷新</button>
                           <table class="table table-striped table-bordered table-hover">
                               <thead>
                               <tr>
                                   <th style="width: 5%">{pigcms{:L_("ID")}</th>
                                   <th style="width: 10%">{pigcms{:L_("操作")}</th>
                                   <th style="width: 10%">{pigcms{:L_("操作时间")}</th>
                                   <th style="width: 10%">{pigcms{:L_("状态")}</th>
                                   <th style="width: 20%">{pigcms{:L_("操作/说明")}</th>
                               </tr>
                               </thead>
                               <tbody>
                               <if condition="$logs">
                                   <volist name="logs" id="l">
                                       <tr>
                                           <td>{pigcms{$l.pigcms_id}</td>
                                           <td>
                                               <if condition="$l['action'] == 1">
                                                   批量新增商品
                                                   <elseif condition="$l['action'] == 2"/>
                                                   批量更新商品
                                                   <elseif condition="$l['action'] == 3"/>
                                                   导出全部计件商品
                                                   <elseif condition="$l['action'] == 4"/>
                                                   批量更新/上传图片
                                                   <elseif condition="$l['action'] == 5"/>
                                                   导出全部计重商品
                                                   <else/>
                                                   其他
                                               </if>
                                           </td>
                                           <td>{pigcms{$l.log_date}</td>
                                           <td style="color: #44C17C">
                                               <if condition="$l['status'] == 2">
                                                   <if condition="$l['action'] == 4">
                                                        <eq name='l.ratio' value='100'>
                                                            <span onclick="showPicSyncProcess({pigcms{$l.pigcms_id})">处理完成</span>
                                                        <else/>
                                                            <span onclick="showPicSyncProcess({pigcms{$l.pigcms_id})">进度({pigcms{$l.ratio}%)</span>
                                                        </eq>
                                                    <else/>
                                                        处理完成
                                                    </if>
                                                   <elseif condition="$l['status'] == 1"/>
                                                   处理中
                                                   <elseif condition="$l['status'] == 3"/>
                                                   处理失败
                                                   <else/>
                                                   待处理
                                               </if>
                                           </td>
                                           <td>
                                               <if condition="$l['status']==0">
                                                  等待处理中...
                                               <elseif condition="$l['status']==1" />
                                                  正在处理中...
                                               <elseif condition="in_array($l['action'],[3,4,5])"/>
                                                   <notempty name="l.file">
                                                       <a href="{pigcms{$l.file}">下载到本地</a>
                                                   <else/>
                                                      <gt name="l.status" value="1">
                                                       {pigcms{$l.success_num}条记录操作成功，{pigcms{$l.error_num}条记录操作失败，<gt name='l.error_num' value='0'><a href="{pigcms{:U('Shop/downFailRecord',array('pigcms_id'=>$l['pigcms_id']))}">下载失败列表</a></gt>
                                                       <else/>
                                                          --
                                                       </gt>
                                                   </notempty>

                                               <else/>
                                                   <gt name="l.error_num" value="0">
                                                       {pigcms{$l.success_num}条记录操作成功，{pigcms{$l.error_num}条记录操作失败，<a href="{pigcms{:U('Shop/downFailRecord',array('pigcms_id'=>$l['pigcms_id']))}">下载失败列表</a>
                                                   <else/>
                                                       {pigcms{$l.success_num}条记录操作成功，{pigcms{$l.error_num}条记录操作失败
                                                   </gt>
                                               </if>
                                           </td>
                                       </tr>
                                   </volist>
                                   <else/>
                                   <tr class="odd"><td class="button-column" colspan="11" >{pigcms{:L_("无内容")}</td></tr>
                               </if>
                               </tbody>
                           </table>
                           {pigcms{$pagebar}
                       </div>
				   </div>
				   
                   <!-- <button class="btn btn-success" onclick="updatePic()">{pigcms{:L_("批量上传/更新图片")}</button>
                    <button class="btn btn-success" onclick="importAddGoods()">{pigcms{:L_("批量新增商品")}</button>
                    <button class="btn btn-success" onclick="importUpdateGoods()">{pigcms{:L_("批量更新商品")}</button>
                    <button class="btn btn-success" onclick="exportGoods()">{pigcms{:L_("批量导出商品")}</button> -->

                </div>
            </div>
        </div>
    </div>
</div> 

<script>
var selected = {pigcms{:intval($_GET['selected'])};
if(!selected){
	selected = 1;
}
var s = selected-1;
var ul_tab=document.getElementById('ul_tab');
var oNav=ul_tab.getElementsByTagName('li'); 

var container=document.getElementById('container');
var oDiv=container.getElementsByClassName('tab');
	for(var i=0;i<oNav.length;i++){
		oNav[i].index=i;
		oNav[i].onclick=function () {
		var s = this.index+1;
		var href = "{pigcms{:U('batchOperation',['store_id'=>$_GET['store_id'],'page'=>$_GET['page']])}&selected="+s;
		window.location.href = href;
	}     
}

//刷新操作
function _update(){
    var selected = $(".ul_tab .act").attr("name");
    var href = "{pigcms{:U('batchOperation',['store_id'=>$_GET['store_id'],'page'=>$_GET['page']])}&selected="+selected;
    window.location.href = href;
 }
</script>

<script type="text/javascript"> 
	
    var store_id = '{pigcms{$storeId|default=0}';

    $('input[name=goods_type]').click(function () {
        var goods_type = $(this).val();
        var act = $(this).attr('data-act');
        if (goods_type == 1) {
            $('.' + act + '_for_piece').show();
            $('.' + act + '_for_weight').hide();
        } else {
            $('.' + act + '_for_piece').hide();
            $('.' + act + '_for_weight').show();
        }
    })

    function importAddGoods() {
        art.dialog.open(
            "{pigcms{:U('Shop/importGoods')}"+'&act=add&store_id='+'{pigcms{$storeId|default=0}',
            {
                lock: true,
                title: '{pigcms{:L_("批量新增商品")}',
                width: 800,
                height: 400,
                yesText: '关闭',
                background: '#000',
                opacity: 0.45,
                id: 'fitment',
                close: function () {
                    location.reload(true);
                }
            });
    }

    function importUpdateGoods(){
        art.dialog.open(
            "{pigcms{:U('Shop/importGoods')}"+'&act=update&store_id='+'{pigcms{$storeId|default=0}',
            {
                lock: true,
                title: '{pigcms{:L_("批量更新商品")}',
                width: 800,
                height: 400,
                yesText: '关闭',
                background: '#000',
                opacity: 0.45,
                id: 'fitment',
                close: function () {
                    location.reload(true);
                }
            });
    }

    function exportGoods(sell_type) {
        var ok = confirm("确认导出后，请在下方操作记录中查看导出进展，待状态为处理完成时，点击下载到本地即可");
        if (ok == true) {
            $.get('{pigcms{:U("Shop/exportGoods")}' + '&store_id=' + store_id + '&sell_type=' + sell_type, function (rs) {
                if(rs.error_no != 0){
                    alert(rs.error_msg);
                }else{
                    history.go(0);
                }
            })
        }
    }

    function updatePic(){
        art.dialog.open(
            "{pigcms{:U('Shop/uploadGoodsPicZip')}"+'&store_id='+'{pigcms{$storeId|default=0}',
            {
                lock: true,
                title: '{pigcms{:L_("批量新增商品")}',
                width: 800,
                height: 400,
                yesText: '关闭',
                background: '#000',
                opacity: 0.45,
                id: 'fitment',
                close: function () {
                    location.reload(true);
                }
            });
    }


    function showPicSyncProcess(id){
        art.dialog.open(
            "{pigcms{:U('Shop/showPicSyncProcess')}"+"&id="+id,
            {
                lock: true,
                title: '{pigcms{:L_("查看图片同步进度")}',
                width: 800,
                height: 400,
                yesText: '关闭',
                background: '#000',
                opacity: 0.45,
                id: 'fitment',
                close: function () {
                    location.reload(true);
                }
            });
    }
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<include file="Public:footer"/>
