---
name: filament-admix-permissions
description: 'Use this skill whenever working with access control in the admix panel — Grupos (roles), `role_id`, administrators, the generic `ResourcePolicy`, the `PermissionRegistry`, custom record/header actions that need their own permission (`getExtraPermissions()` + `->authorize()`), a package-specific Policy, or tests that act as a restricted user. Do not use it for the Form/Table layout itself (see `filament-admix-form-fields` / `filament-admix-table-conventions`) or for scaffolding a whole new package (see `creating-filament-admix-package`).'
license: MIT
metadata:
    author: agenciafmd
---

# Grupos e permissões do Admix O controle de acesso do painel é **automático**: um pacote novo não precisa de config,
Policy nem AuthServiceProvider. Basta o Plugin estar em `config/filament-admix.php` (`plugins`): as permissões de cada
Resource aparecem no formulário de Grupos (`RoleResource`) e já passam a valer em navegação, páginas, actions e bulk
actions. ## Modelo de dados - `roles`: `is_active`, `name`, `permissions` (json, lista de chaves de permissão),
timestamps e softDeletes — model `Agenciafmd\Admix\Models\Role` - `users.role_id`: nullable, FK para `roles` com
`restrictOnDelete()` — um grupo por usuário - **`role_id` nulo = Administrador** (acesso total). Use `$user->isAdmin()`;
nunca compare `role_id` manualmente - grupo inativo ou excluído (soft delete) = **sem acesso nenhum** (nunca vira
administrador) - checagem: `$user->hasPermission(string $permission): bool` ## Chave de permissão O formato é
`"{ResourceClass}@{ability}"`, gerado por `PermissionRegistry::permissionKey($resource, $ability)`. Ex.:
`Agenciafmd\Articles\Resources\Articles\ArticleResource@update`. As abilities padrão são agrupadas: cada checkbox cobre
vários métodos de policy chamados pelo Filament (`PermissionRegistry::ABILITY_MAP`). | ability (checkbox) | métodos
cobertos | aparece quando | |--------------------|------------------|----------------| | view (visualizar) | viewAny,
view | sempre | | create (criar) | create, replicate | sempre | | update (atualizar) | update, reorder | sempre | |
delete (deletar) | delete, deleteAny, forceDelete, forceDeleteAny | sempre | | restore (restaurar) | restore, restoreAny
| o Model usa `SoftDeletes` | | audit (auditoria) | gate `audit` (e `restoreAudit` junto com update) | o Resource tem
`AuditsRelationManager` em `getRelations()` | O rótulo do grupo no formulário é `navigationGroup » pluralModelLabel` do
Resource. Traduza os labels no `pt_BR.json` do pacote. O formulário de Grupos usa o campo `PermissionMatrix` (`Agenciafmd\Admix\Resources\Forms\Components`)
no atributo `permissions`: uma linha por Resource, uma coluna por ability padrão e a coluna "Outros" com as abilities
extras; ao salvar, chaves de Resources que não estão mais no painel são descartadas. ## Como funciona -
`Agenciafmd\Admix\Permissions\PermissionRegistry` (singleton) lê `Filament::getPanel('admix')->getResources()` e monta a
lista de permissões (`groups()`), o mapa model → resource e a chave de cada ability (`permissionFor()`) -
`Agenciafmd\Admix\Policies\ResourcePolicy` é registrada no `FilamentPanelProvider::bootPermissions()` para o model de
cada Resource **que ainda não tem policy**. A decisão acontece no `before()`; os métodos existem explicitamente porque o
Filament só consulta a policy quando `method_exists()` — `__call` não funciona - um `Gate::before` global resolve as
abilities extras (`getExtraPermissions()`) - colunas editáveis (`ToggleColumn`, `SelectColumn`, `TextInputColumn`,
`CheckboxColumn`) ignoram policies no Filament; o admix as desabilita globalmente com `Gate::denies('update', $record)`.
Se a tabela chamar `->disabled()` na coluna, isso sobrescreve o padrão — inclua a checagem de `update` na sua condição -
somente administradores alteram/excluem administradores e deixam alguém sem grupo; ninguém troca o próprio grupo ##
Permissões extras: `getExtraPermissions()` Quando o Resource tem uma action própria (enviar, aprovar, exportar...),
declare a ability no Resource com o método estático `getExtraPermissions()`, que retorna `['ability' => 'label']`. A
ability vira um checkbox a mais no grupo do Resource, com a chave `{ResourceClass}@{ability}`.

@boostsnippet('Example of getExtraPermissions in PostalResource', 'php')
/**
 * @return array{send: string}
 */
public static function getExtraPermissions(): array
{
    return [
        'send' => __('send'),
    ];
}
@endboostsnippet

Na action, proteja com `->authorize('ability')` — o Filament checa a ability contra o record (ou o model, em header
actions) e esconde/bloqueia a action sem permissão. Declarar a ability sem o `authorize()` não protege nada.

@boostsnippet('Example of an action protected by an extra permission', 'php')
    Action::make('send') ->translateLabel() ->authorize('send') ->icon(Heroicon::PaperAirplane) ->link()
    ->action(function (Postal $record): void { // ... }),
@endboostsnippet

Regras: - o nome da ability é camelCase, em inglês, e **não** pode repetir uma ability padrão (`view`, `create`,
`update`, `delete`, `restore`, `audit`) nem um método de policy (`viewAny`, `deleteAny`...) - o label é traduzido com
`__()` em minúsculas (ex.: `"send": "enviar"` no `lang/pt_BR.json` do pacote), seguindo o padrão "pode {label}" das
abilities padrão - a action continua usando `->visible()`/`->hidden()` para regras de negócio; `->authorize()` é só para
permissão ## Policy própria Se o pacote precisar de uma regra que não cabe nas abilities (ex.: só o autor edita), crie a
Policy e registre com `Gate::policy()` no ServiceProvider do pacote. O admix **não** sobrescreve policies existentes —
nesse caso a Policy do pacote é responsável por todas as checagens (inclusive administrador e `hasPermission()`), então
prefira as abilities extras sempre que possível. ## Testes - autentique no guard `admix-web` e defina o painel:
`actingAs($user, 'admix-web'); Filament::setCurrentPanel('admix');` - recarregue o usuário criado pela factory
(`->fresh()`): o strict mode lança `MissingAttributeException` para colunas não preenchidas (ex.: `avatar`) ao
renderizar o painel - use os states da `RoleFactory`: `->withPermissions([...])` e `->inactive()`; sem `role_id` o
usuário é administrador

@boostsnippet('Example of a restricted user test', 'php')
    it('grants only the abilities checked in the role', function (): void { $role = Role::factory() ->withPermissions([
    PermissionRegistry::permissionKey(ArticleResource::class, 'view'), ]) ->create(); $user =
    User::factory()->create(['role_id' => $role->getKey()])->fresh(); actingAs($user, 'admix-web');
    Filament::setCurrentPanel('admix'); get(ArticleResource::getUrl('index'))->assertOk();
    get(ArticleResource::getUrl('create'))->assertForbidden(); Livewire::test(ListArticles::class)
    ->assertActionHidden(CreateAction::class); });
@endboostsnippet
