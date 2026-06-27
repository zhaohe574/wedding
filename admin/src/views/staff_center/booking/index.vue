<template>
    <admin-page-shell class="staff-center-booking-redirect" title="订单管理">
        <el-empty description="该入口已合并至订单管理">
            <el-button type="primary" @click="redirectToOrder">前往订单管理</el-button>
        </el-empty>
    </admin-page-shell>
</template>

<script setup lang="ts" name="staffCenterBooking">
import { onActivated, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { getRoutePath } from '@/router'

const router = useRouter()

const redirectToOrder = () => {
    router.replace({
        path: getRoutePath('ops.order/myOrders') || '/staff-center/order'
    })
}

onMounted(redirectToOrder)
onActivated(redirectToOrder)
</script>
