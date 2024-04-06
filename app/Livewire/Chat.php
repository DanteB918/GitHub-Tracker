<?php

namespace App\Livewire;

use App\Models\GithubApi;
use Livewire\Component;


class Chat extends Component
{
    public $message = '';

    public $response;

    public $date;
    public function render()
    {
        return view('livewire.chat');
    }
    public function send()
    {
        $data = GithubApi::first();

        $commits = $data->getCommits();

        $this->response = $commits;
    }

}
