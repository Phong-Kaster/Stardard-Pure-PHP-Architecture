<?php
/**
 * Cron Controller
 */
class CronController extends Controller
{
    /**
     * Process
     */
    public function process()
    {
        set_time_limit(0);

        $this->posts();

        \Event::trigger("cron.add");

        echo "Cron task processed!";
    }


    /**
     * Process scheduled posts
     */
    private function posts()
    {
        // Get scheduled posts
        $Posts = Controller::model("Posts");
        $Posts->whereIn(TABLE_PREFIX.TABLE_POSTS.".status", ["scheduled"])
              ->where(TABLE_PREFIX.TABLE_POSTS.".is_scheduled", "=", "1")
              ->where(TABLE_PREFIX.TABLE_POSTS.".schedule_date", "<=", date("Y-m-d H:i").":59")
              ->setPageSize(5) // Limit posts to prevent server overload
              ->setPage(1)
              ->fetchData();


        if ($Posts->getTotalCount() < 1) {
            // There is not any scheduled posts
            return true;
        }

        foreach ($Posts->getDataAs("Post") as $Post) {
            // Update post status
            $Post->set("status", "publishing")->save();

            try {
                \InstagramController::publish($Post);
            } catch (\Exception $e) {
                // Do nothing here
            }
        }

        return true;
    }
}