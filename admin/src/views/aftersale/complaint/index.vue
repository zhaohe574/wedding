<template>
    <!-- 投诉管理独立入口：复用售后主组件，固定到 complaint tab（B5 拆分后这里只需换 import） -->
    <TicketPage default-tab="complaint" />
</template>

<script lang="ts" setup name="aftersaleComplaint">
import TicketPage from '../ticket/index.vue'
</script>
