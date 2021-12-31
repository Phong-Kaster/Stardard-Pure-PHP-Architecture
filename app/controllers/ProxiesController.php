<?php
/**
 * Proxies Controller
 */
class ProxiesController extends Controller
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

        // Get Proxies
        $Proxies = Controller::model("Proxies");
            $Proxies->setPageSize(20)
                    ->setPage(Input::get("page"))
                    ->orderBy("id","DESC")
                    ->fetchData();

        // Get countries
        require_once(APPPATH.'/inc/countries.inc.php');

        $this->setVariable("Proxies", $Proxies)
             ->setVariable("Countries", $Countries);

        if (Input::post("action") == "remove") {
            $this->remove();
        }
        $this->view("proxies");
    }


    /**
     * Remove Proxy
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

        $Proxy = Controller::model("Proxy", Input::post("id"));

        if (!$Proxy->isAvailable()) {
            $this->resp->msg = __("Proxy doesn't exist!");
            $this->jsonecho();
        }


        $Proxy->delete();

        $this->resp->result = 1;
        $this->jsonecho();
    }
}