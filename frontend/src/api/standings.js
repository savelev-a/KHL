// src/api/standings.js
import client from './client'

// GET /standings → { data: [...] }
export async function fetchStandings() {
  const { data } = await client.get('/standings')
  return data.data
}

// GET /simulations/latest → { season_id, calculated_at, simulations, age_seconds }
export async function fetchLatestSimulation() {
  const { data } = await client.get('/simulations/latest')
  return data
}

// GET /teams/{id} → { id, name, ..., standing, elo_history }
export async function fetchTeam(id) {
  const { data } = await client.get(`/teams/${id}`)
  return data
}