import api from './api'

export const resourcesService = {
  getAll() { return api.get('/resources') },
  getOne(id) { return api.get(`/resources/${id}`) },
  update(id, data) { return api.put(`/resources/${id}`, data) },
  delete(id) { return api.delete(`/resources/${id}`) }
}
