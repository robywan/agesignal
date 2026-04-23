<?php

use App\Enums\LabTestDocumentStatus;
use App\Jobs\ProcessDocumentJob;
use App\Models\LabTestDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('can render the new document page', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('documents.create'))
        ->assertSuccessful()
        ->assertSee('Nuovo referto');
});

it('uploads a pdf and dispatches document processing', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Storage::fake('local');
    Queue::fake();

    $file = UploadedFile::fake()->create('emocromo.pdf', 256, 'application/pdf');

    Livewire::test('pages::documents.create')
        ->set('testDate', '2026-04-15')
        ->set('documentFile', $file)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('documents.index', absolute: false));

    $document = LabTestDocument::query()->with('media')->sole();

    expect($document->owner_user_id)->toBe($user->id)
        ->and($document->status)->toBe(LabTestDocumentStatus::Pending)
        ->and($document->test_date?->format('Y-m-d'))->toBe('2026-04-15')
        ->and($document->getFirstMedia('files'))->not->toBeNull()
        ->and($document->getFirstMedia('files')?->file_name)->toBe('emocromo.pdf');

    Queue::assertPushed(ProcessDocumentJob::class, 1);
});

it('shows only the authenticated users uploaded documents', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Storage::fake('local');

    $otherUser = User::factory()->create();

    $ownedDocument = $user->labTestDocuments()->create([
        'test_date' => '2026-04-15',
        'status' => LabTestDocumentStatus::Parsed,
    ]);

    $ownedDocument
        ->addMedia(UploadedFile::fake()->create('owned-report.pdf', 64, 'application/pdf'))
        ->usingFileName('owned-report.pdf')
        ->toMediaCollection('files');

    $otherDocument = $otherUser->labTestDocuments()->create([
        'test_date' => '2026-04-14',
        'status' => LabTestDocumentStatus::Extracted,
    ]);

    $otherDocument
        ->addMedia(UploadedFile::fake()->create('other-report.pdf', 64, 'application/pdf'))
        ->usingFileName('other-report.pdf')
        ->toMediaCollection('files');

    $this->get(route('documents.index'))
        ->assertSuccessful()
        ->assertSee('owned-report.pdf')
        ->assertSee('Analisi estratte')
        ->assertDontSee('other-report.pdf');
});
