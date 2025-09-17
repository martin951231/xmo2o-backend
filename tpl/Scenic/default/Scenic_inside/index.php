<include file="Public/header"/>
<include file="Public/header_city"/>
	<!--导航-->
	<section class="navigation">
		<div class="w1200">
			<ul class="clr nav_top">
				<li>
					<a href="{pigcms{:U('Index/index')}">网站首页</a>
				</li>
				<li class="pull on">
					<a href="{pigcms{:U('Scenic_list/index')}">景点门票</a>
				</li>
				<li>
					<a href="{pigcms{:U('Scenic_travel_plan/index')}">行程规划</a>
				</li>
				<li>
					<a href="{pigcms{:U('Scenic_mate/index')}">寻找游伴</a>
				</li>
				<li>
					<a href="{pigcms{:U('Scenic_aguide/index')}">当地向导</a>
				</li>
			</ul>
		</div>
	</section>

    <section class="broadcast">
        <div class="broadcast_top">
            <div class="cast_top" style="background: url({pigcms{$project['pic']}) center no-repeat; background-size: 100% ">
                <!-- <div class="cast_n">
                    <div class="w1200 clr">
                        <div class="fl img">
                            <img src="{pigcms{$project['pic']}" width="350" height="220">
                        </div>
                        <div class="p370">
                            <h2 class="p370_h2">{pigcms{$project['project_title']}</h2>
                            <div class="p370_div">{pigcms{$project['project_conter']}</div>
                        </div>
                    </div>
                </div> -->
            </div>
            <div class="cast_n">
                <div class="w1200 clr">
                    <div class="fl img">
                        <img src="{pigcms{$project['pic']}" width="350" height="220">
                    </div>
                    <div class="p370">
                        <h2 class="p370_h2">{pigcms{$project['project_title']}</h2>
                        <div class="p370_div">{pigcms{$project['project_conter']}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="broadcast_end">
            <div class="w1200">
                <ul>
                	<volist name="project_details" id="prod">
	                    <li class="clr">
	                        <div class="fl img">
	                            <img src="{pigcms{$prod['pic']}" width="410" height="260">
	                        </div>
	                        <div class="p430">
	                            <div class="h2_bb clr">
	                                <h2 class="fl p430_h2">{pigcms{$prod['project_title']}</h2>
	                                <div class="w34 fr">
	                                    <img class="img1" src="{pigcms{$static_path}images/dht_06.png">
	                                    <img class="img2" src="{pigcms{$static_path}images/dht_03.png">
	                                    <audio  class="music" src="{pigcms{$prod['mp3']}"></audio>
	                                </div>
	                                <span class="fr yuybb">语音播报</span>
	                            </div>
	                            <div class="p430_div">{pigcms{$prod['project_conter']}</div>
	                        </div>
	                    </li>
                    </volist>
                </ul>
                <div class="pages">
                	<span class="num">{pigcms{$pagebar}</span>
                </div>
            </div>
        </div>
    </section>
	<script type="text/javascript">
		$(".w34") .click(function(){
			var audio = $(this).find(".music");
			var v=audio[0];
			if(v.paused){ //判断暂停 或者播放
				var music=$(this).parents("li").siblings("li").find(".music");

				for(var i=0; i<music.length;i++){
					var t=music[i];
					t.pause();
					music.siblings(".img1").removeClass("animat-op");
					music.siblings(".img2").removeClass("animat-op1");
				};
				v.play();//播放
				$(this).find(".img1").addClass("animat-op");
				$(this).find(".img2").addClass("animat-op1");
			}else{
				v.pause();// 暂停
				$(this).find(".img1").removeClass("animat-op");
				$(this).find(".img2").removeClass("animat-op1");
			}
		})
	</script>
<include file="Public/footer"/>