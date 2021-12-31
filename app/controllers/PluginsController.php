<?php
/**
 * Plugins Controller
 */
class PluginsController extends Controller
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


        // Get plugins
        $Plugins = Controller::model("Plugins");
        $Plugins->setPageSize(20)
                ->setPage(Input::get("page"))
                ->orderBy("id","DESC")
                ->fetchData();

        $this->setVariable("Plugins", $Plugins);
        
        if (Input::post("action") == "remove") {
            $this->remove();
        } else if (Input::post("action") == "activate") {
            $this->activate();
        } else if (Input::post("action") == "deactivate") {
            $this->deactivate();
        }

        $this->view("plugins");
    }


    /**
     * Remove Plugin
     * @return void
     */
    private function remove()
    {   
        $this->resp->result = 0;

        if (!Input::post("id")) {
            $this->resp->msg = __("ID is requred!");
            $this->jsonecho();
        }

        $Plugin = Controller::model("Plugin", Input::post("id"));

        if (!$Plugin->isAvailable()) {
            $this->resp->msg = __("Invalid ID");
            $this->jsonecho();
        }

        if (!$Plugin->get("is_active")) {
            $idname = $Plugin->get("idname");
            $file = PLUGINS_PATH . "/" . $idname . "/" . $idname . ".php";
            if (file_exists($file)) {
                require_once $file;
            }
        }

        // Trigger plugin remove event
        Event::trigger("plugin.remove", $Plugin);

        if ($Plugin->get("idname")) {
            delete(PLUGINS_PATH . "/" . $Plugin->get("idname"));
        }

        $Plugin->delete();

        
        $this->resp->result = 1;
        $this->jsonecho();
    }


    /**
     * Activate Plugin
     * @return void
     */
    private function activate()
    {   
        $this->resp->result = 0;

        if (!Input::post("id")) {
            $this->resp->msg = __("ID is requred!");
            $this->jsonecho();
        }

        $Plugin = Controller::model("Plugin", Input::post("id"));

        if (!$Plugin->isAvailable()) {
            $this->resp->msg = __("Invalid ID");
            $this->jsonecho();
        }

        if ($Plugin->get("is_active")) {
            $this->resp->msg = __("Plugin is already active!");
            $this->jsonecho();   
        }

        $Plugin->set("is_active", 1)->save();

        // Plugin is activated, load it and trigger plugin.activate event
        if ($Plugin->get("idname")) {
            $idname = $Plugin->get("idname");
            $file = PLUGINS_PATH . "/" . $idname . "/" . $idname . ".php";
            if (file_exists($file)) {
                require_once $file;
            }

            Event::trigger("plugin.activate", $Plugin);
        }

        
        $this->resp->result = 1;
        $this->resp->title = __("Success!");
        $this->resp->msg = __("Plugin activated!");
        $this->jsonecho();
    }


    /**
     * Deactivate Plugin
     * @return void
     */
    private function deactivate()
    {   
        $this->resp->result = 0;

        if (!Input::post("id")) {
            $this->resp->msg = __("ID is requred!");
            $this->jsonecho();
        }

        $Plugin = Controller::model("Plugin", Input::post("id"));

        if (!$Plugin->isAvailable()) {
            $this->resp->msg = __("Invalid ID");
            $this->jsonecho();
        }

        if (!$Plugin->get("is_active")) {
            $this->resp->msg = __("Plugin is already deactive!");
            $this->jsonecho();   
        }

        $Plugin->set("is_active", 0)->save();

        // Plugin is deactivated, load it and trigger plugin.deactivate event
        if ($Plugin->get("idname")) {
            $idname = $Plugin->get("idname");
            $file = PLUGINS_PATH . "/" . $idname . "/" . $idname . ".php";
            if (file_exists($file)) {
                require_once $file;
            }

            Event::trigger("plugin.deactivate", $Plugin);
        }

        
        $this->resp->result = 1;
        $this->resp->title = __("Success!");
        $this->resp->msg = __("Plugin deactivated!");
        $this->jsonecho();
    }
}