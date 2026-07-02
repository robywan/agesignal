<?php

namespace App\Livewire\Pages\Documents;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Referti')]
class Index extends Component
{
    #[Computed]
    public function documents(): Collection
    {
        /** @var User $user */
        $user = Auth::user();

        return $user
            ->labTestDocuments()
            ->with('media')
            ->latest()
            ->get();
    }
}
