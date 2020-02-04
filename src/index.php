<?php

    include __dir__.'/../vendor/autoload.php';
    include __dir__.'/Soup.php';
    include __dir__.'/secrets.php'; //This is excluded on git. It has the secret keys of Yandex and Slack (TRANSLATION_KEY and SLACK_KEY)

    use Smalot\PdfParser\Parser;

    setlocale(LC_ALL, 'nl_NL.utf8', 'nld_NLD');

    const URL = 'http://www.partyline.be/images/downloads/maandkalendernl.pdf';
    const REGEX = '/\d{2}\/\d{2}/';
    const FORMAT = '%d/%m';

    $parser = new Parser();
    $data = getDataFromPDFFile($parser->parseFile(URL));
    $arrayOfSoups = parseSoups($data);
    $topic = generateTopic($arrayOfSoups);
    sendTopicToSlack($topic);

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
        for($i=0; $i<sizeof($data); $i++){
            preg_match(REGEX,$data[$i],$matches);
            if(sizeof($matches) != 1)
                continue;
            $soup = findSoupWithIndex($data, $i);
            if(is_null($soup))
                continue;
            $soup->setDate($matches[0]);
            array_push($array, $soup);
        }
        return $array;
    }

    function findSoupWithIndex($data, $i){
        $soup = new Soup();
        for($j=$i; $j<sizeof($data); $j++){
            if(containsNumber($data[$j]))
                continue;
            if(substr($data[$j], -1) == '*') {
                $soup->setIsVeggie(false);
                $soup->setType(substr($data[$j], 0, -1));
                return $soup;
            }
            $soup->setType($data[$j]);
            if($data[$j+1] == '*')
                $soup->setIsVeggie(false);
            return $soup;
        }
        return null;
    }

    function containsNumber($string){
        return (1 === preg_match('~[0-9]~', $string));
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
        $topic = '';
        $date = strtotime('monday this week');
        $today =  strtotime('today');
        for($i = 0; $i<5; $i++){
            $dateString = strftime(FORMAT, $date);
            $soup = findSoupBasedOnDate($arrayOfSoups, $dateString);
            if($date == $today)
                $topic .= '*';
            $topic .= date('D', $date).': ';
            if(is_null($soup)){
                $topic .= 'Royco';
            } else{
                $topic .= translateSoup($soup->getType());
                if(!$soup->getIsVeggie())
                    $topic .= ' :cut_of_meat:';
            }
            if($date == $today)
                $topic .= '*';
            $topic .= ' ';
            $date += 86400;
        }
        return $topic;
    }

    function findSoupBasedOnDate($arrayOfSoups, $date){
        foreach($arrayOfSoups as $soup){
            if($soup->getDate() == $date)
                return $soup;
        }
        return null;
    }

    /////////////////////////////////////
    ///
    ///      Pushing Topic to Slack
    ///
    /// /////////////////////////////////

    function sendTopicToSlack($topic) {

        $url = "https://mediagenix.slack.com/api/channels.info";
        $data = http_build_query(array("token" => SLACK_KEY, "channel" => "C3TS59BHT"));

        $result = sendAction($url, $data);

        $old_topic = json_decode($result)->channel->topic->value;

        if ($old_topic != $topic) {
            $url = "https://mediagenix.slack.com/api/channels.setTopic";
            $data = http_build_query(array("token" => SLACK_KEY, "topic" => $topic, "channel" => "C3TS59BHT", "username" => "soup_channel"));
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

