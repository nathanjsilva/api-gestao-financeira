<script setup>
import BaseButton from '../../components/base/BaseButton.vue'
import PageHeader from '../../components/layout/PageHeader.vue'
</script>

<template>
  <section class="mx-auto max-w-6xl px-6 py-10">
    <PageHeader
      eyebrow="Organizacao"
      title="Categorias"
      description="Esta tela vai listar, criar, editar e excluir categorias."
    >
      <template #actions>
        <BaseButton>Nova categoria</BaseButton>
      </template>
    </PageHeader>
  </section>
</template>
