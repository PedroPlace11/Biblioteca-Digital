<?php

namespace App\Livewire\Chat;

use App\Models\Room;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class RoomView extends Component
{
    public Room $room;
    public $searchQuery = '';
    public $searchOpen = false;

    public function updatedSearchQuery($value)
    {
        $this->dispatch('search-updated', searchQuery: $value);
    }

    public function render()
    {
        return view('livewire.chat.room-view');
    }
}
