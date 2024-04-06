<?php

namespace App\Models;

use Carbon\Carbon;
use GuzzleHttp\Exception\RequestException;
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
        try{
            $client = new Client([
                'base_uri' => 'https://api.github.com/',
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Accept' => 'application/vnd.github.v3+json',
                ],
            ]);

            $commitsResponse = $client->get("repos/{$this->username}/{$this->repository}/commits?author={$this->username}");

            $commits = json_decode($commitsResponse->getBody());

            return $this->commitDateSorter($commits);
        }catch (RequestException $e) {
            abort( $e->getBody()->getContents() , 403);
        }
    }

    public function commitDateSorter(array $commits)
    {
        $sortedCommits = [];

        foreach ($commits as $commit) {
            $day = Carbon::parse($commit->commit->author->date)->format('l');

            $sortedCommits[$day][] = $commit;
        }

        return $sortedCommits;
    }
}
