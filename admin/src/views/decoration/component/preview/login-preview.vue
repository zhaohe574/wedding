<template>
    <div class="pv-login">
        <div class="pv-login__hero">
            <img v-if="heroImageSrc" class="pv-login__img" :src="heroImageSrc" alt="" />
            <div v-else class="pv-login__fallback">
                <el-icon :size="32"><Picture /></el-icon>
                <span class="pv-login__fallback-title">使用小程序内置默认背景图</span>
                <span class="pv-login__fallback-sub">右侧上传图片后将替换顶部氛围视觉</span>
            </div>
            <div class="pv-login__mask" />
            <div class="pv-login__brand">
                <div class="pv-login__mark">{{ brand.name || '站点名称' }}</div>
                <div v-if="brand.slogan" class="pv-login__en">{{ brand.slogan }}</div>
            </div>
        </div>

        <div class="pv-login__sheet">
            <div class="pv-login__h2">先认识你们，再谈仪式。</div>
            <div class="pv-login__sub">
                登录后可查档期、收藏主持人，随时看订单进度与尾款。
            </div>

            <div class="pv-login__btn pv-login__btn--wechat">微信一键登录</div>
            <div class="pv-login__or">或</div>
            <div class="pv-login__btn pv-login__btn--phone">手机号快捷登录</div>

            <div class="pv-login__agree">
                <span class="pv-login__checkbox" />
                <span class="pv-login__agree-text">
                    我已阅读并同意《用户协议》与《隐私政策》。手机号登录仅限已注册账号。
                </span>
            </div>

            <div class="pv-login__foot">
                <span>遇到登录问题？</span>
                <span class="pv-login__foot-link">联系专属顾问</span>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import { Picture } from '@element-plus/icons-vue'
import { normalizeLoginHeroContent } from '@shared/contracts/decorate'
import { computed } from 'vue'

const props = defineProps<{
    content: Record<string, any>
    brand: { name: string; logo: string; slogan: string }
    domain: string
}>()

const normalized = computed(() => normalizeLoginHeroContent(props.content))

function toUrl(url?: string): string {
    const value = String(url || '').trim()
    if (!value) return ''
    if (/^https?:/i.test(value) || value.startsWith('/static/')) return value
    return props.domain ? `${props.domain.replace(/\/+$/, '')}/${value.replace(/^\/+/, '')}` : value
}

const heroImageSrc = computed(() => toUrl(normalized.value.background_image))
</script>

<style lang="scss" scoped>
.pv-login {
    width: 100%;
    min-height: 100%;
    background: #faf6ee;
    display: flex;
    flex-direction: column;
    font-family: -apple-system, BlinkMacSystemFont, 'PingFang SC', 'Microsoft YaHei', sans-serif;

    &__hero {
        position: relative;
        height: 373px;
        flex: none;
        overflow: hidden;
        background: #181614;
    }

    &__img {
        width: 100%;
        height: 100%;
        display: block;
        object-fit: cover;
    }

    &__fallback {
        width: 100%;
        height: 100%;
        padding-bottom: 64px;
        box-sizing: border-box;
        background: linear-gradient(160deg, #322921 0%, #1e1914 60%, #14110f 100%);
        color: rgba(255, 255, 255, 0.78);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    &__fallback-title {
        font-size: 13px;
        font-weight: 500;
        color: #ffffff;
    }

    &__fallback-sub {
        font-size: 11px;
        color: rgba(255, 255, 255, 0.65);
    }

    &__mask {
        position: absolute;
        inset: 0;
        background: linear-gradient(
            180deg,
            rgba(20, 17, 14, 0.55) 0%,
            rgba(20, 17, 14, 0.05) 34%,
            rgba(20, 17, 14, 0.42) 68%,
            rgba(20, 17, 14, 0.92) 100%
        );
        pointer-events: none;
    }

    &__brand {
        position: absolute;
        left: 25px;
        right: 25px;
        bottom: 27px;
    }

    &__mark {
        font-family: Georgia, 'Songti SC', serif;
        font-size: 31px;
        letter-spacing: 0.25em;
        color: #ffffff;
        line-height: 1.1;
    }

    &__en {
        margin-top: 7px;
        font-family: Georgia, 'Songti SC', serif;
        font-size: 10px;
        letter-spacing: 0.35em;
        color: #d9be82;
    }

    &__sheet {
        flex: 1;
        margin-top: -32px;
        position: relative;
        z-index: 2;
        background: #faf6ee;
        border-radius: 26px 26px 0 0;
        padding: 32px 24px 28px;
        display: flex;
        flex-direction: column;
    }

    &__h2 {
        font-size: 20px;
        font-weight: 600;
        color: #181614;
        line-height: 1.3;
    }

    &__sub {
        margin-top: 8px;
        font-size: 13px;
        line-height: 1.55;
        color: #6b655c;
    }

    &__btn {
        margin-top: 24px;
        height: 46px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        font-weight: 500;

        &--wechat {
            background: #181614;
            color: #ffffff;
        }

        &--phone {
            margin-top: 0;
            background: #fffdf8;
            color: #181614;
            border: 1px solid #8a8175;
        }
    }

    &__or {
        margin: 12px 0;
        text-align: center;
        font-size: 12px;
        color: #6b655c;
    }

    &__agree {
        margin-top: 18px;
        display: flex;
        align-items: flex-start;
        gap: 8px;
    }

    &__checkbox {
        margin-top: 2px;
        width: 14px;
        height: 14px;
        border-radius: 4px;
        border: 1px solid #8a8175;
        background: #fffdf8;
        flex-shrink: 0;
    }

    &__agree-text {
        font-size: 11px;
        line-height: 1.55;
        color: #6b655c;
    }

    &__foot {
        margin-top: auto;
        padding-top: 28px;
        display: flex;
        justify-content: center;
        gap: 4px;
        font-size: 12px;
        color: #6b655c;
    }

    &__foot-link {
        color: #8a6424;
        font-weight: 500;
    }
}
</style>
