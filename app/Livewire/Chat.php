<?php

namespace App\Livewire;

use App\Models\GithubApi;
use Carbon\Carbon;
use Livewire\Component;

class Chat extends Component
{
    public $message = '';
    public $response = [];
    public $date; // Now in format YYYY-Www
    public $weekStart;
    public $weekEnd;
    public $selectedWeek;
    
    public function mount()
    {
        // Default to current week
        $this->setCurrentWeek();
        $this->send();
    }

    public function render()
    {
        return view('livewire.chat');
    }
    
    /**
     * Set the week dates to the current week
     */
    public function setCurrentWeek()
    {
        $now = Carbon::now();
        // Use Y-m-d format consistently for all dates
        $this->weekStart = $now->startOfWeek()->format('Y-m-d');
        $this->weekEnd = Carbon::parse($this->weekStart)->addDays(6)->format('Y-m-d');
        $this->selectedWeek = $this->weekStart;
        $this->date = $now->format('Y').'-W'.$now->format('W'); // Set the week input value
    }
    
    /**
     * Move to the previous week
     */
    public function previousWeek()
    {
        $this->weekStart = Carbon::parse($this->weekStart)->subDays(7)->format('Y-m-d');
        $this->weekEnd = Carbon::parse($this->weekStart)->addDays(6)->format('Y-m-d');
        $this->selectedWeek = $this->weekStart;
        
        // Update the week input value
        $weekDate = Carbon::parse($this->weekStart);
        $this->date = $weekDate->format('Y').'-W'.$weekDate->format('W');
        
        $this->send();
    }
    
    /**
     * Move to the next week
     */
    public function nextWeek()
    {
        $this->weekStart = Carbon::parse($this->weekStart)->addDays(7)->format('Y-m-d');
        $this->weekEnd = Carbon::parse($this->weekStart)->addDays(6)->format('Y-m-d');
        $this->selectedWeek = $this->weekStart;
        
        // Update the week input value
        $weekDate = Carbon::parse($this->weekStart);
        $this->date = $weekDate->format('Y').'-W'.$weekDate->format('W');
        
        $this->send();
    }
    
    /**
     * Fetch commits for the selected date or week
     */
    public function send()
    {
        $data = GithubApi::first();
        
        if (!$data) {
            $this->response = [];
            return;
        }
        
        // If a specific week is selected, use that week
        if ($this->date) {
            try {
                // Parse the week string (format: YYYY-Www)
                if (preg_match('/^(\d{4})-W(\d{1,2})$/', $this->date, $matches)) {
                    $year = $matches[1];
                    $week = $matches[2];
                    
                    // Create a Carbon instance for the first day of that week
                    $dateObj = Carbon::now()->setISODate($year, $week, 1); // 1 = Monday
                    
                    // Update the week dates
                    $this->weekStart = $dateObj->format('Y-m-d');
                    $this->weekEnd = (clone $dateObj)->addDays(6)->format('Y-m-d');
                    $this->selectedWeek = $this->weekStart;
                }
            } catch (\Exception $e) {
                // Silently handle exceptions
            }
        }
        
        // Get commits for the selected week
        $this->response = $data->getCommits($this->weekStart);
    }
}
