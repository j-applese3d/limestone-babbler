# Purpose

The idea here is to enable one to study bird calls without having to read the name of the track being played, 
or manually starting playback.

# Requirements

* PHP. Only tested on PHP 8, but nothing special in there, and you could copy the same logic anywhere
* `say`. Can be installed with `sudo apt-get install gnustep-gui-runtime`. Used to read names
* `cvlc`. Commandline VLC. `sudo apt-get install cvlc`. Used to play audio files
* Only tested on Kubuntu 22.04

# Synopsis

```
php ./playCalls.php <path to folder with audio files>
```

### TODO

> php/cli options
* add option to disable shuffle
* allow custom name matching rules

> media control
* allow skipping a song
* pause/play control

> integration
* add option to read list of XC recordings (i.e. from a Box); download recordings with a rating >=[A,B,C,D,E] AND/OR limit # recordings/species;

