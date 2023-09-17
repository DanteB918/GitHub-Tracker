<div>
    <form class="d-flex justify-center flex-column p-4 form" wire:submit="updateOrCreate">
        <div class="form-group">
            <label for="token" class="form-label">GitHub Access Token</label>
            <div style="display: flex; align-tems: center;">
                <input @if($hidden) type="password" @else type="text" @endif class="form-control w-75" placeholder="GitHub Access Token" wire:model="token" autofocus value="{{ $token }}"/>
                <span class="w-25 text-light d-flex justify-content-center align-items-center eye" wire:click="showText">
                    @if( $hidden )
                        <i class="fa-solid fa-eye"></i>
                    @else
                        <i class="fa-solid fa-eye-slash" style="font-size: 14px;"></i>
                    @endif
                </span>
            </div>
            @error('token') <span class="error" style="color: crimson;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="token" class="form-label">GitHub Username</label>
            <input type="text" class="form-control" placeholder="GitHub username" wire:model="username" value="{{ $username }}"/>
            @error('username') <span class="error" style="color: crimson;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="token" class="form-label">GitHub Repository Name</label>
            <input type="text" class="form-control" placeholder="GitHub Repo Name" wire:model="repository" value="{{ $repository }}"/>
            @error('repository') <span class="error" style="color: crimson;">{{ $message }}</span> @enderror
        </div>
        <button type="submit" class="btn w-50 text-light" style="background-color: crimson;">Submit</button>
    </form>
    <p style="color: crimson;">{{ $status }}</p>
    @if( $token )
        <a href="{{ route('home') }}"><button class="btn w-50 text-light" style="background-color: crimson;">Go to App</button></a>
    @else
        <a href="https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/managing-your-personal-access-tokens" class="text-light" target="_blank">Generate an Access Token</a>
    @endif

</div>
