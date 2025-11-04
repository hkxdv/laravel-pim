import js from '@eslint/js';
import prettier from 'eslint-config-prettier';
import eslintPluginImport from 'eslint-plugin-import';
import jsxA11y from 'eslint-plugin-jsx-a11y';
import react from 'eslint-plugin-react';
import reactHooks from 'eslint-plugin-react-hooks';
import eslintPluginSonarjs from 'eslint-plugin-sonarjs';
import eslintPluginUnicorn from 'eslint-plugin-unicorn';
import globals from 'globals';
import typescript from 'typescript-eslint';

/** @type {import('eslint').Linter.Config[]} */
export default [
  js.configs.recommended,
  ...typescript.configs.recommendedTypeChecked,
  ...typescript.configs.strictTypeChecked,
  ...typescript.configs.stylisticTypeChecked,
  {
    files: ['**/*.{ts,tsx}'],
    languageOptions: {
      parser: typescript.parser,
      parserOptions: {
        project: ['./tsconfig.json'],
        tsconfigRootDir: import.meta.dirname,
        projectService: true,
      },
      globals: {
        ...globals.browser,
      },
    },
    plugins: {
      '@typescript-eslint': typescript.plugin,
      react: react,
      'jsx-a11y': jsxA11y,
      'react-hooks': reactHooks,
      import: eslintPluginImport,
      unicorn: eslintPluginUnicorn,
      sonarjs: eslintPluginSonarjs,
    },
    settings: {
      react: {
        version: 'detect',
      },
      'import/resolver': {
        typescript: {
          alwaysTryTypes: true,
          project: './tsconfig.json',
        },
      },
    },
    rules: {
      ...react.configs.flat.recommended.rules,
      ...react.configs.flat['jsx-runtime'].rules,
      'react/react-in-jsx-scope': 'off',
      'react/prop-types': 'off',
      'react/no-unescaped-entities': 'warn',
      'react-hooks/rules-of-hooks': 'error',
      'react-hooks/exhaustive-deps': 'warn',
      '@typescript-eslint/no-explicit-any': 'error',
      '@typescript-eslint/consistent-type-imports': [
        'error',
        { prefer: 'type-imports', fixStyle: 'inline-type-imports' },
      ],
      '@typescript-eslint/no-unused-vars': [
        'error',
        {
          argsIgnorePattern: '^_',
          varsIgnorePattern: '^_',
          caughtErrorsIgnorePattern: '^_',
        },
      ],
      '@typescript-eslint/no-empty-object-type': 'error',
      '@typescript-eslint/no-misused-promises': 'error',
      '@typescript-eslint/no-floating-promises': 'error',
      '@typescript-eslint/await-thenable': 'error',
      '@typescript-eslint/no-unsafe-assignment': 'warn',
      '@typescript-eslint/no-unsafe-call': 'warn',
      '@typescript-eslint/no-unsafe-member-access': 'warn',
      '@typescript-eslint/no-unsafe-return': 'warn',
      '@typescript-eslint/restrict-template-expressions': ['error', { allowNumber: true }],
      // Temporarily disable due to crash: "typeParameters.params is not iterable"
      // in @typescript-eslint/unified-signatures with TS 5.9 / ESLint 9.
      // Re-enable once upstream fix is available.
      '@typescript-eslint/unified-signatures': 'off',
      ...jsxA11y.configs.recommended.rules,
      'import/first': 'error',
      'import/no-duplicates': ['error', { 'prefer-inline': true }],
      'import/newline-after-import': 'error',
      'import/no-useless-path-segments': ['warn', { noUselessIndex: true }],
      'import/no-named-as-default-member': 'warn',
      ...eslintPluginUnicorn.configs.recommended.rules,
      'unicorn/filename-case': ['error', { case: 'kebabCase', ignore: ['/\.tsx$/'] }],
      'unicorn/prevent-abbreviations': 'off',
      'unicorn/prefer-top-level-await': 'off',
      'unicorn/no-null': 'off',
      'unicorn/prefer-module': 'error',
      'unicorn/no-useless-undefined': ['error', { checkArguments: false }],
      ...eslintPluginSonarjs.configs.recommended.rules,
      'sonarjs/no-duplicate-string': ['warn', { threshold: 5 }],
      'sonarjs/cognitive-complexity': ['warn', 20],
      'sonarjs/no-small-switch': 'warn',
    },
  },
  {
    ignores: ['node_modules/', 'dist/', 'src/ziggy.js', '*.config.js', '*.config.cjs'],
  },
  prettier,
];
