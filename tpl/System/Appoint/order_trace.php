<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title>服务轨迹</title>
    <meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no" />
    <link rel="stylesheet" href="/tpl/Wap/pure/static/css/vant@2.12.47.css" />
    <script src="/tpl/Wap/pure/static/js/vue@2.6.14.min.js" type="text/javascript" charset="utf-8"></script>
    <script src="/tpl/Wap/pure/static/js/vant@2.12.14.min.js" type="text/javascript" charset="utf-8"></script>
</head>

<body>
    <div id="app">
        <van-steps direction="vertical" v-if="order_trace.length > 0" :active="0">
            <van-step v-for="(item,index) in order_trace" :key="index">
                <div style="display: flex;justify-content: space-between;">
                    <h3>{{item.title}}</h3>
                    <p>{{item.create_time}}</p>
                </div>
                <div v-if="item.content">
                    <div v-for="(item1,index1) in item.content" :key="index1">
                        <div v-if="item1 && item1.type == 'image'">
                            <div>{{item1.title}}:</div>
                            <van-image v-for="(item2,index2) in item1.value" @click="imagePreview(item2)" :key="index2" width="100" height="100" :src="item2" style="margin-right: 5px"></van-image>
                        </div>
                        <div v-else style="margin-bottom: 5px;">
                            <span>{{item1.title}}:</span>
                            <span>{{item1.value}}</span>
                        </div>
                    </div>
                </div>
            </van-step>
        </van-steps>
        <p v-else>暂无记录</p>
    </div>
</body>
<script>
    new Vue({
        el: '#app',
        data: {
            order_trace: {pigcms{$order_trace}
        },
        created() {

        },
        methods: {
            imagePreview(item2) {
                vant.ImagePreview({
                    images: [item2],
                    startPosition: 0,
                    closeable: true,
                });
            }
        }
    })
</script>

</html>