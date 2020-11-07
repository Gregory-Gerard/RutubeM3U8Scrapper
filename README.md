# RutubeM3U8Scrapper

RutubeM3U8Scrapper helps you to retrieve a list of HLS streams / M3U8 playlist of a Rutube LiST video, with those HLS streams you can **download a video**, **streaming it in your favorite video player** or whatever you want. You <ins>still need an account</ins> at Rutube LiST website, <ins>but you don't need anymore to watch an ad or answer to their quiz</ins>.

I've developed RutubeM3U8Scrapper for myself so it's maybe not very scalable and bug-proof, but I thought other people would need it!

## Install

Clone the repository

`git clone https://github.com/Gregory-Gerard/RutubeM3U8Scrapper.git`

Install dependencies (Guzzle) with composer

`composer install`

You also need PHP >= 7.0 installed on your computer.

`apt install php`

## Configuration
If you want to use RutubeM3U8Scrapper in your CLI, you need to configure it.

`cp config.example.php config.php` (duplicate config.example.php to config.php and update your config)

Add your login (which is a mobile number with a **+ and your international dialling code** in front of the mobile number) and password in `$credentials` array, this is required because of the design of Rutube LiST (RutubeM3U8Scrapper can't call Rutube LiST api without logging-in).

```php
// example
$credentials = [
    'phone' => '+33611111111',
    'password' => 'some_strong_password'
];
```

## How to use
### CLI
Simply run `rutube.php` with a video id, example:

`php rutube.php 26f9dfab263c80f22c16b0ef1c4b77ad`

`php rutube.php 6105209`

It should echo something like this

```
---------- 640x360 ----------
639000 kbp/s : https://video-317-2.pladform.ru/dive/video-1-102.rutube.ru/mv3SOw-5xvhgL76wizgT9Q/hls-vod/4qZW_DvQHxhg9fVlBGJ7Ow/1604812485/582/0x5000cca255e4e76a/8aa68a1ad46f4a93813fc4262947e5c3.mp4.m3u8?i=640x360_639
1151000 kbp/s : https://video-317-2.pladform.ru/dive/video-1-102.rutube.ru/KGKhKCy2lQA7dUbjY5SQ7g/hls-vod/sS5wrPPUnYso2djOGlVnkA/1604812485/128/0x5000cca255cc90a7/b9132563fe2f40c8b402b9da03c5b879.mp4.m3u8?i=640x360_1151

---------- 1280x720 ----------
1589000 kbp/s : https://video-317-2.pladform.ru/dive/video-1-102.rutube.ru/MlAfBdfHOeX7fVXi1jt5cA/hls-vod/KL41UzT4qJ58DHZ4NlpVcw/1604812485/119/0x500003970b88170e/3750791273114be1ae6d57a434f1c2a1.mp4.m3u8?i=1280x720_1589
```

_________________________________________________________

### Programmatically
For documentation, check RutubeScrapper.php code, full example :
```php
$scrapper = new RutubeScrapper();
$scrapper->login('+33367358878', 'f*riaa');
$internalVideoId = $scrapper->video('26f9dfab263c80f22c16b0ef1c4b77ad');
$award = $scrapper->award($internalVideoId);

foreach($scrapper->streamList($internalVideoId, $award) as $resolution => $bandwidthList) {
    echo PHP_EOL."---------- {$resolution} ----------".PHP_EOL;

    foreach ($bandwidthList as $bandwidth => $url) {
        echo "{$bandwidth} Kbps : {$url}".PHP_EOL;
    }
}
```

`$instance->streamList()` return a multidimensional array on two levels, with the resolution and the different bandwidths for each resolution with its url :
```php
$streamList = [
    '640x360' => [
        639000 => 'https://video-317-2.pladform.ru/dive/video-1-102.rutube.ru/uPbtg49VskvVH_yBfl7WKQ/hls-vod/Sr4PXLRXagAb0vBftkax1A/1604813503/582/0x5000cca255e4e76a/8aa68a1ad46f4a93813fc4262947e5c3.mp4.m3u8?i=640x360_639',
        1151000 => 'https://video-317-2.pladform.ru/dive/video-1-102.rutube.ru/L6Knb9JNjMYm7GRL4VK3LA/hls-vod/G-z2JhlM10AFGeePdlS6Zg/1604813503/128/0x5000cca255cc90a7/b9132563fe2f40c8b402b9da03c5b879.mp4.m3u8?i=640x360_1151',
    ],
    '1280x720' => [
        1589000 => 'https://video-317-2.pladform.ru/dive/video-1-102.rutube.ru/WQJWM8YqYYMqyUGDI4xnxQ/hls-vod/zPao-gq6pwNB8rmrtC1Fsw/1604813503/119/0x500003970ba81d10/3750791273114be1ae6d57a434f1c2a1.mp4.m3u8?i=1280x720_1589',
    ],
];
```

## Use cases

Now with the HLS streams in your possession, you can download a Rutube LiST video with [youtube-dl](https://github.com/ytdl-org/youtube-dl), ffmpeg or streaming it in VLC.
```
youtube-dl -f "mp4" -o "Space Brothers 33.mp4" "https://video-317-2.pladform.ru/dive/video-1-2.rutube.ru/IgolYMB5FHGfVQMBihCISQ/hls-vod/EDXmlhyHtldODsup54cZ5g/1588904466/137/0x5000cca255cd3578/46e0da024e694e99be6529211ccdfc96.mp4.m3u8?i=1280x720_1590"
```

## How it works

I did some reverse engineering into Rutube LiST network trace, so the scrapper code simply makes the same calls that a user makes when watching a video, to describe more precisely (you can also read RutubeScrapper.php):
- First of all, the scrapper need to be logged ;
- The scrapper retrieve the internal video id, that is sometimes different from the id you have in your possession, sometimes not ;
- To retrieve Rutube video balancer, Rutube API ask for an « award token », this award token is given by Rutube ads or Rutube quiz ;
- So, the scrapper call Rutube ads API to retrieve directly your « award token » without watching anything ;
- Then it simply echo the video balancer of your video.

## To-Do
- [x] Update RutubeM3U8Scrapper into a class
