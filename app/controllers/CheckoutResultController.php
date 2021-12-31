<?php
/**
 * CheckoutResult Controller
 */
class CheckoutResultController extends Controller
{
    /**
     * Process
     */
    public function process()
    {
        $Route = $this->getVariable("Route");
        $AuthUser = $this->getVariable("AuthUser");

        if (isset($Route->params->id, $Route->params->hash)) {
            if (!$AuthUser) {
                header("Location: ".APPURL."/login");
                exit;
            }

            $Order = Controller::model("Order", $Route->params->id);
            $this->setVariable("Order", $Order);

            if ($Order->isAvailable() && 
                $Order->get("user_id") == $AuthUser->get("id") && 
                $Route->params->hash == sha1($Order->get("id").NP_SALT)) 
            {
                $PaymentGateway = Payments\Gateway::choose($Order->get("payment_gateway"));

                if ($PaymentGateway) {
                    $PaymentGateway->setOrder($Order);

                    try {
                        $resp = $PaymentGateway->callback([
                            "paymentId" => Input::get("paymentId")
                        ]);

                        if ($resp) {
                            $this->setVariable("Success", true);
                        }
                    } catch (Exception $e) {
                        $this->setVariable("ErrMsg", $e->getMessage());
                    }
                }
            }
        }

        $AuthUser->refresh();
        $this->view("checkout-result");
    }
}
