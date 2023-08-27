<?php

namespace ExternalApi;

use Illuminate\Database\Capsule\Manager as DB;
        
class ExternalApi {

    public $cache = "1 week"; //false or any time in strtotime() format
    public $cacheDir = PATH . 'fajlok/tmp/';
    public $queryTimeout = 30;
    public $query;
    public $name = 'external';
	public $format = 'json';
	private $curl_opts = [];

    function run() {
        $this->runQuery();
    }

    function runQuery() {
        try {
        
            if (!isset($this->rawQuery)) {
                $this->buildQuery();
            }

            if ($this->cache) {
                $this->loadCacheFilePath();
                $this->tryToLoadFromCache();
            }

            if (!isset($this->rawData)) {
                $this->downloadData();
            }

            if ($this->cache) {
                $this->saveToCache();
            }
            
            
        } catch(\Exception $e){
            global $config;
            if($this->format == 'json' ) $this->jsonData = [];
			if($this->format == 'yml' ) $this->xmlData = [];
            $this->error = \Html\Html::printExceptionVerbose($e,true);
            if($config['debug'] > 1) echo $this->error;
            elseif($config['debug'] > 0) addMessage($this->error,'warning');
            return false;
        }
        return true;
    }

    function tryToLoadFromCache() {
        if (file_exists($this->cacheFilePath)) {
            $this->cacheFileTime = date('Y-m-d H:i:s',filemtime($this->cacheFilePath));
            if (filemtime($this->cacheFilePath) > strtotime("-" . $this->cache)) {
                $this->rawData = file_get_contents($this->cacheFilePath);
				if($this->format == 'json' ) {
					$this->jsonData = json_decode($this->rawData);
					if ($this->jsonData === null) {
						throw new \Exception("External API data has been loaded from cache but data is not a valid JSON!\n".$this->rawData);
					} else {
						return true;
					}
				} elseif($this->format == 'xml' ) {
					$this->xmlData = @simplexml_load_string($this->rawData);					
					if ($this->xmlData == false) {
						throw new \Exception("External API data has been loaded from cache but data is not a valid XML!\n".$this->rawData);
					} else {
						return true;
					}
				
				}
            } else {
                unlink($this->cacheFilePath);
                return false;
            }
        } else {
            return false;
        }
    }

    function saveToCache() {
        if (!file_put_contents($this->cacheFilePath, $this->rawData)) {
            throw new \Exception("We could not save the cacheFile to " . $this->cacheFilePath);
        }
    }

    function downloadData() {        
        $header = array("cache-control: no-cache","Content-Type: application/".$this->format);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$this->apiUrl . $this->rawQuery);
		//echo $this->apiUrl . $this->rawQuery."\n";
        curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        curl_setopt($ch, CURLOPT_HEADER  , false);  // we want headers
        curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_USERAGENT, "miserend.hu");



		foreach($this->curl_opts as $name => $value ) {
			curl_setopt($ch, $name, $value );
		}
		
        $this->rawData = curl_exec($ch);
    
        $this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE ); 
        
        $this->saveStat();
        		
        switch ($this->responseCode) {
            case '200':
				if($this->format == 'json' ) {
					$this->jsonData = json_decode($this->rawData);
					if ($this->jsonData === null ) {            					
						throw new \Exception("External API return data is not a valid JSON: " . $this->rawData );
					}
				} else if ($this->format == 'xml') {					
					$this->xmlData = @simplexml_load_string($this->rawData);					
					if ($this->xmlData == false ) {            					
						throw new \Exception("External API return data is not a valid XML: " . $this->rawData );
					}
				}
                break;
               
            case '404':
                $this->Response404();
                break;
                
            default:
                throw new \Exception("External API returned bad http response code: " . $this->responseCode. "\n<br>" . curl_error($ch));
                break;
        }        
    }

    function clearOldCache() {
        $this->cache;
        $this->cacheDir;
        $files = scandir($this->cacheDir);
        foreach ($files as $file) {
            if (preg_match('/^' . $this->name . '_(.*)\.'.$this->format.'/i', $file)) {
                $filemtime = filemtime($this->cacheDir . $file);
                $deadline = strtotime('now -' . $this->cache);
                if ($filemtime < $deadline) {
                    unlink($this->cacheDir . $file);
                }
            }
        }
    }

    function loadCacheFilePath() {
        $this->cacheFilePath = $this->cacheDir . $this->name . "_" . md5($this->query) . ".".$this->format;
    }
       
    function saveStat() {
        
        $query = DB::table('stats_externalapi')->where('url',$this->apiUrl.$this->rawQuery)->where('date',date('Y-m-d'));
        if($current = $query->first()) {   
            if($current->rawdata != $this->rawData ) $diff = $current->diff + 1; else $diff = $current->diff;
            $echo = $query->update([
                        'name' => $this->name,
                        'url' => $this->apiUrl.$this->rawQuery,                    
                        'date' => date('Y-m-d'),                
                        'responsecode' => $this->responseCode,
                        'rawdata' => $this->rawData,
                        'count'=> $current->count + 1,
                        'diff'=> $diff
            ]);
        } else {
            DB::table('stats_externalapi')->insert(
                [
                    'name' => $this->name,
                    'url' => $this->apiUrl.$this->rawQuery,                    
                    'date' => date('Y-m-d'),                
                    'responsecode' => $this->responseCode,
                    'rawdata' => $this->rawData,
                    'count'=> 1,
                    'diff' => 1
                ]
            );
        }
    }
    
    function Response404() {
        throw new \Exception("External API returned 404 = Not Found.");
    }
	
	function curl_setopt($name, $value) {
		$this->curl_opts[$name] = $value;
	
	
	}
	
	
}

class Exception extends \Exception {
	
}