// src/router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import TeamView from '../views/TeamView.vue'

const routes = [
  { path: '/', name: 'home', component: HomeView },
  { path: '/team/:id', name: 'team', component: TeamView, props: true },
]

export default createRouter({
  history: createWebHistory(),
  routes,
})