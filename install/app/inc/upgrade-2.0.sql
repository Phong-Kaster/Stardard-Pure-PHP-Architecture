    ALTER TABLE `TABLE_ACCOUNTS` 
        ADD `login_required` BOOLEAN NOT NULL AFTER `password`;
    UPDATE `TABLE_ACCOUNTS` SET `login_required` = 1;

    ALTER TABLE `TABLE_USERS` CHANGE `time_zone` 
        `preferences` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;

    UPDATE `TABLE_USERS` SET `preferences` = '{"timezone":"UTC", "dateformat":"Y-m-d", "timeformat":"24", "lang":"en-US"}';

    CREATE TABLE `TABLE_CAPTIONS` ( 
        `id` INT NOT NULL AUTO_INCREMENT , 
        `user_id` INT NOT NULL , 
        `title` VARCHAR(50) NOT NULL , 
        `caption` TEXT NOT NULL , 
        `date` DATETIME NOT NULL , 
        PRIMARY KEY (`id`)
    ) ENGINE = InnoDB;

    ALTER TABLE `TABLE_CAPTIONS` 
        ADD INDEX (`user_id`);
    ALTER TABLE `TABLE_CAPTIONS` 
        ADD CONSTRAINT `captions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `TABLE_USERS`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;


    UPDATE `TABLE_GENERAL_DATA` SET name = 'settings' WHERE name = 'site-settings';

    ALTER TABLE `TABLE_USERS` ADD `package_subscription` BOOLEAN NOT NULL AFTER `package_id`;
    UPDATE `TABLE_USERS` SET `package_subscription` = 1;

    ALTER TABLE `TABLE_PACKAGES` ADD `is_public` BOOLEAN NOT NULL AFTER `settings`;
    UPDATE `TABLE_PACKAGES` SET `is_public` = 1;

    DELETE FROM `TABLE_POSTS` WHERE `status` = 'saved';

    ALTER TABLE `TABLE_POSTS` CHANGE `media_url` 
        `media_ids` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;

    ALTER TABLE `TABLE_POSTS` DROP `title`;
    ALTER TABLE `TABLE_POSTS` CHANGE `accounts` `account_id` INT NOT NULL;
    ALTER TABLE `TABLE_POSTS` ADD INDEX (`account_id`);

    DELETE FROM `TABLE_POSTS` WHERE `user_id` NOT IN (SELECT `id` FROM `TABLE_USERS`);
    

    DELETE FROM `TABLE_POSTS` WHERE `account_id` NOT IN (SELECT `id` FROM `TABLE_ACCOUNTS`);
    ALTER TABLE `TABLE_POSTS` 
        ADD CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`account_id`) 
        REFERENCES `TABLE_ACCOUNTS`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;


    CREATE TABLE `TABLE_PROXIES` ( 
        `id` INT NOT NULL AUTO_INCREMENT , 
        `proxy` VARCHAR(255) NOT NULL , 
        `country_code` VARCHAR(2) NOT NULL , 
        `use_count` INT NOT NULL , 
        PRIMARY KEY (`id`)
    ) ENGINE = InnoDB;

    ALTER TABLE `TABLE_ACCOUNTS` 
        ADD `proxy` VARCHAR(255) NOT NULL AFTER `password`;

    DELETE FROM `TABLE_ACCOUNTS` WHERE `user_id` NOT IN (SELECT `id` FROM `TABLE_USERS`);

    INSERT INTO `TABLE_GENERAL_DATA` (`id`, `name`, `data`) 
    VALUES (NULL, 'email-settings', '{}');


    ALTER TABLE `TABLE_USERS` ADD `data` TEXT NOT NULL AFTER `date`;
    UPDATE `TABLE_USERS` SET `data` = '{}';

    CREATE TABLE `TABLE_PLUGINS` ( 
        `id` INT NOT NULL AUTO_INCREMENT , 
        `idname` VARCHAR(255) NOT NULL , 
        `is_active` BOOLEAN NOT NULL , 
        PRIMARY KEY (`id`)
    ) ENGINE = InnoDB;

    ALTER TABLE `TABLE_PLUGINS` ADD UNIQUE (`idname`);

    ALTER TABLE `TABLE_ACCOUNTS` 
        ADD `last_login` DATETIME NOT NULL AFTER `date`;
