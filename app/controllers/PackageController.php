<?php
/**
 * Package Controller
 */
class PackageController extends Controller
{
    /**
     * Process
     */
    public function process()
    {
        $AuthUser = $this->getVariable("AuthUser");
        $Route = $this->getVariable("Route");

        // Auth
        if (!$AuthUser){
            header("Location: ".APPURL."/login");
            exit;
        } else if ($AuthUser->isExpired()) {
            header("Location: ".APPURL."/expired");
            exit;
        } else if (!$AuthUser->isAdmin()) {
            header("Location: ".APPURL."/post");
            exit;
        }

        // Get package
        $Package = Controller::model("Package");
        if (isset($Route->params->id)) {
            $Package->select($Route->params->id);

            if (!$Package->isAvailable()) {
                header("Location: ".APPURL."/packages");
                exit;
            }
        }


        // Get Settings
        $Settings = Controller::model("GeneralData", "settings");

        // Set variables
        $this->setVariable("Settings", $Settings)
             ->setVariable("Package", $Package);
        
        if (Input::post("action") == "save") { 
            $this->save();
        }
        $this->view("package");
    }


    /**
     * Save (new|edit) package
     * @return void
     */
    private function save()
    {
        $this->resp->result = 0;
        $AuthUser = $this->getVariable("AuthUser");
        $Package = $this->getVariable("Package");
        $Settings = $this->getVariable("Settings");

        // Check if this is new or not
        $is_new = !$Package->isAvailable();

        // Check required fields
        if (!Input::post("title")) {
            $this->resp->msg = __("Missing some of required data.");
            $this->jsonecho();
        }


        // Prices
        $monthly_price = (double)Input::post("monthly-price");
        if ($monthly_price < 0) {
            $monthly_price = 0;
        }
        if (isZeroDecimalCurrency($Settings->get("data.currency"))) {
            $monthly_price = round($monthly_price);
        }

        $annual_price = (double)Input::post("annual-price");
        if ($annual_price < 0) {
            $annual_price = 0;
        }
        if (isZeroDecimalCurrency($Settings->get("data.currency"))) {
            $annual_price = round($annual_price);
        }

        // Options
        $options = [];

        // Storage
        $storage_total = (double)Input::post("storage-total");
            if ($storage_total < 0 && $storage_total != "-1") {
                $storage_total = 0;
            }
            if ($storage_total != "-1") {
                $storage_total = number_format($storage_total, 2, ".", "");
            }
        $storage_file = (double)Input::post("storage-file");
            if ($storage_file < 0 && $storage_file != "-1") {
                $storage_file = 0;
            }
            if ($storage_file != "-1") {
                $storage_file = number_format($storage_file, 2, ".", "");
            }
        $options["storage"] = [
            "total" => $storage_total,
            "file" => $storage_file
        ];
        
        // Accounts
        $accounts = (int)Input::post("accounts");
        if ($accounts < 0 && $accounts != "-1") {
            $accounts = 0;
        }
        $options["max_accounts"] = $accounts;

        // File pickers
        $options["file_pickers"] = [
            "dropbox" => (boolean)Input::post("dropbox"),
            "onedrive" => (boolean)Input::post("onedrive"),
            "google_drive" => (boolean)Input::post("google-drive")
        ];

        // Post Types
        $options["post_types"] = [
            "timeline_photo" => (boolean)Input::post("timeline-photo"),
            "timeline_video" => (boolean)Input::post("timeline-video"),

            "story_photo" => (boolean)Input::post("story-photo"),
            "story_video" => (boolean)Input::post("story-video"),

            "album_photo" => (boolean)Input::post("album-photo"),
            "album_video" => (boolean)Input::post("album-video"),
        ];

        // Other options
        $options["spintax"] = (boolean)Input::post("spintax");
        $options["modules"] = Input::post("modules");
        
        $Package->set("title", Input::post("title"))
                ->set("monthly_price", $monthly_price)
                ->set("annual_price", $annual_price)
                ->set("settings", json_encode($options))
                ->set("is_public", Input::post("invisible") ? 0 : 1)
                ->save();


        // Update subscribers
        if (!$is_new && Input::post("update-subscribers")) {
            DB::table(TABLE_PREFIX.TABLE_USERS)
              ->where("package_subscription", "=", 1)
              ->where("package_id", "=", $Package->get("id"))
              ->update(["settings" => json_encode($options)]);
        }



        $this->resp->result = 1;
        if ($is_new) {
            $this->resp->msg = __("Package added successfully! Please refresh the page.");
            $this->resp->reset = true;
        } else {
            if (Input::post("update-subscribers")) {
                $this->resp->msg = __("Changes saved and subscribers updated!");    
            } else {
                $this->resp->msg = __("Changes saved!");
            }
        }
        $this->jsonecho();
    }
}