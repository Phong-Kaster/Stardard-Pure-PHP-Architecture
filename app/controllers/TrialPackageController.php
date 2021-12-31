<?php
/**
 * TrialPackage Controller
 */
class TrialPackageController extends Controller
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

        $TrialPackage = Controller::model("GeneralData", "free-trial");

        $this->setVariable("TrialPackage", $TrialPackage);
        
        if (Input::post("action") == "save") { 
            $this->save();
        }
        $this->view("package-trial");
    }


    /**
     * Save changes to trial package
     * @return void
     */
    public function save()
    {
        $this->resp->result = 0;
        $AuthUser = $this->getVariable("AuthUser");
        $TrialPackage = $this->getVariable("TrialPackage");

        $options = [];

        // Size
        $size = (int)Input::post("size");
        if ($size < 0 && $size != "-1") {
            $size = 0;
        }
        $options["size"] = $size;

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

        $TrialPackage->set("data", json_encode($options))
                     ->save();

        // Update subscribers
        if (Input::post("update-subscribers")) {
            $settings = $options;
            unset($settings["size"]);
            DB::table(TABLE_PREFIX.TABLE_USERS)
              ->where("package_subscription", "=", 1)
              ->where("package_id", "=", 0)
              ->update(["settings" => json_encode($settings)]);
        }

        $this->resp->result = 1;
        if (Input::post("update-subscribers")) {
            $this->resp->msg = __("Changes saved and subscribers updated!");    
        } else {
            $this->resp->msg = __("Changes saved!");
        }
        $this->jsonecho();
    }
}