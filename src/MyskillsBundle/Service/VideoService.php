<?php
namespace MyskillsBundle\Service;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class VideoService {
    private $ffmpeg;
    private $probe;
    private $container;

    public function __construct( Container $container )
    {
        $this->container = $container;
        $this->ffmpeg = \FFMpeg\FFMpeg::create([
            'ffmpeg.binaries' => $container->getParameter('ffmpeg_dir'),
            'ffprobe.binaries' => $container->getParameter('ffprobe_dir'),
            'ffmpeg.threads'   => 4,
            'ffmpeg.timeout'   => 3600,
            'ffprobe.timeout'  => 30
        ]);
        $this->probe = \FFMpeg\FFProbe::create();
    }

    public function cut($mp4VideoSrc, $mp4ClipSrc, $startInSeconds, $finishInSeconds) {
        $frameSrc = str_replace('.mp4', '.jpg', $mp4ClipSrc);
        $video = $this->ffmpeg
                      ->open($mp4VideoSrc);

        $maxInSeconds = intval($this->probe->format($mp4VideoSrc)->get('duration'));

        if($finishInSeconds > $maxInSeconds) {
            $finishInSeconds = $maxInSeconds;
        }

        $delta = $finishInSeconds - $startInSeconds + 1;

        $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds($startInSeconds + round($delta)/2))
              ->save($frameSrc);

//        $video->filters()
//            ->clip(\FFMpeg\Coordinate\TimeCode::fromSeconds($startInSeconds), \FFMpeg\Coordinate\TimeCode::fromSeconds($delta))
//            ->synchronize();
//
//        $video->save(new \FFMpeg\Format\Video\X264(), $mp4ClipSrc);

        $command = "PATH=/usr/local/bin:/usr/bin:/bin:/usr/local/games:/usr/games; /var/www/bin/ffmpeg -ss $startInSeconds -i $mp4VideoSrc -t $delta $mp4ClipSrc -loglevel quiet";
        shell_exec($command);

        return $frameSrc;
    }
}
