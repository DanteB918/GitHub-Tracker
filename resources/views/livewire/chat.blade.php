@php
    use \Carbon\Carbon;
    $now = Carbon::now();
    $weekStartDate = $now->startOfWeek()->format('m-d-Y');
    $weekEndDate = $now->endOfWeek()->format('m-d-Y');
@endphp

<div class="text-light">
    <a href="{{ route('settings') }}" style="color: crimson;"><i class="fa-solid fa-gear"></i> Settings</a>
    <h1>Enter a Date and see all your commits!</h1>
    <em>Press Search with the date empty to see all of this weeks commits.</em>
    <em></em>
    <form class="d-flex justify-content-center flex-row" wire:submit="send">
        <div class="mb-3">
            <input type="date" class="form-control" wire:model.defer="date" />
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
        @if(count($response ?? []))
            @foreach($response as $day => $commit)
                <p><b>{{ $day }}</b></p>
                <ul>
                    @foreach ($commit as $data)
                        @if ($date && ! Carbon::parse($date)->isSameDay(Carbon::parse($data->commit->author->date)) )
                            <?php continue; ?>
                        @endif
                            <li>{{ $data->commit->message }}</li>
                            <li>{{ Carbon::parse($data->commit->author->date)->format('m/d/Y') }}</li>
                            <li><a href="{{ $data->html_url }}" target="_blank" style="color: crimson;">See Info</a></li>
                    @endforeach
                </ul>
            @endforeach
        @endif
    </p>
</div>
