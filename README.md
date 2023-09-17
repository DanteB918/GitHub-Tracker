# GitHub Commit Tracker
A simple laravel application using the github API to search commits on a given repo for a given user.

![image](https://github.com/DanteB918/GitHub-Tracker/assets/100642899/0c0e6160-6044-4366-a159-e35d72b0807f)

## Getting started
Generate a personal access token and throw it in the settings. Then add the username of any user, and the name of any repo, and you can search for the commits that user has made. Great for helping on a weekly report!

## Setting up locally
`docker compose up -d`
This will build the image in our Dockerfile.
before getting into our container, run `npm run dev` for Vite.
`docker exec -it weekly-report /bin/bash` to get into our docker container
`php artisan key:generate`
`php artisan migrate`
and that should do it!
