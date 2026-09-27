<script setup>
import { RouterView } from 'vue-router'
</script>

<template>
  <div id="app" class="flex h-screen bg-[#f5f5f7] text-[#1d1d1f] antialiased">
    <aside class="w-64 bg-white/80 backdrop-blur-xl border-r border-black/5 flex flex-col">
      <div class="p-5 border-b border-black/5">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#007AFF] to-[#5856d6] flex items-center justify-center shadow-lg shadow-blue-500/30">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
          </div>
          <div>
            <h1 class="font-semibold text-[15px] text-[#1d1d1f]">Offline Sync</h1>
            <p class="text-[11px] text-[#86868b]">Field Data Systems</p>
          </div>
        </div>
      </div>

      <nav class="flex-1 p-3 space-y-1">
        <router-link
          to="/"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-[14px] font-medium text-[#1d1d1f] hover:bg-black/5 transition"
          active-class="bg-blue-500/10 text-[#007AFF] font-semibold"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg>
          Dashboard
        </router-link>

        <router-link
          to="/sync"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-[14px] font-medium text-[#1d1d1f] hover:bg-black/5 transition"
          active-class="bg-blue-500/10 text-[#007AFF] font-semibold"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          Sync Simulator
        </router-link>
      </nav>

      <div class="p-3 border-t border-black/5">
        <div class="flex items-center gap-2 px-2">
          <span class="w-2 h-2 rounded-full bg-[#34c759]"></span>
          <span class="text-[12px] text-[#86868b]">Backend connected</span>
        </div>
      </div>
    </aside>

    <main class="flex-1 overflow-auto">
      <RouterView />
    </main>
  </div>
</template>