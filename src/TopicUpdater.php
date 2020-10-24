<?php


    class TopicUpdater {

        public static function updateTopic($topic){
            TopicUpdater::sendTopicToSlack($topic);
        }

        private static function sendTopicToSlack($topic) {

            $url = "https://mediagenix.slack.com/api/conversations.info";
            $data = http_build_query(array("token" => SLACK_KEY, "channel" => "C3TS59BHT"));

            $result = TopicUpdater::sendAction($url, $data);

            $old_topic = json_decode($result)->channel->topic->value;

            if ($old_topic != $topic) {
                $url = "https://mediagenix.slack.com/api/conversations.setTopic";
                $data = http_build_query(array("token" => SLACK_KEY, "topic" => $topic, "channel" => "C3TS59BHT"));
                TopicUpdater::sendAction($url, $data);
            }
        }

        private static function sendAction($url, $data) {
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            $result = curl_exec($curl);
            curl_close($curl);
            return $result;
        }

    }