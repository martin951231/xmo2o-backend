<if condition="$list">
	<volist name="list" id="vo">
		<div class="li newli" id="li_{pigcms{$vo.id}">
			<div class="po-avt-wrap">
				<a href="{pigcms{:U('member',array('uid'=>$vo['uid']))}">
                    <php>if ($vo['avatar']) {</php>
                        <img class="po-avt" src="{pigcms{:replace_file_domain($vo['avatar'])}"/>
                    <php>} else {</php>
                        <img class="po-avt" src="./static/images/user_avatar.jpg"/>
                    <php>}</php>
				</a>
			</div>
			<div class="po-cmt">
				<div class="po-hd cl">   
					<a class="abs " href="{pigcms{:U('category',array('cat_id'=>$vo['cat_id']))}">{pigcms{$vo.cat_name}</a>
					<div class="usr-name mod-usr-name lv">
						<span class="name">
                            <if condition="$vo.nickname neq ''">
                                {pigcms{$vo.nickname|msubstr=###,0,15}
                                <else/>
                                &nbsp;&nbsp;
                            </if>
                        </span>
						<if condition="$vo['topsort']">
							<div class="mod-lv is-star">置顶</div>
						</if>
						<if condition="$vo['redpack_count']">
							<div class="mod-lv is-hot view_jump" data-id="{pigcms{$vo.id}" data-stid="0">红包</div>
						</if>
					</div>
					<div class="post cl">
						
						<if condition="$vo['is_assure']">
							<div class="weui-flex " style="padding-top:10px;background:#fff;   align-items: center;flex-wrap: wrap;">
								<div class="mod-lv is-hot ml0" style="margin-right: 10px;">平台担保</div>
								<div class="main_color">
									<span class="f12">{pigcms{$config['Currency_symbol']}</span>
									<span class="f20">{pigcms{$vo.assure_money}</span>
								</div>
								<if condition="$vo['cost_price'] gt 0">
								<div style="padding-left:10px" class="order_price c9">
									<span class="f12">原价{pigcms{$config['Currency_symbol']}{pigcms{$vo.cost_price}</span>
								</div>
								</if>
								<div style="padding-left:10px" class="c9">
									<if condition="$vo['is_post_free']">
										<span class="f12">包邮</span>
									<else/>
										<span class="f12">运费</span>
										<span class="f12">{pigcms{$config['Currency_symbol']}{pigcms{$vo.postage}</span>
									</if>
								</div>
							</div>
						</if>
						<if condition="$vo['content_label']">
							<div class="cl mt8 item_tags view_jump" data-id="{pigcms{$vo.id}" data-stid="0">
								<volist name="vo['content_label']" id="voo" key="kk">
									<span class="mod-feed-tag b-color{pigcms{$kk}">{pigcms{$voo}</span>
								</volist>
							</div>
						</if>

						<div id="view_jump_{pigcms{$vo.id}" class="view_jump mod-feed-text is-three cl" data-id="{pigcms{$vo.id}" data-stid="0">
                            {pigcms{$vo.description}
                            <span class="block"><span class="main_color">联系人：</span>{pigcms{$vo.lxname}</span>
						</div>
						<if condition="$vo['is_hide'] eq 1">
							<div class="view_jump main_color" data-id="{pigcms{$vo.id}" >打赏后查看全文</div>
						<else/>
							<a class="view_jump showfull main_color" id="showfull_{pigcms{$vo.id}" data-id="{pigcms{$vo.id}" data-stid="0">全文</a>
						</if>
						<div class="cl feed-preview-pic">
							<php>if($vo['imgs']){$imgsArr = unserialize($vo['imgs']);$imgsArr = array_slice($imgsArr,0,3);}</php>
							<if condition="$vo['imgs']">
								<volist name="imgsArr" id="imgvo">
									<span class="imgloading view_jump" data-id="{pigcms{$vo.id}" data-stid="0">
										<img src="{pigcms{:replace_file_domain($imgvo)}"/>
									</span>
								</volist>
							</if>
						</div>
					</div>
					<if condition="$_GET['hongbao']">
						<div class="weui-flex pr">
							<div class="weui-flex__item">
								<span class=" color-red" >
									<i class="iconfont icon-hongbao3 f18"></i> 
									<span class="f12">&yen;</span>
									<span class="f20">{pigcms{$vo.redpack_money}</span>
									<span class="f12">{pigcms{$config['Currency_txt']}</span>
								</span>
							</div>
							<if condition="$vo['redpack_count'] eq $vo['redpack_count_get']">
								<div class="color-gray f12 hlisttip">来晚一步，抢光了</div>  
							<else/>
								<div class="color-red f12 hlisttip">抢红包进行中……</div>
							</if>
						</div>
					<else/>
						<div class="cl pr footer">
							<p class="time">
								<span><em>{pigcms{$vo.views}</em>浏览，</span>
								<if condition="$vo['shares']"><span><em>{pigcms{$vo.shares}</em>分享，</span></if>
								<span>{pigcms{:date('m-d',$vo['updatetime'])}发布</span>
							</p>
							<if condition="$vo['uid'] neq $user_session['uid']">
								<i class="c-icon iconfont icon-qunawanhuifu footer-show-handle" id="c_icon_{pigcms{$vo.id}"></i>
								<div class="touch-panel animated opannel">
									<div class="touch-panel-c weui-flex">
										<if condition="$_GET['type'] neq 'collect'">
											<a href="javascript:void(0)" class="weui-flex__item praise" data-id="{pigcms{$vo.id}" data-href="{pigcms{:U('collectOpt',array('vid'=>$vo['id']))}"><i id="praise_{pigcms{$vo.id}" class="iconfont icon-jinlingyingcaiwangtubiao44"></i>收藏</a>
										<else/>
											<a href="javascript:void(0)" class="weui-flex__item praise" data-id="{pigcms{$vo.id}" data-href="{pigcms{:U('collectOpt',array('vid'=>$vo['id'],'type'=>'cancle'))}"><i id="praise_{pigcms{$vo.id}" class="iconfont icon-jinlingyingcaiwangtubiao24"></i>取消</a>
										</if>

										<?php if($vo['is_hide'] == 1){ ?>
											<a  class="weui-flex__item view_jump" data-id="{pigcms{$vo.id}"><i class="icon-dianhua2 iconfont"></i>电话</a>
											<if condition="$config['is_im']">
												<a  class="weui-flex__item view_jump" data-id="{pigcms{$vo.id}"><i class="icon-sixin2 iconfont"></i>私信</a>                    
											</if>
										<?php }else{ ?>
											<a href="tel:{pigcms{$vo.lxtel}" class="weui-flex__item"><i class="icon-dianhua2 iconfont"></i>电话</a>
											<if condition="$config['is_im']">
												<a href="{pigcms{:U('My/go_im',array('hash'=>'group_user'.$vo['uid'],'title'=>urlencode($vo['nickname'])))}" class="weui-flex__item"><i class="icon-sixin2 iconfont"></i>私信</a>                    
											</if>
										<?php } ?>
									</div>
								</div>
							<else/>
								<a class="a c_opt" href="javascript:;" id="pubitem_{pigcms{$vo.id}" data-id="{pigcms{$vo.id}" data-uid="{pigcms{$vo.uid}" data-wc="0" data-canzd="1" data-canhb="<if condition="$vo['redpack_count'] eq 0">1<else/>0</if>" onclick="return showansi(this);">操作/扩散</a>
							</if>
						</div>
					</if>
				</div>
				<div class="r" id="r_{pigcms{$vo.id}" style="display:none"></div>
				<div class="cmt-wrap" id="cmt_wrap_{pigcms{$vo.id}" style="display:none">
					<div class="like cl">
						<span class="likenum c9"><em id="praises_{pigcms{$vo.id}">0</em>赞</span>
						<span class="likeuser z" id="praise_list_{pigcms{$vo.id}"></span>
					</div>
				</div>
			</div>
			<if condition="$vo['uid'] eq $user_session['uid']">
				<div class="po-act">
					<a class="weui-btn weui-btn_mini p0 mt0" href="javascript:;" onclick="hb_dig('{pigcms{$vo.id}');">置顶</a>
					<if condition="$vo['redpack_count'] eq 0 && $config['classify_hongbao'] eq 1">
						<a class="weui-btn weui-btn_mini p0 mt0" href="javascript:;" onclick="hb_hbchoice('{pigcms{$vo.id}');">红包</a>
					</if>
					<a class="weui-btn weui-btn_mini p0 mt0" href="javascript:;" onclick="hb_shuaxin('{pigcms{$vo.id}');">刷新</a>
					<a class="weui-btn weui-btn_mini p0 mt0" href="javascript:;" onclick="$('#pubitem_{pigcms{$vo.id}').trigger('click');">更多</a>
				</div>
			</if>
		</div>
	</volist>
	<style>
		.newli .po-hd{
			overflow:visible;
		}
		.newli .footer{
			margin-right: -15px;
			overflow: hidden;
		}
		.newli .footer-show-handle{
			padding-right: 15px;
		}
		.newli .touch-panel{
			right:47px;
		}
	</style>
</if>