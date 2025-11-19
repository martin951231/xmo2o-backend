<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>个人中心</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <link rel="stylesheet" type="text/css"  href="{pigcms{$static_path}integral_mall/css/community_base.css?v=02">
    <link rel="stylesheet" type="text/css"  href="{pigcms{$static_path}integral_mall/css/base.css?v=02">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}integral_mall/css/swiper.min.css?v=02">
    <script src="{pigcms{$static_path}integral_mall/js/rem.js"></script>
    <script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}"></script>
    <script src="{pigcms{$static_path}integral_mall/js/swiper.min.js"></script>

    <style>
        .jf_mine li span{
            width: 3.6rem;
        }
        .jf_mine li span i{ width: auto;}
        .jf_mine li span i.jf_dfh{
            background: url("{pigcms{$static_path}integral_mall/images/jf_mine_iocn1.png") center no-repeat;
            background-size: auto 27px;
        }
        .jf_mine li span i.jf_dsh{
            background: url("{pigcms{$static_path}integral_mall/images/jf_mine_iocn2.png") center no-repeat;
            background-size: auto 27px;
        }
        .jf_mine li span i.jf_ywc{
            background: url("{pigcms{$static_path}integral_mall/images/jf_mine_iocn3.png") center no-repeat;
            background-size: auto 27px;
        }
        .jf_mine li span i.jf_ywc1{
            background: url("{pigcms{$static_path}integral_mall/images/jf_mine_iocn4.png") center no-repeat;
            background-size: auto 24px;
        }
        .jf_mine_list li i.jf_store_iocn1{ background: url("{pigcms{$static_path}integral_mall/images/icon_perlist1.png") no-repeat left center; background-size: 0.84rem  auto;}
        .jf_mine_list li i.jf_store_iocn2{ background: url("{pigcms{$static_path}integral_mall/images/icon_perlist2.png") no-repeat left center; background-size: 0.88rem auto;}
        .jf_mine_list li i.jf_store_iocn3{ background: url("{pigcms{$static_path}integral_mall/images/icon_perlist3.png") no-repeat left center; background-size: 0.9rem auto;}
    </style>

</head>
<body style="background: #fff;">
<ul class="claarfix jf_mine">
    <li>
        <strong>
            <img src="{pigcms{$user['avatar']}" /></strong>
        <b>{pigcms{$user['phone']}</b>
    </li>
    <li>
        <span>
            <a href="{pigcms{:U('order_list',array('type'=>1,'mer_id'=>$_GET['mer_id']))}">
                <i class="jf_dfh"> </i>
                <p>待付款</p>
            </a>
        </span>
        <span>
            <a href="{pigcms{:U('order_list',array('type'=>2,'mer_id'=>$_GET['mer_id']))}">
                <i class="jf_dsh"> </i>
                <p>待发货</p>
            </a>
        </span>
        <span>
            <a href="{pigcms{:U('order_list',array('type'=>3,'mer_id'=>$_GET['mer_id']))}">
                <i class="jf_ywc"> </i>
                <p>待收货</p>
            </a>
        </span>
        <span>
            <a href="{pigcms{:U('order_list',array('type'=>4,'mer_id'=>$_GET['mer_id']))}">
                <i class="jf_ywc jf_ywc1"> </i>
                <p>待评价</p>
            </a>
        </span>
    </li>
</ul>

<ul class="claarfix jf_mine_list">
    <li>
        <a href="{pigcms{:U('My_card/merchant_point',array('mer_id'=>$_GET['mer_id'],'type'=>'score'))}">
            <i class="jf_store_iocn1"></i>
            <span>本店可用积分：{pigcms{$score.card_score} <em>积分信息</em></span>
        </a>
    </li>
    <li>
        <a href="{pigcms{:U('My_card/merchant_card',array('mer_id'=>$_GET['mer_id']))}">
            <i class="jf_store_iocn2"></i>
            <span>我的储值卡</span>
        </a>
    </li>
    <li>
        <a href="{pigcms{:U('My/adress')}">
            <i class="jf_store_iocn3"></i>
            <span>我的地址</span>
        </a>
    </li>
    <!-- <li>
        <a href="/prize/winning?id=4518117">
            <i class="jf_store_iocn5"></i>
            <span>我的中奖记录</span>
        </a>
    </li> -->
</ul>
<!--<a href="javascript:;" class="jf_out">退出登录</a>-->

<div class="jf_footer_bar">
    <ul class="clearfix">
        <li>
            <a href="{pigcms{:U('index',array('mer_id'=>$_GET['mer_id']))}">
                <i></i>
                <p>首页</p>
            </a>
        </li>
        <li>
            <a href="{pigcms{:U('storeCate',array('mer_id'=>$_GET['mer_id']))}">
                <i></i><!--class="my_store"-->
                <p>分类</p><!--class="zi_b8"-->
            </a>
        </li>
        <li>
            <a href="{pigcms{:U('order_list',array('mer_id'=>$_GET['mer_id']))}">
                <i></i>
                <p>订单</p>
            </a>
        </li>
        <li class="pr_wd">
            <a href="">
                <i></i>

            </a>
        </li>
    </ul>
</div>
<div class="footer_null"></div>
<script>

</script>
</body>
</html>

