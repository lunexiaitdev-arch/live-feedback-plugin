import axios from 'axios'

const adminObj = window.lunexia_lf_admin || window.lvf_admin || {}

const api = axios.create({
  baseURL: adminObj.rest_url || '/wp-json/lunexia-lf/v1/',
  headers: {
    'X-WP-Nonce': adminObj.nonce || '',
  },
})

// Add error interceptor
api.interceptors.response.use(
  (response) => response,
  (error) => {
    // Handle common errors
    if (error.response?.status === 403) {
      console.error('Permission denied')
    } else if (error.response?.status === 404) {
      console.error('Resource not found')
    }
    return Promise.reject(error)
  }
)

export default api
