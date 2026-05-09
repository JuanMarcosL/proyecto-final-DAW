import api from './api'

export const workOrdersService = {
  getAll() {
    return api.get('/work-orders')
  },
  getOne(id) {
    return api.get(`/work-orders/${id}`)
  },
  create(data) {
    return api.post('/work-orders', data)
  },
  update(id, data) {
    return api.put(`/work-orders/${id}`, data)
  },
  delete(id) {
    return api.delete(`/work-orders/${id}`)
  }
}
