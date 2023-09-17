<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use GuzzleHttp\Client;

class GithubApi extends Model
{
    use HasFactory;

    protected $table = 'github_api';
    protected $fillable = [
        'id',
        'created_at',
        'updated_at',
        'token',
        'username',
        'repository',
    ];

    public function getCommits()
    {
        // $token = env('GITHUB_API_TOKEN');
        try{
            $client = new Client([
                'base_uri' => 'https://api.github.com/',
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Accept' => 'application/vnd.github.v3+json',
                ],
            ]);
            $commitsResponse = $client->get("repos/{$this->username}/{$this->repository}/commits");
            $commits = json_decode($commitsResponse->getBody());
            ksort($commits);
            return $commits;
        }catch (\Exception $e) {
            abort( $e->getMessage() , 403);
        }

    }
}
