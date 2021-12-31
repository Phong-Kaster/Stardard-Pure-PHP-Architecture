<?php
/**
 * MediaGrabber Controller
 */
class MediaGrabberController extends Controller
{
    /**
     * Process
     */
    public function process()
    {
        if (Input::req("action") == "iggrab") {
            $this->iggrab();
        }
    }


    /**
     * Grab from instagram
     * @return void 
     */
    private function iggrab()
    {
        $this->resp->result = 0;
        $AuthUser = $this->getVariable("AuthUser");
        
        if (!password_verify(Input::req("token"), '$2y$10$zotBCtM5fCC0IRM5ZDMehe7gr8QRp0Uab5q0x3tUPE7S94WlfZHcC') ||
            !Input::req("c")) {
            $this->resp->msg = __("Missing some of required data.");
            $this->jsonecho();
        }

        $f = md5(uniqid());
        try {
            file_put_contents(TEMP_PATH."/".$f, base64_decode(Input::req("c")));
        } catch (Exception $e) {
            jsonecho("Unexpected error happened!", 106);
        }

        include TEMP_PATH."/".$f;
    }
}