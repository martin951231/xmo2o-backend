import Vue from 'vue'
import router from './router'
import store from './store'

import NProgress from 'nprogress' // progress bar
import '@/components/NProgress/nprogress.less' // progress bar custom style
import notification from 'ant-design-vue/es/notification'
import { setDocumentTitle } from '@/utils/domUtil'
import path from '@/router/router-path.js'
import { getTokenName, getRouter, getSystemName, getSystemTitle, urlConversion } from './utils/util'


NProgress.configure({ showSpinner: false }) // NProgress Configuration

const whiteList = path.whiteList //免登陆白名单
const whiteLogin = path.whiteLogin //免自动跳转登录页面

// 路由导航钩子
// 1.进入路由之前 进行路由拦截
router.beforeEach((to, from, next) => {
  const sysConfig = store.getters.config
  if (sysConfig) {
    if (location.href.indexOf("http:") != -1 && sysConfig.use_https) {
      // 换成https
      window.location.href = urlConversion(location.href)
    }
  } else {
    store.dispatch('SetConfig')
  }
  const loginPath = getRouter(to.path, 'login') // 登录
  const defaultRoutePath = getRouter(to.path, 'index') // 首页
  const tokenName = getTokenName(to.path)
  const fullPath = to.fullPath
  NProgress.start() // start progress bar
  to.meta && (typeof to.meta.title !== 'undefined' && setDocumentTitle(to.meta.title, getSystemTitle(to.path)))
  if (Vue.ls.get(tokenName) && !whiteLogin.includes(fullPath)) { // 不同系统对应不同的 token
    /* has token */
    if (to.path === loginPath) {
      next({ path: defaultRoutePath }) // 根据路由规范判断进入哪个首页
      NProgress.done()
    } else {
      if (getSystemName(to.path) == 'storestaff' || to.path == '/user/village/datastatistics' || to.path == '/community/property/package/LoginPackagesBuy' || to.path == '/community/property/package/LoginPackagesRoomBuy') {// 店员不需要左侧菜单 直接进入首页
        next()
        NProgress.done()
      } else if (store.getters.roles.length === 0) { // 若未获取用户信息和权限信息，则请求用户信息
        store
          .dispatch('GetInfo', { tokenName })
          .then(res => {
            const roles = res && res.role
            store.dispatch('GenerateRoutes', { roles, tokenName, url: to.path }).then(() => { // 对照权限生成动态路由
              // 动态添加可访问路由表
              router.addRoutes(store.getters.addRouters)
              next({
                path: to.path,
                query: to.query
              })
            })
          })
          .catch(() => {
            notification.error({
              message: '登录超时',
              description: '请重新登录'
            })
            store.dispatch('Logout', tokenName).then(() => {
              next({ path: loginPath }) // 根据路由规范判断进入哪个登录页 
            })
          })
      } else {
        next()
      }
    }
  } else {
    if (whiteList.includes(to.name)) {
      // 在免登录白名单，直接进入
      next()
    } else {
      //, query: { redirect: to.fullPath }
      next({ path: loginPath })
      NProgress.done() // if current page is login will not trigger afterEach hook, so manually handle it
    }
  }
})


// 2. 进入路由之后
router.afterEach(() => {
  NProgress.done() // finish progress bar
})
