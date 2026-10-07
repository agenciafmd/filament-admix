---
name: filament-admix-form-fields
description: 'Use this skill whenever creating or editing the Schema/Form class of a Filament Resource inside an admix `local-{plural}` package — field layout (Grid+Group+Section), the `generateSlug()` macro, and per-field conventions for title/slug/summary/video/tags/image/images/toggles/dates/belongsToMany/repeaters. Do not use it for scaffolding a whole new package (see `creating-filament-admix-package`) or for Table/columns work (see `filament-admix-table-conventions`).'
license: MIT
metadata:
    author: agenciafmd
---

# Form do resource admix

## /src/Resources/Articles/Schemas/ArticleForm.php

Formulário do resource de articles.

- o layout externo é um `Grid::make(3)` com dois `Group`
- o primeiro `Group` (`columnSpan(2)`) contém a seção "Geral" (`__('General')`) com os campos principais do recurso
- o segundo `Group` contém a seção "Informações" (`__('Information')`) com os campos `is_active`, `star`,
  `published_at`, `created_at` e `updated_at`, caso sejam solicitados
- seções extras (relacionamentos, repeaters, imagens, vídeo) entram no primeiro `Group`, depois de "Geral", cada uma
  com `->collapsible()->columns()->columnSpan(2)`; veja [Seções extras](#seções-extras)

@boostsnippet('Example content of ArticleForm', 'php')
<?php

declare(strict_types=1);

namespace Agenciafmd\Articles\Resources\Articles\Schemas;

use Agenciafmd\Admix\Resources\Forms\Components\ImageUploadMultipleWithAutomaticallyResize;
use Agenciafmd\Admix\Resources\Forms\Components\ImageUploadWithAutomaticallyResize;
use Agenciafmd\Admix\Resources\Forms\Components\RichEditorWithDefault;
use Agenciafmd\Admix\Resources\Forms\Components\YouTubeInput;
use Agenciafmd\Admix\Resources\Infolists\Components\DateTimeEntry;
use Agenciafmd\Articles\Services\ArticleService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;

final class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        Group::make([
                            Section::make(__('General'))
                                ->schema([
                                    TextInput::make('title')
                                        ->translateLabel()
                                        ->generateSlug()
                                        ->autofocus()
                                        ->minLength(3)
                                        ->maxLength(255)
                                        ->required(),
                                    TextInput::make('slug')
                                        ->translateLabel()
                                        ->unique()
                                        ->required(),
                                    Textarea::make('summary')
                                        ->translateLabel()
                                        ->required()
                                        ->rows(5)
                                        ->columnSpanFull(),
                                    RichEditorWithDefault::make(name: 'content', directory: 'article/content')
                                        ->required()
                                        ->columnSpanFull(),
                                    ImageUploadWithAutomaticallyResize::make(
                                        name: 'image',
                                        directory: 'article/image',
                                        fileNameField: 'title',
                                    ),
                                    TagsInput::make('tags')
                                        ->translateLabel()
                                        ->suggestions(fn (): array => ArticleService::make()
                                            ->tags()
                                            ->all())
                                        ->columnSpanFull(),
                                ])
                                ->collapsible()
                                ->columns()
                                ->columnSpan(2),
                            Section::make(__('Images'))
                                ->schema([
                                    ImageUploadMultipleWithAutomaticallyResize::make(
                                        name: 'images',
                                        directory: 'article/images',
                                        fileNameField: 'title',
                                    )
                                        ->hiddenLabel(),
                                ])
                                ->collapsible()
                                ->columns()
                                ->columnSpan(2),
                            Section::make(__('Video'))
                                ->afterHeader([
                                    Text::make('Ex. https://youtu.be/aZ6cPPL3QEU')
                                        ->size(TextSize::Small),
                                ])
                                ->schema([
                                    YouTubeInput::make()
                                        ->hiddenLabel()
                                        ->belowContent('')
                                        ->columnSpanFull(),
                                ])
                                ->collapsible()
                                ->columns()
                                ->columnSpan(2),
                        ])
                            ->columnSpan(2),
                        Group::make([
                            Section::make(__('Information'))
                                ->schema([
                                    Toggle::make('is_active')
                                        ->translateLabel()
                                        ->default(true),
                                    Toggle::make('star')
                                        ->translateLabel()
                                        ->default(false),
                                    DateTimePicker::make('published_at')
                                        ->translateLabel()
                                        ->columnSpanFull(),
                                    DateTimeEntry::make('created_at'),
                                    DateTimeEntry::make('updated_at'),
                                ])
                                ->collapsible()
                                ->columns(),
                        ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
@endboostsnippet

## Campos

Utilize a relação abaixo para os campos do formulário, caso sejam solicitados.

### title ou name

Utilize o macro `->generateSlug()` (registrado em `TextInput`) para sincronizar automaticamente o campo `slug`; não
reimplemente o closure manual de `afterStateUpdated`.

@boostsnippet('Example content of title ou name field', 'php')
TextInput::make('title')
    ->translateLabel()
    ->generateSlug()
    ->autofocus()
    ->minLength(3)
    ->maxLength(255)
    ->required(),
@endboostsnippet

### slug

@boostsnippet('Example content of slug field', 'php')
TextInput::make('slug')
    ->translateLabel()
    ->unique()
    ->required(),
@endboostsnippet

### summary ou description

@boostsnippet('Example content of summary or description field', 'php')
Textarea::make('summary')
    ->translateLabel()
    ->required()
    ->rows(5)
    ->columnSpanFull(),
@endboostsnippet

### content (rich editor)

O `RichEditorWithDefault` já aplica o `->translateLabel()`; não repita.

@boostsnippet('Example content of content field', 'php')
RichEditorWithDefault::make(name: 'content', directory: 'article/content')
    ->required()
    ->columnSpanFull(),
@endboostsnippet

### video

Em Section própria, com o exemplo de URL no `afterHeader` e o campo com `->hiddenLabel()`.

@boostsnippet('Example content of video field', 'php')
Section::make(__('Video'))
    ->afterHeader([
        Text::make('Ex. https://youtu.be/aZ6cPPL3QEU')
            ->size(TextSize::Small),
    ])
    ->schema([
        YouTubeInput::make()
            ->hiddenLabel()
            ->belowContent('')
            ->columnSpanFull(),
    ])
    ->collapsible()
    ->columns()
    ->columnSpan(2),
@endboostsnippet

### tags

@boostsnippet('Example content of tags field', 'php')
TagsInput::make('tags')
    ->translateLabel()
    ->suggestions(fn (): array => ArticleService::make()
        ->tags()
        ->all())
    ->columnSpanFull(),
@endboostsnippet

### image e images

- use `ImageUploadWithAutomaticallyResize` (imagem única) e `ImageUploadMultipleWithAutomaticallyResize` (várias
  imagens). O `ImageUploadWithDefault` e o `ImageUploadMultipleWithDefault` estão **deprecated**; ao encontrá-los,
  troque pelo `*WithAutomaticallyResize` correspondente e informe `width`/`height` (ex.: imagem quadrada:
  `width: 500, height: 500`)
- no `directory`, utilize o formato `{recurso}/{campo}`, ex.: `article/image`, `article/images`
- o `fileNameField` tem default `'name'`: omita quando o recurso usa `name` e informe `'title'` quando usa `title`
- não use os macros `maxImageHeight()`/`maxImageWidth()` do `image-optimizer`: as closures deles não declaram retorno,
  o PHPStan devolve `mixed` e a cadeia de métodos quebra. As dimensões vão no `width`/`height` do componente
- `images` fica em Section própria (`__('Images')`), com `->hiddenLabel()`

@boostsnippet('Example content of image field', 'php')
ImageUploadWithAutomaticallyResize::make(name: 'image', directory: 'article/image', fileNameField: 'title'),
@endboostsnippet

@boostsnippet('Example content of a square image field', 'php')
ImageUploadWithAutomaticallyResize::make(name: 'image', directory: 'category/image', width: 500, height: 500),
@endboostsnippet

@boostsnippet('Example content of images field', 'php')
Section::make(__('Images'))
    ->schema([
        ImageUploadMultipleWithAutomaticallyResize::make(
            name: 'images',
            directory: 'article/images',
            fileNameField: 'title',
        )
            ->hiddenLabel(),
    ])
    ->collapsible()
    ->columns()
    ->columnSpan(2),
@endboostsnippet

### is_active

@boostsnippet('Example content of is_active field', 'php')
Toggle::make('is_active')
    ->translateLabel()
    ->default(true),
@endboostsnippet

### star

@boostsnippet('Example content of star field', 'php')
Toggle::make('star')
    ->translateLabel()
    ->default(false),
@endboostsnippet

### published_at

@boostsnippet('Example content of published_at field', 'php')
DateTimePicker::make('published_at')
    ->translateLabel()
    ->columnSpanFull(),
@endboostsnippet

### relacionamentos do tipo belongsToMany

Em Section própria, com o nome do relacionamento no título e o campo com `->hiddenLabel()`.

@boostsnippet('Example content of belongsToMany relationship field', 'php')
Section::make(__('Categories'))
    ->schema([
        CheckboxList::make('categories')
            ->translateLabel()
            ->hiddenLabel()
            ->relationship('categories', 'name')
            ->searchable()
            ->bulkToggleable()
            ->columns(3)
            ->gridDirection(GridDirection::Row)
            ->columnSpanFull(),
    ])
    ->collapsible()
    ->columns()
    ->columnSpan(2),
@endboostsnippet

## Seções extras

Relacionamentos, repeaters, imagens e vídeo ficam em `Section` própria dentro do primeiro `Group`, sempre com
`->collapsible()->columns()->columnSpan(2)`. O campo dentro dela usa `->hiddenLabel()`, porque o título da Section já
diz o que é.

### Repeater em tabela

- `->table([TableColumn::make(...)->markAsRequired()])` com uma coluna por campo (`->markAsRequired()` nas obrigatórias)
- `->compact()` e `->addActionLabel(__('Add ...'))`
- `->minItems(1)` quando a lista é obrigatória, ou `->defaultItems(0)` quando é opcional
- `->simple(...)` quando o item tem um campo só

@boostsnippet('Example content of a table repeater', 'php')
Section::make(__('Preparation'))
    ->schema([
        Repeater::make('instructions')
            ->translateLabel()
            ->hiddenLabel()
            ->table([
                TableColumn::make(__('Step'))
                    ->markAsRequired(),
            ])
            ->simple(
                TextInput::make('step')
                    ->label(__('Step'))
                    ->minLength(3)
                    ->maxLength(255)
                    ->required(),
            )
            ->compact()
            ->addActionLabel(__('Add step'))
            ->minItems(1)
            ->columnSpanFull(),
    ])
    ->collapsible()
    ->columns()
    ->columnSpan(2),
@endboostsnippet

### Repeater de arquivos

Cada item tem `name` e um `FileUploadWithDefault` com `->previewable(false)->downloadable()->openable()`.

@boostsnippet('Example content of a files repeater', 'php')
Section::make(__('Files'))
    ->schema([
        Repeater::make('files')
            ->translateLabel()
            ->hiddenLabel()
            ->table([
                TableColumn::make(__('Name'))
                    ->markAsRequired()
                    ->width('20rem'),
                TableColumn::make(__('File'))
                    ->markAsRequired(),
            ])
            ->schema([
                TextInput::make('name')
                    ->hiddenLabel()
                    ->minLength(3)
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->required(),
                FileUploadWithDefault::make(name: 'file', directory: 'material/file')
                    ->hiddenLabel()
                    ->previewable(false)
                    ->downloadable()
                    ->openable()
                    ->required(),
            ])
            ->compact()
            ->addActionLabel(__('Add file'))
            ->minItems(1)
            ->columnSpanFull(),
    ])
    ->collapsible()
    ->columns()
    ->columnSpan(2),
@endboostsnippet

## Campos controlados pelo config do pacote

Leia a visibilidade com `config()->boolean()` e as dimensões da imagem com `config()->integer()`, sempre com o valor
padrão; o `config()` puro devolve `mixed`, que os métodos do Filament não aceitam. Valores que vêm do formulário
(`$get('campo')`) também são `mixed`: confira o tipo antes de usar.

@boostsnippet('Example content of fields controlled by the config', 'php')
TextInput::make('subtitle')
    ->translateLabel()
    ->maxLength(255)
    ->visible(config()->boolean('local-articles.subtitle.visible', false))
    ->columnSpanFull(),
ImageUploadWithAutomaticallyResize::make(
    name: 'image',
    directory: 'article/image',
    fileNameField: 'title',
    width: config()->integer('local-articles.image.width', 1920),
    height: config()->integer('local-articles.image.height', 1080),
)
    ->visible(config()->boolean('local-articles.image.visible', true)),
@endboostsnippet
