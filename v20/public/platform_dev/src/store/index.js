/**
 *******************
 *   @author 郑亚莉
 *   @date 2020-05-08
 *   @description
 *******************
 */

import Vue from 'vue'
import Vuex from 'vuex'

import app from './modules/app'
import user from './modules/user'
import storestaff from './modules/storestaff.js'
import createPersistedState from 'vuex-persistedstate'
import keepalive from './modules/keepalive'
import config from './modules/config'

// default router permission control
// import permission from './modules/permission'

// 动态路由 + 权限控制
import permission from './modules/async-router'
import getters from './getters'

Vue.use(Vuex)

export default new Vuex.Store({
  plugins: [createPersistedState({
    storage: window.sessionStorage,
    reducer(val) {
      return {
        // 只储存state中的storestaff
        storestaff: val.storestaff
      }
    }
  })],
  modules: {
    app,
    user,
    permission,
    storestaff,
    keepalive,
    config
  },
  state: {

  },
  mutations: {

  },
  actions: {

  },
  getters
})
