<template>
    <div class="search">
        <ElInput
            v-model.trim="searchKeyword"
            placeholder="搜索资讯"
            :suffix-icon="Search"
            @keyup.enter="handleToSearch"
        />
    </div>
</template>
<script lang="ts" setup>
import { ElInput } from 'element-plus'
import { Search } from '@element-plus/icons-vue'
import feedback from '~~/utils/feedback'
const router = useRouter()
const route = useRoute()
const searchKeyword = ref()
const handleToSearch = () => {
    if (!searchKeyword.value) return feedback.msgError('请输入关键词')
    router.push({
        path: '/information/search',
        query: {
            keywords: searchKeyword.value
        }
    })
}
watch(
    route,
    (routeNew) => {
        if (routeNew.path == '/information/search') {
            searchKeyword.value = routeNew.query.keywords
        } else {
            searchKeyword.value = ''
        }
    },
    {
        immediate: true
    }
)
</script>

<style lang="scss" scoped>
.search {
    width: 240px;

    :deep(.el-input__wrapper) {
        padding: 0 14px;
        border-radius: 999px;
        background-color: rgba(27, 23, 20, 0.9);
        box-shadow: 0 0 0 1px var(--pc-line) inset;
        transition: box-shadow var(--dur-fast) var(--ease-out);

        &:hover {
            box-shadow: 0 0 0 1px var(--pc-line-strong) inset;
        }

        &.is-focus {
            box-shadow: 0 0 0 1px var(--pc-gold) inset;
        }
    }

    :deep(.el-input__inner) {
        color: var(--pc-ink);

        &::placeholder {
            color: var(--pc-ink-3);
        }
    }

    :deep(.el-input__suffix-inner) {
        color: var(--pc-ink-2);
    }
}

@media (max-width: 760px) {
    .search {
        width: 160px;
    }
}
</style>
