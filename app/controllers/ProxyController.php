<?php
/**
 * Proxy Controller
 */
class ProxyController extends Controller
{
    /**
     * Process
     */
    public function process()
    {
        $Route = $this->getVariable("Route");
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


        $Proxy = Controller::model("Proxy");
        if (isset($Route->params->id)) {
            $Proxy->select($Route->params->id);

            if (!$Proxy->isAvailable()) {
                header("Location: ".APPURL."/proxies");
                exit;
            }
        }


        // Get countries
        require_once(APPPATH.'/inc/countries.inc.php');


        $this->setVariable("Proxy", $Proxy)
             ->setVariable("Countries", $Countries);

        if (Input::post("action") == "save") {
            $this->save();
        }
        
        $this->view("proxy");
    }


    /**
     * Save (new|edit) proxy
     * @return void 
     */
    private function save()
    {
        $this->resp->result = 0;
        $AuthUser = $this->getVariable("AuthUser");
        $Proxy = $this->getVariable("Proxy");
        $Countries = $this->getVariable("Countries");

        // Check if this is new or not
        $is_new = !$Proxy->isAvailable();

        // Check required fields
        $required_fields = ["proxy"];

        foreach ($required_fields as $field) {
            if (!Input::post($field)) {
                $this->resp->msg = __("Missing some of required data.");
                $this->jsonecho();
            }
        }


        // Check country
        $country = "";
        if (Input::post("country") && isset($Countries[Input::post("country")])) {
            $country = Input::post("country");
        }


        // CHECK PROXY
        if (!isValidProxy(Input::post("proxy"))) {
            $this->resp->msg = __("Proxy is not valid or active!");
            $this->jsonecho();
        }

        // Start setting data
        $Proxy->set("proxy", Input::post("proxy"))
              ->set("country_code", $country)
              ->save();

        $this->resp->result = 1;
        if ($is_new) {
            $this->resp->msg = __("Proxy added successfully! Please refresh the page.");
            $this->resp->reset = true;
        } else {
            $this->resp->msg = __("Changes saved!");
        }
        $this->jsonecho();
    }
}