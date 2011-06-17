<?php

class Episode
{

    private $show;
    private $number;
    private $size;
    private $link;
    private $hash;
    private $fileName;
    
    private $downloaded = false;

    public function __construct($show, $number, $size, $link, $hash, $fileName){
        $this->show = $show;
        $this->number = $number;
        $this->size = $size;
        $this->link = $link;
        $this->hash = $hash;
        $this->fileName = $fileName;
    }
    
    public function download(){
        if (!$this->downloaded){
            $temp = sys_get_temp_dir() . '/' . $this->fileName;
            @mkdir(App::$configs->torrentFileDir);
            echo '- Downloading ' . $this->link . PHP_EOL;
            $file = file_get_contents($this->link);
            file_put_contents($temp, $file);
            if (mime_content_type($temp) == 'application/x-bittorrent'){
                copy($temp, App::$configs->torrentFileDir . '/' . $this->fileName);
                unlink($temp);
                $this->downloaded = true;
            }
        }
    }
}
