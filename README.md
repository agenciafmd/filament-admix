## Customizando

Publique os arquivos do tema:

```bash
php artisan vendor:publish --tag=filament-admix:theme
```

Os arquivos publicados serão:

```
/public/filament-admix/assets/theme-C3cqJWWP.css
/public/filament-admix/manifest.json
/resources/css/filament/filament-admix/theme.css
vite.admix.config.js
```

Se for preciso buildar o tema ou customizar algum plugin, adicione no `package.json`:

```json
"scripts": {
...
"build:admix": "vite build --config vite.admix.config.js"
},
```

Não esqueça de remover a publicação dos assets em `post-update-cmd`.

## Atualização

Para manter os assets atualizados, adicione o comando `@php artisan vendor:publish --tag=filament-admix:theme --ansi --force` ao seu `post-update-cmd` no `composer.json` do seu projeto.
