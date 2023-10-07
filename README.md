# PocketRadio
A lightweight .NBS song player for PocketMine Servers

## Prerequisites
When downloaded from poggit, none.

When manually installing from source, you must install the virions 

## Setup
- Add the plugin to the `plugins` folder
- Put your .nbs files into the generated "songs" folder and restart the server
- The songs will automatically start to play when a player joins the server

## Commands
Use `/radio` to open the radio interface

Use `/radio next` to skip the current song (global)

Use `/radio volume` to change your volume (per player)

Use `/radio pause` to pause or unpause playback (global)

Use `/radio select` to play a specific song (global)

## Planned changes
- Change the radio interface to allow searching ✔, skipping ✔, looping songs or playlists
- Add looping ✔, toggle shuffle mode ✔, playlists ✔, per-user songs
- World settings (plobably done via external plugin)

## API
https://github.com/inxomnyaa/PocketRadio/wiki

## Where do i get .nbs files?
You can find a collection of .nbs songs here: https://forums.pmmp.io/threads/200-nbs-songs.294/

<!It is planned to create a proper, separate website, where you can submit your own .nbs files, search for songs and download them>

## Issues
Make sure to check a few things **before** creating an issue
 - Your installation of PocketMine (pmmp) works properly, and is an official version. No support on custom builds or forks.
 - You have the latest version of PocketRadio. Issues based on outdated releases will be closed as invalid
 - You run a release version from poggit or the releases tab. Self-compiled versions are not supported.