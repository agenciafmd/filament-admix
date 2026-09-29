## Admix

Este pacote é um starter kit para ajudar desenvolvedores.
A ideia principal é facilitar os CRUDS dos recursos mais comuns em aplicações e sites.

### Features

- Usuários: cria usuários para acesso ao painel administrativo (admix).
- Auditoria: registra ações realizadas no sistema, permitindo a restauração dos dados.
- Grupos: controle de permissões automático por Resource (usuário sem grupo é administrador); nenhum pacote precisa de Policy ou config para isso.

### Estrutura para criação de novos recursos / pacotes

Os recursos / pacotes devem seguir as seguintes instruções:
- o nome do pacote deve estar no plural, em inglês e prefixado por `local-`. Ex.: `local-articles`
- os arquivos do pacote deve estar dentro do diretório `packages/agenciafmd/`. Ex: `packages/agenciafmd/local-articles`
- o pacote será carregado pelo composer.json, usando um repositorio customizado do tipo `path` e com a opção `symlink` habilitada.
Ex.
```json
"repositories": {
    "agenciafmd/local-articles": {
        "type": "path",
        "url": "packages/agenciafmd/local-articles",
        "options": {
            "symlink": true
        }
    }
},
```

### Convenções de código

- todo arquivo PHP começa com `declare(strict_types=1);`
- classes de Model, Resource, Schema (Form), Table, Service, ServiceProvider e Pages (Create/Edit/List) são `final class`
- quando o Model utilizar mais de um trait, declare um `use` por linha (não combine em uma única linha)
- métodos que sobrescrevem método de classe pai (ex.: `casts()`) recebem o atributo `#[Override]` (`use Override;`)
- prefira os helpers do Laravel às classes e facades: `str($value)->slug()->toString()` no lugar de `Str::slug($value)` ou `Str::of($value)`, `collect()` no lugar de `Collection::make()`, e `today()`, `now()`, `config()`, `cache()`, `resolve()`, `view()` no lugar das facades equivalentes
- passe sempre o valor para o `str()` (`str($value)`); sem argumento ele devolve um objeto não tipado
- a exceção são as macros: registre e chame macros pela classe (`Str::macro('acronym', ...)`, `Str::acronym(...)` dentro de outra macro)
- métodos que geram valor sem receber texto continuam pela classe (`Str::random(10)`, `Str::uuid()`, `Str::ulid()`), já que não há valor para passar ao `str()`
- os arquivos de config que vieram do skeleton do Laravel (`config/cache.php`, `config/session.php`, `config/database.php`, `config/horizon.php`, etc.) mantêm o `Str::` original, para facilitar a comparação nos upgrades do framework

### Estrutura de arquivos

/config/local-articles.php
/database/factories/ArticleFactory.php
/database/migrations/YYYY_MM_DD_HHMMSS_create_articles_table.php
/database/seeders/ArticleSeeder.php
/lang/pt_BR/fields.php
/lang/pt_BR.json
/src/Models/Article.php
/src/Providers/ArticleServiceProvider.php
/src/Providers/CommandServiceProvider.php
/src/Resources/Articles/Pages/CreateArticle.php
/src/Resources/Articles/Pages/EditArticle.php
/src/Resources/Articles/Pages/ListArticles.php
/src/Resources/Articles/Schemas/ArticleForm.php
/src/Resources/Articles/Tables/ArticlesTable.php
/src/Resources/Articles/ArticleResource.php
/src/Services/ArticleService.php
/src/ArticlesPlugin.php

O conteúdo detalhado de cada arquivo (exemplos de código, tabelas de campos) está nas skills, carregadas sob demanda conforme a tarefa:
- `creating-filament-admix-package` — scaffold completo de um pacote novo (config, factory, migration, seeder, lang, Model, ServiceProviders, Pages, Resource, Service, Plugin)
- `filament-admix-form-fields` — Schema/Form do Resource (layout, campos, macro `generateSlug()`)
- `filament-admix-table-conventions` — Table do Resource (columns, filters, actions, defaultSort)
- `filament-admix-permissions` — Grupos e permissões (`role_id`, `ResourcePolicy`, `PermissionRegistry`, `getExtraPermissions()` para actions próprias, testes com usuário restrito)
- `filament-admix-components` — catálogo de componentes e traits reutilizáveis do Admix (verificar antes de criar um componente novo)
