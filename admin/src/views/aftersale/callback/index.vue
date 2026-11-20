<template>
    <!-- 回访管理独立入口：复用售后主组件，固定到 callback tab（B5 拆分后这里只需换 import） -->
    <TicketPage default-tab="callback" />
</template>

<script lang="ts" setup name="aftersaleCallback">
import TicketPage from '../ticket/index.vue'
</script>
