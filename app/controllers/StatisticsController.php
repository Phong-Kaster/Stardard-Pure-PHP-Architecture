<?php
/**
 * Statistics Controller
 */
class StatisticsController extends Controller
{
    /**
     * Process
     */
    public function process()
    {
        $AuthUser = $this->getVariable("AuthUser");
        $EmailSettings = \Controller::model("GeneralData", "email-settings");

        if (!$AuthUser){
            header("Location: ".APPURL."/login");
            exit;
        } else if (
            !$AuthUser->isAdmin() && 
            !$AuthUser->isEmailVerified() &&
            $EmailSettings->get("data.email_verification")) 
        {
            header("Location: ".APPURL."/profile?a=true");
            exit;
        } else if ($AuthUser->isExpired()) {
            header("Location: ".APPURL."/expired");
            exit;
        }



        // Get accounts
        $Accounts = Controller::model("Accounts");
        $Accounts->where("user_id", "=", $AuthUser->get("id"))
                 ->orderBy("id","DESC")
                 ->fetchData();
        $this->setVariable("Accounts", $Accounts);


        // Get Active Account
        $ActiveAccount = Controller::model("Account", Input::get("account"));
        if (!$ActiveAccount->isAvailable() || 
            $ActiveAccount->get("user_id") != $AuthUser->get("id")) {
            
            $data = $Accounts->getDataAs("Account");
            if (isset($data[0])) {
                $ActiveAccount = $data[0];
            }
        }
        $this->setVariable("ActiveAccount", $ActiveAccount);


        // Set start-end dates
        $this->setDates();

        // Get posts summary
        $this->getPostSummary();

        // Get post count for last six month
        $this->getPostsByMonth();

        if (Input::post("action") == "account-summary") {
            // Get Account Summary
            $this->getAccountSummary();
        }

        $this->view("statistics");
    }


    /**
     * Set start end dates
     */
    private function setDates()
    {
        // Define start-end dates
        $start = new \Moment\Moment(date("Y-m-d H:i:s", time()-86400*30), date_default_timezone_get());
        $end = new \Moment\Moment("now", date_default_timezone_get());

        if (Input::get("d")) {
            $dates = explode("/", Input::get("d"), 2);

            if (isValidDate(trim($dates[0]), "Y-m-d")) {
                $start = new \Moment\Moment(trim($dates[0]), $AuthUser->get("preferences.timezone"));
                $start->setTimezone(date_default_timezone_get());
                
                if (count($dates) == 2 && isValidDate(trim($dates[1]), "Y-m-d")) {
                    $end = new \Moment\Moment(trim($dates[1]), $AuthUser->get("preferences.timezone"));
                    $end->setTimezone(date_default_timezone_get());
                } else {
                    $end = $start;
                }
            }
        }

        $this->setVariable("start", $start)
             ->setVariable("end", $end);

        return $this;
    }


    /**
     * Get posts summary for active account for selected time interval
     * @return [type] [description]
     */
    private function getPostSummary()
    {
        $ActiveAccount = $this->getVariable("ActiveAccount");
        $AuthUser = $this->getVariable("AuthUser");
        $start = $this->getVariable("start");
        $end = $this->getVariable("end");


        if (!$ActiveAccount->isAvailable()) {
            // Active account is not available
            // Which means, user has not got any account
            // Proper message will be displayd in view
            // There is no need to further more action here
            return $this;
        }


        // Count posts in progress
        $query = DB::table(TABLE_PREFIX.TABLE_POSTS)
                 ->select([DB::raw("COUNT(id) as total")])
                 ->where("account_id", "=", $ActiveAccount->get("id"))
                 ->whereIn("status", ["scheduled", "publishing"])
                 ->whereBetween("schedule_date", 
                                $start->format("Y-m-d H:i:s"),
                                $end->format("Y-m-d H:i:s"));
        $res = $query->get();
        $inprogress = $res[0]->total;


        // Count published posts
        $query = DB::table(TABLE_PREFIX.TABLE_POSTS)
                 ->select([DB::raw("COUNT(id) as total")])
                 ->where("account_id", "=", $ActiveAccount->get("id"))
                 ->where("status", "=", "published")
                 ->whereBetween("publish_date", 
                                $start->format("Y-m-d H:i:s"),
                                $end->format("Y-m-d H:i:s"));
        $res = $query->get();
        $published = $res[0]->total;

        // Count failed posts
        $query = DB::table(TABLE_PREFIX.TABLE_POSTS)
                 ->select([DB::raw("COUNT(id) as total")])
                 ->where("account_id", "=", $ActiveAccount->get("id"))
                 ->where("status", "=", "failed")
                 ->whereBetween("create_date", 
                                $start->format("Y-m-d H:i:s"),
                                $end->format("Y-m-d H:i:s"));
        $res = $query->get();
        $failed = $res[0]->total;

        $account_summary = [
            "inprogress" => $inprogress,
            "published" => $published,
            "failed" => $failed,
        ];

        $this->setVariable("PostSummary", json_decode(json_encode($account_summary)));
    }


    /**
     * Get post counts for last six months
     * @return [type] [description]
     */
    private function getPostsByMonth()
    {
        $ActiveAccount = $this->getVariable("ActiveAccount");
        $AuthUser = $this->getVariable("AuthUser");

        if (!$ActiveAccount->isAvailable()) {
            // Active account is not available
            // Which means, user has not got any account
            // Proper message will be displayd in view
            // There is no need to further more action here
            return $this;
        }


        $months = [
            __("Jan"), __("Feb"), __("Mar"), __("Apr"), __("May"), __("Jun"),
            __("Jul"), __("Aug"), __("Sep"), __("Oct"), __("Nov"), __("Dec")
        ];
        $data = [];
        
        $i = 6;
        $now = new \Moment\Moment("now", $AuthUser->get("preferences.timezone"));
        $month = $now->format("m");
        $year = $now->format("Y");

        while ($i > 0) {
            $start = $year."-".$month."-01 00:00:00";
            $start = new \Moment\Moment($start, $AuthUser->get("preferences.timezone"));
            $start->setTimezone(date_default_timezone_get());
            $start = $start->format("Y-m-d H:i:s");

            $lastday = date("t", mktime(0, 0, 0, (int)$month, 1, $year));
            $end = $year."-".$month."-".sprintf("%02d", $lastday)." 23:59:59";
            $end = new \Moment\Moment($end, $AuthUser->get("preferences.timezone"));
            $end->setTimezone(date_default_timezone_get());
            $end = $end->format("Y-m-d H:i:s");


            // Count posts in progress
            $query = DB::table(TABLE_PREFIX.TABLE_POSTS)
                     ->select([DB::raw("COUNT(id) as total")])
                     ->where("account_id", "=", $ActiveAccount->get("id"))
                     ->whereIn("status", ["scheduled", "publishing"])
                     ->whereBetween("schedule_date", $start, $end);
            $res = $query->get();
            $inprogress = $res[0]->total;

            // Count published posts
            $query = DB::table(TABLE_PREFIX.TABLE_POSTS)
                     ->select([DB::raw("COUNT(id) as total")])
                     ->where("account_id", "=", $ActiveAccount->get("id"))
                     ->where("status", "=", "published")
                     ->whereBetween("publish_date", $start, $end);
            $res = $query->get();
            $published = $res[0]->total;

            // Count failed posts
            $query = DB::table(TABLE_PREFIX.TABLE_POSTS)
                     ->select([DB::raw("COUNT(id) as total")])
                     ->where("account_id", "=", $ActiveAccount->get("id"))
                     ->where("status", "=", "failed")
                     ->whereBetween("create_date", $start, $end);
            $res = $query->get();
            $failed = $res[0]->total;

            $total = $inprogress + $published + $failed;

            $data[] = [
                "month" => $months[(int)$month - 1],
                "data" => [
                    "inprogress" => $inprogress,
                    "published" => $published,
                    "failed" => $failed,
                ]
            ];

            $month--;
            if ($month <= 0) {
                $month = 12;
                $year--;
            }
            $month = sprintf("%02d", $month);

            $i--;
        }

        $data = array_reverse($data);

        $this->setVariable("PostsByMonths", json_decode(json_encode($data)));
    }


    /**
     * Get account summary for selected account
     * @return self 
     */
    private function getAccountSummary()
    {
        $this->resp->result = 0;
        $ActiveAccount = $this->getVariable("ActiveAccount");

        if (!$ActiveAccount->isAvailable()) {
            $this->resp->msg = __("Account is not available.");
            $this->jsonecho();
        }


        try {
            $Instagram = \InstagramController::login($ActiveAccount);
        } catch (\Exception $e) {
            $this->resp->msg = $e->getMessage();
            $this->jsonecho();
        }
        
        try {
            $resp = $Instagram->people->getSelfInfo();
        } catch (\Exception $e) {
            $this->resp->msg = $e->getMessage();
            $this->jsonecho();
        }

        if (!$resp->isOk()) {
            $this->resp->msg = __("Couldn't get user info.");
            $this->jsonecho();   
        }


        $this->resp->result = 1;
        $this->resp->data = [
            "media_count" => $resp->getUser()->getMediaCount(),
            "following_count" => $resp->getUser()->getFollowingCount(),
            "follower_count" => $resp->getUser()->getFollowerCount()
        ];
        $this->jsonecho();
    }
}