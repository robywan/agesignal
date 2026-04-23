<?php

use App\Enums\LabTestDocumentStatus;
use App\Jobs\ProcessDocumentJob;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Title('Nuovo referto')] class extends Component {
    use WithFileUploads;

    public ?string $testDate = null;

    public ?TemporaryUploadedFile $documentFile = null;

    protected function rules(): array
    {
        return [
            'testDate' => ['nullable', 'date'],
            'documentFile' => ['required', 'file', 'extensions:pdf', 'mimes:pdf', 'max:10240'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        DB::transaction(function () use ($validated): void {
            /** @var User $user */
            $user = Auth::user();

            $document = $user->labTestDocuments()->create([
                'test_date' => $validated['testDate'],
                'status' => LabTestDocumentStatus::Pending,
            ]);

            $document
                ->addMedia($this->documentFile)
                ->usingFileName($this->documentFile->getClientOriginalName())
                ->toMediaCollection('files');

            ProcessDocumentJob::dispatch($document)->afterCommit();

        });

        Flux::toast(variant: 'success', text: __('Referto caricato. L\'estrazione è stata accodata.'));

        $this->redirect(route('documents.index', absolute: false), navigate: true);
    }
}; 