/* eslint-env node */
require('@rushstack/eslint-patch/modern-module-resolution')

module.exports = {
    root: true,
    ignorePatterns: ['src/uni_modules/'],
    extends: [
        'plugin:vue/vue3-essential',
        'eslint:recommended',
        '@vue/eslint-config-typescript/recommended',
        '@vue/eslint-config-prettier'
    ],
    rules: {
        'prettier/prettier': [
            'warn',
            {
                semi: false,
                singleQuote: true,
                printWidth: 100,
                proseWrap: 'preserve',
                bracketSameLine: false,
                endOfLine: 'auto',
                tabWidth: 4,
                useTabs: false,
                trailingComma: 'none'
            }
        ],
        'vue/multi-word-component-names': 'off',
        '@typescript-eslint/no-explicit-any': 'off',
        '@typescript-eslint/ban-ts-comment': 'off',
        'no-undef': 'off',
        'vue/prefer-import-from-vue': 'off',
        'no-prototype-builtins': 'off',
        'prefer-spread': 'off',
        '@typescript-eslint/no-non-null-assertion': 'off',
        '@typescript-eslint/no-non-null-asserted-optional-chain': 'off',
        // uni-app 小程序端的 scroll-view 自定义下拉刷新仍使用 slot 属性写法
        // （<view slot="refresher">），改成 <template #refresher> 会破坏小程序端渲染，
        // 故该规则在小程序工程内关闭。
        'vue/no-deprecated-slot-attribute': 'off'
    },
    overrides: [
        {
            // .vue 单文件组件的 <script> 里不使用 JSX，
            // 关闭 jsx 才能正确解析 `const fn = <T>(v: T) => ...` 这类泛型箭头函数，
            // 否则 vue-eslint-parser 会把它当成 JSX 标签并抛出 parse error。
            files: ['*.vue'],
            parserOptions: {
                ecmaFeatures: { jsx: false }
            }
        }
    ],
    globals: {}
}
