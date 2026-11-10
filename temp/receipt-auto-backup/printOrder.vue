<template>
    <a-modal :title="title" :width="1300" :visible="visible" :footer="null" :maskClosable="false"
             :confirmLoading="confirmLoading" @cancel="handleCancel">
        <div class="print_container" v-if="printType == 4" :id="table_id">
            <div v-for="(item_list,index_list) in tableList">
                <span v-if="is_title" style="width: 100%; text-align: center;display: inline-block;font-size: 20px;font-weight: bold;">{{ print_title }}</span>
                <div class="receipt">
                    <div class="company-info" style="display: flex;justify-content: flex-end;"
                         v-if="printList3.findIndex(v=>v.title == '收款日期')!=-1">
                        <div class="text-nowrap" style="width: 200px;text-align: left;">
                            收款日期：{{ formatReceiptDate(printList3.find(v=>v.title == '收款日期').value) }}
                        </div>
                    </div>
                    <div class="info-item" v-if="printList1.findIndex(v=>['房号', '小区', '住户姓名'].includes(v.title))!=-1">
                        <div class="info-label">今收到</div>
                        <div class="info-content">
                          <span v-if="printList1.findIndex(v=>v.title=='小区')!=-1" >{{ (item = list1.find(i => i.title === '小区')) && item.value || '' }}&nbsp;&nbsp;</span>
                          <span v-if="printList1.findIndex(v=>v.title=='房号')!=-1" >{{ (item = list1.find(i => i.title === '房号')) && item.value || '' }}&nbsp;&nbsp;</span>
                          <span v-if="printList1.findIndex(v=>v.title=='车位号')!=-1" >{{ (item = list1.find(i => i.title === '车位号')) && item.value || '' }}&nbsp;&nbsp;</span>
                          <span v-if="printList1.findIndex(v=>v.title=='住户姓名')!=-1" >{{ (item = list1.find(i => i.title === '住户姓名')) && item.value || '' }}&nbsp;&nbsp;</span>
                        </div>
                    </div>
                    <div class="info-item" v-if="printList2.findIndex(v=>['收费项目', '起始日期', '终止日期'].includes(v.title))!=-1">
                        <div class="info-label">交来</div>
                        <div class="info-content">
                          <span v-if="printList2.findIndex(v=>v.title=='收费项目')!=-1">&nbsp;&nbsp;{{item_list[0]}}</span>
                          （
                          <span v-if="printList2.findIndex(v=>v.title=='起始日期')!=-1">{{item_list[1]}}</span>
                          -
                          <span v-if="printList2.findIndex(v=>v.title=='终止日期')!=-1">{{item_list[2]}}</span>
                           ）&nbsp;&nbsp;
                        </div>
                    </div>
                    <div class="info-item" style="display:flex;justify-content: space-between;" v-if="printList2.findIndex(v=>v.title=='实收金额')!=-1">
                        <div style="display:flex;">
                            <div class="info-label">人民币(大写)</div>
                            <div class="text-underline">&nbsp;&nbsp;{{numberToChinese(item_list[3] || item_list[0])}}&nbsp;&nbsp;</div>
                        </div>
                        <div style="display:flex;width: 200px;">
                            <div class="">小写：</div>
                            <div class="text-underline">&nbsp;&nbsp;¥{{item_list[3] || item_list[0]}}&nbsp;&nbsp;</div>
                        </div>
                    </div>
                    <div class="handler" style="display: flex;justify-content: flex-end;" v-if="printList3.findIndex(v=>['经手人'].includes(v.title))!=-1">
                        <div class="hand-person text-nowrap" style="width: 200px;text-align: left;">
                            经手人：{{ (name = current_userinfo.name.replace(/^【.*?】|【.*?】$/g, "")) && name || ''}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-else :id="table_id">
            <div v-for="(value_p, index_p) in tableList">
            <span v-if="is_title"
                  style="width: 100%; text-align: center;display: inline-block;font-size: 20px;font-weight: bold;">{{ print_title
                }}</span>

                <div v-if="printType!=3" class="header_show" style="width: 100%; display: flex; flex-wrap: wrap;">
                    <div v-if="item.title!='标题'" style="flex-shrink: 0; margin: 5px 0; word-break: break-word;"
                         :style="{width: item.title=='换行'?'100%':item.width?item.width: item.title.includes('房号') ? 50+'%' : 1/col_num*100+'%', margin:item.title=='换行'?'0':'2px 0'}"
                         class="page_header_item" v-for="(item, index) in list1" :key="index">
                        <div :style="font1style" style="margin-left: 10px;" v-if="item.title!='换行'">
                            {{ item.title
                            }}：{{ item.title.includes('房号') ? item.value.replace(/\d+层/, '') : item.value
                            }}
                        </div>
                    </div>
                </div>
                <a-descriptions v-else style="padding-top: 10px" :column="col_num"
                                :class="'descriptions-header template'+printType+'type'">
                    <a-descriptions-item v-for="(item,index) in list1" :key="index+30" v-if="item.title !== '换行'"
                                         style="white-space: nowrap;">
                        <template v-slot:label>
                        <span :style="font1style">
                            {{ item.title }}{{ item.title.trim().length === 0 ? '' : ':' }}
                        </span>
                        </template>
                        <span
                            :style="font1style">{{ item.title.includes('房号') ? item.value.replace(/\d+层/, '') : item.value
                            }}</span>
                    </a-descriptions-item>
                </a-descriptions>
                <!-- <div>
            <a-table
              bordered
              :columns="columns"
              :data-source="data"
              :pagination="false"
              :loading="confirmLoading">
            </a-table>
          </div> -->

                <div class="module_two" style="display: flex; flex-direction: column; position: relative;">
                    <div v-if="printType*1 == 2 || printType*1 == 3" class="table_top" v-show="printList4.length>0"
                         style="border: 0.5px solid #999999; display: flex;
          align-items: center;justify-content: space-between;width: 100%;height: 27px;">
                        <div class="header_item"
                             style="display: flex; align-items: center; justify-content: space-between;"
                             v-for="(item, index) in printList4" :key="index"
                             :style="{marginLeft: index==0?'10px':'', marginRight:index==printList4.length-1?'20px': ''}">
                            <span :style="font4style">{{ item.title }}：{{ item.value }}</span>
                        </div>
                    </div>

                    <div class="table_container"
                         style="display: flex; align-items: center; width: 100%; border-left: 0.5px solid #999999;">
                        <div class="table_item" style="border: 0.5px solid #999999; border-left: 0; height: 27px; display: flex; align-items: center;
              justify-content: center; font-weight: bold;word-break: break-all;"
                             :style="{width:(1/printList2.length)*100+'%' }"
                             v-for="(item, index) in printList2" :key="index">
                            <span :style="font2style">{{ item.title }}</span>
                        </div>
                    </div>
                    <div class="table_container"
                         style="display: flex; align-items: center; width: 100%; border-left: 0.5px solid #999999;border-right: 0.5px solid #999999;">
                        <div class="table_item" style="border: none; height: 27px; display: flex; align-items: center;
               justify-content: center;font-size: 12px;word-break: break-all;"
                             :style="{width:(1/printList2.length)*100+'%' }"
                             v-for="(item, index) in value_p" :key="index+index_p"><span :style="font2style">{{ item
                            }}</span></div>
                    </div>

                    <div class="table_container"
                         style="display: flex; align-items: center; width: 100%; border-left: 0.5px solid #999999;"
                         v-if="blankline>0" v-for="index of blankline" :key="index+'_blankline'">
                        <div class="table_item" style="border: 0.5px solid #999999; border-left: 0; height: 27px; display: flex; align-items: center;
                justify-content: center;font-size: 12px" :style="{width:(1/printList2.length)*100+'%' }"
                             v-for="(item, mcidx) in printList2" :key="mcidx+'_2blankline'"><span></span></div>
                    </div>

                    <div class="table_footer_two print_list7"
                         style="display: flex; align-items: center; justify-content: center; width: 100%; border-left: 0.5px solid #999999;"
                         v-if="printType*1 == 3" v-for="(item, index) in printList7">
                        <div class="left_title"
                             style="height: 27px; width: 25%; border: 0.5px solid #999999; border-left: 0; display: flex; align-items: center; padding-left: 10px;">
                            <span :style="font7style">{{ item.title }}：</span></div>

                        <div class="right_71" :style="font7style"
                             style="height: 27px;width: 12.5%; border: 0.5px solid #999999; border-left: 0; display: flex; align-items: center; padding-left: 10px;">
                            人民币大写
                        </div>
                        <div class="right_72" :style="font7style"
                             style="height: 27px;width: 40.4%; border: 0.5px solid #999999; border-left: 0; display: flex; align-items: center; padding-left: 10px;">
                            {{ item.value.value2 ? item.value.value1 : '' }}
                        </div>
                        <div class="right_73" :style="font7style"
                             style="height: 27px;width: 22%; border: 0.5px solid #999999; border-left: 0; display: flex; align-items: center; padding-left: 10px;">
                            {{ item.value.value2 ? item.value.value2 : '' }}
                        </div>

                    </div>
                    <div v-if="printType*1 == 2 || printType*1 == 3" v-for="(mc,imc) in active5List"
                         class="table_footer_one print_list5"
                         style="display: flex; align-items: center; justify-content: center; width: 100%; border-left: 0.5px solid #999999;">
                        <div class="table_item"
                             style="border: 0.5px solid #999999; border-right: 0.5px solid #999999 !important; border-left: 0; height: 27px; display: flex; align-items: center; padding-left: 10px;"
                             :style="{width:(1/mc.length)*100+'%' }" v-for="(item, index) in mc">
                            <div :style="font5style" style="width: 100%;height: 100%; display: flex;"
                                 v-if="printType==3">
                            <span
                                style="border-right: 0.5px solid #999999;height: 27px;display: flex;width: 50%;">{{ item.title
                                }}</span><span
                                style="height: 27px;display: flex;width: 50%;margin-left: 10px;">{{ item.value }}</span>
                            </div>
                            <span v-else>{{ item.title }}：{{ item.value }}</span>
                        </div>
                    </div>

                    <div v-if="printType*1 == 2 || printType*1 == 3" class="table_footer_two print_list6"
                         style="display: flex; align-items: center; justify-content: center; width: 100%; border-left: 0.5px solid #999999;"
                         v-for="(item, index) in printList6" :key="index">
                        <div class="left_title" style="height: 27px; width: 25%; border: 0.5px solid #999999; border-left: 0; display: flex; align-items: center;
              padding-left: 10px;"><span :style="font6style">{{ item.title }}：</span></div>
                        <div class="right_content" style="height: 27px; width: 75%; border: 0.5px solid #999999; border-left: 0; display: flex; align-items: center;
              justify-content: space-between; padding-left: 10px;">
                            <div v-if="item.value.value1"
                                 style="width: 100%; display: flex; align-items: center; justify-content: space-between;">
                                <span :style="font6style">{{ item.value.value1 }}</span>
                                <span :style="font6style" style="margin-right: 20px;">{{ item.value.value2 }}</span>
                            </div>
                            <span :style="font6style" v-else>{{ item.value }}</span>
                        </div>
                    </div>
                    <div v-if="seal_img && seal_img.length>10" style="position: absolute;bottom: -95px;left: 55%;">
                        <img :src="seal_img" style="width: 220px;">
                    </div>
                </div>
                <div v-if="printType*1 == 3 && print_desc.length>0"
                     style="margin: 10px;text-align: center;font-size: 16px;font-weight: bold;color: #000;">
                    <span>{{ print_desc }}</span> <span style="margin-left: 20px;">{{ print_time_str }}</span>
                </div>
                <div v-if="not_house_rate_desc.length>0"
                     style="margin: 10px;text-align: center;font-size: 16px;font-weight: bold;color: #000;">
                    <span>{{ not_house_rate_desc }}</span>
                </div>
                <div v-if="printType!=3" class="header_show" style="width: 100%; display: flex; flex-wrap: wrap;">
                    <div v-if="item.title!='标题'" style="flex-shrink: 0; margin: 5px 0; word-break: break-word;"
                         :style="{width: item.title=='换行'?'100%':item.width?item.width:1/ ['收款单位（盖章）'].includes( item.title ) ? 70+'%' : col_num*100+'%', margin:item.title=='换行'?'0':'5px 0'}"
                         class="page_header_item" v-for="(item, index) in list2" :key="index">
                        <div :style="{font1style}" style="margin-left: 10px;" v-if="item.title!='换行'">
                            {{ item.title }}{{ item.title.trim().length === 0 ? '' : ':' }}{{ item.value }}
                        </div>
                    </div>
                </div>
                <a-descriptions v-else style="margin-top: 10px" :column="col_num"
                                :class="'descriptions-footer template'+printType+'type'">
                    <a-descriptions-item v-for="(item1,index1) in list2" :key="index1" v-if="item1.title !== '换行'"
                                         style="white-space: nowrap;">
                        <template v-slot:label>
                        <span :style="font3style" v-if="item1.configure_id==78">
                            {{ item1.title }}
                        </span>
                            <span :style="font3style" v-else>
                            {{ item1.title }}{{ item1.title.trim().length === 0 ? '' : ':' }}
                        </span>
                        </template>
                        <span :style="font3style">{{ item1.value }}</span>
                    </a-descriptions-item>
                </a-descriptions>
            </div>
        </div>
        <span class="table-operator" style="text-align: center;display: inline-block;width: 100%" v-if="is_show">
            <span style="margin-right: 8px;">打印纸张：</span>
            <a-select v-model="printPaper" style="width: 180px; margin-right: 12px;">
                <a-select-option value="a4">A4（每页最多4笔）</a-select-option>
                <a-select-option value="small">小纸张（每页最多3笔）</a-select-option>
            </a-select>
            <a-button type="primary" :loading="printPreparing" @click="print">打印</a-button>
            <div style="margin-top: 8px; color: #888;">请与系统打印窗口的纸张设置保持一致；小纸张请选择每页3笔。</div>
        </span>
    </a-modal>
</template>

<style lang="less" scoped>
.imgname {
    width: 100px;
}

.ant-modal-body {
    padding: 24px;
    zoom: 1;
}

.ant-table table {
    width: 100%;
    text-align: left;
    border-radius: 2px 2px 0 0;
    border-collapse: separate;
    border-spacing: 0;
}

.descriptions-header {
    /deep/ .ant-descriptions-row {
        td {
            padding-bottom: 8px !important;
        }
    }
}

.descriptions-footer {
    /deep/ .ant-descriptions-item {
        &:nth-child(1) {
            text-align: right !important;
        }
    }

    //     /deep/ .ant-descriptions-row{
    //         display: flex;
    //         flex-direction: column;
    //     }
    //     /deep/ .ant-descriptions-item {
    //         &:nth-last-child(1) {
    //             text-align: left;
    //         }
    //         &:nth-child(1) {
    //             text-align: left;
    //         }
    //     }
}

.template3type {
    text-align: center;

    /deep/ .ant-descriptions-item {
        &:nth-last-child(1) {
            text-align: right;
        }

        &:nth-child(1) {
            text-align: left;
        }
    }
}

/deep/ .ant-descriptions-item-colon::after {
    content: ''
}

.module_two {
    display: flex;
    flex-direction: column;

    .table_top {
        border: 0.5px solid #999999;
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        height: 27px;

        .header_item {
            display: flex;
            align-items: center;
            justify-content: space-between;

            &:nth-child(1) {
                margin-left: 10px;
            }

            &:nth-last-child(1) {
                margin-right: 20px;
            }
        }
    }

    .table_container {
        display: flex;
        align-items: center;
        width: 100%;
        border-left: 0.5px solid #999999;

        .table_item {
            border: 0.5px solid #999999;
            border-left: 0;
            height: 27px;
            display: flex;
            align-items: center;
            justify-content: center;

            // font-weight: bold;
            &:nth-child(1) {
                // border-left: 0.5px solid #999999;
            }
        }
    }

    .table_footer_one {
        display: flex;
        align-items: center;
        justify-content: center;
        // border-left: 0.5px solid #999999;
        border-right: 0.5px solid #999999;
        width: 100%;

        .table_item {
            // border: 0.5px solid #999999;
            border: none !important;
            // border-right: 0.5px solid #999999;
            border-left: 0;
            height: 27px;
            display: flex;
            align-items: center;
            padding-left: 10px;
            // &:nth-child(1){
            //     border-right: 0.5px solid #999999;
            // }
        }
    }

    .table_footer_two {
        display: flex;
        align-items: center;
        justify-content: center;
        border-left: 0.5px solid #999999;
        width: 100%;

        .left_title {
            height: 27px;
            width: 25%;
            border: 0.5px solid #999999;
            border-left: 0;
            display: flex;
            align-items: center;
            padding-left: 10px;
        }

        .right_content {
            height: 27px;
            width: 75%;
            border: 0.5px solid #999999;
            border-left: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-left: 10px;
        }
    }
}

.print_container {
    .receipt {
        width: 90%;
        margin: 0 auto;
        padding: 20px;
        background-color: #fff;
    }

    .title {
        text-align: center;
        font-size: 20px;
        font-weight: bold;
        margin-bottom: 20px;
    }

    .company-info {
        margin-bottom: 15px;
    }

    .company-name {
        font-size: 18px;
        font-weight: bold;
        text-align: center;
    }

    .invoice-date {
        text-align: right;
        margin-top: 5px;
    }

    /* 核心修改：用浮动替代flex */

    .info-item {
        margin-bottom: 20px;
        overflow: hidden; /* 清除浮动 */
        zoom: 1; /* 兼容IE旧版本 */
    }

    .info-label {
        width: 120px;
        font-weight: bold;
        float: left; /* 左侧浮动 */

        .amount-small {
            float: left;
            text-align: right;
            font-size: 16px;
        }
    }

    .info-content {
        //margin-left: 130px; /* 避开label宽度，模拟flex的剩余空间 */
        text-decoration-line: underline;
    }

    .amount-capital {
        font-weight: bold;
    }

    .amount-small {
        text-align: right;
        font-size: 16px;
    }
}
</style>

<script>
import villageApi from '@/api/community/village'
import { numberToChineseCapital } from '@/utils/util'
import printJS from 'print-js'
import { createReceiptPrintDocument, createReceiptPrintStyle } from '@/utils/receiptPrintLayout'

const columns = []
const data = []
export default {
    components: {},
    data() {
        return {
            title: '打印预览',
            printPaper: 'a4',
            printPreparing: false,
            list1: [],
            list2: [],
            print_title: '',
            is_show: true,
            labelCol: {
                xs: {
                    span: 24
                },
                sm: {
                    span: 7
                }
            },
            wrapperCol: {
                xs: {
                    span: 24
                },
                sm: {
                    span: 13
                }
            },
            confirmLoading: false,
            form: this.$form.createForm(this),
            visible: false,
            is_title: false,
            data,
            columns,
            order_id: 0,
            template_id: 0,
            choice_ids: [],
            pigcms_id: 0,
            id: 0,
            col_num: 3,
            tableList: [],
            printList1: [],
            printList2: [],
            printList3: [],
            printList4: [],
            printList5: [],
            printList6: [],
            printList7: [],
            printType: 1,
            active5List: [],
            printRecordStatus: true,
            table_id: '',
            font_set: {
                'font1': {
                    'print_type': 1,
                    'size': '',
                    'weight': '',
                    'style': '',
                    'textdecoration': ''
                },
                'font2': {
                    'print_type': 2,
                    'size': '',
                    'weight': '',
                    'style': '',
                    'textdecoration': ''
                },
                'font3': {
                    'print_type': 3,
                    'size': '',
                    'weight': '',
                    'style': '',
                    'textdecoration': ''
                },
                'font4': {
                    'print_type': 4,
                    'size': '',
                    'weight': '',
                    'style': '',
                    'textdecoration': ''
                },
                'font5': {
                    'print_type': 5,
                    'size': '',
                    'weight': '',
                    'style': '',
                    'textdecoration': ''
                },
                'font6': {
                    'print_type': 6,
                    'size': '',
                    'weight': '',
                    'style': '',
                    'textdecoration': ''
                },
                'font7': {
                    'print_type': 7,
                    'size': '',
                    'weight': '',
                    'style': '',
                    'textdecoration': ''
                }
            },
            font1style: '',
            font2style: '',
            font3style: '',
            font4style: '',
            font5style: '',
            font6style: '',
            font7style: '',
            print_desc: '',
            print_time_str: '',
            blankline: 0,
            not_house_rate_desc: '',
            seal_img: '',
            current_userinfo: this.$store.state.user.info
        }
    },
    methods: {
        //中文金额大写转换
        numberToChinese(price){
            return numberToChineseCapital(price)
        },

        formatReceiptDate(value) {
            if (!value) {
                return ''
            }
            return String(value).split(' ')[0]
        },

        getReduceRes(list) {
            let result = list.reduce((pre, current, index, arr) => {
                if (index == 0 && current.title == '换行') {
                    return this.col_num - 1
                }
                if (index == 0 && current.title == '标题') {
                    return -1
                }
                if (index == 0) {
                    return 0
                }
                if (current.title == '换行' && (pre + 2) % this.col_num == 0) {
                    return pre + 1
                }
                if (current.title == '换行' && (pre + 2) % this.col_num != 0) {
                    return pre + this.col_num - (pre + 1 + 1) % this.col_num + 1
                }
                if (current.title == '标题') {
                    return pre
                }
                if (current.title != '换行' && current.title != '标题') {
                    return pre + 1
                }
            }, 0)
            return result
        },

        pageHeaderMap() {
            let mapArr = []
            this.list1.map(v => {
                mapArr.push(v)
                v.realIndex = this.getReduceRes(mapArr)
            })

            this.list1.map((v, i) => {
                if (v.title == '换行') {
                    let width = ''
                    if ((this.list1[i - 1]['realIndex'] + 1) % this.col_num == 0) {
                        width = (1 - (this.list1[i - 1]['realIndex'] + 1) % this.col_num) / this.col_num * 100 + '%'
                    } else {
                        width = (this.col_num + 1 - (this.list1[i - 1]['realIndex'] + 1) % this.col_num) / this.col_num * 100 + '%'
                    }
                    this.list1[i - 1]['width'] = width
                }
            })

            console.log('this.list1===>', this.list1)
        },

        pageFooterMap() {
            let mapArr = []
            this.list2.map(v => {
                mapArr.push(v)
                v.realIndex = this.getReduceRes(mapArr)
            })

            this.list2.map((v, i) => {
                let iilen = i + 1

                if (v.title == '打印日期' && v.value) {
                    const [datePart, timePart] = v.value.split(' ')
                    v.value = datePart
                    v.title = '开票日期'
                }

                if (v.title == '换行') {
                    let width = ''
                    if ((this.list2[i - 1]['realIndex'] + 1) % this.col_num == 0) {
                        width = (1 - (this.list2[i - 1]['realIndex'] + 1) % this.col_num) / this.col_num * 100 + '%'
                    } else {
                        width = (this.col_num + 1 - (this.list2[i - 1]['realIndex'] + 1) % this.col_num) / this.col_num * 100 + '%'
                    }
                    this.list2[i - 1]['width'] = width
                } else if (v.field_name == 'desc') {
                    if (this.list2.length == 1) {
                        this.list2[0]['width'] = '100%'
                    } else if (i / this.col_num == 1 && iilen == this.list2.length) {
                        this.list2[i]['width'] = '100%'
                    }
                }
            })
            console.log('this.list2===>', this.list2)
        },

        add(order_id, template_id, pigcms_id = 0, choice_ids = []) {
            this.table_id = 'print_' + parseInt(Math.random() * (999 - 100 + 1) + 999) + '_' + (new Date())
                .getTime()
            this.title = '打印预览'
            this.visible = true
            this.is_title = false
            this.is_show = true
            this.printRecordStatus = true
            this.order_id = order_id
            this.template_id = template_id
            this.pigcms_id = pigcms_id
            this.choice_ids = choice_ids
            this.data = []
            this.columns = []
            this.print_title = ''
            this.getPrintInfo()
        },
        getPrintInfo() {
            this.confirmLoading = true
            this.seal_img = ''
            this.request(villageApi.getPrintInfo, {
                order_id: this.order_id,
                template_id: this.template_id,
                pigcms_id: this.pigcms_id,
                choice_ids: this.choice_ids
            })
                .then((res) => {
                    this.confirmLoading = false
                    console.log('resx', res)
                    this.printList1 = res.printList1
                    this.printList2 = res.printList2
                    this.printList3 = res.printList3
                    this.printList4 = res.printList4
                    this.printList5 = res.printList5
                    this.printList6 = res.printList6
                    this.printList7 = res.printList7
                    this.tableList = res.tab_list
                    this.printType = res.type
                    this.print_title = res.print_title
                    this.col_num = res.col
                    this.is_title = res.is_title
                    this.list1 = res.printList1

                    this.data = res.data_order
                    this.list2 = res.printList3
                    this.pageHeaderMap()
                    this.pageFooterMap()
                    res.printList2.forEach(item3 => {
                        this.columns.push({
                            title: item3.title,
                            dataIndex: item3.field_name,
                            key: item3.field_name
                        })
                    })

                    const targetItem = res.printList3.find(item => item.title === '收款单位（盖章）')
                    if (targetItem) {
                        targetItem.value = res.print_title
                    }
                    this.font1style = ''
                    this.font2style = ''
                    this.font3style = ''
                    this.font4style = ''
                    this.font5style = ''
                    this.font6style = ''
                    this.font7style = ''
                    if (res.font_set != undefined && res.font_set) {
                        for (let i in res.font_set) {
                            this.font_set[i] = res.font_set[i]
                            this.handleFontStyle(this.font_set[i].print_type)
                        }
                    }
                    if (res.blankline != undefined && res.blankline > 0) {
                        this.blankline = res.blankline
                    }
                    if (res.not_house_rate_desc != undefined && res.not_house_rate_desc.length > 0) {
                        this.not_house_rate_desc = res.not_house_rate_desc
                    }

                    this.active5List = []
                    if (res.printList5 && res.printList5.length > 0) {
                        res.printList5.forEach((item, index) => {
                            let tmpcol = index / 3
                            let ii = Math.floor(tmpcol)
                            if (this.active5List[ii] != undefined) {
                                this.active5List[ii].push(item)
                            } else {
                                this.active5List[ii] = []
                                this.active5List[ii].push(item)
                            }
                        })
                    }
                    if (res.type == 3 && 0 == 1) {
                        if (res.prints_num == 0) {
                            this.print_desc = '第1次打印，打印人：' + res.print_name
                        } else {
                            this.print_desc = '第' + (res.prints_num + 1) + '次打印，请避免重复做账。补打人：' + res.print_name
                        }
                        this.print_time_str = res.print_time
                    } else {
                        this.print_time_str = ''
                        this.print_desc = ''
                    }
                    if (res.is_notpay_print != undefined && res.is_notpay_print == 1) {
                        this.print_time_str = ''
                        this.print_desc = ''
                    }
                    if (res.seal_img) {
                        this.seal_img = res.seal_img
                    }
                    console.log('active5List', this.active5List)
                    console.log('columns', this.columns)
                    setTimeout(() => {
                        this.print()
                    }, 800)
                }).catch((error) => {
                this.visible = false
            })
        },
        handleFontStyle(ii) {
            if (ii == 1) {
                let font1style = ''
                if (this.font_set.font1 != undefined) {
                    if (this.font_set.font1.size && this.font_set.font1.size > 0) {
                        font1style += 'font-size:' + this.font_set.font1.size + 'px;'
                    }
                    if (this.font_set.font1.weight && this.font_set.font1.weight.length > 0) {
                        font1style += 'font-weight:' + this.font_set.font1.weight + ';'
                    }
                    if (this.font_set.font1.style && this.font_set.font1.style.length > 0) {
                        font1style += 'font-style:' + this.font_set.font1.style + ';'
                    }
                    if (this.font_set.font1.textdecoration && this.font_set.font1.textdecoration.length > 0) {
                        font1style += 'text-decoration:' + this.font_set.font1.textdecoration + ';'
                    }
                    this.font1style = font1style
                }
            } else if (ii == 2) {
                let font2style = ''
                if (this.font_set.font2 != undefined) {
                    if (this.font_set.font2.size && this.font_set.font2.size > 0) {
                        font2style += 'font-size:' + this.font_set.font2.size + 'px;'
                    }
                    if (this.font_set.font2.weight && this.font_set.font2.weight.length > 0) {
                        font2style += 'font-weight:' + this.font_set.font2.weight + ';'
                    }
                    if (this.font_set.font2.style && this.font_set.font2.style.length > 0) {
                        font2style += 'font-style:' + this.font_set.font2.style + ';'
                    }
                    if (this.font_set.font2.textdecoration && this.font_set.font2.textdecoration.length > 0) {
                        font2style += 'text-decoration:' + this.font_set.font2.textdecoration + ';'
                    }
                    this.font2style = font2style
                }
            } else if (ii == 3) {
                let font3style = ''
                if (this.font_set.font3 != undefined) {
                    if (this.font_set.font3.size && this.font_set.font3.size > 0) {
                        font3style += 'font-size:' + this.font_set.font3.size + 'px;'
                    }
                    if (this.font_set.font3.weight && this.font_set.font3.weight.length > 0) {
                        font3style += 'font-weight:' + this.font_set.font3.weight + ';'
                    }
                    if (this.font_set.font3.style && this.font_set.font3.style.length > 0) {
                        font3style += 'font-style:' + this.font_set.font3.style + ';'
                    }
                    if (this.font_set.font3.textdecoration && this.font_set.font3.textdecoration.length > 0) {
                        font3style += 'text-decoration:' + this.font_set.font3.textdecoration + ';'
                    }

                }
                this.font3style = font3style
            } else if (ii == 4) {
                let font4style = ''
                if (this.font_set.font4 != undefined) {
                    if (this.font_set.font4.size && this.font_set.font4.size > 0) {
                        font4style += 'font-size:' + this.font_set.font4.size + 'px;'
                    }
                    if (this.font_set.font4.weight && this.font_set.font4.weight.length > 0) {
                        font4style += 'font-weight:' + this.font_set.font4.weight + ';'
                    }
                    if (this.font_set.font4.style && this.font_set.font4.style.length > 0) {
                        font4style += 'font-style:' + this.font_set.font4.style + ';'
                    }
                    if (this.font_set.font4.textdecoration && this.font_set.font4.textdecoration.length > 0) {
                        font4style += 'text-decoration:' + this.font_set.font4.textdecoration + ';'
                    }

                }
                this.font4style = font4style
            } else if (ii == 5) {
                let font5style = ''
                if (this.font_set.font5 != undefined) {
                    if (this.font_set.font5.size && this.font_set.font5.size > 0) {
                        font5style += 'font-size:' + this.font_set.font5.size + 'px;'
                    }
                    if (this.font_set.font5.weight && this.font_set.font5.weight.length > 0) {
                        font5style += 'font-weight:' + this.font_set.font5.weight + ';'
                    }
                    if (this.font_set.font5.style && this.font_set.font5.style.length > 0) {
                        font5style += 'font-style:' + this.font_set.font5.style + ';'
                    }
                    if (this.font_set.font5.textdecoration && this.font_set.font5.textdecoration.length > 0) {
                        font5style += 'text-decoration:' + this.font_set.font5.textdecoration + ';'
                    }
                }
                this.font5style = font5style
            } else if (ii == 6) {
                let font6style = ''
                if (this.font_set.font6 != undefined) {
                    if (this.font_set.font6.size && this.font_set.font6.size > 0) {
                        font6style += 'font-size:' + this.font_set.font6.size + 'px;'
                    }
                    if (this.font_set.font6.weight && this.font_set.font6.weight.length > 0) {
                        font6style += 'font-weight:' + this.font_set.font6.weight + ';'
                    }
                    if (this.font_set.font6.style && this.font_set.font6.style.length > 0) {
                        font6style += 'font-style:' + this.font_set.font6.style + ';'
                    }
                    if (this.font_set.font6.textdecoration && this.font_set.font6.textdecoration.length > 0) {
                        font6style += 'text-decoration:' + this.font_set.font6.textdecoration + ';'
                    }

                }
                this.font6style = font6style
            } else if (ii == 7) {
                let font7style = ''
                if (this.font_set.font7 != undefined) {
                    if (this.font_set.font7.size && this.font_set.font7.size > 0) {
                        font7style += 'font-size:' + this.font_set.font7.size + 'px;'
                    }
                    if (this.font_set.font7.weight && this.font_set.font7.weight.length > 0) {
                        font7style += 'font-weight:' + this.font_set.font7.weight + ';'
                    }
                    if (this.font_set.font7.style && this.font_set.font7.style.length > 0) {
                        font7style += 'font-style:' + this.font_set.font7.style + ';'
                    }
                    if (this.font_set.font7.textdecoration && this.font_set.font7.textdecoration.length > 0) {
                        font7style += 'text-decoration:' + this.font_set.font7.textdecoration + ';'
                    }

                }
                this.font7style = font7style
            }

        },
        async print() {
            if (this.confirmLoading || this.printPreparing) return
            this.printPreparing = true
            let printable
            try {
                await this.$nextTick()
                const root = document.getElementById(this.table_id)
                if (!root || !root.children.length) return
                printable = await createReceiptPrintDocument(root)
            } catch (error) {
                this.$message.error('打印内容准备失败，请重试')
                return
            } finally {
                this.printPreparing = false
            }
            printJS({
                printable,
                type: 'html',
                maxWidth: '100%',
                style: createReceiptPrintStyle(this.printPaper),
                scanStyles: false,
                onPrintDialogClose: () => {
                    console.log('回调================', this.printRecordStatus)
                    if (this.printRecordStatus) {
                        this.printRecordStatus = false
                        this.request(villageApi.printRecordUrl, {
                            order_id: this.order_id,
                            pigcms_id: this.pigcms_id,
                            choice_ids: this.choice_ids
                        }).then((res) => {
                            this.printRecordStatus = true
                        })
                    }
                }
            })
        },
        handleCancel() {
            this.visible = false
            this.printRecordStatus = true
            this.print_desc = ''
            setTimeout(() => {
                // this.post.id = 0;
                this.form = this.$form.createForm(this)
            }, 500)
        },
        jscolspan(arr1, num, arr2) {
            if (arr1 !== undefined) {
                // 下一条有值 且是换行 则占3格
                if (arr1.title === '换行') {
                    return this.col_num
                } else if (arr1.title !== '换行') {
                    return 1
                }
            } else {
                return 1
            }
        }
    }
}
</script>
