<script setup>
const appName = 'Gestao Financeira'
</script>

<template>
  <main class="min-h-screen px-6 py-10 text-slate-50">
    <section class="mx-auto flex min-h-[calc(100vh-5rem)] max-w-5xl items-center">
      <div class="max-w-2xl">
        <p class="mb-3 text-sm font-bold uppercase text-sky-300">Frontend Vue 3</p>
        <h1 class="mb-5 text-5xl font-black leading-none md:text-7xl">{{ appName }}</h1>
        <p class="text-lg leading-8 text-slate-300">
        Estrutura inicial criada. Nas proximas etapas vamos conectar Router,
        Pinia, Axios, Tailwind e autenticacao com Sanctum.
        </p>
      </div>
    </section>
  </main>
</template>
