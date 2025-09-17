/**
 * 向后端请求设置
 */
import Vue from 'vue'
import {
  request
} from '@/utils/request'
import userPlatformApi from '@/api/user/platform'
const config = {
  state: {
    config: null,
  },
  mutations: {
    SET_CONFIG: (state, config) => {
      state.config = config
    }
  },
  actions: {
    SetConfig({ commit }) {
      getSystemConfig().then(res => {
        commit('SET_CONFIG', res)
        Vue.ls.set('system_config', res, null)
      })
    }
  }
}

// 对照路由表和生成有权限的路由表
function getSystemConfig() {
  return new Promise((resolve, reject) => {
    request(userPlatformApi.config).then(res => {
      resolve(res)
    }).catch(err => {
      reject(err)
    })
  })
}



export default config
