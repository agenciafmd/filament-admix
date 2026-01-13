<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Schemas\Components;

use Filament\Forms\Components\RichEditor;

final class RichEditorWithDefault
{
    public static function make(
        string $name,
        string $directory,
    ): RichEditor {
        return RichEditor::make($name)
            ->translateLabel()
            ->toolbarButtons([
                ['bold', 'italic', 'underline', 'strike', 'link'],
                ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd', 'alignJustify'],
                ['details', 'blockquote', 'bulletList', 'orderedList'],
                ['grid', 'gridDelete'],
                ['table', 'attachFiles'], // The `customBlocks` and `mergeTags` tools are also added here if those features are used.
                ['undo', 'redo'],
            ])
            ->floatingToolbars([
                'paragraph' => [
                    'bold', 'italic', 'underline', 'strike', 'link',
                ],
                'heading' => [
                    'h2', 'h3',
                ],
                'table' => [
                    'tableAddColumnBefore', 'tableAddColumnAfter', 'tableDeleteColumn',
                    'tableAddRowBefore', 'tableAddRowAfter', 'tableDeleteRow',
                    'tableMergeCells', 'tableSplitCell',
                    'tableToggleHeaderRow', 'tableToggleHeaderCell',
                    'tableDelete',
                ],
            ])
            ->resizableImages()
            ->fileAttachmentsMaxSize(1024 * 3)
            ->fileAttachmentsDirectory("media/rich-editor/{$directory}")
            ->fileAttachmentsAcceptedFileTypes(['image/*']);
    }
}
