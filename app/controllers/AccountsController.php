<?php
/**
 * Accounts Controller
 */
class AccountsController extends Controller
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

        // Get accounts
        $Accounts = Controller::model("Accounts");
            $Accounts->setPageSize(8)
                     ->setPage(Input::get("page"))
                     ->where("user_id", "=", $AuthUser->get("id"))
                     ->orderBy("id","DESC")
                     ->fetchData();

        $this->setVariable("Accounts", $Accounts);
        
        if (Input::post("action") == "remove") {
            $this->remove();
        }

        $this->view("accounts");
    }



    /**
     * Remove Account
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

        $Account = Controller::model("Account", Input::post("id"));

        if (!$Account->isAvailable() ||
            $Account->get("user_id") != $AuthUser->get("id")) 
        {
            $this->resp->msg = __("Invalid ID");
            $this->jsonecho();
        }

        // Delete instagram session data
        delete(APPPATH . "/sessions/" 
                       . $AuthUser->get("id") 
                       . "/" 
                       . $Account->get("username"));

        $Account->delete();
        
        $this->resp->result = 1;
        $this->jsonecho();
    }
}