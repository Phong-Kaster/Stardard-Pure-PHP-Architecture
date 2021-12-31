<?php
/**
 * Expired Controller
 */
class ExpiredController extends Controller
{
    /**
     * Inetgrations datas
     * @var DataEntry
     */
    private $integrations;


    /**
     * Process
     */
    public function process()
    {
        $AuthUser = $this->getVariable("AuthUser");
        $EmailSettings = \Controller::model("GeneralData", "email-settings");
        
        if (!$AuthUser || !$AuthUser->isExpired()) {
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

        $this->integrations = \Controller::model("GeneralData", "integrations");

        $this->checkRecurringPayments();
        $this->view("expired");
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

        if ($gateway == "stripe") {
            $subscription_id = $AuthUser->get("data.recurring_payments.stripe.subscription_id");
            if ($subscription_id) {
                try {
                    \Stripe\Stripe::setApiKey($this->integrations->get("data.stripe.secret_key"));
                    $subscription = \Stripe\Subscription::retrieve($subscription_id);

                    if (empty($subscription->canceled_at)) {
                        $recurring = true;
                    }
                } catch (\Exception $e) {
                    // Not subscribed
                }
            }
        }

        $this->setVariable("recurring_payments", $recurring);
        if ($recurring) {
            $this->setVariable("recurring_gateway", $gateway)
                 ->setVariable("recurring_subscription", $subscription);
        }
    }
}