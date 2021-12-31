<?php
/**
 * Caption Controller
 */
class CaptionController extends Controller
{
    /**
     * Process
     */
    public function process()
    {
        $AuthUser = $this->getVariable("AuthUser");
        $Route = $this->getVariable("Route");
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
            $Captions->setPage(Input::get("page"))
                     ->where("user_id", "=", $AuthUser->get("id"))
                     ->fetchData();


        // Caption
        if (isset($Route->params->id)) {
            $Caption = Controller::model("Caption", $Route->params->id);
            if (!$Caption->isAvailable() || 
                $Caption->get("user_id") != $AuthUser->get("id")) 
            {
                header("Location: ".APPURL."/captions");
                exit;
            }
        } else {
            $Caption = Controller::model("Caption"); // new caption model
        }


        // Set view variables
        $this->setVariable("Captions", $Captions)
             ->setVariable("Caption", $Caption);


        if (Input::post("action") == "save") {
            $this->save();
        }
        $this->view("caption");
    }


    /**
     * Save (new|edit) caption
     * @return void 
     */
    private function save()
    {
        $this->resp->result = 0;
        $AuthUser = $this->getVariable("AuthUser");
        $Caption = $this->getVariable("Caption");

        $title = Input::post("title");
        $caption = Input::post("caption");


        if (!$title) {
            $this->resp->msg = __("Missing some of required data.");
            $this->jsonecho();
        }

        // Check if this is new or not
        $is_new = !$Caption->isAvailable();

        // Emojione Client
        $Emojione = new \Emojione\Client(new \Emojione\Ruleset());

        $Caption->set("user_id", $AuthUser->get("id"))
                ->set("title", $title)
                ->set("caption", $Emojione->toShort($caption))
                ->save();

        $this->resp->result = 1;
        if ($is_new) {
            $this->resp->redirect = APPURL."/captions";
        } else {
            $this->resp->msg = __("Changes saved!");
        }
        $this->jsonecho();
    }
}