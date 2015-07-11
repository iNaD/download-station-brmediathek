<?php

/**
 * @author Daniel Gehn <me@theinad.com>
 * @version 0.2a
 * @copyright 2015 Daniel Gehn
 * @license http://opensource.org/licenses/MIT Licensed under MIT License
 */

require_once "provider.php";

class SynoFileHostingBRMediathek extends TheiNaDProvider {

    protected $LogPath = '/tmp/br-mediathek.log';

    //This function returns download url.
    public function GetDownloadInfo() {
        $this->DebugLog("Getting download url $this->Url");

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $this->Url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, DOWNLOAD_STATION_USER_AGENT);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

        $rawXML = curl_exec($curl);

        if(!$rawXML)
        {
            $this->DebugLog("Failed to retrieve Website. Error Info: " . curl_error($curl));
            return false;
        }

        curl_close($curl);

        if(preg_match('#dataURL:\'(.*?)\'#si', $rawXML, $match) === 1)
        {
            $curl = curl_init();

            curl_setopt($curl, CURLOPT_URL, 'http://www.br.de/' . $match[1]);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_USERAGENT, DOWNLOAD_STATION_USER_AGENT);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

            $RawXMLData = curl_exec($curl);

            if(!$RawXMLData)
            {
                $this->DebugLog("Failed to retrieve XML. Error Info: " . curl_error($curl));
                return false;
            }

            curl_close($curl);

            $match = array();
            $title = "";

            if(preg_match('#<sharetitle>(.*?)<\/sharetitle>#is', $RawXMLData, $match) == 1)
            {
                $title = $match[1];
            }

            preg_match_all('#<asset type=["|\'](?:\w*)["|\']>(.*?)<\/asset>#si', $RawXMLData, $matches);

            $bestSource = array(
                'bitrate'   => -1,
                'url'       => '',
            );

            foreach($matches[1] as $source)
            {
                if(preg_match("#<downloadUrl>(.*?)<\/downloadUrl>#si", $source, $downloadUrl) !== 1)
                {
                    continue;
                }

                $url = $downloadUrl[1];

                if(strpos($url, '.mp4') !== false)
                {
                    if(preg_match("#<bitrateVideo>(.*?)<\/bitrateVideo>#si", $source, $bitrateVideo) !== 1)
                    {
                        continue;
                    }

                    $bitrate = $bitrateVideo[1];

                    if($bestSource['bitrate'] < $bitrate)
                    {
                        $bestSource['bitrate'] = $bitrate;
                        $bestSource['url'] = $url;
                    }
                }
            }

            if($bestSource['url'] !== '')
            {
                $url = trim($bestSource['url']);

                $DownloadInfo = array();
                $DownloadInfo[DOWNLOAD_URL] = $url;

                $filename = "";
                $pathinfo = pathinfo($url);

                if(empty($title))
                {
                    $filename = $pathinfo['basename'];
                }
                else
                {
                    $filename .= $title . '.' . $pathinfo['extension'];
                }

                $DownloadInfo[DOWNLOAD_FILENAME] = $this->safeFilename($filename);

                return $DownloadInfo;
            }

            $this->DebugLog("Failed to determine best quality: " . json_encode($matches[1]));

            return false;

        }

        $this->DebugLog("Couldn't identify player meta");

        return false;
    }

}
?>
