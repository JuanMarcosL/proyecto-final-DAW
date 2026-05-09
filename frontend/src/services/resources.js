import api from './api'

export const resourcesService = {
  getAll() {
    return api.get('/resources')
  }
}
