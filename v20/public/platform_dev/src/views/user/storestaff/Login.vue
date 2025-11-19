<template>
  <div id="merchantLogin">
    <!-- 头部 -->
    <section id="header">
      <div class="left item">
        <div
          class="logo"
          @click="openUrl"
          :data-url="config.site_url"
          :style="'background-image:url(' + config.site_logo + ')'"
        >
          <div class="title" @click.stop>店员登录</div>
        </div>
      </div>
      <div class="right item" v-if="config.is_demo_domain">
        <div>小猪O2O致力于为快速构建本地生活服务平台提供专业的解决方案</div>
      </div>
    </section>
    <!-- 内容区域 -->
    <section id="content">
      <a-card class="form-card" key="login-card">
        <a-tabs :default-active-key="loginType" size="large" :tabBarStyle="{ textAlign: 'center', fontSize: '16px' }">
          <a-tab-pane key="code" tab="扫码登录">
            <!-- 扫码登录start -->
            <div class="code-container">
              <img class="code-img" v-if="codeInfo.qrcode" :src="codeInfo.qrcode" />
              <div v-else class="code-img">
                <a-spin />
              </div>
            </div>
            <div class="tip">
              打开
              <span style="color: red; margin-right: 10px">手机微信</span>
              <span>扫描二维码</span>
            </div>
            <div class="characteristic">
              <div class="item" key="noinput"><a-icon class="item-icon" type="edit" />免输入</div>
              <div class="item" key="faster"><a-icon class="item-icon" type="rocket" />更快</div>
              <div class="item" key="safer"><a-icon class="item-icon" type="safety" />更安全</div>
            </div>
            <!-- 扫码登录end -->
          </a-tab-pane>
          <a-tab-pane key="account" tab="账户登录">
            <!-- 登录表单start -->
            <login-form @handleLogin="handleAccountLogin" ref="loginForm" />
            <!-- 登录表单end -->
          </a-tab-pane>
        </a-tabs>
      </a-card>
    </section>
    <section class="footer">
      <div class="copyright" v-if="config.copyright_txt">{{ config.copyright_txt }}</div>
    </section>
  </div>
</template>

<script>
import Vue from 'vue'
import { getTokenName, setCookie } from '@/utils/util'
import { mapActions } from 'vuex'
import path from '@/router/router-path.js'
import userStorestaffApi from '@/api/user/storestaff'
// 登录表单
import LoginForm from './modules/LoginForm.vue'

export default {
  components: {
    LoginForm,
  },
  data() {
    return {
      // 登录时选择的tab的key值 code扫码登录 account账户登录
      loginType: 'code',
      // 图片验证码信息
      codeInfo: {},
      interval: null,
      config: null,
    }
  },
  created() {
    this.getConfig()
    this.getLoginCode()
  },
  beforeDestroy() {
    window.clearInterval(this.interval)
  },
  methods: {
    ...mapActions(['Login']),
    getConfig() {
      const config = this.$store.getters.config
      if (config) {
        this.config = config
      } else {
        setTimeout(() => {
          this.getConfig()
        }, 300)
      }
    },
    // 获取登录二维码
    getLoginCode() {
      this.request(userStorestaffApi.qrcode, { mer_id: 1 }).then((res) => {
        // console.log('res', res)
        this.codeInfo = res
        if (res.qrcode_id) {
          this.interval = window.setInterval(() => {
            //  二维码登录
            this.request(userStorestaffApi.codeLoginResult, { qrcode_id: res.qrcode_id })
              .then((data) => {
                if (data.ticket) {
                  const tokenName = getTokenName('merchant')
                  Vue.ls.set(tokenName, data.ticket, null) // vue.ls存储到localstorage,持久化存储
                  setCookie(tokenName, data.ticket, null) // 存入到cookie中
                  this.loginSuccess(data)
                }
              })
              .catch((err) => {
                window.clearInterval(this.interval)
              })
          }, 3000)
        }
      })
    },
    // 账号密码登录
    handleAccountLogin(userInfo) {
      const { Login } = this
      Login({ userInfo, url: userStorestaffApi.login })
        .then((res) => this.loginSuccess(res))
        .catch((err) => this.requestFailed(err))
        .finally(() => {
          this.$refs.loginForm.loginBtn = false
        })
    },
    // 登录成功
    loginSuccess(res) {
      if (res) {
        console.log(res)
        window.clearInterval(this.interval)
        this.$message.success('登录成功！正在跳转~')
        window.setTimeout(() => {
          this.$router.push({ path: path.storestaffIndex })
        }, 600)
      }
    },
    // 登录失败
    requestFailed(err) {
      // 更换验证码
      this.$refs.loginForm.getNewCode()
    },
    // 打开链接
    openUrl(e) {
      console.log(e.target.dataset.url)
      window.open(e.target.dataset.url, '_blank')
    },
  },
}
</script>

<style lang="less" scoped>
#merchantLogin {
  width: 100%;
  height: 100%;
  background: url('../../../assets/merchant/login_bg.png') top left no-repeat;
  background-size: cover;
  color: #666;
  font-family: Microsoft YaHei, Arial, Helvetica, sans-serif;
  font-size: 12px;
  position: relative;
  #header {
    position: fixed;
    width: 100%;
    height: 80px;
    top: 0;
    background-color: #ffffff;
    box-shadow: 0px 0px 6px 0px rgba(0, 0, 0, 0.4);
    display: flex;
    justify-content: space-between;
    .item {
      flex: 1;
      height: 80px;
      display: flex;
      align-items: center;
      justify-content: space-around;
      margin: 0 20px;
    }
    .left {
      .logo {
        position: relative;
        width: 300px;
        height: 50px;
        background: url(https://o2o-demo-img.oss-cn-hangzhou.aliyuncs.com/upload/config/000/000/030/5c6116472db32894.png);
        background-repeat: no-repeat;
        background-size: 160px auto;
        background-position: center left;
        vertical-align: middle;
        display: flex;
        align-items: center;
        cursor: pointer;
        .title {
          cursor: default;
          position: absolute;
          left: 180px;
          border-left: 2px solid rgba(0, 0, 0, 0.1);
          width: 100px;
          height: 30px;
          line-height: 30px;
          vertical-align: middle;
          padding-left: 20px;
          font-size: 16px;
        }
      }
    }
  }
  #content {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: space-around;

    .form-card {
      width: 430px;
      border: none;
      border-radius: 6px;
      box-shadow: 0px 0px 6px 2px rgba(0, 0, 0, 0.07);
      .ant-card-actions {
        border: none;
        color: #b61d1d;
      }
      .code-container {
        text-align: center;
        margin-top: 20px;
        .code-img {
          display: inline-block;
          width: 200px;
          height: 200px;
          line-height: 160px;
          vertical-align: middle;
          padding: 20px;
          border: 1px solid rgba(0, 0, 0, 0.1);
          border-radius: 2px;
        }
      }
      .tip {
        text-align: center;
        margin-top: 20px;
        font-size: 12px;
        .link:hover {
          color: #1890ff;
        }
      }
      .characteristic {
        text-align: center;
        margin: 15px 40px 0 40px;
        font-size: 12px;
        display: flex;
        justify-content: space-around;
        font-size: 14px;
        color: #999999;

        .item {
          flex: 1;
          .item-icon {
            margin-right: 10px;
            border-radius: 50%;
            border: 1px solid #dcdcdc;
            padding: 4px;
          }
        }
      }
      .register-title {
        height: 56px;
        line-height: 56px;
        vertical-align: middle;
        text-align: center;
        color: #1890ff;
        border-bottom: 1px solid #e8e8e8;
        font-weight: 500;
        font-size: 16px;
      }
    }
  }
  .footer {
    position: absolute;
    width: 100%;
    bottom: 0;
    padding: 0 16px;
    margin: 48px 0 24px;
    text-align: center;
    .copyright {
      color: rgba(0, 0, 0, 0.45);
      font-size: 14px;
    }
  }
  #btns {
    position: fixed;
    width: 80px;
    height: 320px;
    bottom: 80px;
    right: 20px;
    background: #ffffff;
    box-shadow: 0px 0px 10px 2px rgba(0, 0, 0, 0.1);
    cursor: pointer;
    .item {
      width: 80px;
      height: 80px;
      box-shadow: 0px -4px 4px 0px #f1f1f1;
      text-align: center;
      display: flex;
      flex-direction: column;
      justify-content: center;
      .tip-icon {
        font-size: 20px;
        margin-bottom: 10px;
      }
      &:hover {
        background: #1890ff;
        color: #ffffff;
        box-shadow: none;
      }
    }
    .active {
      background: #1890ff;
      color: #ffffff;
    }
  }
}
</style>
