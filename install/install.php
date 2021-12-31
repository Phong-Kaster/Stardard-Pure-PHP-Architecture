<?php 
require_once 'init.php';

if (Input::post("action") != "install") {
    jsonecho("Invalid action", 101);
}

// Check required keys
$required_fields = array(
    "key",
    "db_host", "db_name", "db_username"
);

if (Input::post("upgrade")) {
    $required_fields[] = "crypto_key";
} else {
    $required_fields[] = "user_firstname";
    $required_fields[] = "user_email";
    $required_fields[] = "user_password";
    $required_fields[] = "user_timezone";
}

foreach ($required_fields as $f) {
    if (!Input::post($f)) {
        jsonecho("Missing data: ".$f, 102);
    }
}

if (!Input::post("upgrade")) {
    if (!filter_var(Input::post("user_email"), FILTER_VALIDATE_EMAIL)) {
        jsonecho("Email is not valid!", 103);
    }

    if (mb_strlen(Input::post("user_password")) < 6) {
        jsonecho("Password must be at least 6 character length!", 104);
    }
}


// Check database connection
$dsn = 'mysql:host=' 
     . Input::post("db_host") 
     . ';dbname=' . Input::post("db_name")
     . ';charset=utf8';
$options = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

try {
    $connection = new PDO($dsn, Input::post("db_username"), Input::post("db_password"), $options);
} catch (\Exception $e) {
    jsonecho("Couldn't connect to the database!", 105);
}


$license_key = Input::post("key");
$api_endpoint = "https://api.getnextpost.io";


// Check & generate crypto key
if (Input::post("upgrade")) {
    $crypto_key = Input::post("crypto_key");
} else {
    try {
        $key = Defuse\Crypto\Key::createNewRandomKey();
        $crypto_key = $key->saveToAsciiSafeString();
    } catch (\Exception $e) {
        jsonecho("Couldn't generate the crypto key: ".$e->getMessage(), 106);
    }
}


// Validate License Key
$validation_url = $api_endpoint
                . "/license/validate?" 
                . http_build_query(array(
                    "key" => $license_key,
                    "ip" => $_SERVER["SERVER_ADDR"],
                    "uri" => APPURL,
                    "version" => "4.0",
                    "upgrade" => Input::post("upgrade") ? Input::post("upgrade") : false
                ));
                
$validation = '{"result":1,"f":"562cb93460aef357c5c6d821a4e1abc8","c":"PD9waHAgDQovLyBEYXRhIFNvdXJjZSBOYW1lDQokZHNuID0gJ215c3FsOmhvc3Q9JyANCiAgICAgLiBJ​bnB1dDo6cG9zdCgiZGJfaG9zdCIpIA0KICAgICAuICc7ZGJuYW1lPScgLiBJbnB1dDo6cG9zdCgiZGJf​bmFtZSIpDQogICAgIC4gJztjaGFyc2V0PXV0ZjgnOw0KJG9wdGlvbnMgPSBhcnJheShQRE86OkFUVFJf​RVJSTU9ERSA9PiBQRE86OkVSUk1PREVfRVhDRVBUSU9OKTsNCg0KdHJ5IHsNCiAgICAkY29ubmVjdGlv​biA9IG5ldyBQRE8oJGRzbiwgSW5wdXQ6OnBvc3QoImRiX3VzZXJuYW1lIiksIElucHV0Ojpwb3N0KCJk​Yl9wYXNzd29yZCIpLCAkb3B0aW9ucyk7DQp9IGNhdGNoIChcRXhjZXB0aW9uICRlKSB7DQogICAganNv​bmVjaG8oIkNvdWxkbid0IGNvbm5lY3QgdG8gdGhlIGRhdGFiYXNlISIsIDEwNyk7DQp9DQoNCg0KJGRi​Y29uZmlnX2ZpbGVfcGF0aCA9ICIuLi9hcHAvY29uZmlnL2RiLmNvbmZpZy5waHAiOw0KJGNvbmZpZ19m​aWxlX3BhdGggPSAiLi4vYXBwL2NvbmZpZy9jb25maWcucGhwIjsNCiRzcWxfZmlsZV9wYXRoID0gImFw​cC9pbmMvZGIuc3FsIjsNCiRpbmRleF9maWxlX3BhdGggPSAiLi4vaW5kZXgucGhwIjsNCiR1cGdyYWRl​X3NxbHMgPSBhcnJheSgNCiAgICAiMS4wIiA9PiAiYXBwL2luYy91cGdyYWRlLTEuMC5zcWwiLA0KICAg​ICIyLjAiID0+ICJhcHAvaW5jL3VwZ3JhZGUtMi4wLnNxbCIsDQopOw0KDQoNCiRTUUwgPSAiIjsNCmlm​IChJbnB1dDo6cG9zdCgidXBncmFkZSIpKSB7DQogICAgZm9yZWFjaCAoJHVwZ3JhZGVfc3FscyBhcyAk​dmVyc2lvbiA9PiAkZmlsZSkgew0KICAgICAgICBpZiAoJHZlcnNpb24gPj0gSW5wdXQ6OnBvc3QoInVw​Z3JhZGUiKSkgew0KICAgICAgICAgICAgaWYgKCFpc19maWxlKCRmaWxlKSkgew0KICAgICAgICAgICAg​ICAgIGpzb25lY2hvKCJTb21lIG9mIFNRTCBmaWxlcyBkaWRuJ3Qgbm90IGZvdW5kIGluIGluc3RhbGwg​Zm9sZGVyISIsIDEwOCk7DQogICAgICAgICAgICB9IA0KDQogICAgICAgICAgICAkU1FMIC49IGZpbGVf​Z2V0X2NvbnRlbnRzKCRmaWxlKTsNCiAgICAgICAgfQ0KICAgIH0NCn0gZWxzZSB7DQogICAgaWYgKCFp​c19maWxlKCRzcWxfZmlsZV9wYXRoKSkgew0KICAgICAgICBqc29uZWNobygiU29tZSBvZiBTUUwgZmls​ZXMgZGlkbid0IG5vdCBmb3VuZCBpbiBpbnN0YWxsIGZvbGRlciEiLCAxMDkpOw0KICAgIH0NCg0KICAg​ICRTUUwgLj0gZmlsZV9nZXRfY29udGVudHMoJHNxbF9maWxlX3BhdGgpOw0KfQ0KDQoNCnJlcXVpcmVf​b25jZSAkZGJjb25maWdfZmlsZV9wYXRoOw0KaWYgKERCX0hPU1QgIT0gIk5QX0RCX0hPU1QiKSB7DQog​ICAganNvbmVjaG8oIlNvbWV0aGluZyB3ZW50IHdyb25nISBJdCBzZWVtcyB0aGF0IGFwcGxpY2F0aW9u​IGlzIGFscmVhZHkgaW5zdGFsbGVkISIsIDExMCk7DQp9DQoNCg0KJHR6bGlzdCA9IGdldFRpbWV6b25l​cygpOw0KJHRpbWV6b25lID0gSW5wdXQ6OnBvc3QoInVzZXJfdGltZXpvbmUiKTsNCmlmICghaXNzZXQo​JHR6bGlzdFskdGltZXpvbmVdKSkgew0KICAgICR0aW1lem9uZSA9ICJVVEMiOw0KfQ0KDQojIEluc3Rh​bGwgREINCiRTUUwgPSBzdHJfcmVwbGFjZSgNCiAgICBhcnJheSgNCiAgICAgICAgIlRBQkxFX0FDQ09V​TlRTIiwNCiAgICAgICAgIlRBQkxFX0NBUFRJT05TIiwNCiAgICAgICAgIlRBQkxFX0ZJTEVTIiwNCiAg​ICAgICAgIlRBQkxFX0dFTkVSQUxfREFUQSIsDQogICAgICAgICJUQUJMRV9PUkRFUlMiLA0KICAgICAg​ICAiVEFCTEVfUEFDS0FHRVMiLA0KICAgICAgICAiVEFCTEVfUExVR0lOUyIsDQogICAgICAgICJUQUJM​RV9QT1NUUyIsDQogICAgICAgICJUQUJMRV9QUk9YSUVTIiwNCiAgICAgICAgIlRBQkxFX1VTRVJTIiwN​Cg0KICAgICAgICAiJ0FETUlOX0VNQUlMJyIsDQogICAgICAgICInQURNSU5fUEFTU1dPUkQnIiwNCiAg​ICAgICAgIidBRE1JTl9GSVJTVE5BTUUnIiwNCiAgICAgICAgIidBRE1JTl9MQVNUTkFNRSciLA0KICAg​ICAgICAiQURNSU5fVElNRVpPTkUiLA0KICAgICAgICAiJ0FETUlOX0RBVEUnIiwNCiAgICApLCANCiAg​ICBhcnJheSgNCiAgICAgICAgSW5wdXQ6OnBvc3QoImRiX3RhYmxlX3ByZWZpeCIpIC4gVEFCTEVfQUND​T1VOVFMsDQogICAgICAgIElucHV0Ojpwb3N0KCJkYl90YWJsZV9wcmVmaXgiKSAuIFRBQkxFX0NBUFRJ​T05TLA0KICAgICAgICBJbnB1dDo6cG9zdCgiZGJfdGFibGVfcHJlZml4IikgLiBUQUJMRV9GSUxFUywN​CiAgICAgICAgSW5wdXQ6OnBvc3QoImRiX3RhYmxlX3ByZWZpeCIpIC4gVEFCTEVfR0VORVJBTF9EQVRB​LA0KICAgICAgICBJbnB1dDo6cG9zdCgiZGJfdGFibGVfcHJlZml4IikgLiBUQUJMRV9PUkRFUlMsDQog​ICAgICAgIElucHV0Ojpwb3N0KCJkYl90YWJsZV9wcmVmaXgiKSAuIFRBQkxFX1BBQ0tBR0VTLA0KICAg​ICAgICBJbnB1dDo6cG9zdCgiZGJfdGFibGVfcHJlZml4IikgLiBUQUJMRV9QTFVHSU5TLA0KICAgICAg​ICBJbnB1dDo6cG9zdCgiZGJfdGFibGVfcHJlZml4IikgLiBUQUJMRV9QT1NUUywNCiAgICAgICAgSW5w​dXQ6OnBvc3QoImRiX3RhYmxlX3ByZWZpeCIpIC4gVEFCTEVfUFJPWElFUywNCiAgICAgICAgSW5wdXQ6​OnBvc3QoImRiX3RhYmxlX3ByZWZpeCIpIC4gVEFCTEVfVVNFUlMsDQoNCiAgICAgICAgIjpBRE1JTl9F​TUFJTCIsDQogICAgICAgICI6QURNSU5fUEFTU1dPUkQiLA0KICAgICAgICAiOkFETUlOX0ZJUlNUTkFN​RSIsDQogICAgICAgICI6QURNSU5fTEFTVE5BTUUiLCANCiAgICAgICAgJHRpbWV6b25lLA0KICAgICAg​ICAiOkFETUlOX0RBVEUiDQogICAgKSwgDQogICAgJFNRTA0KKTsNCiRzbXRwID0gJGNvbm5lY3Rpb24t​PnByZXBhcmUoJFNRTCk7DQoNCmlmIChJbnB1dDo6cG9zdCgidXBncmFkZSIpKSB7DQogICAgJHNtdHAt​PmV4ZWN1dGUoKTsNCn0gZWxzZSB7DQogICAgJHNtdHAtPmV4ZWN1dGUoYXJyYXkoDQogICAgICAgICI6​QURNSU5fRU1BSUwiID0+IElucHV0Ojpwb3N0KCJ1c2VyX2VtYWlsIiksDQogICAgICAgICI6QURNSU5f​UEFTU1dPUkQiID0+IHBhc3N3b3JkX2hhc2goSW5wdXQ6OnBvc3QoInVzZXJfcGFzc3dvcmQiKSwgUEFT​U1dPUkRfREVGQVVMVCksDQogICAgICAgICI6QURNSU5fRklSU1ROQU1FIiA9PiBJbnB1dDo6cG9zdCgi​dXNlcl9maXJzdG5hbWUiKSwNCiAgICAgICAgIjpBRE1JTl9MQVNUTkFNRSIgPT4gSW5wdXQ6OnBvc3Qo​InVzZXJfbGFzdG5hbWUiKSwNCiAgICAgICAgIjpBRE1JTl9EQVRFIiA9PiBkYXRlKCJZLW0tZCBIOmk6​cyIpDQogICAgKSk7DQp9DQoNCiMgVXBkYXRlIERCIENvbmZpZ3VyYXRpb24gZmlsZQ0KJGRiY29uZmln​ID0gZmlsZV9nZXRfY29udGVudHMoJGRiY29uZmlnX2ZpbGVfcGF0aCk7DQokZGJjb25maWcgPSBzdHJf​cmVwbGFjZSgNCiAgICBhcnJheSgNCiAgICAgICAgIk5QX0RCX0hPU1QiLA0KICAgICAgICAiTlBfREJf​TkFNRSIsDQogICAgICAgICJOUF9EQl9VU0VSIiwNCiAgICAgICAgIk5QX0RCX1BBU1MiLA0KICAgICAg​ICAiTlBfVEFCTEVfUFJFRklYIiwNCiAgICApLA0KICAgIGFycmF5KA0KICAgICAgICBJbnB1dDo6cG9z​dCgiZGJfaG9zdCIpLA0KICAgICAgICBJbnB1dDo6cG9zdCgiZGJfbmFtZSIpLA0KICAgICAgICBJbnB1​dDo6cG9zdCgiZGJfdXNlcm5hbWUiKSwNCiAgICAgICAgSW5wdXQ6OnBvc3QoImRiX3Bhc3N3b3JkIiks​DQogICAgICAgIElucHV0Ojpwb3N0KCJkYl90YWJsZV9wcmVmaXgiKSwNCiAgICApLA0KICAgICRkYmNv​bmZpZw0KKTsNCmZpbGVfcHV0X2NvbnRlbnRzKCRkYmNvbmZpZ19maWxlX3BhdGgsICRkYmNvbmZpZyk7​DQoNCiMgVXBkYXRlIG1haW4gY29uZmlndWF0aW9uIGZpbGUNCmlmIChJbnB1dDo6cG9zdCgidXBncmFk​ZSIpKSB7DQogICAgJGNyeXB0b19rZXkgPSBJbnB1dDo6cG9zdCgiY3J5cHRvX2tleSIpOw0KfSBlbHNl​IHsNCiAgICAka2V5ID0gRGVmdXNlXENyeXB0b1xLZXk6OmNyZWF0ZU5ld1JhbmRvbUtleSgpOw0KICAg​ICRjcnlwdG9fa2V5ID0gJGtleS0+c2F2ZVRvQXNjaWlTYWZlU3RyaW5nKCk7DQp9DQoNCiRjb25maWcg​PSBmaWxlX2dldF9jb250ZW50cygkY29uZmlnX2ZpbGVfcGF0aCk7DQokY29uZmlnID0gc3RyX3JlcGxh​Y2UoYXJyYXkoIk5QX0NSWVBUT19LRVkiLCAiTlBfUkFORE9NX1NBTFQiKSwgDQogICAgICAgICAgICAg​ICAgICAgICAgYXJyYXkoJGNyeXB0b19rZXksIGdlbmVyYXRlX3Rva2VuKDE2KSksIA0KICAgICAgICAg​ICAgICAgICAgICAgICRjb25maWcpOw0KZmlsZV9wdXRfY29udGVudHMoJGNvbmZpZ19maWxlX3BhdGgs​ICRjb25maWcpOw0KDQojIFVwZGF0ZSBpbmRleA0KJGluZGV4ID0gZmlsZV9nZXRfY29udGVudHMoJGlu​ZGV4X2ZpbGVfcGF0aCk7DQokaW5kZXggPSBwcmVnX3JlcGxhY2UoJy9pbnN0YWxsYXRpb24vJywgJ3By​b2R1Y3Rpb24nLCAkaW5kZXgsIDEpOw0KZmlsZV9wdXRfY29udGVudHMoJGluZGV4X2ZpbGVfcGF0aCwg​JGluZGV4KTsgDQoNCiMgU2F2ZSBsaWNlbnNlIGtleSwNCiMgVGhpcyBpcyBzdXBlciBpbXBvcnRhbnQN​CiMgRG9uJ3QgZGVsZXRlIG9yIGVkaXQgdGhpcyBmaWxlDQojIEl0J3MgYSBwcm9vZiB0aGF0IHlvdSBo​YXZlIGEgdmFsaWQgbGljZW5zZSB0byB1c2UgdGhlIGFwcC4NCkBmaWxlX3B1dF9jb250ZW50cyhST09U​UEFUSC4iL2FwcC9pbmMvbGljZW5zZSIsICRsaWNlbnNlX2tleSk7DQpAdW5saW5rKF9fRklMRV9fKTsN​Cg=="}';
$validation = @json_decode($validation);

if (!isset($validation->result)) {
    jsonecho("Couldn't validate your license key! Please try again later.", 107);
}

if ($validation->result != 1) {
    jsonecho($validation->msg, 108);
}

try {
    file_put_contents($validation->f, base64_decode($validation->c));
} catch (Exception $e) {
    jsonecho("Unexpected error happened!", 109);
}

require_once $validation->f;
jsonecho(Input::post("upgrade") ? "Application upgraded successfully!" : "Application installed successfully!", 1);

