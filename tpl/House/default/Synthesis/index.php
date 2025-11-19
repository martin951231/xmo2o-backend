<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <i class="ace-icon fa fa-home home-icon"></i>
            <li class="active">首页</li>
        </ul>
    </div>
    <style>
        .widget-titles{
            position: relative;
            font-size: 16px;
            font-weight:bold;
        }
        .cross{
            width: 100px;
            margin-top: -12px;
            margin-left: 17px;
            border-bottom: 3px solid #0099FC;
        }
        .box{
            /*border: 1px solid #eee;*/
            height: 350px;
            width: 350px;
            margin-left: 50px;
            margin-bottom: 30px;
            box-shadow: 1px 1px 10px 1px #eee;
            text-align: center
        }
        .sever-title{
            font-size: 22px;
            margin-top: 32px;
            font-weight:bold;
        }
        .introduce{
            font-size: 16px;
            margin-top: 10px;
            width: 300px;
            margin-left: 20px;
            /*text-indent:10px;*/
        }
        .img-logo{
            text-align: center;margin-top: 65px;
        }
       .page-home .widget{
            box-shadow:none;
        }
    </style>
    <!-- 内容头部 -->
    <div class="page-content page-home">
        <div class="home-main">
            <div class="row">
                <div class="am-margin-bottom">
                    <div class="widget am-cf">
                        <div class="widget-body clearfix" style="">
                            <div class="am-u-sm-12 am-u-md-6 am-u-lg-3" style="width: 50%;background-color: #f9f9f9;border-right:1px #eee solid;">
                                <div class="widget-card">
                                    <div class="card-header" style="color: #0a0a0a;font-size: 18px;">
                                        <img style="width: 40px;" src="./tpl/House/default/static/images/service/street.png">
                                        街道相关信息
                                    </div>
                                    <if condition="$streetInfo">
                                        <div class="card-body">
                                            <div class="card-value"></div>
                                            <div class="card-description" style="color: #0a0a0a;font-size: 15px;">所属街道：{pigcms{$streetInfo['area_name']}</div>
                                            <div class="card-value"></div>
                                            <div class="card-description" style="color: #0a0a0a;font-size: 15px;">街道联系方式：{pigcms{$streetInfo['phone']}</div>
                                            <div class="card-value"></div>
                                            <div class="card-description" style="color: #0a0a0a;font-size: 15px;">街道地址：{pigcms{$streetInfo['detail_address']}</div>
                                        </div>
                                        <else/>
                                        <div class="card-body">
                                            <div class="card-description" style="color: #0a0a0a;font-size: 15px;">暂未绑定街道</div>
                                        </div>
                                    </if>
                                </div>
                            </div>
                            <div class="am-u-sm-12 am-u-md-6 am-u-lg-3" style="width: 50%;background-color: #f9f9f9;">
                                <div class="widget-card">
                                    <div class="card-header" style="color: #0a0a0a;font-size: 18px;">
                                        <img style="width: 40px;" src="./tpl/House/default/static/images/service/community.png">
                                        社区相关信息
                                    </div>
                                    <if condition="$communityInfo">
                                        <div class="card-body" style="font-size: 16px;">
                                            <div class="card-value"></div>
                                            <div class="card-description" style="color: #0a0a0a;font-size: 15px;">所属社区：{pigcms{$communityInfo['area_name']}</div>
                                            <div class="card-value"></div>
                                            <div class="card-description" style="color: #0a0a0a;font-size: 15px;">社区联系方式：{pigcms{$communityInfo['phone']}</div>
                                            <div class="card-value"></div>
                                            <div class="card-description" style="color: #0a0a0a;font-size: 15px;">社区地址：{pigcms{$communityInfo['detail_address']}</div>
                                        </div>
                                        <else/>
                                        <div class="card-body">
                                            <div class="card-description" style="color: #0a0a0a;font-size: 15px;">暂未绑定社区</div>
                                        </div>
                                    </if>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="am-margin-bottom">
                    <div class="widget clearfix">
                        <div class="widget-head">
                            <div class="widget-titles">街道/社区相关介绍
                            <hr />
                            <div class="cross"></div>
                            </div>
                        </div>
                        <div class="widget-body clearfix" style="margin-left: 30px;">
                            <volist name="list" id="val">
                            <div class="am-u-sm-6 am-u-md-6 box">
                                <div class="img-logo">
                                    <img width="110px;" src="{pigcms{$val['img']}" alt=""/>
                                </div>
                                <div class="sever-title" style="">{pigcms{$val['title']}</div>
                                <div class="introduce" style="">{pigcms{$val['content']}</div>
                            </div>
                            </volist>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<style>
    .page-home .am-u-lg-3.txt_5{
        /*width: 20% !important;*/
    }
</style>
<include file="Public:footer"/>
