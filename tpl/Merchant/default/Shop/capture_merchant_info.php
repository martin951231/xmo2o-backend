<?php if(empty($_GET['not_first'])){ ?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8"/>
    <title>{pigcms{:L_("微页面")}</title>
    <link href="{pigcms{$static_path}diypage/css/customField.css" type="text/css" rel="stylesheet"/>
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
    <style type="text/css">
        .tips{
            text-align: center;margin-bottom: 15px;color: red;
        }
        .table .td-cont {
            line-height: 30px;
        }
        .btn_msg {
            border: none;
            display: inline-block;
            padding: 4px 11px;
            margin-bottom: 0;
            font-size: 12px;
            line-height: 20px;
            color: #fff;
            text-align: center;
            vertical-align: middle;
            cursor: pointer;
            -webkit-border-radius: 2px;
            -moz-border-radius: 2px;
            border-radius: 2px;
            background: #009adb;
        }
        .btn_qx {
            border: none;
            display: inline-block;
            padding: 4px 11px;
            margin-bottom: 0;
            font-size: 12px;
            line-height: 20px;
            color: #fff;
            text-align: center;
            vertical-align: middle;
            cursor: pointer;
            -webkit-border-radius: 2px;
            -moz-border-radius: 2px;
            border-radius: 2px;
            background: #999999;
        }
        .btn_fixed {
            position: fixed;
            bottom: 15px;
            right: 20px;
        }
    </style>
</head>
    <body style="background-color:#ffffff;">
<?php } ?>
    <div class="modal-header">
        <a class="close js-news-modal-dismiss">×</a>
        <!-- {pigcms{:L_("顶部")}tab -->
        <ul class="module-nav modal-tab">
            <li class="active"><a href="javascript:void(0);" class="js-modal-tab">{pigcms{:L_("商户列表")}</a></li>
        </ul>
    </div>

    <div class="modal-body">
        <div class="tab-content">
            <div id="js-module-feature" class="tab-pane module-feature active">
                <table class="table" style="margin-bottom: 15px;">
                    <colgroup>
                        <col class="modal-col-title">
                        <col class="modal-col-time">
                        <col class="modal-col-action">
                    </colgroup>
                    <!-- {pigcms{:L_("表格头部")} -->
                    <thead>
                    <tr>
                        <th class="title" style="background-color:#f5f5f5;">
                            <div class="td-cont">
                                <span>{pigcms{:L_("选择白名单成员")}</span>
                            </div>
                        </th>
                        <th class="time" style="background-color:#f5f5f5;text-align: right;padding-right: 20px;">
                            <div class="td-cont">
                                <a class="btn" onclick="give_white_list('{pigcms{$this->config['site_url']}/merchant.php?c=Shop&a=capture_merchant&goods_id={pigcms{$goods_id}')">{pigcms{:L_("选择商家")}</a>
                            </div>
                        </th>
                    </tr>
                    </thead>
                    <!-- {pigcms{:L_("表格数据区")} -->
                    <tbody>
                    <volist name="merchant_list" id="vo">
                        <tr>
                            <td class="title" style="max-width:300px;">
                                <div class="td-cont">
                                    <a target="_blank" class="new_window" >{pigcms{$vo.name}</a>
                                </div>
                            </td>
                            <if condition="!isset($config['not_o2o_demo'])">
                                <td class="time">
                                    <div class="td-cont">
                                        <span>{pigcms{$vo.phone}</span>
                                    </div>
                                </td>
                            </if>
                            <td class="opts">
                                <div class="td-cont">
                                    <if condition="$white_msg[$vo['mer_id']] neq ''">
                                        <button class="btn js-choose2" disabled="true">{pigcms{:L_("取消选择")}</button>
                                        <else/>
                                        <button class="btn js-choose" data-id="{pigcms{$vo.mer_id}" data-title="{pigcms{$vo.name}" data-goods_id="{pigcms{$goods_id}">{pigcms{:L_("选取")}</button>
                                    </if>
                                </div>
                            </td>
                        </tr>
                    </volist>
                    </tbody>
                </table>
                <div>
                    <div class="tips capture_merchant_info_tip">{pigcms{:L_("加入到白名单的商家才能在批发市场中查看此商品")}</div>
                    <if condition="$white_msg">
                        <p class="tag_box">
                            <volist name="white_msg" id="vo">
                                <span class="tag tag_{pigcms{$vo.mer_id}" data-mer_id="{pigcms{$vo.mer_id}" data-k="{pigcms{$key}">
                                    {pigcms{$vo.name}
                                    <a class="del">X</a>
                                </span>
                            </volist>
                        </p>
                    </if>
                </div>
            </div>
        </div>
    </div>

    <div class="btn_fixed">
        <input type="button" class="btn btn-primary btn_msg" value="{pigcms{:L_("确认")}">
        &nbsp;&nbsp;&nbsp;&nbsp;
        <input type="reset" value="{pigcms{:L_("取消")}" class="button btn_qx" />
    </div>
<!--    <div class="bin_info">-->
<!--        <button class="btn" id="dosubmit">{pigcms{:L_("确认")}</button>-->
<!--        <input type="reset" value="{pigcms{:L_("取消")}" class="button" />-->
<!--    </div>-->
    <script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
    <script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
    <script type="text/javascript" src="./static/layer/layer.js"></script>
    <style type="text/css">
        .tag_box .tag{
            display: inline-block;
            padding: 2px 2px;
            margin: 0px 5px 5px 0;
            border: 1px solid #ccc;
        }
        .tag_box .tag .del{
            display: inline-block;
            padding: 0 4px;
            color: #277fc1;
            font-weight: bold;
            cursor: pointer;
        }
    </style>
    <script type="text/javascript">

        $('.btn_msg').click(function(){
            if(!$('.tag_box').length || !$('.tag_box').find('.tag').length){
                layer.alert('{pigcms{:L_("请先选择白名单成员！")}');
                return false;
            }
            var mer_arr=[];
            $('.tag_box').find('.tag').each(function(){
                mer_arr.push($(this).data('mer_id'));
            });
            var mer_str = '';
            if (mer_arr) {
               mer_str=mer_arr.join(',');
            } else {
                layer.alert('{pigcms{:L_("请先选择白名单成员！")}');
                return false;
            }
            console.log('mer_arr', mer_arr);
            console.log('mer_str', mer_str);

            $.post("{pigcms{:U('Shop/merchant_white_list')}",{goods_id: "{pigcms{$goods_id}", white_list: mer_str},function(result){
                console.log('result', result);
                if(result.status == 1){
                    layer.alert(result.info);
                }else{
                    layer.alert(result.info);
                }
            })

        });

        function give_white_list(url) {
            var viewportHeight = (window.screen.height / 1.5) - 100;
            // parent.layer.open({
            //     title:false,
            //     closeBtn: 0,
            //     type:2,
            //     content:url,
            //     area:['600px','700px'],
            //     shade: 0.2,
            //     cancel:function(){
            //         var json=[];
            //         $('.tag_box').find('.tag').each(function(){
            //             json.push($('.capture_merchant_info_tip'));
            //         });
            //         json.sort(function(b,a){
            //             if(a.data('k')>b.data('k')){
            //                 return -1;
            //             }else{
            //                 return 1;
            //             }
            //         });
            //         $('.tag_box').empty();
            //         $.each(json,function(n,v){
            //             $('.tag_box').append(v);
            //         })
            //     },
            //     move:false
            // });
            var obj=$('.capture_merchant_info_tip');
            art.dialog.data('dom',obj);
            art.dialog.open(url,{
                // init: function(){
                //     var iframe = this.iframe.contentWindow;
                //     window.top.art.dialog.data('iframe_handle',iframe);
                // },
                id: 'select_merchant',
                title:'{pigcms{:L_("选择商户")}',
                padding: 0,
                width: 650,
                height: viewportHeight,
                lock: true,
                resize: false,
                background:'black',
                button: null,
                fixed: false,
                close: null,
                left: '50%',
                top: '38.2%',
                opacity:'0.4',
                close:function(){
                    var json=[];
                    $('.tag_box').find('.tag').each(function(){
                        json.push($(this));
                    });
                    json.sort(function(b,a){
                        if(a.data('k')>b.data('k')){
                            return -1;
                        }else{
                            return 1;
                        }
                    });
                    $('.tag_box').empty();
                    $.each(json,function(n,v){
                        $('.tag_box').append(v);
                    })
                }
            });
        }

        $(function(){
            $('.js-modal iframe',parent.document).height($('body').height()+16);
            $('.modal-header .close').live('click',function(){
                artClose();
            });

            $('.btn_qx').live('click',function(){
                artClose();
            });
            $('#select_store').click(function(){
                var obj=$(this);
                art.dialog.data('dom',obj);
                art.dialog.open("{pigcms{:U('Shop/store_list')}",{
                    init: function(){
                        var iframe = this.iframe.contentWindow;
                        window.top.art.dialog.data('iframe_handle',iframe);
                    },
                    id: 'select_store',
                    title:'{pigcms{:L_("选择店铺")}',
                    padding: 0,
                    width: 600,
                    height: 500,
                    lock: true,
                    resize: false,
                    background:'black',
                    button: null,
                    fixed: false,
                    close: null,
                    left: '50%',
                    top: '38.2%',
                    opacity:'0.4',
                    close:function(){
                        var json=[];
                        $('.tag_box').find('.tag').each(function(){
                            json.push($(this));
                        });
                        json.sort(function(b,a){
                            if(a.data('k')>b.data('k')){
                                return -1;
                            }else{
                                return 1;
                            }
                        });
                        $('.tag_box').empty();
                        $.each(json,function(n,v){
                            $('.tag_box').append(v);
                        })
                    }
                });
            });
            $(document).on('click','.tag_box .tag .del',function(){
                $(this).parent().remove();
            });
        });
        function artClose(){
            if(parent.layer.getFrameIndex){
                var index = parent.layer.getFrameIndex(window.name); //"先得到当前iframe层的索引")}
                parent.layer.close(index); //"再执行关闭")}\
                return
            }
            parent.layer.close();
        }
    </script>
<?php if(empty($_GET['not_first'])){ ?>
    </body>
    </html>
<?php } ?>