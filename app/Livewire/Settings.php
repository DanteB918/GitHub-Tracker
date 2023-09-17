<?php

namespace App\Livewire;

use Livewire\Component;
use \App\Models\GithubApi;

class Settings extends Component
{
    public $token = '';
    public $username = '';
    public $repository = '';
    public $status;
    public $hidden = 1;

    protected $rules = [
        'token' => 'required',
        'username' => 'required',
        'repository' => 'required',
    ];
    public function render()
    {
        if (GithubApi::first()){
            $this->token = GithubApi::first()->token;
            $this->username = GithubApi::first()->username;
            $this->repository = GithubApi::first()->repository;
        }
        return view('livewire.settings');
    }
    public function updateOrCreate()
    {
        $this->validate();
        try{
            GithubApi::query()->delete();
            GithubApi::updateOrCreate([
                'token' => $this->token,
                'username' => $this->username,
                'repository' => $this->repository
            ]);
        }catch (\Exception $e){
            abort( $e->getMessage() , 403);
        }
        $this->status = 'Updated!';
    }
    public function showText()
    {
        $this->hidden ? $this->hidden = 0 : $this->hidden = 1;
    }
}
