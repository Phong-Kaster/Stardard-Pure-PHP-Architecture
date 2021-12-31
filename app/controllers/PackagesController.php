<?php
/**
 * Packages Controller
 */
class PackagesController extends Controller
{
    /**
     * Process
     */
    public function process()
    {
        $AuthUser = $this->getVariable("AuthUser");

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

        // Get packages
        $Packages = Controller::model("Packages");
            $Packages->search(Input::get("q"))
                     ->setPageSize(10)
                     ->setPage(Input::get("page"))
                     ->orderBy("id","DESC")
                     ->fetchData();

        $this->setVariable("Packages", $Packages)
             ->setVariable("Settings", Controller::model("GeneralData", "site-settings"));

        if (Input::post("action") == "remove") {
            $this->remove();
        }
        $this->view("packages");
    }


    /**
     * Remove Package
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
        
        $Package = Controller::model("Package", Input::post("id"));

        if (!$Package->isAvailable()) {
            $this->resp->msg = __("Package doesn't exist!");
            $this->jsonecho();
        }

        $Package->delete();

        $this->resp->result = 1;
        $this->jsonecho();
    }
}