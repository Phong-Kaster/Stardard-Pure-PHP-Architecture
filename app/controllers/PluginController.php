<?php
/**
 * Plugin Controller
 */
class PluginController extends Controller
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


        if (isset($Route->params->hash)) {
            if (file_exists(TEMP_PATH . "/plugin-". $Route->params->hash . ".zip")) {
                $this->install();
            } else {
                header("Location: ".APPURL."/plugins");
                exit;
            }
        }

        if (Input::post("action") == "upload") {
            $this->upload();
        }

        $this->view("plugin");
    }


    /**
     * Upload zip
     * @return void 
     */
    private function upload()
    {
        $this->resp->result = 0;

        // Check file
        if (empty($_FILES["file"]) || $_FILES["file"]["size"] <= 0) {
            $this->resp->msg = __("File not received!");
            $this->jsonecho();
        }

        // Check file extension
        $ext = strtolower(pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION));
        if ($ext != "zip") {
            $this->resp->msg = __("Only zip files are allowed");
            $this->jsonecho();
        }

        // Upload file
        $tempname = uniqid();
        $temp_dir = TEMP_PATH;
        if (!file_exists($temp_dir)) {
            mkdir($temp_dir);
        } 
        $filepath = $temp_dir . "/plugin-" . $tempname . ".zip";
        if (!move_uploaded_file($_FILES["file"]["tmp_name"], $filepath)) {
            $this->resp->msg = __("Oops! An error occured. Please try again later!");
            $this->jsonecho();
        }

        // Check & validate plugin
        try {
            $this->check($tempname);
        } catch (Exception $e) {
            $this->resp->msg = $e->getMessage();
            $this->jsonecho();
        }

        $this->resp->result = 1;
        $this->resp->redirect = APPURL . "/plugins/install/".$tempname;
        $this->jsonecho();
    }


    private function install()
    {
        $Route = $this->getVariable("Route");
        $tempname = $Route->params->hash;
        $filepath = TEMP_PATH . "/plugin-" . $tempname . ".zip";

        $InstallResult = new stdClass;
        $InstallResult->resp = 0;

        // Check & validate plugin (yes, again)
        try {
            $idname = $this->check($tempname);
        } catch (Exception $e) {
            $InstallResult->msg = $e->getMessage();
            $this->setVariable("InstallResult", $InstallResult);
            return false;
        }

        // Create a directory for plugin
        $plugin_dir = PLUGINS_PATH . "/" . $idname;
        mkdir($plugin_dir, 0777, true);

        // Extract plugin files from archive
        $zip = new ZipArchive;
        if ($zip->open($filepath) !== TRUE) {
            $zip->close();
            $InstallResult->msg = __("Oops! An error occured. Please try again later!");
            $this->setVariable("InstallResult", $InstallResult);
            return false;
        }
        $zip->extractTo($plugin_dir);
        $zip->close();

        // Get plugin config.
        $config = require $plugin_dir . "/config.php";

        // Record plugin
        $Plugin = Controller::model("Plugin");
        $Plugin->set("idname", $config["idname"])
               ->save();

        // Installed, remove zip archive
        delete($filepath);


        // Load plugin
        $file = PLUGINS_PATH . "/" . $idname . "/" . $idname . ".php";
        if (file_exists($file)) {
            require_once $file;
        }

        Event::trigger("plugin.install", $Plugin);
        

        $InstallResult->resp = 1;
        $this->setVariable("InstallResult", $InstallResult);
        return false;
    }


    /**
     * Checks the plugin zip archive in temp directory
     * and return validation result
     * 
     * @param  string $tempname file-hash
     * @return string           idname of the plugin
     */
    private function check($tempname)
    {   
        // Check if zip extension installed
        if (!class_exists('ZipArchive')) {
            throw new Exception(__("Please enable PHP ZIP extension (ZipArchive) and try again!"));
        }

        $filepath = TEMP_PATH . "/plugin-" . $tempname . ".zip";

        // Validate plugin
        $zip = new ZipArchive;
        if ($zip->open($filepath) !== TRUE) {
            $zip->close();
            delete($filepath);
            throw new Exception(__("Oops! An error occured. Please try again later!"));
        }

        $config_file_content = $zip->getFromName("config.php");
        if (!$config_file_content) {
            $zip->close();
            delete($filepath);
            throw new Exception(__("Plugin is not valid for installation."));
        }

        // Save config as temp file;
        $config_file_path = TEMP_PATH . "/plugin-". $tempname."-config.php";
        if (!file_put_contents($config_file_path, $config_file_content)) {
            $zip->close();
            delete($filepath);
            throw new Exception(__("Oops! An error occured. Please try again later!"));
        }

        $config = require($config_file_path);
        if (empty($config["idname"])) {
            $zip->close();
            delete($filepath);
            delete($config_file_path);
            throw new Exception(__("Plugin is not valid for installation."));
        }

        $idname = $config["idname"];
        if (preg_match("/[^A-Za-z0-9\-]/", $idname)) {
            $zip->close();
            delete($filepath);
            delete($config_file_path);
            throw new Exception(__("Plugin idname is not valid."));
        }


        $Plugin = Controller::model("Plugin", $idname);
        if ($Plugin->isAvailable()) {
            $zip->close();
            delete($filepath);
            delete($config_file_path);
            throw new Exception(__("Plugin with same idname is already exists."));  
        }

        // Plugin seems as valid,
        // Delete temporary plugin config file
        delete($config_file_path);

        return $idname;
    }
}