<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

#[Description('Remove the media files that are not linked to any record')]
#[Signature('media:prune
        {--dry-run : List the files that would be removed, without removing them.}')]
final class MediaPrune extends Command
{
    private const string DIRECTORY = 'media';

    /**
     * Pastas dentro de `media` que não seguem o padrão `{model}/{field}`.
     *
     * @var list<string>
     */
    private const array IGNORED_DIRECTORIES = ['rich-editor'];

    public function handle(): int
    {
        $disk = Storage::disk(config()->string('filament.default_filesystem_disk', 'public'));
        $isDryRun = (bool) $this->option('dry-run');
        $removed = 0;

        foreach ($disk->directories(self::DIRECTORY) as $modelDirectory) {
            if (in_array(basename($modelDirectory), self::IGNORED_DIRECTORIES, true)) {
                continue;
            }

            foreach ($disk->directories($modelDirectory) as $fieldDirectory) {
                $linkedValues = $this->linkedValues(basename($modelDirectory), basename($fieldDirectory));

                if ($linkedValues === null) {
                    $this->components->warn("Ignorando {$fieldDirectory}: tabela ou coluna não encontrada.");

                    continue;
                }

                $removed += $this->prune($disk, $fieldDirectory, $linkedValues, $isDryRun);
            }
        }

        $this->components->info($isDryRun
            ? "{$removed} arquivo(s) seriam removidos."
            : "{$removed} arquivo(s) removidos com sucesso.");

        return self::SUCCESS;
    }

    /**
     * Junta todos os valores da coluna em um único texto, cobrindo caminhos simples, listas em JSON e caminhos com barras escapadas.
     */
    private function linkedValues(string $model, string $field): ?string
    {
        $table = str($model)->replace('-', '_')->plural()->toString();

        if (! Schema::hasColumn($table, $field)) {
            return null;
        }

        return DB::table($table)
            ->whereNotNull($field)
            ->pluck($field)
            ->map(static fn (mixed $value): string => is_scalar($value) ? (string) $value : '')
            ->implode("\n");
    }

    private function prune(Filesystem $disk, string $fieldDirectory, string $linkedValues, bool $isDryRun): int
    {
        $removed = 0;

        foreach ($disk->allFiles($fieldDirectory) as $file) {
            if (str_contains($linkedValues, basename($file))) {
                continue;
            }

            $this->components->twoColumnDetail($file, $isDryRun ? 'removível' : 'removido');

            if (! $isDryRun) {
                $disk->delete($file);
            }

            $removed++;
        }

        return $removed;
    }
}
