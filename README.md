# Filament – Admix

[![Downloads](https://img.shields.io/packagist/dt/agenciafmd/filament-admix.svg?style=flat-square)](https://packagist.org/packages/agenciafmd/filament-admix)
[![Licença](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

Núcleo do painel administrativo (Admix) da Agência F&MD, baseado em Filament. Entrega o painel em `/admix` com usuários, grupos de permissões e auditoria, além de componentes reutilizáveis para os pacotes `filament-*`.

## Requisitos

- PHP ^8.4
- Laravel ^12.0 | ^13.0
- Filament ^5.0
- agenciafmd/laravel-support v12.x-dev | dev-master

## Instalação

1. Instale o pacote via Composer:

```bash
composer require agenciafmd/filament-admix
```

2. Configure o guard, o provider e o broker de senha usados pelo painel (`admix-web` e `admix-users`) em `config/auth.php`:

```php
'guards' => [
    'admix-web' => [
        'driver' => 'session',
        'provider' => 'admix-users',
    ],
],

'providers' => [
    'admix-users' => [
        'driver' => 'eloquent',
        'model' => Agenciafmd\Admix\Models\User::class,
    ],
],

'passwords' => [
    'admix-users' => [
        'provider' => 'admix-users',
        'table' => 'password_reset_tokens',
        'expire' => 60,
        'throttle' => 60,
    ],
],
```

3. Publique o tema (CSS, logo, favicon e `vite.admix.config.js`), que o painel usa para renderizar a marca, e faça o build (veja [Customizando o tema](#customizando-o-tema)):

```bash
php artisan vendor:publish --tag=filament-admix:theme
```

4. Execute as migrações (ajustes na tabela `users`, grupos, notificações, importações/exportações e auditoria):

```bash
php artisan migrate
```

5. Crie o primeiro usuário do painel:

```bash
php artisan admix:create-user
```

6. (Opcional) Publique o arquivo de configuração:

```bash
php artisan vendor:publish --tag=filament-admix:config
```

## Features

- **Usuários**: CRUD dos usuários com acesso ao painel (menu **Usuários**), com exportação e edição de perfil.
- **Auditoria**: registra as ações realizadas nos models auditáveis e permite consultar e restaurar os dados pelo relation manager `Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager`.
- **Grupos**: controle de permissões automático por Resource (menu **Grupos**). Usuário sem grupo é administrador e tem acesso total. Todo Resource registrado no painel entra na matriz de permissões (visualizar, criar, editar, excluir e, quando se aplica, restaurar e auditoria) sem precisar de Policy nem de config. Permissões próprias são declaradas no Resource com `getExtraPermissions()`, no formato `['ability' => 'rótulo']`.
- **Login automático**: no ambiente `local`, o painel faz login sozinho (veja `auto_login` em [Configuração](#configuração)).
- **Aviso de homologação**: fora de `production`, o painel exibe uma faixa avisando que é o ambiente de homologação.

### Comandos e agendamentos

| Comando | Descrição | Agendamento |
|---|---|---|
| `admix:create-user` | Cria (ou atualiza, pelo e-mail) um usuário do painel. | — |
| `audit:prune {days=180}` | Remove auditorias mais antigas que `days`. | diário, 03h |
| `notifications:clear {days?}` | Remove notificações mais antigas que `days`. | diário, 04h (90 dias) |

Também são agendados o `auth:clear-resets` (a cada 15 minutos), o `clockwork:clean` (diário, 04h) e o `model:prune` de usuários e grupos (diário, 03h). Os minutos dos horários vêm de `schedule.minutes`.

## Registrando plugins

Os pacotes `filament-*` são ativados pela chave `plugins` em `config/filament-admix.php`. Cada item é a classe de um `Filament\Contracts\Plugin`; classes inexistentes são ignoradas.

```php
use Agenciafmd\Articles\ArticlesPlugin;

return [
    'plugins' => [
        ArticlesPlugin::class,
    ],
];
```

## Configuração

Arquivo: `config/filament-admix.php`

```php
use Filament\Support\Colors\Color;

return [
    'schedule' => [
        'minutes' => sprintf('%02d', abs(crc32((string) env('APP_NAME', 'FMD'))) % 60),
    ],
    'timestamp' => [
        'format' => env('ADMIX_TIMESTAMP_FORMAT', 'd/m/Y H:i:s'),
    ],
    'auto_login' => env('ADMIX_AUTO_LOGIN', true),
    'plugins' => [
        //
    ],
    'colors' => [
        'primary' => Color::Slate,
    ],
    'font' => 'Ubuntu Sans',
];
```

| Chave | Padrão | Descrição |
|---|---|---|
| `schedule.minutes` | derivado do `APP_NAME` | Minuto (`00`–`59`) usado nos agendamentos do Admix e dos pacotes, para não concentrar tudo no mesmo horário entre projetos. |
| `timestamp.format` | `d/m/Y H:i:s` | Formato de data/hora exibido no painel. |
| `auto_login` | `true` | Só em `local`: um e-mail faz login com aquele usuário; `true` faz login com o primeiro administrador ativo; `false` desliga. |
| `plugins` | `[]` | Plugins registrados no painel. |
| `colors` | `['primary' => Color::Slate]` | Cores do painel. |
| `font` | `Ubuntu Sans` | Fonte do painel. |

Variáveis de ambiente:

```dotenv
ADMIX_TIMESTAMP_FORMAT="d/m/Y H:i:s"
ADMIX_AUTO_LOGIN=true
```

## Componentes e traits reutilizáveis

Antes de criar um componente novo num pacote, verifique se já existe um equivalente no Admix.

Componentes de formulário (`Agenciafmd\Admix\Resources\Forms\Components`):

| Componente | Descrição |
|---|---|
| `ImageUploadWithAutomaticallyResize` | Upload de imagem única, com redimensionamento automático. |
| `ImageUploadMultipleWithAutomaticallyResize` | Upload de múltiplas imagens, com redimensionamento automático. |
| `FileUploadWithDefault` | Upload de arquivo genérico, com nome derivado de outro campo. |
| `VideoUploadWithDefault` | Upload de vídeo (mp4). |
| `RichEditorWithDefault` | Editor de texto rico com a configuração padrão. |
| `YouTubeInput` | Campo de URL de vídeo do YouTube. |
| `IconPickerWithDefault` | Seletor de ícone (heroicons/tabler/frontend). |
| `PasswordInput` | Campo de senha com regra de validação e `dehydrated` condicional. |
| `PermissionMatrix` | Matriz de permissões do formulário de Grupos. |
| `DateTimePickerDisabled` | Data/hora desabilitada, oculta na criação. |

`ImageUploadWithDefault` e `ImageUploadMultipleWithDefault` estão depreciados; use as versões `WithAutomaticallyResize`.

Outros:

| Classe | Namespace | Descrição |
|---|---|---|
| `DateTimeEntry` | `Agenciafmd\Admix\Resources\Infolists\Components` | Exibição de data/hora (ex.: `created_at`/`updated_at`). |
| `RatingColumn` | `Agenciafmd\Admix\Resources\Table\Columns` | Coluna de avaliação para tabelas. |
| `RedirectBack` | `Agenciafmd\Admix\Resources\Concerns` | Nas Pages de Create/Edit, volta para a listagem após salvar. |
| `WithScopes` | `Agenciafmd\Admix\Traits` | Scopes `isActive` e `sort` para o Model (o `sort` lê o `$defaultSort`). |
| `DefaultNotificationAndFileName` | `Agenciafmd\Admix\Exports\Concerns` | Notificação e nome de arquivo padrão para exportações. |
| `PermissionRegistry` | `Agenciafmd\Admix\Permissions` | Lista as permissões dos Resources e monta as chaves `{ResourceClass}@{ability}`. |
| `ResourcePolicy` | `Agenciafmd\Admix\Policies` | Policy genérica registrada automaticamente para os models dos Resources. |

O painel também registra os macros `TextInput::generateSlug()` e `TextColumn::limitWithTooltip()`.

## Customizando o tema

Publique os arquivos do tema:

```bash
php artisan vendor:publish --tag=filament-admix:theme
```

Os arquivos publicados serão:

```text
/resources/filament/filament-admix/css/theme.css
/resources/filament/filament-admix/svg/favicon.svg
/resources/filament/filament-admix/svg/logo.svg
/lang/vendor/filament-icon-picker/pt_BR/icon-picker.php
/vite.admix.config.js
```

O build gera os assets em `/public/filament-admix` (incluindo o `manifest.json`). Se for preciso buildar o tema ou customizar algum plugin, adicione no `package.json`:

```json
"scripts": {
    "build:admix": "vite build --config vite.admix.config.js"
}
```

Não esqueça de remover a publicação dos assets em `post-update-cmd`, para que as customizações não sejam sobrescritas.

## Atualização

Para manter os assets atualizados, adicione o comando `@php artisan vendor:publish --tag=filament-admix:theme --ansi --force` ao seu `post-update-cmd` no `composer.json` do seu projeto.

## Licença

Este pacote é software livre e está disponível nos termos da licença MIT.
