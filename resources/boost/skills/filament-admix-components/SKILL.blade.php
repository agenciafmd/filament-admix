---
name: filament-admix-components
description: 'Use this skill before adding a new form or infolist field to any admix package, to check whether a reusable Filament component already exists (image/file/video upload, rich editor, YouTube input, icon picker, password input, disabled datetime, datetime entry) or a reusable trait/concern (`RedirectBack`, `WithScopes`). Do not use it for generic non-Filament PHP code or for the field-by-field Form conventions themselves (see `filament-admix-form-fields`).'
license: MIT
metadata:
    author: agenciafmd
---

# Componentes reutilizáveis do Admix

Antes de criar um novo componente de formulário, verifique se já existe um equivalente no pacote `filament-admix`.
Evite reimplementar upload de arquivo/vídeo, seletor de ícone, campo de senha etc.

## Componentes

| componente                                 | namespace                                       | descrição                                                                                                                                                       |
|--------------------------------------------|-------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------|
| ImageUploadWithAutomaticallyResize         | Agenciafmd\Admix\Resources\Forms\Components     | upload de imagem única, redimensionada e cortada para `width`/`height`                                                                                          |
| ImageUploadMultipleWithAutomaticallyResize | Agenciafmd\Admix\Resources\Forms\Components     | upload de múltiplas imagens, redimensionadas e cortadas para `width`/`height`                                                                                   |
| ImageUploadWithDefault                     | Agenciafmd\Admix\Resources\Forms\Components     | **deprecated**: use `ImageUploadWithAutomaticallyResize`                                                                                                        |
| ImageUploadMultipleWithDefault             | Agenciafmd\Admix\Resources\Forms\Components     | **deprecated**: use `ImageUploadMultipleWithAutomaticallyResize`                                                                                                |
| FileUploadWithDefault                      | Agenciafmd\Admix\Resources\Forms\Components     | upload de arquivo genérico, com nome de arquivo derivado de outro campo (`fileNameField`, default `'name'`)                                                     |
| VideoUploadWithDefault                     | Agenciafmd\Admix\Resources\Forms\Components     | upload de vídeo (mp4), baseado em `FileUploadWithDefault`                                                                                                       |
| RichEditorWithDefault                      | Agenciafmd\Admix\Resources\Forms\Components     | editor de texto rico com a configuração padrão do pacote; já aplica `->translateLabel()`                                                                        |
| YouTubeInput                               | Agenciafmd\Admix\Resources\Forms\Components     | campo de URL de vídeo do YouTube                                                                                                                                |
| IconPickerWithDefault                      | Agenciafmd\Admix\Resources\Forms\Components     | seletor de ícone (sets `heroicons`, `tabler` e `frontend`); veja o aviso abaixo                                                                                 |
| PasswordInput                              | Agenciafmd\Admix\Resources\Forms\Components     | campo de senha com regra de validação e `dehydrated` condicional                                                                                                |
| PermissionMatrix                           | Agenciafmd\Admix\Resources\Forms\Components     | matriz de permissões (linhas = Resources, colunas = abilities, coluna "Outros" para `getExtraPermissions()`), usada no formulário de Grupos no campo `permissions` |
| DateTimePickerDisabled                     | Agenciafmd\Admix\Resources\Forms\Components     | campo de data/hora desabilitado, oculto na criação (ex.: `created_at`/`updated_at` editáveis só na edição)                                                      |
| DateTimeEntry                              | Agenciafmd\Admix\Resources\Infolists\Components | exibição (infolist) de data/hora, usado em `created_at`/`updated_at` no formulário                                                                              |

`IconPickerWithDefault` declara o set `frontend`, que só existe em projetos que registram esse set no blade-icons. Sem
ele, o ícone salvo não resolve no `svg()` do blade-icons. Confira se o projeto tem o set antes de usar o componente; se não
tiver, use o `IconPicker` do Guava com `->sets(['heroicons', 'tabler'])`.

## Traits e concerns

| trait/concern      | namespace                            | descrição                                                                                                                                                                  |
|--------------------|--------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| RedirectBack       | Agenciafmd\Admix\Resources\Concerns  | usado nas Pages de Create/Edit para retornar à listagem após salvar; não use em Resource aninhado, que não tem página `index`                                              |
| WithScopes         | Agenciafmd\Admix\Traits              | fornece os scopes `isActive` e `sort` para o Model; o `sort` lê o `$defaultSort`, que é obrigatório no Model, em vez de reimplementar ordenação                            |
| PermissionRegistry | Agenciafmd\Admix\Permissions         | lista as permissões dos Resources do painel e monta as chaves `{ResourceClass}@{ability}` (`permissionKey()`, `permissionFor()`); não reimplemente listas de permissões    |
| ResourcePolicy     | Agenciafmd\Admix\Policies            | policy genérica registrada automaticamente para os models dos Resources; não crie Policy por model (veja a skill `filament-admix-permissions`)                             |

## Extensões do PHPStan

O admix e o `laravel-support` declaram extensões em `extra.phpstan.includes` do `composer.json`, que só são carregadas
pelo `phpstan/extension-installer`. Ele é pré-requisito: confira se está no `require-dev` do projeto (e liberado em
`config.allow-plugins`). Sem ele, aparecem centenas de erros de macros, de scopes do `WithScopes` e de métodos do Faker.
Com ele instalado, não anote nem ignore os erros que as extensões resolvem.

| extensão                          | pacote          | o que o PHPStan passa a entender                                                                                                     |
|-----------------------------------|-----------------|--------------------------------------------------------------------------------------------------------------------------------------|
| FilamentMacroMethodsExtension     | filament-admix  | macros registrados com o `Macroable` do Filament, como `TextInput::generateSlug()`, `TextColumn::limitWithTooltip()` e o `optimize()` do `image-optimizer` |
| WithScopesBuilderMethodsExtension | filament-admix  | os scopes do `WithScopes` num `Builder` sem model definido, como o `$query->sort()` do `defaultSort` das Tables                      |
| FakerProviderMethodsExtension     | laravel-support | os métodos do nosso Faker `Provider`, como `localImage()`, `htmlParagraphs()`, `tags()` e `youtubeRandomUri()`                       |

Um macro novo do Filament, registrado no `boot()` de um ServiceProvider, é reconhecido automaticamente. Dentro da closure
do macro, o `$this` já é o componente, então não é preciso anotar o tipo do `$this`.

@verbatim
O `FilamentMacroMethodsExtension` monta o tipo de retorno a partir da closure do macro em runtime e tem precedência
sobre os `@method` dos stubs. Por isso:
@endverbatim

- todo macro novo declara o tipo de retorno na closure (`function (): static`)
- macro de terceiro cuja closure não declara retorno vira `mixed` e quebra a cadeia de métodos, mesmo que o pacote
  dono traga stub (ex.: `maxImageHeight()`/`maxImageWidth()` do `image-optimizer`). Não use esses macros nos forms;
  prefira o parâmetro equivalente do componente do admix
