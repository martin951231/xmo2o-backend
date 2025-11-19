<!doctype html>
<html>
<head>
	<include file="header"/>
	<link rel="stylesheet" href="{pigcms{$static_path}css/vant@2.12.47.css"/>
	<link rel="stylesheet" href="{pigcms{$static_path}classifynew/css/cropper.css"/>
	<script src="{pigcms{$static_path}js/vue@2.6.14.min.js" type="text/javascript" charset="utf-8"></script>
	<script src="{pigcms{$static_path}js/vant@2.12.14.min.js" type="text/javascript" charset="utf-8"></script>
	<script src="{pigcms{$static_path}js/common.js?t={pigcms{:time()}" type="text/javascript"></script>
</head>
<style>
	.x_header{z-index:10}.weui-cells_form .weui-cell__ft{font-size:17px}
	.weui-switch-cp__input:checked~.weui-switch-cp__box, .weui-switch:checked {border-color: #ff4d00!important;background-color: #ff4d00!important;}
	.weui-cells_radio .weui-check + .weui-icon-checked:before {color:unset !important;}
	.border_t:before {
		content: " ";
		position: absolute;
		left: 0;
		top: 0;
		right: 0;
		height: 1px;
		border-top: 1px solid #e5e5e5;
		color: #e5e5e5;
		-webkit-transform-origin: 0 0;
		transform-origin: 0 0;
		-webkit-transform: scaleY(0.5);
		transform: scaleY(0.5);
		left: 15px;
		z-index: 2;
	}

	.color-FF4D00{
		color: #FF4D00!important;
	} 
	.real-estate{
		overflow-y: auto;
		max-height: 50vh;
	}
	.real-estate .item{
		display: flex;
		background: #FFFFFF;
		border-radius: 5px;
		padding: 15px;
		margin: 0 10px 10px 10px;
		position: relative;
	}
	.select_h{
		position: absolute;
		right: 2px;
		top:0px;
	}
	.real-estate .item .left{
		margin-right: 20px;
		display: flex;
    	align-items: center;
	}
	.real-estate .item .left img{
		width: 70px;
		height: 70px;
		border-radius: 4px;
	}
	.real-estate .item .right .title{
		font-size: 16px;
		font-weight: bold;
		color: #1D1D1D;
		margin-bottom: 6px;
	}
	.real-estate .item .right .desc{
		font-size: 14px;
		font-weight: 500;
		color: #888888;
		margin-bottom: 2px;
	}
	.real-estate .item .right .desc:last-child{
		margin-bottom: 0
	}
	.header-action{
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 20px;
	}
	.js_dialog_2_title{
		font-size: 17px;
		font-family: PingFang SC;
		font-weight: bold;
		color: #1F1F1F
	}
	.js_dialog_2_close,.js_dialog_2_complete{
		font-size: 15px;
		font-family: PingFang SC;
		font-weight: 500;
		color: #8B8B8B;
	}
</style>
<body>
<!-- 获取自定义表单房源名称 -->
<php>
	$houseName = '';
	foreach($catfield as $v){
		if($v['type'] == 7){
			$houseName = $v['name'];
		}
	}
</php>
<div id="app" style="display:none">
	<if condition="!$is_weixin_browser && !$is_app_browser">
		<header class="x_header bgcolor_11 cl f15">
			<a class="z f14" href="javascript:window.history.go(-1);"><i class="iconfont icon-fanhuijiantou w15"></i>返回</a>
			<a class="y sidectrl " href="{pigcms{:U('fabu')}">切换</a>
			<div class="navtitle">{pigcms{$page_title}</div>
		</header>
		<div class="x_header_fix" ></div>
	</if>
	<div class="page__bd">
		<form action="{pigcms{:U('Classify/fabuTosave',array('cid'=>$cid))}" method="post" id="form" enctype="multipart/form-data">
			<input type="hidden" name="cid" value="{pigcms{$cid}" />
			<input type="hidden" name="fcid" value="{pigcms{$fcid}" />
			<input type="hidden" name="pfcid" value="{pigcms{$_GET.pfcid}" />
			<input type="hidden" name="longitude" value="" />
			<input type="hidden" name="latitude" value="" />
			<input type="hidden" name="province" value="" />
			<input type="hidden" name="city" value="" />
			<input type="hidden" name="area" value="" />
            <input type="hidden" name="adress" value="" class="adress" />

			<div class="weui-cells__title" style="margin-bottom:1em;">《用户协议条款》
				【提醒】请认真全面填写信息，以便大家查阅。
				<br/>【声明】发布的所有信息，平台只负责发布、展示，与平台本身无关，不承担任何责任。
				<if condition="$config['classify_verify']"><br/>【审核】信息发布后需要平台进行审核后，才会展示出来提供浏览。</if>
				<if condition="$fabuTmp['reward_type'] eq 2 || $fabuTmp['reward_type'] eq 4"><br/>【费用】本分类发布收取 <span style="color:red;">{pigcms{$fabuTmp.reward_publish|floatval=###} {pigcms{$config['Currency_txt']}</span></if>
			</div>
			<div class="weui-cells weui-cells_form">
				<volist name="catfield" id="vv" key="kk">
					<if condition="$vv['type'] eq 1">
						<div class="weui-cell">
							<div class="weui-cell__hd">
								<php>if($vv['iswrite']>0){echo '<strong style="color:red;">*</strong>';}else{echo '&nbsp;';}</php>
							</div>
							<div class="weui-cell__hd"><label class="weui-label">{pigcms{$vv['name']}</label></div>
							<div class="weui-cell__bd">
								<input name="input[{pigcms{$kk}][vv]" class="weui-input" placeholder="{pigcms{$vv['name']}" type="text" value="" <php>if($vv['inarr']==1)echo 'onkeyup="value=clearNoNum(this.value)"';</php>/>
								<input name="input[{pigcms{$kk}][tn]"  value="{pigcms{$vv['name']}"  type="hidden" />
								<input name="input[{pigcms{$kk}][unit]"  value="{pigcms{$vv['inunit']}"  type="hidden" />
								<input name="input[{pigcms{$kk}][inarr]"  value="{pigcms{$vv['inarr']}"  type="hidden" />
								<input name="input[{pigcms{$kk}][input]"  value="{pigcms{$vv['input']}"  type="hidden" />
								<input name="input[{pigcms{$kk}][iswrite]"  value="{pigcms{$vv['iswrite']}"  type="hidden" />
								<input name="input[{pigcms{$kk}][isfilter]"  value="{pigcms{$vv['isfilter']}"  type="hidden" />
								<input name="input[{pigcms{$kk}][type]"  value="1"  type="hidden" />
							</div>
							<php>if(($vv['inarr']==1) && !empty($vv['inunit'])){</php>
								<div class="weui-cell__ft">
									{pigcms{$vv['inunit']}
								</div>
							<php>}</php>
						</div>
					<elseif condition="$vv['type'] eq 2"/>
						<div class="weui-cell weui-cell_select weui-cell_select-after">
							<div class="weui-cell__hd">
								<php>if($vv['iswrite']>0){echo '<strong style="color:red;">*</strong>';}else{echo '&nbsp;';}</php>
							</div>
							<div class="weui-cell__hd"><label class="weui-label">{pigcms{$vv['name']}</label></div>
							<div class="weui-cell__bd">
								<select class="weui-select" name="input[{pigcms{$kk}][vv]">
									<volist name="vv['opt']" id="opt">
										<option value="{pigcms{$opt}">{pigcms{$opt}</option>
									</volist>
								</select>
								<input name="input[{pigcms{$kk}][tn]"  value="{pigcms{$vv['name']}"  type="hidden" />
								<input name="input[{pigcms{$kk}][input]"  value="{pigcms{$vv['input']}"  type="hidden" />
								<input name="input[{pigcms{$kk}][iswrite]"  value="{pigcms{$vv['iswrite']}"  type="hidden" />
								<input name="input[{pigcms{$kk}][isfilter]"  value="{pigcms{$vv['isfilter']}"  type="hidden" />
								<input name="input[{pigcms{$kk}][type]"  value="2"  type="hidden" />
							</div>
						</div>
					<elseif condition="$vv['type'] eq 3"/>
						<div class="weui-cell">
							<div class="weui-cell__hd">
								<php>if($vv['iswrite']>0){echo '<strong style="color:red;">*</strong>';}else{echo '&nbsp;';}</php>
							</div>
							<div class="weui-cell__hd"><label class="weui-label">{pigcms{$vv['name']}</label></div>
							<div class="weui-cell__bd">
								<div class="post-tags cl" id="post-typeid" style="padding:5px 0px;">
									<volist name="vv['opt']" id="opt">
										<a class="weui-btn weui-btn_mini weui-btn_default " href="javascript:;" onclick="return setTypeidNew(this);">{pigcms{$opt}</a>
										<input class="none" name="input[{pigcms{$kk}][vv][]" type="checkbox" value="{pigcms{$opt}"/>
									</volist>
								</div>
								<input name="input[{pigcms{$kk}][tn]"  value="{pigcms{$vv['name']}"  type="hidden" />
								<input name="input[{pigcms{$kk}][input]"  value="{pigcms{$vv['input']}"  type="hidden" />
								<input name="input[{pigcms{$kk}][iswrite]"  value="{pigcms{$vv['iswrite']}"  type="hidden" />
								<input name="input[{pigcms{$kk}][isfilter]"  value="{pigcms{$vv['isfilter']}"  type="hidden" />
								<input name="input[{pigcms{$kk}][type]"  value="3"  type="hidden" />
							</div>
						</div>
					<elseif condition="$vv['type'] eq 4"/>
						<div class="weui-cell weui-cell_select weui-cell_select-after">
							<div class="weui-cell__hd">
								<php>if($vv['iswrite']>0){echo '<strong style="color:red;">*</strong>';}else{echo '&nbsp;';}</php>
							</div>
							<div class="weui-cell__hd"><label class="weui-label">{pigcms{$vv['name']}</label></div>
							<div class="weui-cell__bd">
								<select class="weui-select" name="input[{pigcms{$kk}][vv]">
									<volist name="vv['opt']" id="opt">
										<option value="{pigcms{$opt}">{pigcms{$opt}</option>
									</volist>
								</select>
								<input name="input[{pigcms{$kk}][tn]"  value="{pigcms{$vv['name']}"  type="hidden" />
								<input name="input[{pigcms{$kk}][input]"  value="{pigcms{$vv['input']}"  type="hidden" />
								<input name="input[{pigcms{$kk}][iswrite]"  value="{pigcms{$vv['iswrite']}"  type="hidden" />
								<input name="input[{pigcms{$kk}][isfilter]"  value="{pigcms{$vv['isfilter']}"  type="hidden" />
								<input name="input[{pigcms{$kk}][type]"  value="4"  type="hidden" />
							</div>
						</div>
					<elseif condition="$vv['type'] eq 5"/>
						<div class="weui-cell">
							<div class="weui-cell__hd">
								<php>if($vv['iswrite']>0){echo '<strong style="color:red;">*</strong>';}else{echo '&nbsp;';}</php>
							</div>
							<div class="weui-cell__hd"><label class="weui-label">{pigcms{$vv['name']}</label></div>
							<div class="weui-cell__bd">
								<textarea name="input[{pigcms{$kk}][vv]" class="weui-textarea" rows="3" placeholder="{pigcms{$vv['name']}"></textarea>
								<input name="input[{pigcms{$kk}][tn]"  value="{pigcms{$vv['name']}"  type="hidden" />
								<input name="input[{pigcms{$kk}][input]"  value="{pigcms{$vv['input']}"  type="hidden" />
								<input name="input[{pigcms{$kk}][iswrite]"  value="{pigcms{$vv['iswrite']}"  type="hidden" />
								<input name="input[{pigcms{$kk}][isfilter]"  value="{pigcms{$vv['isfilter']}"  type="hidden" />
								<input name="input[{pigcms{$kk}][type]"  value="5"  type="hidden" />
							</div>
						</div>
					<elseif condition="$vv['type'] eq 6"/>
						<div class="weui-cell weui-cell_access ">
							<div class="weui-cell__hd">
								<i class="iconfont  main_color">&#xe751;</i>
							</div>
							<div class="weui-cell__hd"><label class="weui-label">{pigcms{$vv['name']}</label></div>
							<div class="weui-cell__bd weui-cell__ft" id="color-gray" style="text-align:left">
								<div class="adress">
									<?php if(isset($now_adress['adress'])): ?><?php echo $now_adress['adress']; ?><?php else : ?>点击选择位置<?php endif; ?>
								</div>

							</div>
							<input name="input[{pigcms{$kk}][vv]" value="{pigcms{$now_adress['adress']}"  type="hidden" />
							<input name="input[{pigcms{$kk}][tn]"  value="{pigcms{$vv['name']}"  type="hidden" />
							<input name="input[{pigcms{$kk}][iswrite]"  value="{pigcms{$vv['iswrite']}"  type="hidden" />
							<input name="input[{pigcms{$kk}][input]"  value="{pigcms{$vv['input']}"  type="hidden" />
							<input name="input[{pigcms{$kk}][isfilter]"  value="{pigcms{$vv['isfilter']}"  type="hidden" />
							<input name="input[{pigcms{$kk}][url]"  value="{pigcms{$vv['url']}"  type="hidden" />
							<input name="input[{pigcms{$kk}][type]"  value="6"  type="hidden" />
						</div>
					<elseif condition="$vv['type'] eq 7"/>
						<div class="weui-cell_select weui-cell_select-after weui-cell">
							<div class="weui-cell__hd">
								<php>if($vv['iswrite']>0){echo '<strong style="color:red;">*</strong>';}else{echo '&nbsp;';}</php>
							</div>
							<div class="weui-cell__hd"><label class="weui-label">{pigcms{$vv['name']}</label></div>
							<div class="weui-cell__bd" @click="dialogOpen">
								<input :placeholder="houseItem?'已选':'点击选择'" class="weui-input" style="height: 45px;width: 100%;" readonly="readonly"/>
							</div>
						</div>
						<input name="input[{pigcms{$kk}][type]"  value="7"  type="hidden" />
						<input name="input[{pigcms{$kk}][house_info]"  v-model="house_info"  type="hidden" />
						<div class="weui-bottom-fixed-opr-page__content" style="padding: 0" v-if="houseItem" @click="dialogOpen">
							<div class="real-estate">
								<div class="item">
									<div class="left">
										<img :src="houseItem.village_logo" alt="" style="width: 77px;height:77px">
									</div>
									<div class="right">
									<div class="title">{{houseItem.village_name}}</div>
									<div class="desc" v-if="houseItem.house_address">{{houseItem.house_address}}{{houseItem.room}}</div>
									<div class="desc" v-if="houseItem.housesize">{{houseItem.housesize}}平方</div>
									<div class="desc" v-if="houseItem.village_address">{{houseItem.village_address}}</div>
									</div>
								</div>
							</div>
						</div>
					</if>
				</volist>
				
                <div class="weui-cell">
					<div class="weui-cell__bd">
						<textarea name="description" class="weui-textarea" placeholder="请填写信息的内容，越详细越好" rows="4"></textarea>
					</div>
				</div>
				<if condition="$fabuset['isupimg']">
					<div class="weui-cell">
						<div class="weui-cell__bd">
							<div class="weui-uploader">
								<div class="weui-uploader__hd">
									<p class="weui-uploader__title">上传照片</p>
									<div class="weui-uploader__info" style="font-size:12px;">最多上传8张照片（可不上传）</div>
								</div>
								<div class="weui-uploader__bd">
									<ul class="weui-uploader__files" id="uploaderFiles">
									</ul>
									<div class="weui-uploader__input-box">
										<input id="uploaderInput" class="weui-uploader__input" type="file" accept="image/*"/>
									</div>
								</div>
							</div>
						</div>
					</div>
				</if>
            </div>
			<div class="weui-cells weui-cells_form">
				<div class="weui-cell">
					<div class="weui-cell__hd">
						<i class="iconfont icon-wode main_color"></i>
					</div>
					<div class="weui-cell__hd"><label class="weui-label">联系人</label></div>
					<div class="weui-cell__bd">
						<input name="lxname" class="weui-input" type="text" placeholder="请填写联系人姓名" value=""/>
					</div>
				</div>
				<div class="weui-cell">
					<div class="weui-cell__hd">
						<i class="iconfont icon-dianhua1 main_color"></i>
					</div>
					<div class="weui-cell__hd"><label class="weui-label">电话号码</label></div>
					<div class="weui-cell__bd">
						<input name="lxtel" class="weui-input" type="tel" placeholder="请填写联系人电话号码" value="{pigcms{$user_session.phone}"/>
					</div>
				</div>
				<if condition="$fabuset['is_transaction_config'] eq 0">
					<div class="weui-cell weui-cell_switch">
						<div class="weui-cell__hd">
							<i class="iconfont  main_color">&#xe616;</i>
						</div>
						<div class="weui-cell__bd">担保交易</div>
						<div class="weui-cell__ft">
							<label for="is_assure" class="weui-switch-cp">
								<input id="is_assure" class="weui-switch-cp__input" name="is_assure" value="1" type="checkbox"/>
								<div class="weui-switch-cp__box"></div>
							</label>
						</div>
					</div>
					<div id='assure_money' class="weui-cell " style="padding:0px;display:none;">
						<div class="weui-cell " >
							<div class="weui-cell__hd">
								<i class="iconfont  main_color">&#xe626;</i>
							</div>
							<div class="weui-cell__hd"><label class="weui-label">售卖金额</label></div>
							<div class="weui-cell__bd">
								<input class="weui-input" type="text" name="assure_money" onkeyup="value=clearNoNum(this.value)" placeholder="请输入售卖金额">
							</div>
						</div>
						<div class="weui-cell " >
							<div class="weui-cell__hd">
								<!--<i class="iconfont  main_color icon-shouru"></i>-->
								<i class="iconfont  main_color">&#xe670;</i>
							</div>
							<div class="weui-cell__hd"><label class="weui-label">原价</label></div>
							<div class="weui-cell__bd">
								<input class="weui-input" type="text" name="cost_price"  onkeyup="value=clearNoNum(this.value)" placeholder="请输入原价">
							</div>
						</div>
	                    <div class="weui-cell " >
							<div class="weui-cell__hd">
								<!--<i class="iconfont  main_color icon-youhuiquan"></i>-->
								<i class="iconfont  main_color">&#xe6a7;</i>
							</div>
	                        <div class="weui-cell__hd"><label class="weui-label">是否包邮</label></div>
	                        <div class="weui-cell__bd weui-cells_radio" style="display:flex">
	                            <div class="weui-cell__bd">
	                                <label for="x11">
	                                    <span>包邮</span>
	                                    <input type="radio" class="weui-check is_baoyou" name="is_post_free" id="x11" value="1" checked="checked">
	                                    <span class="weui-icon-checked"></span>
	                                </label>
	                            </div>
	                            <div class="weui-cell__bd">
	                                <label for="x12">
	                                    <span>不包邮</span>
	                                    <input type="radio" class="weui-check is_baoyou" name="is_post_free" id="x12"  value="0">
	                                    <span class="weui-icon-checked"></span>
	                                </label>
	                            </div>
	                        </div>
	                    </div>
						<div class="weui-cell baoyou"  style="display:none">
							<div class="weui-cell__hd">
								<i class="iconfont  main_color">&#xe68d;</i>
							</div>
							<div class="weui-cell__hd"><label class="weui-label">运费</label></div>
							<div class="weui-cell__bd">
								<input class="weui-input" type="text" name="postage"  onkeyup="value=clearNoNum(this.value)"  placeholder="请输入运费">
							</div>
						</div>
					</div>
				</if>
			</div>
			<div class="weui-agree mt10" >
				<input id="weuiAgree" type="checkbox"  readonly="" class="weui-agree__checkbox"/>
				<span class="weui-agree__text">
					阅读并同意<a href="javascript:$('#agree__text').popup();" >《用户协议条款》</a>
				</span>
			</div>
			<div id="agree__text" class="weui-popup__container">
				<div class="weui-popup__overlay"></div>
				<div class="weui-popup__modal">
					<div class="fixpopuper">
					<article class="weui-article">
						<h1>用户协议条款</h1>
						<section>
							<section>
								<h3>一、服务使用规范：</h3>
								<p>
								</p><p>通过本平台服务平台，您可以按照本平台的规则发布各种生活信息。但所发布的信息不得含有以下内容：</p>
								<p>1) 反对宪法所确定的基本原则，煽动抗拒、破坏宪法和法律、行政法规实施的；<br>
									2) 煽动危害国家安全、泄露国家秘密、颠覆国家政权，推翻社会主义制度的；<br>
									3) 煽动分裂国家、破坏国家统一、损害国家荣誉和民族利益的；<br>
									4) 煽动民族仇恨、民族歧视，破坏民族团结的；<br>
									5) 捏造或者歪曲事实，散布谣言，扰乱社会秩序的；<br>
									6) 进行政治宣传或破坏国家宗教政策、宣扬封建迷信、淫秽、色情、赌博、暴力、凶杀、恐怖、教唆犯罪的；<br>
									7) 公然侮辱他人或者捏造事实诽谤他人的，或者进行其他恶意攻击的；<br>
									8) 损害国家机关信誉的；<br>
									9) 其他违反宪法和法律法规的；</p>
								<p></p>                    </section>
						</section>
					</article>
						<div class="footer_fix"></div>
						<div class="bottom_fix"></div>
					</div>
					<div class="fix-bottom">
						<a class="weui-btn weui-btn_primary close-popup" href="javascript:$('#weuiAgree').prop('checked',true);">我已阅读并同意此协议</a>
					</div>
				</div>
			</div>
			<div class="fix-bottom" style="position: relative">
				<input type="submit" class="weui-btn weui-btn_primary" name="dosubmit" id="dosubmit" value="确认发布">
			</div>
		</form>
    </div>
	<div id="popctrl" class="weui-popup__container">
		<div class="weui-popup__overlay"></div>
		<div class="weui-popup__modal">
			<div style="height:100vh"><img id="photo"></div>
			<div class="pub_funcbar">
				<a class="weui-btn close-popup weui-btn_primary" data-method="confirm">确定</a>
				<a class="weui-btn close-popup weui-btn_default" data-method="destroy">取消</a>
			</div>
		</div>
	</div>

	<div class="masker" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);display:none;z-index:1000" onclick='$("#choose_sh").select("close")'></div>
	<div class="cl footer_fix"></div>
	<!-- vue模块start -->
	<van-popup v-model="popupShow" position="bottom" round>
		<div style="padding: 20px 0 0 0;background-color: #f6f6f6;">
			<div class="header-action" style="padding: 0 20px">
				<div class="js_dialog_2_close" @click="dialogCancel">取消</div>
				<div class="js_dialog_2_title">{pigcms{$houseName}</div>
				<div class="js_dialog_2_complete color-FF4D00" @click="dialogOk">完成</div>
			</div>
			<div class="real-estate" v-if="houseList.length">
				<div class="item" v-for="(item,index) in houseList" :key="index" @click="selectHouse(item,index)">
					<div class="left">
						<img :src="item.village_logo" alt="">
					</div>
					<div class="right">
						<div class="select_h" v-if="houseIndex === index"><van-icon name="success" color="#ff4d00"></van-icon></div>
						<div class="title">{{item.village_name}}</div>
						<div class="desc" v-if="item.house_address">{{item.house_address}}{{item.room}}</div>
						<div class="desc" v-if="item.housesize">{{item.housesize}}平方</div>
					</div>
				</div>
			</div>
			<div v-else>
				<van-empty description="暂无数据"></van-empty>
			</div>
		</div>
	</van-popup>
	<!-- vue模块end -->
	</div>
	<include file="footer"/>
	<script src="{pigcms{$static_path}js/jquery.cookie.js"></script>
	<if condition="$config['is_demo_domain']">
		<script>
			var isShow = hb_getcookie('classifyFabuDetailDemoTip');
			if(!isShow){
				demoDomain_tip("系统后台添加分类时，可以自定义除内容外其他的填写项、发布信息是否需要收费、是否允许上传图片、信息是否需要人工审核（<span style='font-size:12px;'>系统后台已支持全平台的敏感词设置过滤显示</span>）等。", "发布页面使用提醒");
				hb_setcookie('classifyFabuDetailDemoTip','1',86400*365);
			}
		</script>
	</if>
	<if condition="$is_weixin_browser">
		<script type="text/javascript">
			window.shareData = {
				"moduleName":"Home",
				"moduleID":"0",
				"imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
				"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Classifynew/index')}",
				"tTitle": "{pigcms{$config.classify_title}",
				"tContent": "{pigcms{$config.site_name}"
			};
		</script>
		{pigcms{$shareScript}
	</if>
	<script>
		var _this = '';
		// 实例化vue
		var app = new Vue({
			el: '#app',
			data: {	
				houseList: [],
				popupShow: false,
				houseIndex: -1,
				houseItem: '',
				house_info: '',
				classify_time_order: 0,//yd.aihorse.net山西小红马定制字段
			},
			created() {
				$("#app").show();
				_this = this;
				this.classify_time_order = {pigcms{:C('customization.classify_time_order')?:0};
				if(this.classify_time_order == 1){
					this.getDetail();
				}
			},
			methods: {
				getDetail() {
					//获取房源
					v20Axios('/classify/api.ClassifyMenuOrder/userRoomInfo',{},res=>{
						this.houseList = res.roomlist
                	})
				},
				//打开弹窗
				dialogOpen(){
					this.popupShow = true
				},
				//取消
				dialogCancel(){
					this.popupShow = false;
					this.houseItem = null;
					this.house_info = ''
					this.houseIndex = -1;
				},
				//完成
				dialogOk(){
					this.popupShow = false
					this.houseItem = this.houseList[this.houseIndex];
				},
				//选中房产
				selectHouse(item,index){
					this.houseIndex = index;
					this.houseItem = item;
					this.popupShow = false
					this.house_info = JSON.stringify(item)
				},
			}
		})
	</script>
	<script>
		function clearNoNum(value){
			//清除"数字"和"."以外的字符

			value = value.replace(/[^\d.]/g,"");

			//验证第一个字符是数字而不是
			value = value.replace(/^\./g,"");

			//只保留第一个. 清除多余的
			value = value.replace(/\.{2,}/g,".");
			value = value.replace(".","$#$").replace(/\./g,"").replace("$#$",".");

			//只能输入两个小数
			value = value.replace(/^(\-)*(\d+)\.(\d\d).*$/,'$1$2.$3');
			return value;
		}
		function setTypeidNew(dom){
			if($(dom).hasClass("tag-on")){
				$(dom).removeClass("tag-on");
				$(dom).next('input').attr('checked',false);
			}else{
				$(dom).addClass("tag-on");
				$(dom).next('input').attr('checked',true);
			}
		}
	</script>
	<script src="{pigcms{$static_path}classifynew/js/cropper.min.js" type="text/javascript"></script>
	<script>
		var photoct = $('#photo');
		var imgpop = $('#popctrl');
		var uploadinput = $('#uploaderInput');
		var max_upload_num = 6;
		var boxer = $('#uploaderFiles');
        var adress=$.cookie('user_address')
		 if(adress!='null' && adress!='' && adress){
			 adress=JSON.parse(adress)

			 $("input[name=area]").val(adress.area)
			 $("input[name=city]").val(adress.city)
			 $("input[name=latitude]").val(adress.latitude)
			 $("input[name=longitude]").val(adress.longitude)
			 $("input[name=province]").val(adress.province);
             $("input[name=adress]").val(adress.adress);
			 $(".adress").html(adress.adress)
			 $.cookie('user_address',null)
		 }
		$(function () {
			var URL = window.URL || window.webkitURL;
			var blobURL;
			var file;
			uploadinput.on("change", function () {
				photoct.cropper('destroy').cropper({
					minContainerHeight: 320,
					autoCropArea:1
				});
				var files = this.files;
				if (!photoct.data('cropper')) {
					return;
				}
				if (files && files.length) {
					file = files[0];
					if (/^image\/\w+$/.test(file.type)) {
						blobURL = URL.createObjectURL(file);
						photoct.one('built.cropper', function () {
							URL.revokeObjectURL(blobURL);
						}).cropper('reset').cropper('replace', blobURL);
						uploadinput.val('');
						imgpop.popup();
					} else {
						$.toptip('Please choose an image file.', 'error');
					}
				}
			});

			$('.pub_funcbar a').each(function () {
				var btn = $(this);
				var mtd = btn.attr('data-method');
				btn.on('click', function () {
					if (mtd == 'destroy') {
					} else if (mtd == 'confirm') {
						result = photoct.cropper('getCroppedCanvas');
						photo = result.toDataURL('image/jpeg');
						var img=photo.split(',')[1];
						img=window.atob(img);
						var ia = new Uint8Array(img.length);
						for (var i = 0; i < img.length; i++) {
							ia[i] = img.charCodeAt(i);
						}
						var bfile = new Blob([ia], {type:"image/jpeg"});


						compress(bfile, function(cmp_photo){
							var img = cmp_photo.split(',')[1];
							img = window.atob(img);
							var ia = new Uint8Array(img.length);
							for (var i = 0; i < img.length; i++) {
								ia[i] = img.charCodeAt(i);
							}
							var blob = new Blob([ia], {type:"image/jpeg"});
							var formdata=new FormData();
							formdata.append('filename','file');
							formdata.append('file',blob);

							$.ajax({
								type: 'post',
								url: "{pigcms{:U('ajaxImgUpload')}",
								data :  formdata,
								processData : false,
								contentType : false,
								success: function (data) {
									if(null==data){ tip_common('error|'+ERROR_TIP); return false;}
									if(data.indexOf('success')!==-1){
										var html = '<li class="weui-uploader__file weui-uploader__file_status" style="background-image:url(' + cmp_photo + ')"><input type="hidden" name="inputimg[]" value="' + data.split('|')[1] + '"/><div class="weui-uploader__file-content"><i class="weui-icon-warn iconfont icon-shanchu"></i></div></li>';
										$('#loadingimg').remove();
										boxer.append(html);
                                        var len = document.getElementById('uploaderFiles').getElementsByTagName('li').length;
                                        console.log('lem',len);
                                        if (8<=len) {
                                            $('.weui-uploader__input-box').hide();
                                        } else {
                                            $('.weui-uploader__input-box').show();
                                        }
									} else {
										$('#loadingimg').remove();
										tip_common(data);
									}
								}
							});
						});

						boxer.append('<li id="loadingimg" class="weui-uploader__file weui-uploader__file_status"><div class="weui-uploader__file-content"><img src="{pigcms{$static_path}classifynew/images/loading.gif"/></div></li>');


					} else {
						var opt = btn.attr('data-option');
						photoct.cropper(mtd, opt);
					}
				});
			});
		});
		function compress(file, callback){
			var reader = new FileReader();

			reader.onload = function (e) {

				var image = $('<img/>');
				image.on('load', function () {
					var square = 640;
					var canvas = document.createElement('canvas');


					if (this.width > this.height) {
						canvas.width = Math.round(square * this.width / this.height);
						canvas.height = square;
					} else {
						canvas.height = Math.round(square * this.height / this.width);
						canvas.width = square;
					}

					var context = canvas.getContext('2d');
					context.clearRect(0, 0, square, square);
					var imageWidth = canvas.width;
					var imageHeight = canvas.height;
					var offsetX = 0;
					var offsetY = 0;
					context.drawImage(this, offsetX, offsetY, imageWidth, imageHeight);
					var data = canvas.toDataURL('image/jpeg', 0.8);
					console.log([imageWidth,imageHeight]);
					callback(data);
				});

				image.attr('src', e.target.result);
			};

			reader.readAsDataURL(file);
		}
		function sms_time() {
			var o = $('#vcodebtn');
			if (SMS_WAIT_TIME <= 0) {
				o.removeAttr("disabled");
				o.html("获取验证码");
				SMS_WAIT_TIME = 120;
			} else {
				o.attr("disabled", true);
				o.html("重发(" + SMS_WAIT_TIME + ")");
				SMS_WAIT_TIME--;
				setTimeout(function() {
					sms_time();
				}, 1000);
			}
		}
			$('#vcode_tel').on('keyup', function () {
			var vcode_btn = $('#vcode_btn'), vcode_area = $('#vcode_area'), that =$(this);
			var ombi = that.data('old'), omval = that.val();
			if(omval == ombi){
				vcode_btn.addClass('w0');
				vcode_area.addClass('none');
			}else{
				vcode_btn.removeClass('w0');
				vcode_area.removeClass('none');
			}
		});

		if($('.openlocation').length>0){
		PUB_VARID = $('.openlocation').data('id');
		setTimeout(function () {
			IGNORETIP = 1;
			he_getlocation(setPoint);
			if(typeof wx!=='undefined'){
				wx.ready(function () {
					he_getlocation(setPoint);
				});
			}
		}, 300);
	}
	function dosubmit_s() {
		if(!$('#multip').val() || $('#multip').val() == ''){
			$.alert('请选择发布规格');
			return false;
		}
		return true;
	}


		$("#is_assure").on("change",function(){
			if($(this).prop("checked")){
				$("#assure_money").css('display', 'block');
			}else{
				$("#assure_money").css('display', 'none');
			}
		})
		$(".is_baoyou").on("change",function(){
			 console.log($(this).val())
			if($(this).val()==0){
				$(".baoyou").show()
			}else{
				$(".baoyou").hide()
			}
		})

		$("#color-gray").click(function(){
			var detail={}
			detail.cid = $('input[name="cid"]').val();
			detail.fcid = $('input[name="fcid"]').val();
			detail.pfcid = $('input[name="pfcid"]').val();
			$.cookie("user_address", JSON.stringify(detail));
			location.href = "/wap.php?g=Wap&c=Classify&a=adres_map&cid=6&fcid=1&pfcid=0&cid="+detail.cid+"&fcid="+detail.fcid+"&pfcid="+detail.pfcid;
		});
	</script>
</body>
</html>