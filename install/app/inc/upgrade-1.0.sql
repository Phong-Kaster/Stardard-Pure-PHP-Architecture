    UPDATE `TABLE_GENERAL_DATA` SET name='site-settings', data = '{}' WHERE name = 'general-settings';
    UPDATE `TABLE_GENERAL_DATA` SET name='integrations', data = '{}' WHERE name = 'payment-settings';

    DELETE FROM `TABLE_PACKAGES` WHERE `type` = 1;
    ALTER TABLE `TABLE_PACKAGES` DROP `type`;

    INSERT INTO `TABLE_GENERAL_DATA` (`id`, `name`, `data`) 
        VALUES (NULL, 'free-trial', '{}');

    ALTER TABLE `TABLE_PACKAGES` 
        CHANGE `price` `monthly_price` DOUBLE(10,2) NOT NULL;

    ALTER TABLE `TABLE_PACKAGES` 
        ADD `annual_price` FLOAT(10,2) NOT NULL AFTER `monthly_price`;

    ALTER TABLE `TABLE_PACKAGES` 
        DROP `size`;

    ALTER TABLE `TABLE_USERS` 
        ADD `settings` TEXT NOT NULL AFTER `package_id`;

    ALTER TABLE `TABLE_ORDERS` 
        ADD `data` TEXT NOT NULL AFTER `user_id`;

    ALTER TABLE `TABLE_ORDERS` 
        ADD `payment_gateway` VARCHAR(20) NOT NULL AFTER `status`;

    ALTER TABLE `TABLE_ORDERS` 
        CHANGE `txn_id` `payment_id` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;

    ALTER TABLE `TABLE_ORDERS` DROP `package_id`;

    ALTER TABLE `TABLE_ACCOUNTS` 
        CHANGE `instagram_id` `instagram_id` VARCHAR(255) NOT NULL;
