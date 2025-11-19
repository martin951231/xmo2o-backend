    <style type="text/css">
        #pageLoadTip{display:none !important;}
        .m_side___2zO5Y {
            position: fixed;
            right: 0;
            bottom: 110px;
            z-index:101;
        }
        .maskeds {
            position: fixed;
            left: 0;
            right: 0;
            top: 0;
            bottom: 0;
            z-index: 100;
            background: rgba(0,0,0,0.7);
            -webkit-transition: all .3s ease-in-out;
            transition: all .3s ease-in-out;
            display: none;
        }
        .m_side___2zO5Y.active___3rDUx .masker {
            display: block;
        }
        .m_side___2zO5Y .nav___YbyA2 {
            position: absolute;
            bottom: 0;
            right: 0;
            z-index: 2001;
            width: 210px;
            height: 45px;
            padding: 5px 0 5px;
            background: #fff;
            border-radius: 4px 0 0 4px;
            -webkit-transform: translate3d(100%,0,0);
            transform: translate3d(100%,0,0);
            -webkit-transition: -webkit-transform .3s;
            transition: -webkit-transform .3s;
            transition: transform .3s;
            transition: transform .3s,-webkit-transform .3s;
        }
        .m_side___2zO5Y.active___3rDUx .nav___YbyA2 {
            -webkit-transform: translateZ(0);
            transform: translateZ(0);
        }
        .m_side___2zO5Y .nav___YbyA2 .btn___1kW3t {
            width: 44px;
            height: 44px;
            position: absolute;
            left: -44px;
            top: 50%;
            margin-top: -23px;
            border-radius: 4px 0 0 4px;
        }
        .m_side___2zO5Y.active___3rDUx .nav___YbyA2 .btn___1kW3t:before {
            -webkit-transform: translateY(-50%) rotate(180deg);
            transform: translateY(-50%) rotate(180deg);
        }

        .m_side___2zO5Y .nav___YbyA2 .btn___1kW3t span {
            color: #fff;
            font-size: 12px;
            width: 30px;
            position: absolute;
            top: 50%;
            left: 21px;
            -webkit-transform: translateY(-50%);
            transform: translateY(-50%);
        }
        .m_side___2zO5Y .nav___YbyA2 ul {
            display: -ms-flexbox;
            display: flex;
        }
        .m_side___2zO5Y .nav___YbyA2 ul li {
            width: 25%;
            text-align: center;   
            position: relative;         
            
        }
        .m_side___2zO5Y .nav___YbyA2 ul li a {
            color: #333;
        }
        .m_side___2zO5Y .nav___YbyA2 ul p{font-size:12px}
        .iconfont {
            font-size: 20px;
        }
        .m_side___2zO5Y .nav___YbyA2 ul em{
            margin: 0 auto;
            display: block;
            height: 23px;
            width: 22px;
            background-repeat: no-repeat;
            background-size: 100%;
            margin-top:5px
        }
        .home{

            background-image: url({pigcms{$static_path}mall/images/floatTabble_title.png);
        }
         .my {
            background-image: url({pigcms{$static_path}mall/images/floatTabble_center.png);
        }
          .group {
            background-image: url({pigcms{$static_path}mall/images/floatTabble_car.png);
        }
         .store {
            background-image: url({pigcms{$static_path}mall/images/floatTabble_order.png);
        }
        .cart_num{
            border:1px solid #f00;
            border-radius: 10px;
            position: absolute;
            right: 0px;
            top:0px;
            padding: 0px 6px;
            color: #f00;
            font-size: 10px;
            line-height: 16px;
            background:#fff
        }
    </style>
	<if condition="!$_SESSION['session_store_id']">
		<div class="m_side___2zO5Y">
			<div class="maskeds cursor"></div>
			<div class="nav___YbyA2">
				<div class="btn___1kW3t cursor">
					<img src="{pigcms{$static_path}mall/images/daohang1.png" class="w100 h100 "/>
					<img src="{pigcms{$static_path}mall/images/daohang2.png" class="w100 h100 none"/>
				   <!-- <span><i class="speed">快速</i><br<i class="banners">导航</i></span>-->
				</div>
				<ul>
					<li>
						<a href="{pigcms{:U('Mall/home')}">
							<em class="home"></em>
							<p>首页</p>
						</a>
					</li>
					<li>
						<a href="{pigcms{:U('Mall/category')}">
							<em class="store"></em>
							<p>分类</p>
						</a>
					</li>
					<li>
						<a href="{pigcms{:U('Mall/cart')}">
							<em class="group"></em>
							<label class="cart_num"> </label>
							<p>购物车</p>
						</a>
					</li>
					<li>
						<a href="{pigcms{:U('Mall/myCenter',array('from'=>'mall'))}">
							<em class="my"></em>
							<p>我的</p>
						</a>
					</li>
				</ul>
			</div>
		</div>
	</if>

<script>
	var cart_init_number = {pigcms{$cart_init_number}; 
	$(".btn___1kW3t").on("click",function(){
    
		if(cart_init_number >= 99){ 
			cart_init_number = String(cart_init_number).substring(2,0)
			$('.cart_num').html(cart_init_number + '+')
		}else{
			$('.cart_num').html(cart_init_number)
		}

		if($(".m_side___2zO5Y").hasClass("active___3rDUx")){
			$(".m_side___2zO5Y").removeClass("active___3rDUx");
			$(".btn___1kW3t img").eq(1).addClass("none")
			$(".btn___1kW3t img").eq(0).removeClass("none")
			$(".maskeds").hide()
		}else{
			$(".maskeds").show();
			$(".btn___1kW3t img").eq(0).addClass("none")
			$(".btn___1kW3t img").eq(1).removeClass("none")
			$(".m_side___2zO5Y").addClass("active___3rDUx")
		}
	})
	$(".maskeds").on("click",function(){
		$(".btn___1kW3t").trigger("click")
	})
</script>