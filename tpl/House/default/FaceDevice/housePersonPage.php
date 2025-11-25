<include file="Public:header"/>
<div class="main-content">
    <div class="page-content">
        <div class="row">
            <div class="col-xs-12">
                <form  class="form-horizontal" method="post" onSubmit="return check_submit()" action="__SELF__">
                    <div class="tab-content">
                        <volist name="aboutInfo['txtBox']" id="vo">
                            <div class="form-group">
                                <label class="col-sm-4" style="padding-top: 4px;text-align: right;color:{pigcms{$vo['titleColor']}">
                                    <if condition="$vo['title']"><b>{pigcms{$vo['title']}</b>：</if>
                                </label>
                                <div class="col-sm-8" style="word-wrap: break-word; word-break: normal;padding-top: 6px;text-align: left;color:{pigcms{$vo['contentColor']}">
                                    {pigcms{$vo['content']}
                                    <if condition="$vo['is_btn'] && $vo['btnPage']">
                                        <a style="color: #2681f3!important;text-decoration:underline;" href="javascript:void(0);" onclick='bounced("{pigcms{$vo['btnPage']}")'>
                                        <b>{pigcms{$vo['btnTxt']}</b>
                                        </a>
                                    </if>
                                </div>
                                <if condition="$vo['img']">
                                    <img src="{pigcms{$vo['img']}">
                                </if>
                                <if condition="$vo['imgs']">
                                    <volist name="vo['imgs']" id="val">
                                        <img src="{pigcms{$val}" style="max-width: 640px;max-height: 480px;margin-right: 10px;">
                                    </volist>
                                </if>
                            </div>
                        </volist>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    function bounced(url) {
        var index = layer.open({
            type: 2,
            area: ['750px', '550px'],
            fixed: true, //不固定
            title: '人脸上传照片',
            content: url
        });
        layer.style(index, {
            top: '20px'
        });
    }
    $('.btnPagePath').click(function (){
        var pgicmsId = $(this).data('user_id');
        layer.open({
            content: '是否进行重新同步操作！',
            shadeClose: false,
            closeBtn: 0
            ,btn: ['确定', '取消']
            ,yes:function(index){
                layer.load(0, {shade: [0.6,'#fff']});
                console.log('确定',pgicmsId)
                $.ajax({
                    url:"{pigcms{:U('synDeviceUser')}",
                    type:"post",
                    data:{"pigcms_id":pgicmsId},
                    dataType: "Json",
                    success:function(d){
                        device_restart = false;
                        layer.closeAll();
                        if (d.errorCode>0) {
                            layer.msg(d.errorMsg)
                        } else {
                            layer.msg('同步命令已发送，等待执行。5秒后自动刷新页面')
                            setTimeout(function() {
                                window.location.reload();
                            },5000)
                        }
                    },
                });
            }
        });
    })
    $('.delPagePath').click(function (){
        var pgicmsId = $(this).data('user_id');
        console.log('pgicmsId',pgicmsId)
    })

    function sysDelUser(pigcms_id) {

    }
</script>

<include file="Public:footer"/>
