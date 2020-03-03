<?PHP
    error_reporting(1); ini_set('display_errors', 1);

    /*
    *   This is a simple API wrapper written by Freek Bes for the Zermelo API.
    *   Documentation for the Zermelo API is available at developers.zermelo.nl
    *   or at schoolnaam.zportal.nl/static/swagger/
    */

    class Zermelo {
        // the subdomain for zermelo: ex. damstedelyceum.zportal.nl
        private $subdomain = null;

        // an access token for the zermelo portal instance
        private $accessToken = null;

        // the branch of school of which to retrieve appointments
        private $branch = null;

        // the filename of the cache file
        private $cacheFileName = "schedule.json";

        // the duration for which cache will be valid (in seconds)
        private $cacheDuration = 120;

        // the cache folder location
        private function getCacheFolder() {
            return dirname(__FILE__) . "/cache";
        }

        // create cache dir if it does not exist
        private function initializeCacheDir() {
            if (!file_exists($this->getCacheFolder())) {
                mkdir($this->getCacheFolder(), 0777, true);
            }
        }

        // this function gets run on construction of a class instance
        function __construct() {
            $this->initializeCacheDir();
        }

        // set the subdomain
        public function setSchool($school) {
            $this->subdomain = $school;
        }

        // set the api token
        public function setApiToken($apiToken) {
            $this->accessToken = $apiToken;
        }

        // set the branch of school
        public function setBranchOfSchool($branchOfSchool) {
            $this->branch = $branchOfSchool;
        }

        // get the timestamp for the start of the schedule to retrieve
        private function getStartTimestamp() {
            return strtotime("now - 51 minute");
            // return strtotime("28-08-2019 00:00:00");
        }

        // get the timestamp for the end of the schedule to retrieve
        private function getEndTimestamp() {
            return strtotime("today + 1 day");
            // return strtotime("29-08-2019 00:00:00");
        }

        // get the api url
        private function getApiUrl() {
            return "https://".$this->subdomain.".zportal.nl/api/v3/appointments?access_token=".urlencode($this->accessToken)."&valid=true&start=".$this->getStartTimestamp()."&end=".$this->getEndTimestamp()."&branchOfSchool=".$this->branch."&fields=id,appointmentInstance,start,end,startTimeSlot,endTimeSlot,subjects,teachers,locations,groups,lastModified,new,cancelled,teacherChanged,groupChanged,locationChanged,timeChanged,changeDescription,remark";
        }

        // check if appointment has changed
        private function isInvalidForReturn($a) {
            return (
                (empty($a["changeDescription"]) && empty($a["remark"]))
            );
        }

        // retrieve all appointments for a certain time period
        // boolean to set whether to return all appointments or only changed ones
        private function getAppointmentsFromAPI($onlyChanged) {
            // retrieve appointments from Zermelo
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->getApiUrl());
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
            // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->secure);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            curl_close($ch);
            $json = json_decode($result, true);
            $schedule = $json["response"]["data"];
            $scheduleCount = count($schedule);

            // check if appointments have actually changed
            if ($onlyChanged) {
                for ($i = 0; $i < $scheduleCount; $i++) {
                    if ($this->isInvalidForReturn($schedule[$i])) {
                        unset($schedule[$i]);
                    }
                }
                $schedule = array_values($schedule);
            }
            return $schedule;
        }

        // checks if the cache is outdated and needs updating
        private function cacheNeedsUpdating() {
            $cacheFile = $this->getCacheFolder() . "/" . $this->cacheFileName;
            return (!file_exists($cacheFile) || (time() - $this->cacheDuration) > intval(filemtime($cacheFile)));
        }

        // saves appointments to cache
        private function updateCache($appointments) {
            $cacheFile = $this->getCacheFolder() . "/" . $this->cacheFileName;
            return file_put_contents($cacheFile, json_encode($appointments, JSON_UNESCAPED_UNICODE));
        }

        // retrieves appointments from cache or from an api call
        public function getAppointments() {
            if ($this->cacheNeedsUpdating()) {
                $appointments = $this->getAppointmentsFromAPI(true);
                $this->updateCache($appointments);
                return $appointments;
            }
            else {
                $cacheFile = $this->getCacheFolder() . "/" . $this->cacheFileName;
                $appointments = json_decode(file_get_contents($cacheFile), true);
                return $appointments;
            }
        }
    }
?>