<?php


    class Soup {

        private $date;
        private $isVeggie = true;
        private $type;

        public function getDate() {
            return $this->date;
        }

        public function setDate($date) {
            $this->date = $date;
        }

        public function getIsVeggie() {
            return $this->isVeggie;
        }

        public function setIsVeggie($isVeggie) {
            $this->isVeggie = $isVeggie;
        }

        public function getType() {
            return $this->type;
        }

        public function setType($type) {
            $this->type = $type;
        }


    }