<include file="Public/header"/>
<include file="Public/header_user"/>
	<section class="personal">
		<div class="w1200 clr">
			<div class="fl nav">
				<ul>
					<li class="gr">
						<a href="{pigcms{:U('index')}">个人信息</a>
					</li>
					<li class="mp">
						<a href="{pigcms{:U('ticket_order_list')}">门票订单</a>
					</li>
					<li class="dy">
						<a href="{pigcms{:U('aguide_order_list')}">导游订单</a>
					</li>
					<li class="xd">
						<a href="{pigcms{:U('add_guide')}">申请向导</a>
					</li>
					<li class="xd">
						<a href="{pigcms{:U('guide_order')}">我是向导订单</a>
					</li>
					<li class="yb on">
						<a href="{pigcms{:U('mate')}">我的游伴</a>
					</li>
					<li class="cx">
						<a href="{pigcms{:U('travel_person')}">出行人信息</a>
					</li>
					<li class="xc">
						<a href="{pigcms{:U('manager_travel')}">我的行程</a>
					</li>
					<li class="sc">
						<a href="{pigcms{:U('collection')}">我的收藏</a>
					</li>
				</ul>
			</div>
            <div class="fr navright">
                <div class="allorder">
                    <div class="allorder_top">
                        <ul class="clr">
                            <li <if condition="!$_GET['is_mate']">class="on"</if>>
                                <a href="{pigcms{:U('Scenic_user/mate')}">发起的游伴</a>
                            </li>
                            <li <if condition="$_GET['is_mate'] eq 2">class="on"</if>>
                                <a href="{pigcms{:U('Scenic_user/mate',array('is_mate'=>2))}">参与的游伴</a>
                            </li>
                        </ul>
                    </div>
                    <div class="allorder_end swim_end">
                        <dl>
                            <dt class="clr">
                                <div class="w225 fl">景区名</div>
                                <div class="w160 fl">游玩时间</div>
                                <div class="w340 fl">游玩介绍</div>
                                <div class="w135 fl">报名时间</div>
                            </dt>
                            <volist name="scenic_mate" id="vo">
	                            <dd>
	                                <a href="{pigcms{$vo['url']}">
	                                    <div class="dd_end clr">
	                                        <div class="w225 fl">
	                                            <h2>{pigcms{$vo['scenic_list']['scenic_title']}</h2>
	                                            <div class="w225_bot">
	                                                <span class="ari poll">{pigcms{$vo['people_number']}</span>
	                                                <span class="ari note">{pigcms{$vo['words_number']}</span>
	                                            </div>
	                                        </div>
	                                        <div class="w160 fl ari fzc6">{pigcms{$vo['start_time']}</div>
	                                        <div class="w340 fl fzc6">
	                                            <p>{pigcms{$vo['mate_intr']}</p>
	                                        </div>
	                                        <div class="w135 fl">
	                                            <p class="ari fzc6">{pigcms{:date('Y-m-d H:i:s',$vo['scenic_mate']['create_time'])}</p>
	                                            <if condition="$vo['mate_status'] eq 0 OR $vo['mate_status'] eq 1 OR $vo['mate_status'] eq 2"><p class="jxz">进行中</p>
	                                            <elseif condition="$vo['mate_status'] eq 3"/>
	                                            	<p class="jxz" style="background:#3fc67f;">已完成</p><else/><p class="jxz yjs">已结束</p>
	                                            </if>
	                                        </div>
	                                    </div>
	                                </a>
	                            </dd>
                            </volist>
                        </dl>
						<style type="text/css">
							.pagebar{background:#fe6903;color:#fff;border:1px solid #fe6903;display: inline-block;height: 30px;line-height: 30px;text-align: center;margin: 0 2px;border: 1px solid #e3e3e3;padding:0 15px;}
						</style>
						<div class="pages">
							<span class="num">{pigcms{$pagebar}</span>
						</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
	<script type="text/javascript">
		/*删除*/
		$(".swim_del").click(function(){
			$(this).parents("dd").fadeOut(function(){
				$(this).remove();
			});
		})
	</script>
<include file="Public/footer"/>