<?php

namespace App\Models;

use Carbon\Carbon;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

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

    /**
     * Get a pre-configured GitHub API client
     *
     * @return Client
     */
    protected function getClient()
    {
        return new Client([
            'base_uri' => 'https://api.github.com/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/vnd.github.v3+json',
            ],
        ]);
    }

    /**
     * Get all commits and PR activities for the user's repository for a specific week
     *
     * @param string|null $weekStart Starting date of the week (format: Y-m-d)
     * @return array Commits and PR activities organized by day of week
     */
    public function getCommits($weekStart = null)
    {
        try {
            $client = $this->getClient();
            $weekData = $this->getCommitsForWeek($client, $weekStart);
            
            // Add PR reviews and comments data to the same structure
            $prActivities = $this->getPrActivitiesForWeek($client, $weekStart);
            
            // Merge PR activities with commits
            foreach ($prActivities as $day => $activities) {
                if (isset($weekData[$day])) {
                    // Add PR activities to existing day data
                    $weekData[$day] = array_merge($weekData[$day], $activities);
                } else {
                    // If no commits for this day, just use PR activities
                    $weekData[$day] = $activities;
                }
            }
            
            return $weekData;
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                abort(403, $e->getResponse()->getBody()->getContents());
            }
            abort(403, 'GitHub API request failed');
        }
    }
    
    /**
     * Get commits for a specific week
     *
     * @param Client $client GuzzleHttp client
     * @param string|null $weekStart Starting date of the week (format: Y-m-d)
     * @return array Commits organized by day of week
     */
    protected function getCommitsForWeek($client, $weekStart = null)
    {
        // Step 1: Get all branches with pagination support
        $branchesUrl = "repos/{$this->username}/{$this->repository}/branches?per_page=100";
        $branches = $this->fetchAllPages($client, $branchesUrl);
        
        $allCommits = [];
        
        // Step 2: Get commits from each branch with pagination support
        foreach ($branches as $branch) {
            $params = [
                "sha" => $branch->name,
                "per_page" => 100 // Increase items per page
            ];
            
            // If author is specified, add to params
            if ($this->username) {
                $params['author'] = $this->username;
            }
            
            $commitsUrl = "repos/{$this->username}/{$this->repository}/commits?" . http_build_query($params);
            $commits = $this->fetchAllPages($client, $commitsUrl);
            
            // Add branch name and type to each commit
            foreach ($commits as $commit) {
                $commit->branch = $branch->name;
                $commit->activity_type = 'commit';
                $allCommits[] = $commit;
            }
        }
        
        // Step 3: Filter unique commits (same SHA might appear in multiple branches)
        $uniqueCommits = $this->filterUniqueCommits($allCommits);
        
        // Step 4: Filter by week if specified
        if ($weekStart) {
            $uniqueCommits = $this->filterCommitsByWeek($uniqueCommits, $weekStart);
        }
        
        // Step 5: Sort and organize by day
        return $this->commitDateSorter($uniqueCommits);
    }
    
    /**
     * Get PR activities (reviews, approvals, comments) for a specific week
     *
     * @param Client $client GuzzleHttp client
     * @param string|null $weekStart Starting date of the week (format: Y-m-d)
     * @return array PR activities organized by day of week
     */
    protected function getPrActivitiesForWeek($client, $weekStart = null)
    {
        // Get all PRs that have activity in the date range
        $params = [
            'state' => 'all', // Get both open and closed PRs
            'per_page' => 100,
            'sort' => 'updated',
            'direction' => 'desc'
        ];
        
        $prUrl = "repos/{$this->username}/{$this->repository}/pulls?" . http_build_query($params);
        $pullRequests = $this->fetchAllPages($client, $prUrl);
        
        $allPrActivities = [];
        
        // Calculate date range if week start is specified
        $startDate = null;
        $endDate = null;
        
        if ($weekStart) {
            $startDate = Carbon::parse($weekStart)->startOfDay();
            $endDate = (clone $startDate)->addDays(6)->endOfDay();
        }
        
        // Process each PR
        foreach ($pullRequests as $pr) {
            // 1. Get PR reviews
            $reviewsUrl = "repos/{$this->username}/{$this->repository}/pulls/{$pr->number}/reviews";
            $reviews = $this->fetchAllPages($client, $reviewsUrl);
            
            foreach ($reviews as $review) {
                // Skip if we're filtering by date and the review is outside our range
                if ($startDate && $endDate) {
                    $reviewDate = Carbon::parse($review->submitted_at);
                    if (!$reviewDate->between($startDate, $endDate)) {
                        continue;
                    }
                }
                
                // Only include if it's by the authenticated user
                if ($this->username && $review->user->login !== $this->username) {
                    continue;
                }
                
                // Add PR info to the review activity
                $review->pr_title = $pr->title;
                $review->pr_number = $pr->number;
                $review->pr_url = $pr->html_url;
                $review->activity_type = 'review';
                $review->submit_date = $review->submitted_at;
                
                $allPrActivities[] = $review;
            }
            
            // 2. Get PR comments
            $commentsUrl = "repos/{$this->username}/{$this->repository}/pulls/{$pr->number}/comments";
            $comments = $this->fetchAllPages($client, $commentsUrl);
            
            foreach ($comments as $comment) {
                // Skip if we're filtering by date and the comment is outside our range
                if ($startDate && $endDate) {
                    $commentDate = Carbon::parse($comment->created_at);
                    if (!$commentDate->between($startDate, $endDate)) {
                        continue;
                    }
                }
                
                // Only include if it's by the authenticated user
                if ($this->username && $comment->user->login !== $this->username) {
                    continue;
                }
                
                // Add PR info to the comment activity
                $comment->pr_title = $pr->title;
                $comment->pr_number = $pr->number;
                $comment->pr_url = $pr->html_url;
                $comment->activity_type = 'comment';
                $comment->submit_date = $comment->created_at;
                
                $allPrActivities[] = $comment;
            }
        }
        
        // Sort and organize PR activities by day
        return $this->prActivityDateSorter($allPrActivities);
    }
    
    /**
     * Sort PR activities by day of the week
     *
     * @param array $activities Array of PR review and comment objects
     * @return array PR activities organized by day of week
     */
    protected function prActivityDateSorter($activities)
    {
        $sortedActivities = [
            'Monday' => [],
            'Tuesday' => [],
            'Wednesday' => [],
            'Thursday' => [],
            'Friday' => [],
            'Saturday' => [],
            'Sunday' => []
        ];

        foreach ($activities as $activity) {
            $date = Carbon::parse($activity->submit_date);
            $day = $date->format('l'); // Day name (Monday, Tuesday, etc.)
            $sortedActivities[$day][] = $activity;
        }

        return $sortedActivities;
    }
    
    /**
     * Fetch all pages of results from GitHub API
     *
     * @param Client $client GuzzleHttp client
     * @param string $url Initial URL to fetch
     * @return array Merged results from all pages
     */
    protected function fetchAllPages($client, $url)
    {
        $results = [];
        $nextUrl = $url;
        
        while ($nextUrl) {
            $response = $client->get($nextUrl);
            $pageResults = json_decode($response->getBody());
            $results = array_merge($results, $pageResults);
            
            $nextUrl = $this->getNextPageUrl($response);
        }
        
        return $results;
    }
    
    /**
     * Extract the next page URL from GitHub API response
     *
     * @param ResponseInterface $response Response from GitHub API
     * @return string|null URL for the next page or null if no more pages
     */
    protected function getNextPageUrl($response)
    {
        if (!$response->hasHeader('Link')) {
            return null;
        }
        
        $links = $response->getHeader('Link')[0];
        $matches = [];
        
        // Parse Link header to find next page URL
        if (preg_match('/<([^>]*)>; rel="next"/', $links, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Filter commits to only include those in a specific week
     *
     * @param array $commits Array of commit objects
     * @param string $weekStart Start date of the week (format: Y-m-d)
     * @return array Filtered commits
     */
    protected function filterCommitsByWeek($commits, $weekStart)
    {
        $startDate = Carbon::parse($weekStart)->startOfDay();
        $endDate = (clone $startDate)->addDays(6)->endOfDay();
        
        return array_filter($commits, function($commit) use ($startDate, $endDate) {
            $commitDate = Carbon::parse($commit->commit->author->date);
            return $commitDate->between($startDate, $endDate);
        });
    }
    
    /**
     * Filter unique commits by SHA
     *
     * @param array $commits Array of commit objects
     * @return array Unique commits
     */
    protected function filterUniqueCommits($commits)
    {
        $uniqueCommits = [];
        $processedShas = [];
        
        foreach ($commits as $commit) {
            if (!in_array($commit->sha, $processedShas)) {
                $uniqueCommits[] = $commit;
                $processedShas[] = $commit->sha;
            }
        }
        
        return $uniqueCommits;
    }

    /**
     * Sort commits by day of the week
     *
     * @param array $commits Array of commit objects
     * @return array Commits organized by day of week
     */
    public function commitDateSorter($commits)
    {
        $sortedCommits = [
            'Monday' => [],
            'Tuesday' => [],
            'Wednesday' => [],
            'Thursday' => [],
            'Friday' => [],
            'Saturday' => [],
            'Sunday' => []
        ];

        foreach ($commits as $commit) {
            $date = Carbon::parse($commit->commit->author->date);
            $day = $date->format('l'); // Day name (Monday, Tuesday, etc.)
            $sortedCommits[$day][] = $commit;
        }

        return $sortedCommits;
    }
}
