---
name: filament-admix-components
description: 'Use this skill before adding a new form or infolist field to any admix package, to check whether a reusable Filament component already exists (image/file/video upload, rich editor, YouTube input, icon picker, password input, disabled datetime, datetime entry) or a reusable trait/concern (`RedirectBack`, `WithScopes`). Do not use it for generic non-Filament PHP code or for the field-by-field Form conventions themselves (see `filament-admix-form-fields`).'
license: MIT
metadata:
    author: agenciafmd
---

# Componentes reutilizáveis do Admix Antes de criar um novo componente de formulário, verifique se já existe um
equivalente no pacote `filament-admix`. Evite reimplementar upload de arquivo/vídeo, seletor de ícone, campo de senha,
etc. | componente | namespace | descrição | |------------+-----------+-----------| | ImageUploadWithAutomaticallyResize
| Agenciafmd\Admix\Resources\Forms\Components | upload de imagem única, com redimensionamento automático de imagem | |
ImageUploadMultipleWithAutomaticallyResize | Agenciafmd\Admix\Resources\Forms\Components | upload de múltiplas imagens,
com redimensionamento automático de imagem | | FileUploadWithDefault | Agenciafmd\Admix\Resources\Forms\Components |
upload de arquivo genérico, com nome de arquivo derivado de outro campo | | VideoUploadWithDefault |
Agenciafmd\Admix\Resources\Forms\Components | upload de vídeo (mp4), baseado em FileUploadWithDefault | |
RichEditorWithDefault | Agenciafmd\Admix\Resources\Forms\Components | editor de texto rico (rich editor) com
configuração padrão do pacote | | YouTubeInput | Agenciafmd\Admix\Resources\Forms\Components | campo de URL de vídeo do
YouTube | | IconPickerWithDefault | Agenciafmd\Admix\Resources\Forms\Components | seletor de ícone
(heroicons/tabler/frontend) | | PasswordInput | Agenciafmd\Admix\Resources\Forms\Components | campo de senha com regra
de validação e `dehydrated` condicional | | PermissionMatrix |
Agenciafmd\Admix\Resources\Forms\Components | matriz de permissões (linhas = Resources, colunas = abilities, coluna
"Outros" para `getExtraPermissions()`), usada no formulário de Grupos no campo `permissions` | | DateTimePickerDisabled | Agenciafmd\Admix\Resources\Forms\Components | campo
de data/hora desabilitado, oculto na criação (ex.: `created_at`/`updated_at` editáveis só na edição) | | DateTimeEntry |
Agenciafmd\Admix\Resources\Infolists\Components | exibição (infolist) de data/hora, usado em `created_at`/`updated_at`
no formulário | Traits e concerns reutilizáveis: | trait/concern | namespace | descrição |
|------------+-----------+-----------| | RedirectBack | Agenciafmd\Admix\Resources\Concerns | usado nas Pages de
Create/Edit para retornar à listagem após salvar | | WithScopes | Agenciafmd\Admix\Traits | fornece os scopes `isActive`
e `sort` para o Model; leia `$defaultSort` em vez de reimplementar ordenação | | PermissionRegistry |
Agenciafmd\Admix\Permissions | lista as permissões dos Resources do painel e monta as chaves `{ResourceClass}@{ability}`
(`permissionKey()`, `permissionFor()`); não reimplemente listas de permissões | | ResourcePolicy |
Agenciafmd\Admix\Policies | policy genérica registrada automaticamente para os models dos Resources; não crie Policy por
model (veja a skill `filament-admix-permissions`) |
