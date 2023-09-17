@php
    use \Carbon\Carbon;
    $now = Carbon::now();
    $weekStartDate = $now->startOfWeek()->format('Y-m-d');
    $weekEndDate = $now->endOfWeek()->format('Y-m-d');
@endphp

<div class="text-light">
    <a href="{{ route('settings') }}" style="color: crimson;"><i class="fa-solid fa-gear"></i> Settings</a>
    <h1>Enter a Date and see all your commits!</h1>
    <em>Press Search with the date empty to see all of this weeks commits.</em>
    <em></em>
    <form class="d-flex justify-content-center flex-row" wire:submit="send">
        <div class="mb-3">
            <input type="date" class="form-control" wire:model="date" />
        </div>
        <div class="mx-2">
            <button type="submit" class="btn text-light" style="background-color: crimson;"><i class="fa-solid fa-magnifying-glass"></i></button>
        </div>
    </form>
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

    <p class="text-light">
        @if( $response )
            @foreach($response as $data)
                @php
                    $commitDate = Carbon::parse($data->commit->author->date)->format('m-d-Y');
                    $x = Carbon::parse($data->commit->author->date)->format('l');
                    if ( cache()->has('day') ) {
                        $done = [];
                        $done[] = cache()->get('day');
                        if (in_array($x, $done)){
                            cache()->forget('day');
                        }
                    }else{
                        cache()->put('day', $x, now()->addMinutes(2));
                    }
                @endphp
                @if($commitDate >= $weekStartDate && $commitDate <= $weekEndDate && ! $date)
                    @if(cache()->has('day'))
                        <b>{{ cache()->get('day') }}</b><br />
                    @endif
                    <ul>
                        <li>{{ $data->commit->message }}</li>
                        <li><a href="{{ $data->parents[0]->html_url }}" target="_blank" style="color: crimson;">See Info</a></li>
                    </ul>
                @else
                    @if(Carbon::parse($date)->format('m-d-Y') === $commitDate )
                        @if(cache()->has('day'))
                            <b>{{ cache()->get('day') }}</b><br />
                            <ul>
                        @endif
                            <li>{{ $data->commit->message }}</li>
                            <li><a href="{{ $data->parents[0]->html_url }}" target="_blank" style="color: crimson;">See Info</a></li>
                            @if($loop->last)
                                </ul>
                            @endif
                    @endif
                @endif
            @endforeach
            @php
                cache()->flush();
            @endphp
        @endif
    </p>
</div>
