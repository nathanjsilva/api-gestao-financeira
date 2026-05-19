<template>
  <main class="min-h-screen bg-slate-950 text-slate-50">
    <div class="grid min-h-screen lg:grid-cols-[1fr_520px]">
      <section class="hidden border-r border-white/10 bg-slate-900/40 px-10 py-12 lg:flex lg:flex-col lg:justify-between">
        <div>
          <p class="text-sm font-bold uppercase text-sky-300">Gestao Financeira</p>
          <h1 class="mt-6 max-w-xl text-6xl font-black leading-none">
            Controle mensal simples, visual e seguro.
          </h1>
        </div>

        <p class="max-w-lg text-base leading-7 text-slate-400">
          Frontend Vue conectado a uma API Laravel com Sanctum, preparado para dashboard,
          lancamentos, categorias e reserva financeira.
        </p>
      </section>

      <section class="flex min-h-screen items-center justify-center px-6 py-10">
        <div class="w-full max-w-md">
          <RouterView />
        </div>
      </section>
    </div>
  </main>
</template>
