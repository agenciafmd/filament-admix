<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Tests\Feature\Forms;

use Agenciafmd\Admix\Models\User;
use Agenciafmd\Admix\Resources\Auth\Pages\EditProfile;
use Agenciafmd\Support\Jobs\OptimizeImage;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;
use Livewire\Component;
use Livewire\Livewire;
use Tests\TestCase;

use function Pest\Laravel\actingAs;

pest()->extend(TestCase::class)->use(RefreshDatabase::class);

/**
 * PNG gerado pelo Imagick, já que o `UploadedFile::fake()->image()` depende do GD.
 */
function pngImage(int $width = 10, int $height = 10): string
{
    return (string) new ImageManager(new Driver)
        ->createImage($width, $height)
        ->encodeUsingFileExtension('png');
}

/**
 * Formulário com os componentes do Filament sem nenhuma configuração do admix.
 */
function uploadForm(): Component
{
    return new class extends Component implements HasSchemas
    {
        use InteractsWithSchemas;

        /** @var array<string, mixed> */
        public ?array $data = [];

        public function mount(): void
        {
            $this->form->fill();
        }

        public function form(Schema $schema): Schema
        {
            return $schema
                ->components([
                    FileUpload::make('file')
                        ->disk('public')
                        ->directory('uploads'),
                    RichEditor::make('content')
                        ->fileAttachmentsDisk('public')
                        ->fileAttachmentsDirectory('attachments'),
                ])
                ->statePath('data');
        }

        public function save(): void
        {
            $this->form->getState();
        }

        public function render(): string
        {
            return '<div>{{ $this->form }}</div>';
        }
    };
}

it('queues the optimization of the file uploaded by a FileUpload', function (): void {
    Storage::fake('public');
    Queue::fake([OptimizeImage::class]);

    $livewire = Livewire::test(uploadForm())
        ->set('data.file', UploadedFile::fake()->createWithContent('photo.png', pngImage()))
        ->call('save');

    $path = collect($livewire->get('data.file'))->sole();

    Storage::disk('public')->assertExists($path);
    Queue::assertPushedOn('low', OptimizeImage::class, static fn (OptimizeImage $job): bool => $job->disk === 'public' && $job->path === $path);
});

it('queues the optimization of the file attached to a RichEditor', function (): void {
    Storage::fake('public');
    Queue::fake([OptimizeImage::class]);

    Livewire::test(uploadForm())
        ->set('componentFileAttachments.data.content', UploadedFile::fake()->createWithContent('photo.png', pngImage()))
        ->call('callSchemaComponentMethod', 'form.content', 'saveUploadedFileAttachmentAndGetUrl');

    $path = collect(Storage::disk('public')->allFiles('attachments'))->sole();

    Queue::assertPushedOn('low', OptimizeImage::class, static fn (OptimizeImage $job): bool => $job->disk === 'public' && $job->path === $path);
});

it('queues the optimization of the image resized by the admix component', function (): void {
    Storage::fake('public');
    config()->set('filament.default_filesystem_disk', 'public');
    Queue::fake([OptimizeImage::class]);

    $user = User::factory()->create(['email' => 'profile@gmail.com'])->fresh();
    actingAs($user, 'admix-web');
    Filament::setCurrentPanel('admix');

    Livewire::test(EditProfile::class)
        ->fillForm(['avatar' => UploadedFile::fake()->createWithContent('avatar.png', pngImage(600, 600))])
        ->call('save')
        ->assertHasNoFormErrors();

    $path = $user->fresh()->avatar;

    Storage::disk('public')->assertExists($path);
    Queue::assertPushed(OptimizeImage::class, 1);
    Queue::assertPushedOn('low', OptimizeImage::class, static fn (OptimizeImage $job): bool => $job->disk === 'public' && $job->path === $path);
});

it('does not queue the optimization when it is disabled in the config', function (): void {
    Storage::fake('public');
    Queue::fake([OptimizeImage::class]);
    config()->set('filament-admix.fileupload_image_optimize', false);
    config()->set('filament-admix.richeditor_image_optimize', false);

    Livewire::test(uploadForm())
        ->set('data.file', UploadedFile::fake()->createWithContent('photo.png', pngImage()))
        ->call('save')
        ->set('componentFileAttachments.data.content', UploadedFile::fake()->createWithContent('attachment.png', pngImage()))
        ->call('callSchemaComponentMethod', 'form.content', 'saveUploadedFileAttachmentAndGetUrl');

    Storage::disk('public')->assertCount('uploads', 1);
    Storage::disk('public')->assertCount('attachments', 1);
    Queue::assertNotPushed(OptimizeImage::class);
});

it('does not queue the optimization of the image resized by the admix component when it is disabled in the config', function (): void {
    Storage::fake('public');
    config()->set('filament.default_filesystem_disk', 'public');
    config()->set('filament-admix.fileupload_image_optimize', false);
    Queue::fake([OptimizeImage::class]);

    $user = User::factory()->create(['email' => 'profile@gmail.com'])->fresh();
    actingAs($user, 'admix-web');
    Filament::setCurrentPanel('admix');

    Livewire::test(EditProfile::class)
        ->fillForm(['avatar' => UploadedFile::fake()->createWithContent('avatar.png', pngImage(600, 600))])
        ->call('save')
        ->assertHasNoFormErrors();

    Storage::disk('public')->assertExists($user->fresh()->avatar);
    Queue::assertNotPushed(OptimizeImage::class);
});

it('queues the optimization when the config does not have the options', function (): void {
    Storage::fake('public');
    Queue::fake([OptimizeImage::class]);
    config()->set('filament-admix', collect(config()->array('filament-admix'))
        ->except(['fileupload_image_optimize', 'richeditor_image_optimize'])
        ->all());

    Livewire::test(uploadForm())
        ->set('data.file', UploadedFile::fake()->createWithContent('photo.png', pngImage()))
        ->call('save')
        ->set('componentFileAttachments.data.content', UploadedFile::fake()->createWithContent('attachment.png', pngImage()))
        ->call('callSchemaComponentMethod', 'form.content', 'saveUploadedFileAttachmentAndGetUrl');

    Queue::assertPushed(OptimizeImage::class, 2);
});
