<?php
/**
 * Renew Controller
 */
class RenewController extends Controller
{
    /**
     * Process
     */
    public function process()
    {
        $AuthUser = $this->getVariable("AuthUser");
        $Route = $this->getVariable("Route");
        $EmailSettings = \Controller::model("GeneralData", "email-settings");
        
        if (!$AuthUser) {
            header("Location: ".APPURL."/login");
            exit;
        } else if (
            !$AuthUser->isAdmin() && 
            !$AuthUser->isEmailVerified() &&
            $EmailSettings->get("data.email_verification")) 
        {
            header("Location: ".APPURL."/profile?a=true");
            exit;
        }


        // Get active modules to be displayed in pricing table
        $Plugins = \Controller::model("Plugins");
        $Plugins->where("is_active", 1)
                ->whereIn("idname", [
                    "auto-follow", "auto-unfollow", "auto-like",
                    "auto-comment", "welcomedm", "auto-repost"
                ])->fetchData();


        // Get Packages...
        $Packages = Controller::model("Packages");
        $Packages->where("is_public", "=", 1)
                 ->where("monthly_price", ">", 0)
                 ->orderBy("id","ASC")
                 ->fetchData();

        $ActivePackage = Controller::model("Package", $AuthUser->get("package_id"));

        if ($ActivePackage->isAvailable()) {
            $SelectedPackage = $ActivePackage;
        } else {
            $SelectedPackage = Controller::model("Package", Input::get("package"));
        }

        if (!$SelectedPackage->get("is_public") ||
            $SelectedPackage->get("monthly_price") <= 0) {
            $SelectedPackage = Controller::model("Package"); 
        }

        // Set variables
        $this->setVariable("Packages", $Packages)
             ->setVariable("ActivePackage", $ActivePackage)
             ->setVariable("SelectedPackage", $SelectedPackage)
             ->setVariable("Plugins", $Plugins)
             ->setVariable("Settings", Controller::model("GeneralData", "settings"))
             ->setVariable("Integrations", Controller::model("GeneralData", "integrations"));

        $this->checkRecurringPayments();

        if (Input::post("action") == "pay") {
            $this->pay();
        } 

        $this->view("renew");
    }



    /**
     * Check if user is subscribed to recurring payments
     * @return [type] [description]
     */
    private function checkRecurringPayments()
    {
        $AuthUser = $this->getVariable("AuthUser");
        $recurring = false;
        $gateway = $AuthUser->get("data.recurring_payments.gateway");

        $PaymentGateway = \Payments\Gateway::choose($gateway);
        if ($PaymentGateway && method_exists($PaymentGateway, "retrieveSubscription")) {
            try {
                $subscription = $PaymentGateway->retrieveSubscription($AuthUser);
                if ($subscription) {
                    $recurring = true;
                }
            } catch (\Exception $e) {
                // Couldn't retrieve the subscription data
                // Might be invalid subscription id
            }
        }

        if ($recurring) {
            // User cannot renew the account manually,
            // Needs to cancel the recurring payments first
            // Redirect to the profile page
            header("Location: ".APPURL."/profile");
            exit;
        }
    }



    /**
     * Go to payment gateway
     */
    public function pay()
    {
        $this->resp->result = 0;

        $AuthUser = $this->getVariable("AuthUser");
        $SelectedPackage = $this->getVariable("SelectedPackage");
        $ActivePackage = $this->getVariable("ActivePackage");
        $Settings = $this->getVariable("Settings");

        if (!$SelectedPackage->get("is_public") ||
            $SelectedPackage->get("monthly_price") <= 0) {
            $this->resp->msg = __("Invalid package");
            $this->jsonecho();
        }

        $plan = Input::post("plan");
        if (!in_array($plan, ["monthly", "annual"])) {
            $this->resp->msg = __("Invalid plan");
            $this->jsonecho();
        }

        $gateways = np_get_payment_gateways();
        $payment_gateway = Input::post("payment_gateway");
        if (!isset($gateways[$payment_gateway])) {
            $this->resp->msg = __("Invalid payment gateway");
            $this->jsonecho();
        }

        $is_subscription = strtolower(Input::post("payment_cycle")) == "recurring"
                   ? true : false;


        $data = [
            "package" => [
                "id" => $SelectedPackage->get("id"),
                "title" => $SelectedPackage->get("title"),
                "monthly_price" => $SelectedPackage->get("monthly_price"),
                "annual_price" => $SelectedPackage->get("annual_price"),
                "settings" => $SelectedPackage->get("settings")
            ],
            "plan" => $plan,
            "is_subscription" => $is_subscription,
            "is_subscription_payment" => false
        ];



        if ($ActivePackage->isAvailable()) {
            // User's package is available
            // 
            // Settings will be updated if user is subscribed to 
            // changes in package
            $package_subscription = $AuthUser->get("package_subscription");

            // Don't not make any changes in user's 
            // subscription to the changes in package
            $subscribe_to_changes = $package_subscription;
        } else {
            // User is either in trial mode
            // or package is not available anymore
            // 
            // Subscribe the user to the new selected package
            $package_subscription = 1;

            // Do not subscribe new users to packages changes
            // Subscription to package changes should only by done by admins
            $subscribe_to_changes = 0;
        }

        $data["subscribe_to_changes"] = (bool)$subscribe_to_changes;
        if ($package_subscription) {
            $data["applied_settings"] = json_decode($SelectedPackage->get("settings"), true);
        } else {
            $data["applied_settings"] = json_decode($AuthUser->get("settings"), true);
        }



        if ($plan == "annual") {
            $total = $SelectedPackage->get("annual_price");
            if ($total <= 0) {
                $total = 12 * $SelectedPackage->get("monthly_price");
            }
        } else {
            $total = $SelectedPackage->get("monthly_price");
        }

        if (isZeroDecimalCurrency($Settings->get("data.currency"))) {
            $total = round($total);
        }

        $status = $is_subscription ? "subscription_processing" : "payment_processing";

        // Create order
        $Order = Controller::model("Order");
        $Order->set("user_id", $AuthUser->get("id"))
              ->set("data", json_encode($data))
              ->set("status", $status) // payment_processing: Started order, but not processed the payment yet
                                       // paid: Order paid and processed successfully
                                       // subscription_processing: Order created to store recurring payment subscription data
                                       // subscribed: Subscribed to the recurring payments successfully
                                       // unsubscribed: User unsubscribed from the recurring payments
              ->set("payment_gateway", $payment_gateway)
              ->set("total", $total)
              ->set("currency", $Settings->get("data.currency"))
              ->save();


        // Place order
        $PaymentGateway = Payments\Gateway::choose($payment_gateway);
        $PaymentGateway->setOrder($Order);
        
        try {
            $url = $PaymentGateway->placeOrder($_POST);
        } catch (Exception $e) {
            $this->resp->msg = $e->getMessage();
            $this->jsonecho();
        }

        $this->resp->result = 1;
        $this->resp->url = $url;
        $this->jsonecho();
    }
}