<template>
  <main class="min-h-screen bg-transparent text-slate-50 relative overflow-hidden">
    <div class="grid min-h-screen lg:grid-cols-[1fr_520px]">
      <section class="hidden border-r border-white/10 bg-slate-900/40 px-10 py-12 lg:flex lg:flex-col lg:justify-between">
        <div>
          <p class="text-sm font-bold uppercase text-sky-300">Gestão Financeira</p>
          <h1 class="mt-6 max-w-xl text-6xl font-black leading-none">
            Controle mensal simples, visual e seguro.
          </h1>
        </div>

        <p class="max-w-lg text-base leading-7 text-slate-400">
          Frontend Vue conectado a uma API Laravel com Sanctum, preparado para dashboard,
          lançamentos, categorias e reserva financeira.
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

<style scoped>
main {
  background: transparent;
}

main::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(15, 23, 42, 0.28) 0%, rgba(15, 23, 42, 0.28) 100%);
  pointer-events: none;
}

main > .grid {
  position: relative;
  z-index: 1;
}
</style>
