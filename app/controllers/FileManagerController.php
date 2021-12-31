<?php
/**
 * FileManager Controller
 */
class FileManagerController extends Controller
{
    /**
     * Process
     */
    public function process()
    {
        $AuthUser = $this->getVariable("AuthUser");
        $EmailSettings = \Controller::model("GeneralData", "email-settings");

        if (!$AuthUser){
            $this->resp->result=0;
            $this->resp->msg = "User is not authorized.";
            $this->resp->error_code = "filemanager_unauthorized_user";
            $this->jsonecho();
        } else if (
            !$AuthUser->isAdmin() && 
            !$AuthUser->isEmailVerified() &&
            $EmailSettings->get("data.email_verification")) 
        {
            $this->resp->result=0;
            $this->resp->msg = "User's email is not verified.";
            $this->resp->error_code = "filemanager_unverified_email";
            $this->jsonecho();
        } else if ($AuthUser->isExpired()) {
            $this->resp->result=0;
            $this->resp->msg = "Account expired";
            $this->resp->error_code = "filemanager_expired_user";
            $this->jsonecho();
        }

        $this->connect();
    }



    /**
     * Connect to file manager
     * @return void
     */
    private function connect()
    {   
        $AuthUser = $this->getVariable("AuthUser");

        $connector_options = [
            "host" => DB_HOST,
            "database" => DB_NAME,
            "username" => DB_USER,
            "password" => DB_PASS,
            "charset" => DB_ENCODING,
            "table_name" => TABLE_PREFIX.TABLE_FILES,
            "opions" => array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ),

            "user_id" => $AuthUser->get("id")
        ];
        $Connector = new OneFileManager\Connector;
        $Connector->setOptions($connector_options)->init();



        /**
         * File manager configurations
         */
        $path_to_users_directory = ROOTPATH."/assets/uploads/"
                                 . $AuthUser->get("id")
                                 . "/";

        if (!file_exists($path_to_users_directory)) {
            mkdir($path_to_users_directory);
        } 

        $user_dir_url = APPURL."/assets/uploads/"
                      . $AuthUser->get("id")
                      . "/";

        $allow = ["jpeg", "jpg", "png", "mp4"];
        if (get_option("np_video_processing")) {
            $allow = array_merge($allow, ["mov", "m4v", "mpg"]);
        }

        $options = [
            "path" => $path_to_users_directory,
            "url" => $user_dir_url,

            "allow" => $allow,
            "queue_size" => 10
        ];

        if ($AuthUser->get("settings.storage.file") >= 0) {
            $options["max_file_size"] = (double)$AuthUser->get("settings.storage.file") * 1024*1024;
        }

        if ($AuthUser->get("settings.storage.total") >= 0) {
            $options["max_storage_size"] = (double)$AuthUser->get("settings.storage.total") * 1024*1024;
        }


        $FileManager = new OneFileManager\FileManager;
        $FileManager->setOptions($options)
                    ->setConnector($Connector)
                    ->run();
    }
}
