<?php 
// Language slug
// 
// Will be used theme routes
$langs = [];
foreach (Config::get("applangs") as $l) {
    if (!in_array($l["code"], $langs)) {
        $langs[] = $l["code"];
    }

    if (!in_array($l["shortcode"], $langs)) {
        $langs[] = $l["shortcode"];
    }
}
$langslug = $langs ? "[".implode("|", $langs).":lang]" : "";


/**
 * Theme Routes
 */

// Index (Landing Page)
// 
// Replace "Index" with "Login" to completely disable Landing page 
// After this change, Login page will be your default landing page
// 
// This is useful in case of self use, or having different 
// landing page in different address. For ex: you can install the script
// to subdirectory or subdomain of your wordpress website.
App::addRoute("GET|POST", "/", "Index");
App::addRoute("GET|POST", "/".$langslug."?/?", "Index");

// Login
App::addRoute("GET|POST", "/".$langslug."?/login/?", "Login");

// Signup
// 
//  Remove or comment following line to completely 
//  disable signup page. This might be useful in case 
//  of self use of the script
App::addRoute("GET|POST", "/".$langslug."?/signup/?", "Signup");

// Logout
App::addRoute("GET", "/".$langslug."?/logout/?", "Logout");

// Recovery
App::addRoute("GET|POST", "/".$langslug."?/recovery/?", "Recovery");
App::addRoute("GET|POST", "/".$langslug."?/recovery/[i:id].[a:hash]/?", "PasswordReset");



/**
 * App Routes
 */

// New|Edit Post
App::addRoute("GET|POST", "/post/[i:id]?/?", "Post");

// Instagram Accounts
App::addRoute("GET|POST", "/accounts/?", "Accounts");
// New Instagram Account
App::addRoute("GET|POST", "/accounts/new/?", "Account");
// Edit Instagram Account
App::addRoute("GET|POST", "/accounts/[i:id]/?", "Account");

// Caption Templates
App::addRoute("GET|POST", "/captions/?", "Captions");
// New Caption Template
App::addRoute("GET|POST", "/captions/new/?", "Caption");
// Edit Caption Template
App::addRoute("GET|POST", "/captions/[i:id]/?", "Caption");

// Settings
$settings_pages = [
  "site", "logotype", "other", "experimental",
  "google-analytics", "google-drive", "dropbox", "onedrive", "paypal", "stripe", "facebook", "recaptcha",
  "proxy",

  "notifications", "smtp"
];
App::addRoute("GET|POST", "/settings/[".implode("|", $settings_pages).":page]?/?", "Settings");

// Packages
App::addRoute("GET|POST", "/packages/?", "Packages");
// New Package
App::addRoute("GET|POST", "/packages/new/?", "Package");
// Edit Package
App::addRoute("GET|POST", "/packages/[i:id]/?", "Package");
// Free Trial Package
App::addRoute("GET|POST", "/packages/trial/?", "TrialPackage");

// Users
App::addRoute("GET|POST", "/users/?", "Users");
// New User
App::addRoute("GET|POST", "/users/new/?", "User");
// Edit User
App::addRoute("GET|POST", "/users/[i:id]/?", "User");
App::addRoute("GET|POST", "/profile/?", "Profile");

// Calendar
App::addRoute("GET|POST", "/calendar/?", "Calendar");
App::addRoute("GET|POST", "/calendar/[i:year]/[i:month]/?", "Calendar");
// Calendar Day
App::addRoute("GET|POST", "/calendar/[i:year]/[i:month]/[i:day]?", "Calendar");

// Proxies
App::addRoute("GET|POST", "/proxies/?", "Proxies");
// New Proxy
App::addRoute("GET|POST", "/proxies/new/?", "Proxy");
// Edit Proxy
App::addRoute("GET|POST", "/proxies/[i:id]/?", "Proxy");

// Statistics
App::addRoute("GET|POST", "/statistics/?", "Statistics");

// Expired
App::addRoute("GET", "/expired/?", "Expired");
// Renew
App::addRoute("GET|POST", "/renew/?", "Renew");

// Checkout Results
App::addRoute("GET|POST", "/checkout/[i:id].[a:hash]/?", "CheckoutResult");
App::addRoute("GET|POST", "/checkout/error/?", "CheckoutResult");

// Cron
App::addRoute("GET", "/cron/?", "Cron");

// Plugins (Modules)
App::addRoute("GET|POST", "/plugins/?", "Plugins");
// Upload plugin
App::addRoute("GET|POST", "/plugins/install/?", "Plugin");
// Install plugin
App::addRoute("GET|POST", "/plugins/install/[a:hash]/?", "Plugin");

// Email verification
App::addRoute("GET|POST", "/verification/email/[i:id].[a:hash]?/?", "EmailVerification");
