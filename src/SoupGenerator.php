<?php

    use Smalot\PdfParser\Parser;

    const BASE_URL = 'https://www.partyline.be/images/downloads/';
    const ONE_DAY = 86400;

    class SoupGenerator {

        public static function getSoupOfTheDay(){
            $soups = SoupGenerator::getSoups();
            foreach($soups as $soup){
                /** @var Soup $soup */
                if($soup->isSoupOfTheDay())
                    return $soup;
            }
            return null;
        }

        public static function getSoups(){
            $url = SoupGenerator::generateURL();
            if(empty($url))
                return array();
            $data = SoupGenerator::getDataFromURL($url);
            return SoupGenerator::parseSoups($data);
        }

        /////////////////////////////////////
        ///
        ///      Generating URL
        ///
        ////////////////////////////////////

        private static function generateURL(){
            $today = strtotime('today');
            $monday = strtotime('monday this week');
            $friday = strtotime('friday this week');
           // if(date('N', $today) >= 6)
              //  return null; //weekend
            $url = BASE_URL.'menu'.strftime('%d',$monday).'t'.strftime('%d',$friday).strftime('%b',$monday).'nl.pdf';
            if(!SoupGenerator::urlExists($url))
                return null;
            return $url;
        }

        private static function urlExists($url){
            $file_headers = @get_headers($url);
            if(!$file_headers || SoupGenerator::containsSubstring($file_headers[0], '404'))
                return false;
            return true;
        }

        private static function containsSubstring($string, $needle){
            return (strpos($string, $needle) !== false);
        }

        /////////////////////////////////////
        ///
        ///      Parsing PDF File
        ///
        /// /////////////////////////////////

        private static function getDataFromURL($url){
            $pdf = (new Parser())->parseFile($url);
            $data = explode("\t", $pdf->getText());
            $data = array_values(array_filter(array_map('trim',$data)));
            return $data;
        }

        private static function parseSoups($data){
            $array = array();
            $key  = array_search ( 'Soep (* vleesbouillons)' , $data);

            if($key === false)
                return [];

            $sunday = strtotime('previous sunday');

            for($i=1; $i<6; $i++){
                $isVeggie = true;
                $type = $data[$i+$key];
                if(substr($type, -1) == '*'){
                    $type = substr($type, 0, -1);
                    $isVeggie = false;
                }
                $soup = new Soup();
                $soup->setType($type);
                $soup->setDate($sunday + $i * ONE_DAY);
                $soup->setIsVeggie($isVeggie);
                array_push($array, $soup);
            }
            return $array;
        }
    }