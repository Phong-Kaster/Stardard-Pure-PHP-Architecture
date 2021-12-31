<?php
/**
 * Account Controller
 */
class AccountController extends Controller
{
    /**
     * Instagram user name
     * @var string
     */
    private $username;

    /**
     * Instagram password
     * @var string
     */
    private $password;

    /**
     * Proxy address
     * @var string
     */
    private $proxy;

    /**
     * Whether it's system or user proxy
     * @var boolean
     */
    private $system_proxy = false;

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

        // Get accounts
        $Accounts = Controller::model("Accounts");
            $Accounts->setPage(Input::get("page"))
                     ->where("user_id", "=", $AuthUser->get("id"))
                     ->fetchData();

        // Account
        if (isset($Route->params->id)) {
            $Account = Controller::model("Account", $Route->params->id);
            if (!$Account->isAvailable() || 
                $Account->get("user_id") != $AuthUser->get("id")) 
            {
                header("Location: ".APPURL."/accounts");
                exit;
            }
        } else {
            $max_accounts = $AuthUser->get("settings.max_accounts");
            if ($Accounts->getTotalCount() >= $max_accounts && $max_accounts != "-1") {
                // Max. limit exceeds
                header("Location: ".APPURL."/accounts");
                exit;
            }

            $Account = Controller::model("Account"); // new account model
        }

        // Set view variables
        $this->setVariable("Accounts", $Accounts)
             ->setVariable("Account", $Account)
             ->setVariable("Settings", Controller::model("GeneralData", "settings"));

        if (Input::post("action") == "save") {
            $this->save();
        } else if (Input::post("action") == "2fa") {
            $this->twofa();
        } else if (Input::post("action") == "resend-2fa") {
            $this->resend2FA();
        } else if (Input::post("action") == "challenge") {
            $this->challenge();
        } else if (Input::post("action") == "resend-challenge") {
            $this->resendChallenge();
        }

        $this->view("account");
    }


    /**
     * Save (new|edit)
     * @return void 
     */
    private function save()
    {
        $this->resp->result = 0;

        $AuthUser = $this->getVariable("AuthUser");
        $Account = $this->getVariable("Account");
        $Settings = $this->getVariable("Settings");
        $IpInfo = $this->getVariable("IpInfo");

        // Check if this is new or not
        $is_new = !$Account->isAvailable();

        $this->username = strtolower(Input::post("username"));
        $this->password = Input::post("password");

        // Check required data
        if (!$this->username || !$this->password) {
            $this->resp->msg = __("Missing some of required data.");
            $this->jsonecho();
        }

        // Check username syntax
        if (!preg_match("/^([a-z0-9_][a-z0-9_\.]{1,28}[a-z0-9_])$/", $this->username)) {
            $this->resp->msg = __("Please include a valid username.");
            $this->jsonecho();
        }

        // Check username
        $check_username = true;
        if ($Account->isAvailable() && $Account->get("username") == $this->username) {
            $check_username = false;
        }

        if ($check_username) {
            foreach ($this->getVariable("Accounts")->getData() as $a) {
                if ($a->username == $this->username) {
                    // This account is already exists (for the current user)
                    $this->resp->msg = __("Account is already exists!");
                    $this->jsonecho();
                    break;
                }
            }
        }

        // Check proxy
        if ($Settings->get("data.proxy")) {
            if (Input::post("proxy") && $Settings->get("data.user_proxy")) {
                $this->proxy = Input::post("proxy");

                if (!isValidProxy($this->proxy)) {
                    $this->resp->msg = __("Proxy is not valid or active!");
                    $this->jsonecho();
                }
            } else {
                $user_country = !empty($IpInfo->countryCode) 
                              ? $IpInfo->countryCode : null;
                $countries = [];
                if (!empty($IpInfo->neighbours)) {
                    $countries = $IpInfo->neighbours;
                }
                array_unshift($countries, $user_country);
                $this->proxy = ProxiesModel::getBestProxy($countries);
                $this->system_proxy = true;
            }
        }

        // Remove previous session folder to make guarantee full relogin
        $session_dir = SESSIONS_PATH . "/" . $AuthUser->get("id") . "/" . $this->username;
        if (file_exists($session_dir)) {
            @delete($session_dir);
        }   

        // Encrypt the password
        try {
            $passhash = Defuse\Crypto\Crypto::encrypt($this->password, 
                        Defuse\Crypto\Key::loadFromAsciiSafeString(CRYPTO_KEY));
        } catch (\Exception $e) {
            $this->resp->msg = __("Encryption error");
            $this->jsonecho();
        }

        // Setup Instagram Client
        // Allow web usage
        // Since mentioned risks has been consider internally by Nextpost,
        // setting this property value to the true is not risky as it's name
        \InstagramAPI\Instagram::$allowDangerousWebUsageAtMyOwnRisk = true;
        
        $storageConfig = [
            "storage" => "file",
            "basefolder" => SESSIONS_PATH."/".$AuthUser->get("id")."/",
        ];

        $Instagram = new \InstagramAPI\Instagram(false, false, $storageConfig);
        $Instagram->setVerifySSL(SSL_ENABLED);

        if ($this->proxy) {
            $Instagram->setProxy($this->proxy);
        }
        
        $logged_in = false;
        try {
            $login_resp = $Instagram->login($this->username, $this->password);

            if ($login_resp !== null && $login_resp->isTwoFactorRequired()) {
                $this->resp->result = 2;
                $this->resp->twofa_required = true;
                $this->resp->msg = __(
                    "Enter the code sent to your number ending in %s", 
                    $login_resp->getTwoFactorInfo()->getObfuscatedPhoneNumber());
                $this->resp->identifier = $login_resp->getTwoFactorInfo()->getTwoFactorIdentifier();

                $_SESSION["TWOFA".$this->resp->identifier] = [
                    "username" => $this->username,
                    "password" => $this->password,
                    "proxy" => $this->proxy
                ];
            } else if ($login_resp) {
                $logged_in = true;
            }
        } catch (InstagramAPI\Exception\CheckpointRequiredException $e) {
            $this->resp->exception = $e;
            $this->resp->msg = __("Please goto <a href='http://instagram.com' target='_blank'>instagram.com</a> and pass checkpoint!");
        } catch (InstagramAPI\Exception\ChallengeRequiredException $e) {
            $this->handleChallengeException($Instagram, $e);
        } catch (InstagramAPI\Exception\AccountDisabledException $e) {
            $this->resp->msg = __(
                "Your account has been disabled for violating Instagram terms. <a href='%s'>Click here</a> to learn how you may be able to restore your account.", 
                "https://help.instagram.com/366993040048856");
        } catch (InstagramAPI\Exception\SentryBlockException $e) {
            $this->resp->msg = __("Your account has been banned from Instagram API for spam behaviour or otherwise abusing.");
        } catch (InstagramAPI\Exception\IncorrectPasswordException $e) {
            $this->resp->msg = __("The password you entered is incorrect. Please try again.");
        } catch (InstagramAPI\Exception\InvalidUserException $e) {
            $this->resp->msg = __("The username you entered doesn't appear to belong to an account. Please check your username and try again.");
        } catch (InstagramAPI\Exception\InstagramException $e) {
            if ($e->hasResponse()) {
                $msg = $e->getResponse()->getMessage();
            } else {
                $msg = explode(":", $e->getMessage(), 2);
                $msg = end($msg);
            }
            $this->resp->msg = $msg;
        } catch (\Exception $e) {
            $this->resp->msg = __("Oops! Something went wrong. Please try again later!");
        }

        if (!$logged_in) {
            // Not logged in
            // Either an error occured or 2FA login required or 
            // checkpoint required 

            if (!$is_new) {
                // Account is not new
                // Since new attempt to login has been made,
                // Account must be marked as not-logged-in for now
                $Account->set("login_required", 1)->save();
            }

            // Output result
            $this->jsonecho();
        }

        // Logged in successfully
        // Process and save data
        $Account->set("user_id", $AuthUser->get("id"))
                ->set("password", $passhash)
                ->set("proxy", $this->proxy ? $this->proxy : "")
                ->set("login_required", 0)
                ->set("instagram_id", $login_resp->getLoggedInUser()->getPk())
                ->set("username", $login_resp->getLoggedInUser()->getUsername())
                ->set("login_required", 0)
                ->save();

        // Update proxy use count
        if ($this->proxy && $this->system_proxy) {
            $Proxy = Controller::model("Proxy", $this->proxy);
            if ($Proxy->isAvailable()) {
                $Proxy->set("use_count", $Proxy->get("use_count") + 1)
                      ->save();
            }
        }

        $this->resp->result = 1;
        if ($is_new) {
            $this->resp->redirect = APPURL."/accounts";
        } else {
            $this->resp->changes_saved = true;
            $this->resp->msg = __("Changes saved!");
        }
        $this->jsonecho();
    }


    /**
     * Finish 2FA
     * @return void 
     */
    protected function twofa()
    {
        $this->resp->result = 0;

        $AuthUser = $this->getVariable("AuthUser");
        $Account = $this->getVariable("Account");

        // Check if this is new or not
        $is_new = !$Account->isAvailable();

        $security_code = Input::post("twofa-security-code");
        $twofaid = Input::post("2faid");

        if (!isset($_SESSION["TWOFA".$twofaid])) {
            $this->resp->msg = __("Oops! Something went wrong. Please try again later!");
            $this->resp->error_code = "account_invalid_identifier";
            $this->jsonecho();
        }

        // These variables have been saved to the session after validation
        // There is no need to validate them again here.
        $username = $_SESSION["TWOFA".$twofaid]["username"];
        $password = $_SESSION["TWOFA".$twofaid]["password"];
        $proxy = $_SESSION["TWOFA".$twofaid]["proxy"];

        // Encrypt the password
        try {
            $passhash = Defuse\Crypto\Crypto::encrypt($password, 
                        Defuse\Crypto\Key::loadFromAsciiSafeString(CRYPTO_KEY));
        } catch (\Exception $e) {
            $this->resp->msg = __("Encryption error");
            $this->jsonecho();
        }

        // Setup Instagram Client
        // Allow web usage
        // Since mentioned risks has been consider internally by Nextpost,
        // setting this property value to the true is not risky as it's name
        \InstagramAPI\Instagram::$allowDangerousWebUsageAtMyOwnRisk = true;
        
        $storageConfig = [
            "storage" => "file",
            "basefolder" => SESSIONS_PATH."/".$AuthUser->get("id")."/",
        ];

        $Instagram = new \InstagramAPI\Instagram(false, false, $storageConfig);
        $Instagram->setVerifySSL(SSL_ENABLED);

        if ($proxy) {
            $Instagram->setProxy($proxy);
        }

        try {
            $resp = $Instagram->finishTwoFactorLogin($username, $password, $twofaid, $security_code);
        } catch (InstagramAPI\Exception\CheckpointRequiredException $e) {
            $this->resp->msg = __("Please goto <a href='http://instagram.com' target='_blank'>instagram.com</a> and pass checkpoint!");
            $this->jsonecho();
        } catch (InstagramAPI\Exception\ChallengeRequiredException $e) {
            $this->handleChallengeException($Instagram, $e);
        } catch (InstagramAPI\Exception\InvalidSmsCodeException $e) {
            $this->resp->msg = __("Please check the security code sent you and try again.");
            $this->jsonecho();
        } catch (InstagramAPI\Exception\AccountDisabledException $e) {
            $this->resp->msg = __(
                "Your account has been disabled for violating Instagram terms. <a href='%s'>Click here</a> to learn how you may be able to restore your account.", 
                "https://help.instagram.com/366993040048856");
            $this->resp->login_failed = true;
            $this->jsonecho();
        } catch (InstagramAPI\Exception\SentryBlockException $e) {
            $this->resp->msg = __("Your account has been banned from Instagram API for spam behaviour or otherwise abusing.");
            $this->resp->login_failed = true;
            $this->jsonecho();
        } catch (InstagramAPI\Exception\InstagramException $e) {
            if ($e->hasResponse()) {
                $msg = $e->getResponse()->getMessage();
            } else {
                $msg = explode(":", $e->getMessage(), 2);
                $msg = end($msg);
            }
            $this->resp->msg = $msg;
            $this->jsonecho();
        } catch (\Exception $e) {
            $this->resp->msg = __("Oops! Something went wrong. Please try again later!");
            $this->resp->login_failed = true;
            $this->jsonecho();
        }

        $Account->set("user_id", $AuthUser->get("id"))
                ->set("password", $passhash)
                ->set("proxy", $proxy ? $proxy : "")
                ->set("login_required", 0)
                ->set("instagram_id", $resp->getLoggedInUser()->getPk())
                ->set("username", $resp->getLoggedInUser()->getUsername())
                ->set("login_required", 0)
                ->save();


        // Update proxy use count
        if ($proxy && $is_system_proxy == true) {
            $Proxy = Controller::model("Proxy", $proxy);
            if ($Proxy->isAvailable()) {
                $Proxy->set("use_count", $Proxy->get("use_count") + 1)
                      ->save();
            }
        }

        $this->resp->result = 1;
        if ($is_new) {
            $this->resp->redirect = APPURL."/accounts";
        } else {
            $this->resp->changes_saved = true;
            $this->resp->msg = __("Changes saved!");
        }
        $this->jsonecho();
    }


    /**
     * Resend the same SMS code for the 2FA login
     * @return void 
     */
    protected function resend2FA()
    {
        $this->resp->result = 0;

        $AuthUser = $this->getVariable("AuthUser");
        $Account = $this->getVariable("Account");

        $twofaid = Input::post("id");

        if (!isset($_SESSION["TWOFA".$twofaid])) {
            $this->resp->msg = __("Oops! Something went wrong. Please try again later!");
            $this->resp->error_code = "account_invalid_identifier";
            $this->jsonecho();
        }

        $username = $_SESSION["TWOFA".$twofaid]["username"];
        $password = $_SESSION["TWOFA".$twofaid]["password"];
        $proxy = $_SESSION["TWOFA".$twofaid]["proxy"];

        // Encrypt the password
        try {
            $passhash = Defuse\Crypto\Crypto::encrypt($password, 
                        Defuse\Crypto\Key::loadFromAsciiSafeString(CRYPTO_KEY));
        } catch (\Exception $e) {
            $this->resp->msg = __("Encryption error");
            $this->jsonecho();
        }

        // Setup Instagram Client
        // Allow web usage
        // Since mentioned risks has been consider internally by Nextpost,
        // setting this property value to the true is not risky as it's name
        \InstagramAPI\Instagram::$allowDangerousWebUsageAtMyOwnRisk = true;

        $storageConfig = [
            "storage" => "file",
            "basefolder" => SESSIONS_PATH."/".$AuthUser->get("id")."/",
        ];

        $Instagram = new \InstagramAPI\Instagram(false, false, $storageConfig);
        $Instagram->setVerifySSL(SSL_ENABLED);

        if ($proxy) {
            $Instagram->setProxy($proxy);
        }

        try {
            $resp = $Instagram->sendTwoFactorLoginSMS($username, $password, $twofaid);
        } catch (InstagramAPI\Exception\InstagramException $e) {
            if ($e->hasResponse()) {
                $msg = $e->getResponse()->getMessage();
            } else {
                $msg = explode(":", $e->getMessage(), 2);
                $msg = end($msg);
            }
            $this->resp->msg = $msg;
            $this->jsonecho();
        } catch (\Exception $e) {
            $this->resp->msg = __("Oops! Something went wrong. Please try again later!");
            $this->resp->devmsg = $e->getMessage();
            $this->jsonecho();
        }

        $_SESSION["TWOFA".$resp->getTwoFactorInfo()->getTwoFactorIdentifier()] = [
            "username" => $username,
            "password" => $password,
            "proxy" => $proxy
        ];

        $this->resp->msg = __("SMS sent.");
        $this->resp->identifier = $resp->getTwoFactorInfo()->getTwoFactorIdentifier();
        $this->resp->result = 1;
        $this->jsonecho();
    }


    /**
     * Finish challenge
     * @return void 
     */
    protected function challenge()
    {
        $this->resp->result = 0;

        $AuthUser = $this->getVariable("AuthUser");
        $Account = $this->getVariable("Account");

        // Check if this is new or not
        $is_new = !$Account->isAvailable();

        $security_code = Input::post("challenge-security-code");
        $challengeid = Input::post("challengeid");

        if (!isset($_SESSION["CHALLENGE".$challengeid])) {
            $this->resp->msg = __("Oops! Something went wrong. Please try again later!");
            $this->resp->error_code = "account_invalid_identifier";
            $this->jsonecho();
        }

        // These variables have been saved to the session after validation
        // There is no need to validate them again here.
        $api_path = $_SESSION["CHALLENGE".$challengeid]["api_path"];
        $username = $_SESSION["CHALLENGE".$challengeid]["username"];
        $password = $_SESSION["CHALLENGE".$challengeid]["password"];
        $proxy = $_SESSION["CHALLENGE".$challengeid]["proxy"];
        $system_proxy = $_SESSION["CHALLENGE".$challengeid]["system_proxy"];

        // Encrypt the password
        try {
            $passhash = Defuse\Crypto\Crypto::encrypt($password, 
                        Defuse\Crypto\Key::loadFromAsciiSafeString(CRYPTO_KEY));
        } catch (\Exception $e) {
            $this->resp->msg = __("Encryption error");
            $this->jsonecho();
        }

        // Setup Instagram Client
        // Allow web usage
        // Since mentioned risks has been consider internally by Nextpost,
        // setting this property value to the true is not risky as it's name
        \InstagramAPI\Instagram::$allowDangerousWebUsageAtMyOwnRisk = true;
        
        $storageConfig = [
            "storage" => "file",
            "basefolder" => SESSIONS_PATH."/".$AuthUser->get("id")."/",
        ];

        $Instagram = new \InstagramAPI\Instagram(false, false, $storageConfig);
        $Instagram->setVerifySSL(SSL_ENABLED);

        if ($proxy) {
            $Instagram->setProxy($proxy);
        }

        try {
            $login_resp = $Instagram->finishChallengeLogin($username, $password, $api_path, $security_code);
        } catch (InstagramAPI\Exception\CheckpointRequiredException $e) {
            $this->resp->msg = __("Sorry, we couldn't add this account at the moment.") ." "
                             . __("Please try again some time later.");
            $this->resp->login_failed = true;
            $this->jsonecho();
        } catch (InstagramAPI\Exception\ChallengeRequiredException $e) {
            $this->resp->msg = __("Sorry, we couldn't add this account at the moment.") ." "
                             . __("Please try again some time later.");
            $this->resp->login_failed = true;
            $this->jsonecho();
        } catch (InstagramAPI\Exception\AccountDisabledException $e) {
            $this->resp->msg = __(
                "Your account has been disabled for violating Instagram terms. <a href='%s'>Click here</a> to learn how you may be able to restore your account.", 
                "https://help.instagram.com/366993040048856");
            $this->resp->login_failed = true;
            $this->jsonecho();
        } catch (InstagramAPI\Exception\SentryBlockException $e) {
            $this->resp->msg = __("Your account has been banned from Instagram API for spam behaviour or otherwise abusing.");
            $this->resp->login_failed = true;
            $this->jsonecho();
        } catch (InstagramAPI\Exception\InstagramException $e) {
            if ($e->hasResponse()) {
                $msg = $e->getResponse()->getMessage();
            } else {
                $msg = explode(":", $e->getMessage(), 2);
                $msg = end($msg);
            }
            $this->resp->msg = $msg;
            $this->jsonecho();
        } catch (\Exception $e) {
            $this->resp->msg = __("Oops! Something went wrong. Please try again later!");
            $this->resp->login_failed = true;
            $this->jsonecho();
        }

        if (!$login_resp->isOk()) {
            $this->resp->msg = __("Please check the code sent to you and try again.");
            $this->jsonecho();
        }

        $Account->set("user_id", $AuthUser->get("id"))
                ->set("password", $passhash)
                ->set("proxy", $proxy ? $proxy : "")
                ->set("login_required", 0)
                ->set("instagram_id", $login_resp->getLoggedInUser()->getPk())
                ->set("username", $login_resp->getLoggedInUser()->getUsername())
                ->set("login_required", 0)
                ->save();

        // Update proxy use count
        if ($proxy && $system_proxy == true) {
            $Proxy = Controller::model("Proxy", $proxy);
            if ($Proxy->isAvailable()) {
                $Proxy->set("use_count", $Proxy->get("use_count") + 1)
                      ->save();
            }
        }

        $this->resp->result = 1;
        if ($is_new) {
            $this->resp->redirect = APPURL."/accounts";
        } else {
            $this->resp->changes_saved = true;
            $this->resp->msg = __("Changes saved!");
        }
        $this->jsonecho();
    }

    /**
     * Resend the same verification code for the checkpoint challenge
     * @return void 
     */
    protected function resendChallenge()
    {
        $this->resp->result = 0;

        $AuthUser = $this->getVariable("AuthUser");
        $Account = $this->getVariable("Account");

        $challengeid = Input::post("id");

        if (!isset($_SESSION["CHALLENGE".$challengeid])) {
            $this->resp->msg = __("Oops! Something went wrong. Please try again later!");
            $this->resp->error_code = "account_invalid_identifier";
            $this->jsonecho();
        }

        // These variables have been saved to the session after validation
        // There is no need to validate them again here.
        $api_path = $_SESSION["CHALLENGE".$challengeid]["api_path"];
        $username = $_SESSION["CHALLENGE".$challengeid]["username"];
        $choice = $_SESSION["CHALLENGE".$challengeid]["choice"];
        $proxy = $_SESSION["CHALLENGE".$challengeid]["proxy"];


        // Parse the API path and generate api path for the verification code resend.
        preg_match("/^\/challenge\/([0-9]+)\/([A-Za-z0-9]+)(\/)?/", $api_path, $matches);
        if (!$matches) {
            $this->resp->msg = __("Oops! Something went wrong. Please try again later!");
            $this->resp->error_code = "invalid_api_path";
            $this->jsonecho();
        }

        $api_resend_path = "/challenge/replay/" . $matches[1] . "/" . $matches[2] . "/";

        // Setup Instagram Client
        // Allow web usage
        // Since mentioned risks has been consider internally by Nextpost,
        // setting this property value to the true is not risky as it's name
        \InstagramAPI\Instagram::$allowDangerousWebUsageAtMyOwnRisk = true;

        $storageConfig = [
            "storage" => "file",
            "basefolder" => SESSIONS_PATH."/".$AuthUser->get("id")."/",
        ];

        $Instagram = new \InstagramAPI\Instagram(false, false, $storageConfig);
        $Instagram->setVerifySSL(SSL_ENABLED);

        if ($proxy) {
            $Instagram->setProxy($proxy);
        }

        try {
            $resp = $Instagram->resendChallengeCode($username, $api_resend_path, $choice);
        } catch (InstagramAPI\Exception\InstagramException $e) {
            if ($e->hasResponse()) {
                $msg = $e->getResponse()->getMessage();
            } else {
                $msg = explode(":", $e->getMessage(), 2);
                $msg = end($msg);
            }
            $this->resp->msg = $msg;
            $this->jsonecho();
        } catch (\Exception $e) {
            $this->resp->msg = __("Oops! Something went wrong. Please try again later!");
            $this->resp->devmsg = $e->getMessage();
            $this->jsonecho();
        }

        $this->resp->msg = __("Verification code sent.");
        $this->resp->identifier = $challengeid;
        $this->resp->result = 1;
        $this->jsonecho();
    }

    /**
     * Handle the InstagramAPI\Exception\ChallengeRequiredException
     * @param  InstagramAPI\Exception\ChallengeRequiredException $e 
     * @return void    
     */
    protected function handleChallengeException($Instagram, $e)
    {
        if (!($Instagram instanceof InstagramAPI\Instagram)) {
            $this->resp->msg = __("Oops! Something went wrong. Please try again later!");
            $this->resp->error_code = "invalid_instagram_client";
            $this->jsonecho();
        }

        if (!($e instanceof InstagramAPI\Exception\ChallengeRequiredException)) {
            $this->resp->msg = __("Oops! Something went wrong. Please try again later!");
            $this->resp->error_code = "unexpected_exception";
            $this->jsonecho();
        }

        if (!$e->hasResponse() || !$e->getResponse()->isChallenge()) {
            $this->resp->msg = __("Oops! Something went wrong. Please try again later!");
            $this->resp->error_code = "unexpected_exception_response";
            $this->jsonecho();
        }

        try {
            $api_path = $e->getResponse()->getChallenge()->getApiPath();
    
            // Try to send challenge code via SMS.
            $choice = InstagramAPI\Constants::CHALLENGE_CHOICE_SMS;
            $challenge_resp = $Instagram->sendChallangeCode($api_path, $choice);

            if ($challenge_resp->status != "ok") {
                // Failed to send challenge code via SMS. Try with email.
                $choice = InstagramAPI\Constants::CHALLENGE_CHOICE_EMAIL;
                $challenge_resp = $Instagram->sendChallangeCode($api_path, $choice);
            }

            if ($challenge_resp->status != "ok") {
                $this->resp->msg = __("Could't send verification code for the login challenge! Please try again later!");
                $this->jsonecho();
            }

            if ($choice == InstagramAPI\Constants::CHALLENGE_CHOICE_SMS) {
                $this->resp->msg = __(
                    "Enter the code sent to your number ending in %s",
                    $challenge_resp->step_data->contact_point);
            } else {
                $this->resp->msg = __(
                    "Enter the 6-digit code sent to the email address %s",
                    $challenge_resp->step_data->contact_point);
            }
    
            $this->resp->result = 2;
            $this->resp->challenge_required = true;
            $this->resp->identifier = uniqid();
            

            $_SESSION["CHALLENGE".$this->resp->identifier] = [
                "api_path" => $api_path,
                "choice" => $choice,
                "username" => $this->username,
                "password" => $this->password,
                "proxy" => $this->proxy,
                "system_proxy" => $this->system_proxy
            ];
        } catch (InstagramAPI\Exception\InstagramException $e) {
            if ($e->hasResponse()) {
                $msg = $e->getResponse()->getMessage();
            } else {
                $msg = explode(":", $e->getMessage(), 2);
                $msg = end($msg);
            }
            $this->resp->msg = $msg;
        } catch (\Exception $e) {
            $this->resp->msg = __("Oops! Something went wrong. Please try again later!");
        }
    }
}