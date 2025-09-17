<include file="NewPublic:header"/>
    <link rel="stylesheet" href="{pigcms{$static_public}layui2/css/layui.css"  media="all"/>
    <link rel="stylesheet" href="{pigcms{$static_public}application/css/base.css?t=12"  media="all"/>
    <style>
        .application_tag_box {
            display: inline-block;
            border: 1px solid red;
            font-weight: 100;
            font-size: 12px;
            padding: 0px 5px;
            color: red;
            margin-left: 3px;
        }
        .application-item .application-entry .application-logo>img {
            border-radius: 5px;
        }
    </style>
<div class="page-content" <if condition="!$_GET['iframe']">style="margin-left: 150px;"</if>>
        <div class="layui-main layui-anim layui-anim-up">
            <div id="LAY_preview">
                <volist name="application_list" id="vo">
                    <if condition="$vo['application_list']">
                        <div class="application-list">
                            <div class="application-title">
                                <span class="title-main">{pigcms{$vo.cat_name}</span>
                                <span class="title-desc">{pigcms{$vo.cat_desc}</span>
                            </div>
                            <div class="application-list-content clearfix">
                                <volist name="vo['application_list']" id="voo">
                                    <li class="application-item">
                                        <a href="{pigcms{:U('detail',array('application_id'=>$voo['application_id'],'from'=>$_GET['from'],'iframe' => $_GET['iframe']))}" class="application-entry">
                                            <div class="application-logo">
                                                <img src="{pigcms{$config.site_url}/static/application/img/{pigcms{$voo.application_label}.png?t=011"/>
                                            </div>
                                            <div class="application-name">
                                                <span class="application-name-content">{pigcms{$voo.application_name}</span>
                                                <div class="zent-popover-wrapper zent-pop-wrapper" style="display:inline-block;padding-left: 5px;">
                                                    <if condition="isset($voo['application_ico_arr']['wxapp'])">
                                                        <span class="support-wxapp"></span>
                                                    </if>
                                                    <if condition="isset($voo['application_ico_arr']['hot'])">
                                                        <span class="support-hot"></span>
                                                    </if>
                                                    <if condition="isset($voo['application_ico_arr']['recommend'])">
                                                        <span class="support-recommend"></span>
                                                    </if>
                                                </div>
                                                <if condition="isset($voo['application_tag_txt']) && $voo['application_tag_txt']">
                                                    <div class="application_tag_box">
                                                        {pigcms{$voo['application_tag_txt']}
                                                    </div>
                                                </if>
                                            </div>
                                            <div class="application-desc">{pigcms{$voo.application_txt}</div>
                                        </a>
                                    </li>
                                </volist>
                            </div>
                        </div>
                    </if>
                </volist>
            </div>
        </div>
    </div>
    <script src="{pigcms{$static_public}layui2/layui.all.js" charset="utf-8"></script>
    <script>
        layui.$('.application-item').width((layui.$('.application-list').width() - 16*4) / 4);

        var layer_tips_index = null;

        layui.$('.support-wxapp').hover(function(){
            layer_tips_index = layer.tips('小程序已支持', layui.$(this), {
                tips: [1, '#0FCC6E']
            });
        },function(){
            layer.close(layer_tips_index);
        });
        layui.$('.support-hot').hover(function(){
            layer_tips_index = layer.tips('热门应用', layui.$(this), {
                tips: [1, '#FF0202']
            });
        },function(){
            layer.close(layer_tips_index);
        });
        layui.$('.support-recommend').hover(function(){
            layer_tips_index = layer.tips('强烈推荐', layui.$(this), {
                tips: [1, '#2882F3']
            });
        },function(){
            layer.close(layer_tips_index);
        });
    </script>
<include file="NewPublic:footer"/>