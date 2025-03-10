<div class="text-light">
    @php
        use \Carbon\Carbon;
    @endphp

    <a href="{{ route('settings') }}" style="color: crimson;"><i class="fa-solid fa-gear"></i> Settings</a>
    <h1>GitHub Weekly Commit Summary</h1>
    
    <div class="d-flex flex-column align-items-center mb-4">
        <!-- Week navigation -->
        <div class="d-flex justify-content-between align-items-center w-100 mb-3">
            <button wire:click="previousWeek" class="btn btn-outline-light"><i class="fa-solid fa-arrow-left"></i> Previous Week</button>
            <div class="text-center">
                <!-- Use component's weekStart and weekEnd properties -->
                <h3>Week of {{ date('M d, Y', strtotime($weekStart)) }} - {{ date('M d, Y', strtotime($weekEnd)) }}</h3>
            </div>
            <button wire:click="nextWeek" class="btn btn-outline-light">Next Week <i class="fa-solid fa-arrow-right"></i></button>
        </div>
        
        <!-- Date selector for specific day -->
        <div class="mb-3 text-center">
            <p class="mb-2">Or select a specific week:</p>
            <form class="d-flex justify-content-center flex-row" wire:submit.prevent="send">
                <div class="mb-3">
                    <input type="week" class="form-control" wire:model="date" />
                </div>
                <div class="mx-2">
                    <button type="submit" class="btn text-light" style="background-color: crimson;"><i class="fa-solid fa-magnifying-glass"></i></button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Loading animation -->
    <div class="d-flex justify-content-center">
        <div wire:loading>
            <div class="center">
                <div class="wave"></div>
                <div class="wave"></div>
                <div class="wave"></div>
                <div class="wave"></div>
                <div class="wave"></div>
                <div class="wave"></div>
                <div class="wave"></div>
                <div class="wave"></div>
                <div class="wave"></div>
                <div class="wave"></div>
            </div>
        </div>
    </div>
    
    <!-- Commits display -->
    <div class="commits-container mt-4">
        @php
            $dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            $hasCommits = false;
            $hasActivity = false;
            
            // Ensure we have valid date objects
            if (isset($weekStart)) {
                $weekStartDate = new DateTime($weekStart);
            } else {
                $weekStartDate = new DateTime();
                $weekStartDate->modify('monday this week');
            }
        @endphp
        
        @foreach($dayNames as $index => $day)
            @php
                // Calculate the date for this day of the week
                $dayDate = clone $weekStartDate;
                $dayDate->modify("+{$index} days");
                $formattedDate = $dayDate->format('M d, Y');
                $dayHasActivity = false;
            @endphp
            <div class="day-section mb-4">
                <h3 class="day-header">{{ $day }} - {{ $formattedDate }}</h3>
                
                <!-- Display activities for this day -->
                @if(isset($response[$day]) && count($response[$day]))
                    @php 
                        $dayHasActivity = true;
                        $hasActivity = true; 
                    @endphp
                    <div class="activities-list">
                        @foreach($response[$day] as $activity)
                            @php
                                $activityType = $activity->activity_type ?? 'commit';
                            @endphp
                            
                            <!-- Different card styles for different activity types -->
                            @if($activityType === 'commit')
                                @php $hasCommits = true; @endphp
                                <div class="activity-card commit-card mb-3 p-3" style="background-color: #2a2a2a; border-radius: 8px;">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <span class="badge bg-secondary mb-2">Commit</span>
                                            <h5 class="activity-message">{{ $activity->commit->message }}</h5>
                                            <p class="activity-meta">
                                                <span class="badge bg-primary">{{ $activity->branch ?? 'Unknown Branch' }}</span>
                                                <span class="ms-2">{{ date('h:i A', strtotime($activity->commit->author->date)) }}</span>
                                            </p>
                                        </div>
                                        <div>
                                            <a href="{{ $activity->html_url }}" target="_blank" class="btn btn-sm" style="background-color: crimson; color: white;">
                                                <i class="fa-solid fa-external-link-alt"></i> View
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @elseif($activityType === 'review')
                                <div class="activity-card review-card mb-3 p-3" style="background-color: #2d2d3a; border-radius: 8px;">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <span class="badge bg-info mb-2">PR Review</span>
                                            <h5 class="activity-message">
                                                Review on PR #{{ $activity->pr_number }}: {{ $activity->pr_title }}
                                            </h5>
                                            @if(isset($activity->body) && !empty($activity->body))
                                                <div class="review-body p-2 my-2" style="background-color: #22222c; border-left: 3px solid #6c757d; padding-left: 10px;">
                                                    {{ $activity->body }}
                                                </div>
                                            @endif
                                            <p class="activity-meta">
                                                <span class="badge bg-{{ $activity->state === 'APPROVED' ? 'success' : ($activity->state === 'CHANGES_REQUESTED' ? 'warning' : 'secondary') }}">
                                                    {{ $activity->state }}
                                                </span>
                                                <span class="ms-2">{{ date('h:i A', strtotime($activity->submit_date)) }}</span>
                                            </p>
                                        </div>
                                        <div>
                                            <a href="{{ $activity->pr_url }}" target="_blank" class="btn btn-sm" style="background-color: crimson; color: white;">
                                                <i class="fa-solid fa-external-link-alt"></i> View PR
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @elseif($activityType === 'comment')
                                <div class="activity-card comment-card mb-3 p-3" style="background-color: #2d332d; border-radius: 8px;">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <span class="badge bg-success mb-2">PR Comment</span>
                                            <h5 class="activity-message">
                                                Comment on PR #{{ $activity->pr_number }}: {{ $activity->pr_title }}
                                            </h5>
                                            @if(isset($activity->body) && !empty($activity->body))
                                                <div class="comment-body p-2 my-2" style="background-color: #23291f; border-left: 3px solid #28a745; padding-left: 10px;">
                                                    {{ $activity->body }}
                                                </div>
                                            @endif
                                            <p class="activity-meta">
                                                <span class="ms-2">{{ date('h:i A', strtotime($activity->submit_date)) }}</span>
                                            </p>
                                        </div>
                                        <div>
                                            <a href="{{ $activity->pr_url }}" target="_blank" class="btn btn-sm" style="background-color: crimson; color: white;">
                                                <i class="fa-solid fa-external-link-alt"></i> View PR
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <p class="text-light">No activity</p>
                @endif
            </div>
        @endforeach
        
        @if(!$hasActivity && count($response) > 0)
            <div class="alert alert-info">
                No GitHub activity found for this week. Try selecting a different week.
            </div>
        @elseif(count($response) === 0)
            <div class="alert alert-warning">
                <p>No GitHub data available. Please check your GitHub settings and repository configuration.</p>
                <a href="{{ route('settings') }}" class="btn btn-sm" style="background-color: crimson; color: white;">Go to Settings</a>
            </div>
        @endif
    </div>

    <style>
        .day-header {
            border-bottom: 1px solid #444;
            padding-bottom: 8px;
            margin-bottom: 16px;
        }
        
        .activity-message {
            word-break: break-word;
        }
        
        .activity-meta {
            font-size: 0.9rem;
            color: #aaa;
        }
        
        .review-body, .comment-body {
            font-size: 0.9rem;
            white-space: pre-line;
            max-height: 150px;
            overflow-y: auto;
            border-radius: 4px;
        }
    </style>
</div>
