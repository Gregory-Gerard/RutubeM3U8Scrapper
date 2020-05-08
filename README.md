# RutubeM3U8Scrapper

I was tired of RuTube LiST advertising system, so I made sure to not have an ad again and in the same time retrieved the list of HLS streams (thus the m3u8 playlists) video. I developed it for myself so it's not very scalable and bug-proof, but I thought other people would need it!

## Install

Clone the repository

`git clone https://github.com/Gregory-Gerard/RutubeM3U8Scrapper.git`

Install dependencies (Guzzle) with composer

`composer install`

You also need PHP >= 7.0 installed on your computer.

`apt install php`

## Configuration

Create a `config.php` file, a copy of `config.example.php` (`cp config.example.php config.php`)

Then add your login (which is a mobile number now and with a + in front of the mobile number) and password in `$credentials`, this is required because of the design of Rutube LiST (the api won't let you know any HLS streams before you are logged in and watched an ad).

```php
// example
$credentials = [
    'phone' => '+33611111111',
    'password' => 'some_strong_password'
];
```

## How to use

Simply run `rutube.php` with a video id, example:

`php rutube.php 26f9dfab263c80f22c16b0ef1c4b77ad`

(it works with iframe / embed video id too):

`php rutube.php 6105209`

It should echo something like this

```
HLS Stream Found ! (640x360, 639000 kb/s) : https://video-1-102.rutube.ru/hls-vod/6Y1WJ80LQpUx1Bdi3xgnjg/1588995638/582/0x5000cca255e4e76a/8aa68a1ad46f4a93813fc4262947e5c3.mp4.m3u8?i=640x360_639
HLS Stream Found ! (640x360, 1151000 kb/s) : https://video-1-102.rutube.ru/hls-vod/Vp6dlkYAqojx0VEaqgWF6Q/1588995638/135/0x5000cca255cd1c8c/b9132563fe2f40c8b402b9da03c5b879.mp4.m3u8?i=640x360_1151
HLS Stream Found ! (1280x720, 1589000 kb/s) : https://video-1-102.rutube.ru/hls-vod/nzpkI0ZbpWEyCvZKMhBPig/1588995638/119/0x500003970b88170e/3750791273114be1ae6d57a434f1c2a1.mp4.m3u8?i=1280x720_1589
```

## Use cases

Thanks to the HLS streams, you can download your video with [youtube-dl](https://github.com/ytdl-org/youtube-dl) for example, or streaming it in VLC.
```
youtube-dl -f "mp4" -o "Space Brothers 33.mp4" "https://video-317-2.pladform.ru/dive/video-1-2.rutube.ru/IgolYMB5FHGfVQMBihCISQ/hls-vod/EDXmlhyHtldODsup54cZ5g/1588904466/137/0x5000cca255cd3578/46e0da024e694e99be6529211ccdfc96.mp4.m3u8?i=1280x720_1590"
```

You can also check api.php and the bypass ad system and use it to integrate it into a sort of adblocker or an extension or even a Tampermonkey script (which I did) for example, to automatically play the videos without any ads.

## How it works

With a lot of reverse engineering, the code simply makes the same calls that a user makes when watching a video, to describe more precisely (you can also read api.php):
- First of all, you need to be logged in, so the code call rutube login page with your credentials
- It retrieves some infos of the video, thanks to the video id provided to the rutube.php
- Using some bad design of Rutube LiST, the script retrieve an encrypted token of the user logged in, provided by Rutube LiST itself, to use it in the next call
- Script call the ad system for your video, thanks to the encrypted token and video id, and retrieve an other token (called award or club_token) to bypass the ad + quiz system, once more a bad design from Rutube LiST.
- Then it simply echo each HLS streams given by Rutube LiST.