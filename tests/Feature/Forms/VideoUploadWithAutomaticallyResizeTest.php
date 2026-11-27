<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Tests\Feature\Forms;

use Agenciafmd\Admix\Resources\Forms\Components\VideoUploadWithAutomaticallyResize;
use Agenciafmd\Support\Jobs\OptimizeVideo;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Livewire;
use Tests\TestCase;

pest()->extend(TestCase::class);

/**
 * Formulário com um vídeo de dimensões fixas (mudo, o padrão) e outro com dimensões vindas de outro campo e com áudio.
 */
function videoForm(): Component
{
    return new class extends Component implements HasSchemas
    {
        use InteractsWithSchemas;

        /** @var array<string, mixed> */
        public ?array $data = [];

        public function mount(): void
        {
            $this->form->fill(['size' => 480]);
        }

        public function form(Schema $schema): Schema
        {
            return $schema
                ->components([
                    VideoUploadWithAutomaticallyResize::make(name: 'video', directory: 'banner/video', width: 640, height: 360)
                        ->disk('public'),
                    VideoUploadWithAutomaticallyResize::make(
                        name: 'square_video',
                        directory: 'banner/square_video',
                        width: static fn (Get $get): mixed => $get('size'),
                        height: static fn (Get $get): mixed => $get('size'),
                        mute: false,
                    )
                        ->disk('public'),
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

it('shows the dimensions of the video in the label', function (): void {
    Livewire::test(videoForm())
        ->assertSee('Max. 640x360')
        ->assertSee('Max. 480x480');
});

it('queues the conversion of the uploaded video with the dimensions and the mute of the field', function (): void {
    Storage::fake('public');
    Queue::fake([OptimizeVideo::class]);

    $livewire = Livewire::test(videoForm())
        ->set('data.video', UploadedFile::fake()->create('video.mp4', 100, 'video/mp4'))
        ->set('data.square_video', UploadedFile::fake()->create('square.mp4', 100, 'video/mp4'))
        ->call('save')
        ->assertHasNoErrors();

    $videoPath = collect($livewire->get('data.video'))->sole();
    $squareVideoPath = collect($livewire->get('data.square_video'))->sole();

    Storage::disk('public')->assertExists([$videoPath, $squareVideoPath]);
    Queue::assertPushedOn('low', OptimizeVideo::class, static fn (OptimizeVideo $job): bool => $job->disk === 'public'
        && $job->path === $videoPath
        && [$job->width, $job->height] === [640, 360]
        && $job->mute);
    Queue::assertPushed(OptimizeVideo::class, static fn (OptimizeVideo $job): bool => $job->path === $squareVideoPath
        && [$job->width, $job->height] === [480, 480]
        && ! $job->mute);
});

it('does not queue the conversion when it is disabled in the config', function (): void {
    Storage::fake('public');
    Queue::fake([OptimizeVideo::class]);
    config()->set('filament-admix.fileupload_video_optimize', false);

    $livewire = Livewire::test(videoForm())
        ->set('data.video', UploadedFile::fake()->create('video.mp4', 100, 'video/mp4'))
        ->call('save')
        ->assertHasNoErrors();

    Storage::disk('public')->assertExists(collect($livewire->get('data.video'))->sole());
    Queue::assertNotPushed(OptimizeVideo::class);
});

it('queues the conversion when the config does not have the option', function (): void {
    Storage::fake('public');
    Queue::fake([OptimizeVideo::class]);
    config()->set('filament-admix', collect(config()->array('filament-admix'))
        ->except('fileupload_video_optimize')
        ->all());

    Livewire::test(videoForm())
        ->set('data.video', UploadedFile::fake()->create('video.mp4', 100, 'video/mp4'))
        ->call('save')
        ->assertHasNoErrors();

    Queue::assertPushed(OptimizeVideo::class, 1);
});
