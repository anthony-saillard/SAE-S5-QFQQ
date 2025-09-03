import js from '@eslint/js'
import globals from 'globals'
import pluginVue from 'eslint-plugin-vue'
import pluginQuasar from '@quasar/app-webpack/eslint'
import stylisticJs from '@stylistic/eslint-plugin'
import jsdoc from 'eslint-plugin-jsdoc'

export default [
  {
    ignores: [
      '.husky',
      '.idea',
      '.vscode',
      'docs',
      'node_modules',
      'public',
      'src/assets',
      'public',
      'eslint.config.js',
      'babel.config.js',
    ]
  },
  ...pluginQuasar.configs.recommended(),
  js.configs.recommended,
  ...pluginVue.configs['flat/recommended'],
  jsdoc.configs['flat/recommended'],
  {
    plugins: {
      '@stylistic/js': stylisticJs,
      jsdoc
    },
    languageOptions: {
      ecmaVersion: 'latest',
      sourceType: 'module',

      globals: {
        ...globals.browser,
        ...globals.node,
        process: 'readonly',
        cordova: 'readonly',
        Capacitor: 'readonly',
        chrome: 'readonly',
        browser: 'readonly',
        __APP_VERSION__: 'readonly'
      }
    },
    settings: {
      jsdoc: {
        tagNamePreference: {
          return: 'return'
        }
      }
    },
    rules: {
      curly: ['error', 'all'],
      'brace-style': ['error', '1tbs', { allowSingleLine: false }],
      'function-call-argument-newline': ['error', 'consistent'],
      'jsdoc/check-tag-names': ['error', {
        definedTags: ['consumes', 'produces', 'route'],
      }],
      'vue/script-indent': [
        'error',
        2,
        {
          baseIndent: 1,
          switchCase: 1
        }
      ],
      quotes: ['error', 'single'],
      semi: ['error', 'never'],
      'comma-dangle': ['error', 'never'],
      'jsdoc/require-jsdoc': 'off',
      'jsdoc/require-param-description': 'off',
      'jsdoc/require-returns': 'off',
      'jsdoc/require-param': 'off',
      'jsdoc/require-param-type': 'off',
      'jsdoc/tag-lines': 'off',
      'jsdoc/require-returns-description': 'off',
      'jsdoc/no-undefined-types': 'off',
      'jsdoc/check-types': 'off',
      'vue/script-setup-uses-vars': 'error',
      'vue/multi-word-component-names': 'off',
      'vue/require-default-prop': 'off',
      'vue/v-on-event-hyphenation': 'off',
      'generator-star-spacing': 'off',
      'vue/no-dupe-keys': 'off',
      'arrow-parens': 'off',
      'one-var': 'off',
      'no-void': 'off',
      'no-console': 'error',
      'multiline-ternary': 'off',
      'prefer-promise-reject-errors': 'off',

      '@stylistic/js/indent': ['error', 2, {
        SwitchCase: 1
      }],
      '@stylistic/js/space-before-function-paren': ['error',
        {
          anonymous: 'always',
          named: 'never',
          asyncArrow: 'always'
        }
      ],

      'vue/order-in-components': [
        'error',
        {
          order: [
            'el',
            'name',
            'key',
            'parent',
            'functional',
            ['delimiters', 'comments'],
            ['components', 'directives', 'filters'],
            'extends',
            'mixins',
            ['provide', 'inject'],
            'ROUTER_GUARDS',
            'layout',
            'middleware',
            'validate',
            'scrollToTop',
            'transition',
            'loading',
            'inheritAttrs',
            'model',
            ['props', 'propsData'],
            'emits',
            'setup',
            'asyncData',
            'data',
            'fetch',
            'head',
            'computed',
            'watch',
            'watchQuery',
            'LIFECYCLE_HOOKS',
            'methods',
            ['template', 'render'],
            'renderError'
          ]
        }
      ],

      'vue/max-attributes-per-line': [
        'error',
        {
          singleline: { max: 4 },
          multiline: { max: 4 }
        }
      ],

      'no-debugger': process.env.APP_ENV === 'prod' ? 'error' : 'off'
    }
  },
  {
    files: ['**/*.vue'],
    rules: {
      '@stylistic/indent': 'off',
      '@stylistic/js/indent': 'off'
    }
  }
]
