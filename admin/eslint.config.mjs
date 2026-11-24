// ESLint 9 Flat Config
//
// 背景：ESLint 9 只读取扁平配置（eslint.config.*），旧的 .eslintrc.cjs 完全不再生效，
// 导致原 `npm run lint` 直接报错。本文件用扁平配置重建原有规则集。
//
// 说明：
// 1) 文件后缀使用 .mjs，因为 admin/package.json 未声明 "type": "module"，
//    .js 会被 Node 当作 CommonJS 解析而无法使用 import 语法。
// 2) unplugin-auto-import 生成的全局变量沿用旧配置的 `no-undef: off` 兜底，
//    不再额外引入 .eslintrc-auto-import.json。
import js from '@eslint/js'
import vuePrettierSkipFormatting from '@vue/eslint-config-prettier/skip-formatting'
import simpleImportSort from 'eslint-plugin-simple-import-sort'
import pluginVue from 'eslint-plugin-vue'
import globals from 'globals'
import tseslint from 'typescript-eslint'

/**
 * globals 包里存在带首尾空格的键（如 "AudioWorkletGlobalScope "），
 * ESLint 9 会因此直接抛错，这里统一清洗键名。
 * @param {Record<string, unknown>} source
 * @returns {Record<string, unknown>}
 */
const sanitizeGlobals = (source) =>
    Object.fromEntries(
        Object.entries(source).map(([key, value]) => [key.trim(), value])
    )

export default tseslint.config(
    {
        // 忽略构建产物与自动生成文件
        ignores: [
            'dist/**',
            'dist-audit/**',
            'node_modules/**',
            '.vscode/**',
            '.idea/**',
            'auto-imports.d.ts',
            'components.d.ts'
        ]
    },
    js.configs.recommended,
    ...tseslint.configs.recommended,
    ...pluginVue.configs['flat/essential'],
    vuePrettierSkipFormatting,
    {
        files: ['**/*.{js,mjs,cjs,ts,mts,cts,vue}'],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: {
                ...sanitizeGlobals(globals.browser),
                ...sanitizeGlobals(globals.node),
                module: 'readonly'
            }
        },
        plugins: {
            'simple-import-sort': simpleImportSort
        },
        rules: {
            'simple-import-sort/imports': 'error',
            '@typescript-eslint/no-unused-vars': [
                'warn',
                {
                    argsIgnorePattern: '^_',
                    varsIgnorePattern: '^_',
                    caughtErrors: 'none'
                }
            ],
            'vue/no-mutating-props': 'off',
            '@typescript-eslint/no-unused-expressions': [
                'warn',
                {
                    allowShortCircuit: true,
                    allowTernary: true
                }
            ],
            'no-empty': ['warn', { allowEmptyCatch: true }],
            '@typescript-eslint/no-empty-object-type': 'off',
            'vue/multi-word-component-names': 'off',
            '@typescript-eslint/no-explicit-any': 'off',
            '@typescript-eslint/ban-ts-comment': 'off',
            'no-undef': 'off',
            'vue/prefer-import-from-vue': 'off',
            'no-prototype-builtins': 'off',
            'prefer-spread': 'off',
            '@typescript-eslint/no-non-null-assertion': 'off',
            '@typescript-eslint/no-non-null-asserted-optional-chain': 'off'
        }
    },
    {
        // Vue 单文件组件需要 vue 解析器 + TS 解析器
        files: ['**/*.vue'],
        languageOptions: {
            parserOptions: {
                parser: tseslint.parser,
                extraFileExtensions: ['.vue'],
                sourceType: 'module'
            }
        }
    }
)
