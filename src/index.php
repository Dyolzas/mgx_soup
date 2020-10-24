<?php

    include __dir__.'/../vendor/autoload.php';
    include __dir__.'/Soup.php';
    include __dir__.'/secrets.php'; //This is excluded on git. It has the secret keys of Yandex and Slack (TRANSLATION_KEY and SLACK_KEY)

    use Smalot\PdfParser\Parser;

    setlocale(LC_ALL, array('nl_NL.utf8','nl_NL@euro','nl_NL', 'nld_NLD', 'dutch'));

    const BASE_URL = 'https://www.partyline.be/images/downloads/';
    // 30/01
    //const REGEX = '/\d{2}\/\d{2}/';
    //const FORMAT = '%d/%m';

    // ma 3 feb
    const ONE_DAY = 86400;
    const REGEX = '/(?:ma|di|wo|do|vr) \d{1,2} [a-z]{3}/';
    const FORMAT = '%a %e %b';

    $url = generateURL();
    if(!is_null($url)){
        $data = getDataFromPDFFile((new Parser())->parseFile($url));
        $arrayOfSoups = parseSoups($data);
        $topic = generateTopic($arrayOfSoups);
        sendTopicToSlack($topic);
    }

    /////////////////////////////////////
    ///
    ///      Generating URL
    ///
    ////////////////////////////////////

    function generateURL(){
        $today = strtotime('today');
        $monday = strtotime('monday this week');
        $friday = strtotime('friday this week');
        if(date('N', $today) >= 6)
            return null; //weekend
        $url = BASE_URL.'menu'.strftime('%d',$monday).'t'.strftime('%d',$friday).strftime('%b',$monday).'nl.pdf';
        if(!urlExists($url))
            return null;
        return $url;
    }

    function urlExists($url){
        $file_headers = @get_headers($url);
        if(!$file_headers || containsSubstring($file_headers[0], '404'))
            return false;
        return true;
    }

    function containsSubstring($string, $needle){
        return (strpos($string, $needle) !== false);
    }

    /////////////////////////////////////
    ///
    ///      Parsing PDF File
    ///
    /// /////////////////////////////////

    function getDataFromPDFFile($pdf){
        $data = explode("\t", $pdf->getText());
        $data = array_values(array_filter(array_map('trim',$data)));
        return $data;
    }

    function parseSoups($data){
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

    /////////////////////////////////////
    ///
    ///      Translating
    ///
    /// /////////////////////////////////

    function translateSoup($string){
        $result = basicTranslateSoup($string.' soep')->text[0];
        $result = trim(substr($result, 0, strpos($result, 'soup')?:-4));
        return $result;
    }

    function basicTranslateSoup($string){
        return json_decode(file_get_contents('https://translate.yandex.net/api/v1.5/tr.json/translate?key='.TRANSLATION_KEY.'&lang=nl-en&text='.urlencode($string)));
    }

    /////////////////////////////////////
    ///
    ///      Creating Topic
    ///
    /// /////////////////////////////////

    function generateTopic($arrayOfSoups){
        return implode(' ', $arrayOfSoups);
    }

    /////////////////////////////////////
    ///
    ///      Pushing Topic to Slack
    ///
    /// /////////////////////////////////

    function sendTopicToSlack($topic) {

        $url = "https://mediagenix.slack.com/api/conversations.info";
        $data = http_build_query(array("token" => SLACK_KEY, "channel" => "C3TS59BHT"));

        $result = sendAction($url, $data);

        $old_topic = json_decode($result)->channel->topic->value;

        if ($old_topic != $topic) {
            $url = "https://mediagenix.slack.com/api/conversations.setTopic";
            $data = http_build_query(array("token" => SLACK_KEY, "topic" => $topic, "channel" => "C3TS59BHT"));
            sendAction($url, $data);
        }
    }

    function sendAction($url, $data) {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }

