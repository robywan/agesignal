<?php

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Referti')] class extends Component {
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
}; 