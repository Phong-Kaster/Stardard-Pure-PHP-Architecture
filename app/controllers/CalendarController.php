<?php
/**
 * Calendar Controller
 */
class CalendarController extends Controller
{
    /**
     * Process
     */
    public function process()
    {
        $AuthUser = $this->getVariable("AuthUser");
        $Route = $this->getVariable("Route");
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


        // Identify active account
        $ActiveAccount = Controller::model("Account", Input::get("account"));
        if ($ActiveAccount->isAvailable() &&
            $ActiveAccount->get("user_id") != $AuthUser->get("id")) {
            // Account doesn't belong to the authorized user
            $ActiveAccount = Controller::model("Account");
        }


        // Set view variables
        $this->setVariable("Accounts", $Accounts)
             ->setVariable("ActiveAccount", $ActiveAccount);


        if (Input::post("action") == "remove") {
            $this->remove();
        }


        if (isset($Route->params->day)) {
            $this->dayView();
        } else {
            $this->monthView();
        }
    }


    /**
     * Generate month view
     * @return null 
     */
    private function monthView()
    {
        $Route = $this->getVariable("Route");
        $AuthUser = $this->getVariable("AuthUser");
        $Accounts = $this->getVariable("Accounts");
        $ActiveAccount = $this->getVariable("ActiveAccount");

        // Check and validate date
        $year = isset($Route->params->year) ? $Route->params->year : 0;
        $month = isset($Route->params->month) ? $Route->params->month : 0;

        if (!isValidDate($year."-".$month."-01", "Y-m-d")) {
            $now = new DateTime("now", new DateTimeZone(date_default_timezone_get()));
            $now->setTimezone(new DateTimeZone($AuthUser->get("preferences.timezone")));

            $year = $now->format("Y");
            $month = $now->format("m");

            header("Location: ".APPURL."/calendar/".$year."/".$month);
            exit;
        }

        if ($Accounts->getTotalCount() > 0) {
            // Define start and end dates
            $start = new DateTime(
                $year . "-" . $month . "-01 00:00:00",
                new DateTimeZone($AuthUser->get("preferences.timezone")));
            $start->setTimezone(new DateTimeZone(date_default_timezone_get()));

            if ($month == 12) {
                $end = ($year + 1) . "-01-01 00:00:00";
            } else {
                $end = $year . "-" . sprintf("%02d", $month + 1) . "-01 00:00:00";
            }
            $end = new DateTime(
                $end, 
                new DateTimeZone($AuthUser->get("preferences.timezone")));
            $end->setTimezone(new DateTimeZone(date_default_timezone_get()));


            // Get scheduled
            $ScheduledPosts = Controller::model("Posts");
            $ScheduledPosts->where(TABLE_PREFIX.TABLE_POSTS.".user_id", "=", $AuthUser->get("id"))
                           ->where("is_scheduled", "=", 1)
                           ->whereIn("status", ["scheduled", "processing"])
                           ->where("schedule_date", ">=", $start->format("Y-m-d H:i:s"))
                           ->where("schedule_date", "<", $end->format("Y-m-d H:i:s"));

            if ($ActiveAccount->isAvailable()) {
                $ScheduledPosts->where(TABLE_PREFIX.TABLE_POSTS.".account_id", "=", $ActiveAccount->get("id"));
            }
            
            $ScheduledPosts->fetchData();

            // Completed (failed and published) posts
            $CompletedPosts = Controller::model("Posts");
            $CompletedPosts->where(TABLE_PREFIX.TABLE_POSTS.".user_id", "=", $AuthUser->get("id"))
                           ->whereIn("status", ["published", "failed"])
                           ->where("publish_date", ">=", $start->format("Y-m-d H:i:s"))
                           ->where("publish_date", "<", $end->format("Y-m-d H:i:s"));

            if ($ActiveAccount->isAvailable()) {
                $CompletedPosts->where(TABLE_PREFIX.TABLE_POSTS.".account_id", "=", $ActiveAccount->get("id"));
            }

            $CompletedPosts->fetchData();

            // post counts
            $postcounts = [];
            foreach ($ScheduledPosts->getData() as $p) {
                $d = new DateTime(
                    $p->schedule_date, 
                    new DateTimeZone(date_default_timezone_get()));
                $d->setTimezone(new DateTimeZone($AuthUser->get("preferences.timezone")));

                $daynumber = $d->format("d");

                if (empty($postcounts[$daynumber])) {
                    $postcounts[$daynumber] = [
                        "scheduled" => 0,
                        "published" => 0,
                        "failed" => 0
                    ];
                }

                $postcounts[$daynumber]["scheduled"]++;
            }

            foreach ($CompletedPosts->getData() as $p) {
                $d = new DateTime(
                    $p->publish_date, 
                    new DateTimeZone(date_default_timezone_get()));
                $d->setTimezone(new DateTimeZone($AuthUser->get("preferences.timezone")));

                $daynumber = $d->format("d");

                if (empty($postcounts[$daynumber])) {
                    $postcounts[$daynumber] = [
                        "scheduled" => 0,
                        "published" => 0,
                        "failed" => 0
                    ];
                }

                if ($p->status == "published") {
                    $postcounts[$daynumber]["published"]++;
                } else {
                    $postcounts[$daynumber]["failed"]++;
                }
            }

            // Set variables
            $this->setVariable("postcounts", $postcounts);
        }

        // Set variables
        $this->setVariable("month", $month)
             ->setVariable("year", $year)
             ->setVariable("viewtype", "month");

        $this->view("calendar");
    }


    /**
     * Generate day view
     * @return null 
     */
    private function dayView()
    {
        $Route = $this->getVariable("Route");
        $AuthUser = $this->getVariable("AuthUser");
        $Accounts = $this->getVariable("Accounts");
        $ActiveAccount = $this->getVariable("ActiveAccount");
        
        // Check validate date
        $day = $Route->params->day;
        $year = $Route->params->year;
        $month = $Route->params->month;

        if (!isValidDate($year."-".$month."-".$day, "Y-m-d")) {
            if (isValidDate($year."-".$month."-01", "Y-m-d")) {
                $url = APPURL."/calendar/".$year."/".$month;
            } else {
                $url = APPURL."/calendar/";
            }

            header("Location: ".$url);
            exit;
        }

        if ($Accounts->getTotalCount() > 0) {
            // Define start and end dates
            $start = new DateTime(
                $year . "-" . $month . "-" . $day . " 00:00:00",
                new DateTimeZone($AuthUser->get("preferences.timezone")));
            $start->setTimezone(new DateTimeZone(date_default_timezone_get()));

            $end = new DateTime(
                $year . "-" . $month . "-" . $day . " 23:59:59",
                new DateTimeZone($AuthUser->get("preferences.timezone")));
            $end->setTimezone(new DateTimeZone(date_default_timezone_get()));


            // Get scheduled posts
            $ScheduledPosts = Controller::model("Posts");
            $ScheduledPosts->where(TABLE_PREFIX.TABLE_POSTS.".user_id", "=", $AuthUser->get("id"))
                           ->where("is_scheduled", "=", 1)
                           ->whereIn("status", ["scheduled", "processing"])
                           ->where("schedule_date", ">=", $start->format("Y-m-d H:i:s"))
                           ->where("schedule_date", "<", $end->format("Y-m-d H:i:s"))
                           ->orderBy("schedule_date", "ASC");

            if ($ActiveAccount->isAvailable()) {
                $ScheduledPosts->where(TABLE_PREFIX.TABLE_POSTS.".account_id", "=", $ActiveAccount->get("id"));
            }
            
            $ScheduledPosts->fetchData();

            // Get published posts
            $PublishedPosts = Controller::model("Posts");
            $PublishedPosts->where(TABLE_PREFIX.TABLE_POSTS.".user_id", "=", $AuthUser->get("id"))
                           ->whereIn("status", ["published"])
                           ->where("publish_date", ">=", $start->format("Y-m-d H:i:s"))
                           ->where("publish_date", "<", $end->format("Y-m-d H:i:s"))
                           ->orderBy("publish_date", "DESC");

            if ($ActiveAccount->isAvailable()) {
                $PublishedPosts->where(TABLE_PREFIX.TABLE_POSTS.".account_id", "=", $ActiveAccount->get("id"));
            }

            $PublishedPosts->fetchData();

            // Get failed posts
            $FailedPosts = Controller::model("Posts");
            $FailedPosts->where(TABLE_PREFIX.TABLE_POSTS.".user_id", "=", $AuthUser->get("id"))
                           ->whereIn("status", ["failed"])
                           ->where("publish_date", ">=", $start->format("Y-m-d H:i:s"))
                           ->where("publish_date", "<", $end->format("Y-m-d H:i:s"))
                           ->orderBy("publish_date", "DESC");

            if ($ActiveAccount->isAvailable()) {
                $FailedPosts->where(TABLE_PREFIX.TABLE_POSTS.".account_id", "=", $ActiveAccount->get("id"));
            }

            $FailedPosts->fetchData();

            // Set variables
            $this->setVariable("ScheduledPosts", $ScheduledPosts)
                 ->setVariable("PublishedPosts", $PublishedPosts)
                 ->setVariable("FailedPosts", $FailedPosts);
        }


        $this->setVariable("year", $year)
             ->setVariable("month", $month)
             ->setVariable("day", $day)
             ->setVariable("viewtype", "day");

        $this->view("calendar");   
    }


    /**
     * Remove Post
     * @return void
     */
    private function remove()
    {
        $this->resp->result = 0;
        $AuthUser = $this->getVariable("AuthUser");

        if (!Input::post("id")) {
            $this->resp->msg = __("ID is requred!");
            $this->jsonecho();
        }

        $Post = Controller::model("Post", Input::post("id"));

        if (!$Post->isAvailable() || 
            $Post->get("user_id") != $AuthUser->get("id") ||
            in_array($Post->get("status"), ["published", "publishing"])) 
        {
            $this->resp->msg = __("Invalid ID");
            $this->jsonecho();
        }

        $Post->delete();

        $this->resp->result = 1;
        $this->jsonecho();
    }
}