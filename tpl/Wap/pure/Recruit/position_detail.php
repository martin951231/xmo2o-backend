<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=no,width=device-width"/>
    <meta http-equiv="pragma" content="no-cache"/>
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <meta name='apple-touch-fullscreen' content='yes'/>
    <meta name="apple-mobile-web-app-status-bar-style" content="black"/>
    <meta name="format-detection" content="telephone=no"/>
    <meta name="format-detection" content="address=no"/>
    <title>职位详情</title>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}classifynew/css/weui.css">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/workIcon.css">
    <script type="text/javascript" src="https://apps.bdimg.com/libs/jquery/1.9.0/jquery.min.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/recruit.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}recruit/css/style.css"></script>
    <style>
        .color_f{color:#fff}
        .mian-bg{background:#ff4d00}
        .bg_f{background:#fff}
        .main_color{color:#ff4d00}
        .align_center{text-align:center}
        .mg-r5{margin-right:5px}
        .no_border:before{content:none !important;}
        .color_999{color:#999}
        .color_7{color:#777}
        .pt10{    padding-top: 10px;}
        body {background-color: #f4f4f4;}
        .w100{width:100%}
        .h100{height:100%}
        .flex{display:flex !important;display: -webkit-box; display: -moz-box; display: -ms-flexbox; display: -webkit-flex; align-items: center;  }
        .flex_one{  flex:1;  -webkit-box-flex: 1;  -webkit-flex: 1;  }
        .rel{position:relative}
        .abs{position:absolute}
        .font11{font-size:11px !important;}
        .font12{font-size:12px !important;}
        .font13{font-size:13px !important;}
        .pd10{    padding: 10px;}
        .pd_b{padding-bottom: 10px;}
        .border-l:after {
            content: " ";
            position: absolute;
            left: 0;
            top: 0;
            width: 1px;
            bottom: 0;
            border-left: 1px solid #d5d5d6;
            color: #d5d5d6;
            -webkit-transform-origin: 0 0;
            transform-origin: 0 0;
            -webkit-transform: scaleX(.5);
            transform: scaleX(.5);
        }
        .mrt_10{margin-top: 10px;}
        .persor_normal{border-left: 2px solid red;padding-left: 5px;}
        .clamp{    -webkit-line-clamp: unset;}
        .welfare span{color:#777;border-radius:100px;padding:3px 5px ;border:1px solid #ddd;display:inline-block;margin-right:5px;margin-bottom:5px}
        .jobs .w50:nth-child(odd)
        {
            text-align: center;
            margin-right: 5px;
            margin-top: 10px;
            background: #eee;
            width:48%

        }
        .jobs .w50:nth-child(even)
        {
            text-align: center;
            margin-left: 5px;
            margin-top: 10px;
            background: #eee;
            width:48%
        }
        .jobs .w50>div{text-align:left;padding:10px}
        footer{position:fixed;bottom:0px;width:100%;height:45px;line-height:45px}
        .main-content{padding-bottom:44px}
        footer  .name{    text-align: left;
            line-height: 20px;
            padding-left: 5px;}
        footer .limg p{margin-top: -25px;
            width: 55px;
            height: 55px;
            background: #fff;
            border-radius: 50%}
        footer .limg p img{margin-top: 3px;
            width: 45px;}
        .mask {  position: fixed;  top: 0px; left: 0px;  background: rgba(0,0,0,0.7);  z-index: 888;  width: 100%;height:100%;  display: none;  }
        .resume_select {
            position: fixed;
            bottom: 0;
            left: 0;
            z-index: 999;
            width: 100%;
            height: 100%;
            background-color: #fff;
            /*color: #FFFF01;*/
        }
        .share {
            position: fixed;
            bottom: 0;
            left: 0;
            z-index: 999;
            width: 100%;
            height: 100%;
            color: #FFFF01;
        }
    </style>
</head>
<body>
  <div class="main-content">
      <section>
          <header class="mian-bg color_f align_center pt10" style="height:135px;font-size:13px">
              <p>职位详情</p>
              <p style="font-size:30px; line-height:100px;">{pigcms{$position_detail['position_name']}</p>
              <!-- <div class="weui-cell  no_border" style="justify-content:center">
                  <div class="weui-cell__hd mg-r5">
                      <i class="iconfont">&#xe627;</i>
                      <span>薪水高</span>
                  </div>
                 
              </div> -->
          </header>
      </section>
      <section class="pd10 bg_f mrt_10">
          <div class="flex" style="align-items: flex-start;">
              <div class="flex_one rel align_center font12 ">
                  <span class="color_999">工资</span>
                  <p class="main_color">{pigcms{$position_detail['salary_text']}/月</p>
              </div>
              <div class="flex_one rel align_center font12 border-l">
                  <span class="color_999">经验</span>
                  <p class="main_color">{pigcms{$position_detail['work_years_text']}工作经验</p>
              </div>
             <!--  <div class="flex_one rel align_center font12 border-l">
                  <span class="color_999">休息</span>
                  <p class="main_color">单双休</p>
              </div>
              <div class="flex_one rel align_center font12 border-l">
                  <span class="color_999">工作</span>
                  <p class="main_color">白天8小时(不包括加班2小时)</p>
              </div> -->
          </div>
      </section>
      <section class="mrt_10">
          <div class="weui-panel weui-panel_access">
              <div class="weui-panel__hd "><span class="persor_normal main_color">基本信息</span></div>
              <div class="weui-panel__bd">
                  <div class="weui-media-box weui-media-box_text">
                      <p class="weui-media-box__desc clamp">{pigcms{$position_detail['desc']}</p>
                  </div>
              </div>
          </div>
      </section>
      <section class="mrt_10">
          <div class="weui-panel weui-panel_access">
              <div class="weui-panel__hd "><span class="persor_normal main_color">福利待遇</span></div>
              <div class="weui-panel__bd">
                  <div class="weui-media-box weui-media-box_text pd_b">
                      <p class="weui-media-box__desc clamp welfare">
                        <volist name="position_detail['welfare_text']" id="vo">
                            <span>{pigcms{$vo}</span>
                        </volist>
                         <!--  <span>岗位津贴</span>
                          <span>公司涟漪会</span>
                          <span>组织相亲</span>
                          <span>篮球比赛</span> -->
                      </p>
                  </div>
              </div>
          </div>
      </section>
      <section class="mrt_10">
          <div class="weui-panel weui-panel_access">
              <div class="weui-panel__hd "><span class="persor_normal main_color">企业信息</span></div>
              <div class="weui-panel__bd">
                  <div class="weui-media-box weui-media-box_text pd_b">
                      <h4>{pigcms{$position_detail['company_name']}</h4>
                      <div class="flex font13 color_999">
                          <div class="flex_one " style="padding-right:7px">
                              <span style=" padding-right: 5px;">{pigcms{$position_detail['city_name']}</span><br>
                              <span>工作地址：{pigcms{$position_detail['address']}</span>
                          </div>
                          <if condition="$long_lat['lat']">
                          <div class="align_center rel border-l" style="padding: 0px 0px 0px 10px;">
                              <i class="iconfont main_color"> &#xe61c;</i><br>
                              <span>{pigcms{$position_detail['distance']}</span>
                          </div>
                          </if>
                      </div>
                  </div>
              </div>
          </div>
      </section>
      <section class="mrt_10 bg_f">
          <div class="flex align_center font13 color_999" style="padding:10px 0px">
                <div class="flex_one" style="    padding: 5px 0px;">
                    <img src="{pigcms{$static_path}recruit/images/collect_1.png" style="    width: 14px; height: 14px;" />

                    <if condition="!$collect_info">
                        <span class="main_color collect" data-is_cancel="0">收藏</span>
                    <else/>
                        <span class="main_color collect" data-is_cancel="1">取消收藏</span>
                    </if>
                    此信息
                </div>
                 <div class="flex_one rel border-l" style="    padding: 5px 0px;">
                    <img src="{pigcms{$static_path}recruit/images/share.png" style="    width: 14px; height: 14px;" />
                    <span class="main_color goshare">推荐</span>给朋友
                    <div class="share" style="display: none;">
                        <img id="share"  src="{pigcms{$static_path}recruit/images/guide.png">
                    </div>
                </div>
          </div>
      </section>
        <if condition="$recommend_position_list">
        <section class="mrt_10 jobs  font13">
            <div class="weui-panel weui-panel_access">
                <div class="weui-panel__hd "><span class="persor_normal main_color">附近相似职位</span></div>
                <div class="weui-panel__bd">
                    <div class="weui-media-box weui-media-box_text pd_b flex job" style="padding:0px 10px 30px;flex-direction: row; flex-wrap: wrap;">
                        <volist name="recommend_position_list" id="vo">
                        <div class="w50 link newWin" data-url="{pigcms{:U('position_detail',array('position_id'=>$vo['position_id']))}">
                            <div>
                                <h4>{pigcms{$vo.position_name}</h4>
                                <span class="main_color">{pigcms{$vo.salary_text}</span><span class="color_7">/月</span>
                            </div>
                        </div>
                        </volist>
                    </div>
                </div>
            </div>
      </section>
    </if>
  </div>
<section>
    <footer class="flex font13 bg_f align_center">
        <div style="flex:3" class="flex ">
            <div style="padding-left: 20px;" class="limg">
                <p>
                    <img src="{pigcms{$position_detail['avatar']}" />
                </p>

            </div>
            <div class="flex_one name">
                <p>{pigcms{$position_detail.nickname}</p>
                <!-- <span class="color_7">2222</span> -->
            </div>
        </div>
        <div class="mian-bg color_f send_resume" style="flex:2;background: #06c1ae"  data-url="{pigcms{:U('do_send_resume')}">
           投递简历                 
        </div>
        <if condition="$config['is_im']">
            <div class="mian-bg color_f link newWin" style="flex:2" data-url="{pigcms{:U('My/go_im',array('hash'=>'group_user'.$position_detail['uid'],'title'=>urlencode($position_detail['nickname'])))}">
               聊聊看                 
            </div>
        </if>
    </footer>
</section>
<div class="resume_select" style="display: none;background: #EBEBEB;"> 
    <!-- <div class="page__bd" style="background-color: #FF4F00"> 
        <div href="javascript:;" class="icon_back" id="aaa" style="display: inline-block;width: 40px;    height: 40px;color: #FF4F00">返回</div>
        <ul class="top_fixed">
            <li class="topName">
                <span class="nav-head-name">选择简历</span>
            </li> 
        </ul> 
    </div> -->
    <if condition="$resume_list">
    <volist name="resume_list" id="vo"> 
        <div class="weui-media-box weui-media-box_text" data-resume_id="{pigcms{$vo.resume_id}" style="background: #fff;margin: 10px 0;">
            <h4 class="weui-media-box__title">{pigcms{$vo.resume_name}</h4>
        </div>
    </volist>
    <else/>
     <p style="text-align: center; margin-top: 20px;color: #aaa;">暂无简历<a href="{pigcms{:U('my_resume_base')}">去添加</a></p>
    </if>
</div>
<div class="mask"></div>
<script type="text/javascript" src="static/activity/assets/js/components/jquery/1.11.1/jquery.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/simplite/simplite.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/jquery-weui/0.8.2/js/jquery-weui.js"></script>
<script>
View={
    init:function(){
        this.click()
    },
    click:function(){
        var _this=this
        //收藏
        $(document).on("click",'.collect',function(){
            var cid = '{pigcms{$position_detail["position_id"]}';
            var is_cancel=$(this).attr('data-is_cancel');
            var html = '取消收藏';
            var _tmp = '1';

            if (is_cancel=='1') {
                html = '收藏';
                _tmp = '0';
            }
            //点击确认
            $.ajax({
                type : "post",
                url : '{pigcms{:U('do_collect')}',
                dataType : "json",
                data:{'cid':cid,is_cancel:is_cancel},
                async:false,
                success : function(data){
                    if(data.errorMsg!='success'){
                        $.alert(data.errorMsg)
                        return false
                    }
                    $.alert(data.result)
                    $('.collect').html(html);
                    $('.collect').attr('data-is_cancel',_tmp);
                },
                error:function(res){
                    console.log(res)
                }
            })
        })

        $(document).on("click",'.goshare',function(){
            $('.share').show();
            $(".mask").show();
        })
        $(document).on("click",'.share',function(){
            $('.share').hide();
            $(".mask").hide();
        })

        //选择简历
        $(document).on("click",'.send_resume',function(){
            $('.resume_select').show();
        })
        //投递简历
        $(document).on("click",'.resume_select>div',function(){
            var position_id = '{pigcms{$position_detail["position_id"]}';
            var resume_id=$(this).data('resume_id')
            var need_score = '{pigcms{$config["recruit_send_resume_need_score"]}';
            var text = '';
            if (need_score) {
                    text = '您将消耗'+need_score+'{pigcms{$config["score_name"]},确定投递简历吗？';
            }else{
                text = '确定投递简历吗？';
            }
           
            $.confirm({
                title: '提示',
                text: text,
                onOK: function () {
                    //点击确认
                    $.ajax({
                        type : "post",
                        url : "{pigcms{:U('do_resume_send')}",
                        dataType : "json",
                        data:{'position_id':position_id,resume_id:resume_id},
                        async:false,
                        success : function(data){
                            if(data.errorMsg!='success'){
                                if (data.errorCode=='73000008') {
                                    $.alert(data.errorMsg,function(){
                                        window.location.href="{pigcms{:U('Login/index')}";
                                    })
                                }else if(data.errorCode=='20120035'){
                                    $.alert(data.errorMsg,function(){
                                        window.location.href="{pigcms{:U('My/bind_user')}";
                                    })
                                }else if(data.errorCode=='20140081'){
                                    $.alert(data.errorMsg,function(){
                                        window.location.href="{pigcms{:U('score_package')}";
                                    })
                                }else{
                                    $.alert(data.errorMsg);
                                }
                                return false
                            }else{
                                $.alert(data.result,function(){
                                    window.location.href="{pigcms{:U('my_resume_send')}";
                                })
                            }
                        },
                        error:function(res){

                            console.log(res)
                        }
                    })
                },
                onCancel: function () {
                }
            });
        })
    }
}
window.View=View;
View.init()
</script>
<script type="text/javascript">
    var userOpenid = "{pigcms{$user_session.openid}";
    window.shareData = {  
      "moduleName":"Recruit",
      "moduleID":"0",
      "imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>", 
      "sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Recruit/position_detail',array('position_id'=>$position_detail['position_id']))}",
      "tTitle": "{pigcms{$position_detail.position_name}",
      "tContent": "{pigcms{$position_detail.position_name}"
    };
</script> 
{pigcms{$shareScript} 
</body>
</html>