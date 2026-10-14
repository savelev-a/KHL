// src/stores/standings.js
import { defineStore } from 'pinia'
import { fetchStandings } from '../api/standings'

export const useStandingsStore = defineStore('standings', {
  state: () => ({
    items: [],        // сырой массив из /standings
    loading: false,
    error: null,
    loadedAt: null,   // Date последней успешной загрузки
  }),

  getters: {
    // Разбивка по конференциям. Сортировка — как пришла с бэка (по position).
    east: (state) => state.items.filter((r) => r.team.conference_key === 'east'),
    west: (state) => state.items.filter((r) => r.team.conference_key === 'west'),
  },

  actions: {
    async load() {
      this.loading = true
      this.error = null
      try {
        this.items = await fetchStandings()
        this.loadedAt = new Date()
      } catch (e) {
        this.error = e.message || 'Не удалось загрузить таблицу'
      } finally {
        this.loading = false
      }
    },
  },
})