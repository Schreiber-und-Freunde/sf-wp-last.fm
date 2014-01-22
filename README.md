wp-last.fm
==========

last.fm API Integration for Wordpress 

**Notice:** This is no complete API implementation. Only the features we need for www.schreiber-freunde.de are implemented. If you miss a feature feel free to implement it and send a pull request or file a feature request in the issues section.

## Usage
The following wrapper functions are implemented yet:
```
lastfm_get_recent_tracks($user);
```
Returns the last played tracks.

```
lastfm_get_user_info($user);
```
Returns the available information about the given user.

```
lastfm_get_play_count($user);
```
Returns the total played tracks for the given user.
* * *
**Be careful and cache the results. This plugin won't do this for you!**