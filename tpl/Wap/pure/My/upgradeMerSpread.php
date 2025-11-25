<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title>升级推广商</title>
    <meta name="viewport"
          content="width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no" />
    <meta http-equiv="pragma" content="no-cache" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vant@2.12/lib/index.css" />
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6/dist/vue.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vant@2.12/lib/vant.min.js"></script>
    <script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
    <!-- <script src="https://cdn.bootcss.com/vConsole/3.3.4/vconsole.min.js"></script>
    <script>
        // 初始化
        var vConsole = new VConsole();
    </script> -->
</head>

<body>
<div id="app">
    <!-- 表单 -->
    <van-form @submit="onSubmit" validate-trigger="onSubmit" :show-error="false">
        <!-- 升级类型 -->
        <van-field readonly clickable name="upgrade_type" label="升级类型" :value="upgradeData.text"
                   :right-icon=" showArrow ? 'arrow-down' : ''" placeholder="请选择升级类型" @click="selectUpgradeType()">
        </van-field>
        <div class="tips">每邀请一个推广商升级后即可获得最高
            <span :style="{color: mainColor, margin: '0 5px'}">{{ currency +upgradeData.commission}}</span>佣金哦~
        </div>
        <div class="line"></div>
        <!-- 自定义部分 -->
        <template v-if="formList.length">
            <template v-for="(formItem,index) of formList" :key="formItem.name">
                <!-- 单行输入框  input-->
                <van-field :required="formItem.required" v-if="formItem.type == 'input'" v-model="formItem.value"
                           :name="formItem.name" :label="formItem.label" :placeholder="formItem.tips"
                           @input="setFormValue($event, index)"
                           :rules="[{ required: formItem.required, message: formItem.tips }]">
                </van-field>
                <!-- 身份证号  idcard-->
                <van-field :required="formItem.required" v-if="formItem.type == 'idcard'" v-model="formItem.value"
                           :name="formItem.name" :label="formItem.label" :placeholder="formItem.tips"
                           @input="setFormValue($event, index)" :error-message="idMessage" maxlength="18"
                           :formatter="idFormatter" format-trigger="onBlur"
                           :rules="[{ required: formItem.required, message: formItem.tips }]">
                </van-field>
                <!-- 多行输入框  textarea-->
                <van-field :required="formItem.required" v-if="formItem.type == 'textarea'" v-model="formItem.value"
                           type="textarea" autosize rows="3" :name="formItem.name" :label="formItem.label"
                           :placeholder="formItem.tips" @input="setFormValue($event, index)"
                           :rules="[{ required: formItem.required, message: formItem.tips }]">
                </van-field>
                <!-- 日期选择 date -->
                <van-field :required="formItem.required" v-if="formItem.type == 'date'" v-model="formItem.value"
                           :name="formItem.name" :label="formItem.label" :placeholder="formItem.tips" readonly clickable
                           @click="showPicker(index,'showDatePicker')" right-icon="arrow-down"
                           :rules="[{ required: formItem.required, message: formItem.tips }]">
                </van-field>
                <!-- 选择器 select -->
                <van-field :required="formItem.required" v-if="formItem.type == 'select'"
                           v-model="formItem.valueTxt" :name="formItem.name" :label="formItem.label"
                           right-icon="arrow-down" :placeholder="formItem.tips" readonly clickable
                           @click="showPicker(index,'showSelectPicker')"
                           :rules="[{ required: formItem.required, message: formItem.tips }]">
                </van-field>
                <!-- 图片上传 image -->
                <van-field :required="formItem.required" v-if="formItem.type == 'image'" :name="formItem.name" readonly
                           :label="formItem.label" :rules="[{ required: formItem.required, message: formItem.tips }]">
                    <template #input>
                        <van-uploader v-model="formItem.value" multiple :max-count="formItem.max_count"
                                      :after-read="(file,detail) => uploadImg(file,detail,index)"
                                      @delete="(file,detail) => deleteImg(file,detail,index)" :preview-full-image="false">
                        </van-uploader>

                    </template>
                </van-field>
                <!-- 身份证正反面 idcard_image -->
                <template v-if="formItem.type == 'idcard_image'">
                    <van-field  v-model="formItem.value" :required="formItem.required" :name="formItem.name" :label="formItem.label"  readonly
                               :rules="[{ required: formItem.required, message: formItem.tips }]">
                    </van-field>
                    <!-- 正面 -->
                    <div class="ic-img">
                        <van-uploader :after-read="(file,detail) => uploadIdImg(file,detail,index, 'front')"
                                      :max-count="1">
                            <div class="upload" :style="'width:'+ imgBoxWidth +';height:'+ imgBoxWidth">
                                <div class="img-con box-corner ">
                                    <div class="img-box">
                                        <img v-if="formItem.front" key="front_c" class="id-img"
                                             :src="formItem.front" />
                                        <img v-else class="up-img" key="front_i"
                                             src="./static/auth/auth_camera_icon.png" />
                                    </div>
                                </div>
                                <div class="upload-tip"><span class="color-orange">点击</span>上传身份证<span
                                            class="color-orange">正面</span>照片</div>
                            </div>
                        </van-uploader>
                        <div class="upload" :style="'width:'+ imgBoxWidth +';height:'+ imgBoxWidth">
                            <div class="img-con">
                                <div class="img-box">
                                    <img class="eg-img" src="./static/auth/auth_face_icon.png" />
                                </div>
                            </div>
                            <div class="upload-tip">正确示例：身份证正面</div>
                        </div>
                    </div>
                    <!-- 反面 -->
                    <div class="ic-img" style="margin-top: 10px;">
                        <van-uploader :after-read="(file,detail) => uploadIdImg(file,detail,index,'back')"
                                      :max-count="1">
                            <div class="upload" :style="'width:'+ imgBoxWidth +';height:'+ imgBoxWidth">
                                <div class="img-con box-corner ">
                                    <div class="img-box">
                                        <img v-if="formItem.back" key="back_c" class="id-img"
                                             :src="formItem.back" />
                                        <img v-else class="up-img" key="back_i"
                                             src="./static/auth/auth_camera_icon.png" />

                                    </div>
                                </div>
                                <div class="upload-tip"><span class="color-orange">点击</span>上传身份证<span
                                            class="color-orange">背面</span>照片
                                </div>

                            </div>
                        </van-uploader>
                        <div class="upload" :style="'width:'+ imgBoxWidth +';height:'+ imgBoxWidth">
                            <div class="img-con">
                                <div class="img-box">
                                    <img class="eg-img" src="./static/auth/auth_back_icon.png" />
                                </div>
                            </div>
                            <div class="upload-tip">正确示例：身份证背面</div>
                        </div>
                    </div>
                </template>
            </template>
        </template>
        <div class="submit">
            <van-button block :color="mainColor" native-type="submit">去支付（{{ upgradeData.price}}）</van-button>
        </div>
    </van-form>
    <!-- upgrade选择弹框 -->
    <van-popup v-model="showUpgradePicker" round position="bottom">
        <van-picker show-toolbar :columns="ajaxData.upgrade_list" @cancel="showUpgradePicker = false"
                    @confirm="onUpgradeConfirm"></van-picker>
    </van-popup>
    <!-- 时间选择框 -->
    <van-popup v-model="showDatePicker" position="bottom">
        <van-datetime-picker v-model="date" type="date" @confirm="onDateConfirm" @cancel="showDatePicker = false" />
    </van-popup>
    <!-- 选择器弹框 -->
    <van-popup v-model="showSelectPicker" position="bottom">
        <van-picker show-toolbar :columns="columns" @confirm="onSelectConfirm" @cancel="showSelectPicker = false" />
    </van-popup>
</div>

</body>
<script>
    var _this = null;
    var currentDate = new Date()
    var app = new Vue({
        el: '#app',
        data: {
            // 升级类型选择值
            upgradeData: {
                value: '{pigcms{$spreadTypes[0]["value"]}',
                text: '{pigcms{$spreadTypes[0]["text"]}',
                commission: '{pigcms{$spreadTypes[0]["commission"]}',
                price: '{pigcms{$spreadTypes[0]["price"]}',
            },
            // 是否显示升级类型选择弹框
            showUpgradePicker: false,
            // 从后端获取的数据
            ajaxData: {},
            mainColor: '',
            currency: '{pigcms{:C("config.Currency_symbol")}',
            formList: [],
            currentIndex: -1,
            // 显示时间选择框
            showDatePicker: false,
            date: currentDate,
            // 显示选择器弹框
            showSelectPicker: false,
            columns: [],
            // 身份证号
            idPattern: /^[1-9]\d{5}(18|19|20|(3\d))\d{2}((0[1-9])|(1[0-2]))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$/,
            idMessage: '',
            idCorrect: true,
            imgBoxWidth: '100px',
            showArrow: true
        },
        created() {
            _this = this;
            this.imgBoxWidth = (document.body.clientWidth - 50) / 2 + "px"
            this.getFormData()
        },
        methods: {
            selectUpgradeType() {
                if (this.ajaxData.upgrade_list.length > 1) {
                    this.showUpgradePicker = true;
                } else {
                    this.showUpgradePicker = false;
                }
            },
            getFormData() {
                // ajax请求
                var data = {
                    currency: '{pigcms{:C("config.Currency_symbol")}', // 货币符号
                    main_color: '#1FC5AE', // 主题色
                    // 升级类型列表
                    upgrade_list: JSON.parse('{pigcms{$spreadTypes|json_encode=###}'),
                    // 自定义表单数据
                    form_list: JSON.parse('{pigcms{$settings[0]["fields"]|json_encode=###}')
                }
                this.ajaxData = data;
                // 请求回调数据的处理
                this.currency = data.currency
                this.mainColor = data.main_color

                _this.transformFormData(data.form_list,data.upgrade_list)
            },
            // 提交表单
            onSubmit(values) {
                if (!this.idCorrect) {
                    return;
                }
                for (var i of this.formList) {
                    if (i.valueTxt) {
                        values[i.name] = i.value
                    }
                    if (i.type == 'idcard_image') {
                        values[i.name] = {
                            front: i.front,
                            back: i.back,
                        }
                    }
                }
                var post = {
                    type:this.upgradeData.value,
                    form_data:values
                }
                console.log('表单内所有的数据', post);
                $.post('{pigcms{:U("My/upgradeMerSpread")}',post, function (rs) {
                    if (rs.error_no == 0) {
                        location.href = rs.retval.pay_url;
                    } else {
                        alert(rs.error_msg);
                    }
                })
            },
            // 升级类型确认
            onUpgradeConfirm(val) {
                this.formList = [];
                this.showUpgradePicker = false
                this.upgradeData = val
                var that = this;
                $.get('{pigcms{:U("My/ajaxGetForm")}'+'&type='+val.value,function(rs){
                    var data = {
                        currency: '{pigcms{:C("config.Currency_symbol")}', // 货币符号
                        main_color: '#1FC5AE', // 主题色
                        // 升级类型列表
                        upgrade_list: JSON.parse('{pigcms{$spreadTypes|json_encode=###}'),
                        // 自定义表单数据
                        form_list: rs.settings.fields
                    }
                    _this.transformFormData(data.form_list,data.upgrade_list)
                })
            },
            transformFormData(list, upgrade_list){
                if (upgrade_list) {
                    if(upgrade_list.length == 1) {
                       this.showArrow = false 
                    }else {
                       this.showArrow = true  
                    }
                }
                if (list && list.length) {
                    this.formList = list.map(function (item) {
                        item.valueTxt = ''
                        item.tips = '请输入' + item.label
                        if (item.type == 'select' || item.type == 'date') {
                            item.tips = '请选择' + item.label
                            if (item.type == 'select' && item.list && item.list.length) {
                                item.list.map(function (i) {
                                    if (i.value == item.value) {
                                        item.valueTxt = i.text
                                    }
                                })
                            }
                        }   
                        if(item.type == 'image' || item.type == 'idcard_image'){
                            item.tips = '请上传' + item.label
                        }
                        return item
                    })
                }
            },
            // 选择日期
            onDateConfirm(date) {
                this.setFormValue(getTimeStrByDate(date), this.currentIndex)
                this.showDatePicker = false
                this.date = currentDate
            },
            onSelectConfirm(val) {
                this.setFormValue(val.value, this.currentIndex, val.text)
                this.showSelectPicker = false
                this.columns = []
            },
            // 显示选择弹框
            showPicker(index, type) {
                this.currentIndex = index
                this[type] = true
                var item = this.formList[index]
                if (item.list && item.list.length) {
                    this.columns = item.list
                }
            },
            // 设置表单值
            setFormValue(val, index, text = '') {
                var item = this.formList[index]
                item.value = val
                item.valueTxt = text
                this.$set(this.formList, index, item)
            },
            // 校验身份证号
            idFormatter(val) {
                if (!this.idPattern.test(val) && val != '') {
                    this.idMessage = "请输入正确的身份证号码~";
                    this.idCorrect = false;
                } else {
                    this.idMessage = ''
                    this.idCorrect = true
                }
                return val
            },
            // 上传图片
            uploadImg(file, detail, index) {
                if (file.length > 1) {
                //使用for循环来适配多选情况
                    for (let i in file) {
                        this.upimg(file[i],index,i)
                    }
                } else {
                    this.upimg(file,index,0)
                }
            },
            upimg(e,i,j) {
                vant.Toast.loading({
                    message: '正在上传...',
                    forbidClick: true,
                    duration: 0
                });
                var formData = new FormData();
                formData.append("file", e.file);
                $.ajax({
                    url:'{pigcms{:U("My/uploadPic")}'+'&picType=spread',
                    type:"POST",
                    data:　formData,
                    processData:false,   
                    contentType:false,   
                    success:function(data){
                        var item =  _this.formList[i]
                        if(j == 'front' || j == 'back') {//身份证正反面
                            item[j] = data.result.url
                            if(item.front && item.back){
                                item.value = '  '
                            }
                        }else {
                            var fileList = item.value
                            fileList[j] = data.result
                        }
                        _this.$set( _this.formList, i , item)
                        vant.Toast.clear();
                        vant.Toast('上传成功~')
                    },
                    error:function(data){
                        vant.Toast.clear();
                        vant.Toast.fail('上传失败!')
                    }
                });
            },
            deleteImg(file, detail, index) {
                var item = this.formList[index]
                this.$set(this.formList, index, item)
            },
            uploadIdImg(file, detail, index, type) {
                this.upimg(file,index,type)
            }
        }
    })
    function getTimeStrByDate(date) {
        var y = date.getFullYear();
        var M = date.getMonth() + 1;
        var d = date.getDate();
        return y + '-' + (M < 10 ? ('0' + M) : M) + '-' + (d < 10 ? ('0' + d) : d);
    }
</script>
<style>
    html,
    body,
    #app {
        width: 100%;
        height: 100%;
        background-color: #ffffff;
    }

    .tips {
        padding: 0px 15px;
        line-height: 40px;
        vertical-align: middle;
        font-size: 12px;
        color: #666666;
        /* margin-bottom: 10px; */
    }

    .ic-img {
        width: 100%;
        padding: 0 15px;
        box-sizing: border-box;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .ic-img .upload {
        padding: 15px 10px;
        box-sizing: border-box;
        background-color: #FD874510;

    }

    .ic-img .upload .img-con {
        width: 90%;
        height: 75%;
        margin: auto;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .box-corner {
        background: linear-gradient(to left, #FD8745, #FD8745) left top no-repeat,
        linear-gradient(to bottom, #FD8745, #FD8745) left top no-repeat,
        linear-gradient(to left, #FD8745, #FD8745) right top no-repeat,
        linear-gradient(to bottom, #FD8745, #FD8745) right top no-repeat,
        linear-gradient(to left, #FD8745, #FD8745) left bottom no-repeat,
        linear-gradient(to bottom, #FD8745, #FD8745) left bottom no-repeat,
        linear-gradient(to left, #FD8745, #FD8745) right bottom no-repeat,
        linear-gradient(to left, #FD8745, #FD8745) right bottom no-repeat;
        background-size: 2px 10px, 10px 2px, 2px 10px, 10px 2px;
    }

    .ic-img .upload .img-con .img-box {
        width: 80%;
        height: 70%;
        background-color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .ic-img .upload .img-con .img-box .up-img {
        width: 35px;
        height: 27px;
    }

    .ic-img .upload .img-con .img-box .id-img {
        width: 100%;
        height: 100%;
    }

    .ic-img .upload .img-con .img-box .eg-img {
        width: 80%;
        height: 70%;
    }

    .upload-tip {
        font-size: 12px;
        color: #666666;
        width: 100%;
        text-align: center;
        height: 25%;
        display: flex;
        align-items: flex-end;
        justify-content: center;
    }

    .color-orange {
        color: #FD8745;
        font-weight: bold;
    }

    .submit {
        margin: 0 20px;
        padding: 40px 0;
    }

    .line {
        width: 100%;
        height: 10px;
        background-color: #f4f4f4;
    }
</style>

</html>
