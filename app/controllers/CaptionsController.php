<?php
/**
 * Captions Controller
 */
class CaptionsController extends Controller
{
    /**
     * Process
     */
    public function process()
    {
        $AuthUser = $this->getVariable("AuthUser");
        $EmailSettings = \Controller::model("GeneralData", "email-settings");

        // Auth
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

        // Get captions
        $Captions = Controller::model("Captions");
            $Captions->setPageSize(8)
                     ->setPage(Input::get("page"))
                     ->where("user_id", "=", $AuthUser->get("id"))
                     ->orderBy("id","DESC")
                     ->fetchData();

        $this->setVariable("Captions", $Captions);
        
        if (Input::post("action") == "remove") {
            $this->remove();
        }

        $this->view("captions");
    }



    /**
     * Remove Caption
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

        $Caption = Controller::model("Caption", Input::post("id"));

        if (!$Caption->isAvailable() || 
            $Caption->get("user_id") != $AuthUser->get("id")) 
        {
            $this->resp->msg = __("Invalid ID");
            $this->jsonecho();
        }

        $Caption->delete();
        
        $this->resp->result = 1;
        $this->jsonecho();
    }
}