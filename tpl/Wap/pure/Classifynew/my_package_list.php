<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport"
        content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name='apple-touch-fullscreen' content='yes' />
    <meta name="apple-mobile-web-app-status-bar-style" content="black" />
    <meta name="format-detection" content="telephone=no" />
    <meta name="format-detection" content="address=no" />
    <title>我的套餐</title>
    <link rel="stylesheet" href="{pigcms{$static_path}css/vant@2.12.47.css"/>
	<script src="{pigcms{$static_path}js/vue@2.6.14.min.js" type="text/javascript" charset="utf-8"></script>
	<script src="{pigcms{$static_path}js/vant@2.12.14.min.js" type="text/javascript" charset="utf-8"></script>
    <script src="{pigcms{$static_path}classifynew/js/jquery-2.1.4.js" type="text/javascript"></script>
    <script src="{pigcms{$static_path}js/common.js?t={pigcms{:time()}" type="text/javascript"></script>
</head>
<style>
    body{
        backGround-color: #f4f4f4;
    }
    .list {
        
    }
    .list .item{
        background-color: #fff;
        padding: 15px;
        border-radius: 10px;
        margin: 10px
    }
    .list .item .title{
        font-size: 17px;
        font-weight: bold;
        color: #1C1C1C;
        margin-bottom: 10px
    }
    .list .item .desc,.time{
        font-size: 14px;
        font-weight: 500;
        color: #666666;
        margin-bottom: 5px
    }
    .list .item .time{
        font-size: 12px;
        margin-bottom: 0
    }
</style>
<body>
    <div id="app">
        <van-tabs v-model="active" color="#ff4d00" @change="tabsChange">
            <van-tab :title="item.name" v-for="item in navList"></van-tab>
        </van-tabs>
        <van-list
            v-model="loading"
            :finished="finished"
            finished-text="没有更多了"
            @load="onLoad"
            >
            <div class="list">
            <div class="item" v-for="item in list" :key="item.id">
                <div class="title">{{item.menu_name}}</div>
                <div class="desc">{{item.name}}</div>
                <div class="time">有效期：{{item.expire_time}}</div>
            </div>
        </div>
        </van-list>
    </div>
</body>
<script>
    var _this = '';
    // 实例化vue
    var app = new Vue({
        el: '#app',
        data: {
            navList: [{
                name: '全部',
                value: 0,
            },{
                name: '进行中',
                value: 1,
            },{
                name: '已过期',
                value: 2,
            },],
            list: [],
            active: 0,
            loading: false,
            finished: false,
            formData: {
                page: 1,
                pageSize: 10,
                status: 0,
            },
        },
        created() {
            
        },
        methods: {
            tabsChange(){
                this.formData.status.page = 1;
                this.loading = true;
                this.finished = false;
                this.onLoad();
            },
            onLoad() {
                this.formData.status =  this.navList[this.active].value;
                v20Axios('/classify/api.ClassifyMenuOrder/orderList',this.formData,res=>{
                    this.list = res.data
                    //加载状态结束
                    this.loading = false;
                    if(this.formData.page < res.last_page){
                        this.formData.page++;
                    }else{
                        this.finished = true;
                    }
                })
            },
        }
    })
</script>

</html>