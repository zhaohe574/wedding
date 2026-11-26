<template>
    <footer class="layout-footer">
        <!--
            原「会员中心」链接指向 /user/info，但 PC 官网定位为展示型站点
            （无客户登录、个人中心、预约与付款），该路由并不存在，
            会被预渲染爬虫判定为死链，故移除。
        -->
        <nav class="layout-footer__links" aria-label="协议与政策">
            <NuxtLink :to="`/policy/${PolicyAgreementEnum.SERVICE}`">
                用户协议
            </NuxtLink>
            <span class="layout-footer__sep" aria-hidden="true">·</span>
            <NuxtLink :to="`/policy/${PolicyAgreementEnum.PRIVACY}`">
                隐私政策
            </NuxtLink>
        </nav>
        <div
            v-if="appStore.getCopyrightConfig.length"
            class="layout-footer__copyright"
        >
            <template
                v-for="item in appStore.getCopyrightConfig"
                :key="item.key"
            >
                <a
                    v-if="item.value"
                    :href="item.value"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    {{ item.key }}
                </a>
                <span v-else>{{ item.key }}</span>
            </template>
        </div>
    </footer>
</template>
<script lang="ts" setup>
import { useAppStore } from '@/stores/app'
import { PolicyAgreementEnum } from '@/enums/appEnums'
const appStore = useAppStore()
</script>

<style lang="scss" scoped>
.layout-footer {
    padding: 32px 24px;
    border-top: 1px solid var(--pc-line);
    background: var(--pc-bg-deep);
    color: var(--pc-ink-3);
    font-size: 13px;
    text-align: center;

    &__links {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;

        a {
            color: var(--pc-ink-2);
            text-decoration: none;
            transition: color var(--dur-fast) var(--ease-out);

            &:hover {
                color: var(--pc-gold);
            }
        }
    }

    &__sep {
        color: var(--pc-ink-3);
    }

    &__copyright {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 6px 18px;
        margin-top: 14px;
        color: var(--pc-ink-3);
        font-size: 12px;

        a {
            color: var(--pc-ink-3);
            text-decoration: none;
            transition: color var(--dur-fast) var(--ease-out);

            &:hover {
                color: var(--pc-gold);
            }
        }
    }
}
</style>
