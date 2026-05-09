import api from './api'

export const appointmentsService = {
  getAll() {
    return api.get('/appointments')
  },
  getOne(id) {
    return api.get(`/appointments/${id}`)
  },
  create(data) {
    return api.post('/appointments', data)
  },
  update(id, data) {
    return api.put(`/appointments/${id}`, data)
  },
  delete(id) {
    return api.delete(`/appointments/${id}`)
  }
}
