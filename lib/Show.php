<?php

require_once('Episode.php');

class Show
{

    static $defaultQuality = 'HDTV';
    static $defaultReleaseDay = 0;
    static $defaultFolder = '/tmp';
    static $defaultSince = 0;

    private $episodes = array();
    
    private $quality;
    private $releaseDay;
    private $since;
    private $name;
    private $folder;
    
    private $lastFound = 0;
    
    public function __construct($name){
        $this->name = $name;
        $this->quality = self::$defaultQuality;
        $this->load();
    }
    
    public function onReleasePeriod(){
        if ( (date('w') >= $this->releaseDay) && (time() - $this->lastFound > 172800) )
            return true;
        return false;
    }

    public function check(){
        $url = 'http://www.ezrss.it/search/index.php?show_name=' . urlencode($this->name) . '&quality=' . $this->quality . '&mode=rss';
        $rss = @file_get_contents($url);
        if ($rss && strstr($rss, '<!DOCTYPE torrent')){
            $xml = simplexml_load_string($rss);
            if (isset($xml->channel->item)){
                $episodes = $xml->channel->item;
                foreach ($episodes as $episode){
                    if (preg_match("@{$this->quality}@i", $episode->title)){
                        
                        preg_match ('@([0-9]+)x([0-9]+)@', $episode->title, $mat);
                        $number = $mat[2] < 10 ? $mat[1] . '0' . $mat[2] : $mat[1] . $mat[2];
                        
                        if ($number < $this->since) continue;
                        if (isset($this->episodes[$number])) continue;
                        
                        $episode = new Episode($this, 
                                               $number, 
                                               (string)$episode->torrent->contentLength, 
                                               (string)$episode->link, 
                                               (string)$episode->torrent->infoHash, 
                                               (string)$episode->torrent->fileName);
                                               
                        $this->episodes[$number] = $episode;
                        $episode->download();
                        $this->lastFound = time();
                    }                
                }
            }
            if ($this->lastFound == 0) $this->lastFound = time() - 86400;
            $this->save();
        }
        
        
        
    }
    
    public function save(){
        @mkdir ('cache');
        $episodes = serialize($this->episodes);
        file_put_contents('cache/' . $this->name . '.serial', $episodes);
    }
    
    public function load(){
        if (file_exists('cache/' . $this->name . '.serial')){
            $this->episodes = unserialize(file_get_contents('cache/' . $this->name . '.serial'));
        }
    }
        
    public function getName(){
        return $this->name;
    }
}
