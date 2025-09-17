<template>
  <div>
    <a-card :body-style="{ padding: '24px 32px' }" :bordered="false">
      <a-tabs :default-active-key="0" @change="firstTabChange">
        <a-tab-pane :key="index" :tab="item.gname" v-for="(item, index) of groupList">
          <a-tabs type="card" @change="secondTabChange">
            <a-tab-pane :key="itemTab.tab_id" :tab="itemTab.name" v-for="itemTab of configTab">
              <a-form @submit="handleSubmit" :form="form">
                <div v-for="itemv of itemTab.list" :key="itemv.name">
                  <a-form-item
                    v-if="itemv.name == 'merchant_recharge_agreement'"
                    :label="itemv.info"
                    :labelCol="labelCol"
                    :wrapperCol="wrapperCol"
                  >
                    <rich-text :info.sync="itemv.value" />
                  </a-form-item>
                  <a-form-item
                    v-else-if="itemv.name == 'mobile_head_color'"
                    :label="itemv.info"
                    :labelCol="labelCol"
                    :wrapperCol="wrapperCol"
                  >
                    <color-picker :color.sync="itemv.value" />
                  </a-form-item>
                  <form-item
                    v-else
                    :labelCol="labelCol"
                    :wrapperCol="wrapperCol"
                    :title="itemv.info"
                    :name="itemv.name"
                    :type="itemv.type"
                    :required="Boolean(itemv.required)"
                    :value="itemv.value"
                    :tips="itemv.desc"
                    :digits="itemv.digits"
                    :url="Boolean(itemv.url)"
                    :max="itemv.max"
                    :min="itemv.min"
                    :selectArray="itemv.typeValue"
                    :rows="Number(itemv.rows)"
                    :placeholder="itemv.placeholder"
                    :key="itemv.gid"
                  ></form-item>
                </div>
                <a-form-item :wrapperCol="{ span: 24 }" style="text-align: left">
                  <a-button htmlType="submit" type="primary">提交</a-button>
                  <!--                  <a-button htmlType="submit" type="primary" style="margin-left:10px;">获取及时聊天的key</a-button>-->
                </a-form-item>
              </a-form>
            </a-tab-pane>
          </a-tabs>
        </a-tab-pane>
      </a-tabs>
    </a-card>
  </div>
</template>
<script>
import configPlatformApi from '@/api/config/platform/config'
import FormItem from '@/components/FormItem/FormItem'
import ColorPicker from '@/components/ColorPicker/ColorPicker'
import RichText from '@/components/RichText/RichText'
import { transferFormValue } from '@/utils/util'
export default {
  name: 'ConfigPlatform',
  components: {
    FormItem,
    ColorPicker,
    RichText,
  },
  data() {
    return {
      form: this.$form.createForm(this),
      labelCol: { lg: { span: 6 }, sm: { span: 7 } },
      wrapperCol: { lg: { span: 14 }, sm: { span: 17 } },
      groupList: [],
      configTab: [],
      configList: [],
      currentList: undefined,
      gid: '',
      GLindex: 0,
    }
  },
  mounted() {
    if (this.$route.path.indexOf('gid') != -1) {
      this.gid = this.$route.path.slice(this.$route.path.indexOf('=') + 1)
    }
    console.log(1111, this.gid)
    this.getData(this.gid)
  },

  created() {},
  methods: {
    firstTabChange(index) {
      const item = this.groupList[index]
      this.GLindex = index
      this.getTabData(item.gid)
    },
    secondTabChange(key) {
      console.log(key)
    },
    getTabData(gid) {
      this.request(configPlatformApi.config, { gid: gid }, 'get').then((res) => {
        this.configTab = res.config_list
      })
    },
    getData(gid) {
      this.request(configPlatformApi.config, { gid: gid }, 'get').then((res) => {
        console.log(res.config_list)
        this.groupList = res.group_list
        this.configTab = res.config_list
      })
    },
    handleSubmit(e) {
      e.preventDefault()
      this.form.validateFields((err, values) => {
        if (!err) {
          let formData = { ...values }
          // 要转换的数据类型
          const types = ['switch', 'date', 'time', 'image']
          this.configTab.forEach((configList) => {
            // 富文本
            const richText = configList.list.find((item) => {
              return item['name'] == 'merchant_recharge_agreement'
            })
            if (richText) {
              formData.merchant_recharge_agreement = richText.value
            }
            // 颜色选择
            const colors = configList.list.find((item) => {
              return item['name'] == 'mobile_head_color'
            })
            if (colors) {
              formData.mobile_head_color = colors.value
            }
            transferFormValue(configList.list, formData, types)
          })
          // 最后的数据
          console.log('Received values of form: ', formData)
          this.request(configPlatformApi.amendConfig, formData).then((result) => {
            const item = this.groupList[this.GLindex]
            this.getTabData(item.gid)
            this.$message.success(result.msg)
          })
        }
      })
    },
  },
}
</script>