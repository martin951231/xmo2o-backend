<template>
  <div id="merchantLogin">
    <!-- 头部 -->
    <section id="header">
      <div class="left item">
        <div class="logo">
          <div class="title">商家注册协议</div>
        </div>
      </div>
      <div class="right item" v-if="config.is_demo_domain">
        <div>小猪O2O致力于为快速构建本地生活服务平台提供专业的解决方案</div>
      </div>
    </section>
    <!-- 内容区域 -->
    <section id="content">
      <a-card class="content-card" v-html="config.store_register_agreement"></a-card>
    </section>
    <section class="footer">
      <div class="copyright" v-if="config.copyright_txt">{{ config.copyright_txt }}</div>
    </section>
  </div>
</template>

<script>
export default {
  components: {},
  data() {
    return {
      config: null,
    }
  },
  created() {
    this.getConfig()
  },
  methods: {
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
        width: 350px;
        height: 50px;
        background: url(https://o2o-demo-img.oss-cn-hangzhou.aliyuncs.com/upload/config/000/000/030/5c6116472db32894.png)
          no-repeat;
        background-size: 160px auto;
        background-position: center left;
        vertical-align: middle;
        display: flex;
        align-items: center;
        .title {
          position: absolute;
          left: 180px;
          border-left: 2px solid rgba(0, 0, 0, 0.1);
          width: 150px;
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
    // display: flex;
    // justify-content: space-around;
    .content-card {
      position: absolute;
      width: 80%;
      top: 120px;
      left: 10%;
      border-radius: 4px;
      border: none;
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
