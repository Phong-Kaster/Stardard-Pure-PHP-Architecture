-- phpMyAdmin SQL Dump
-- version 4.4.14
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Jul 31, 2017 at 11:59 PM
-- Server version: 5.6.26
-- PHP Version: 5.6.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------

--
-- Table structure for table `TABLE_ACCOUNTS`
--

CREATE TABLE IF NOT EXISTS `TABLE_ACCOUNTS` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `instagram_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` text COLLATE utf8_unicode_ci NOT NULL,
  `proxy` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `date` datetime NOT NULL,
  `last_login` datetime NOT NULL,
  `login_required` tinyint(1) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `TABLE_CAPTIONS`
--

CREATE TABLE IF NOT EXISTS `TABLE_CAPTIONS` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `caption` text COLLATE utf8_unicode_ci NOT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `TABLE_FILES`
--

CREATE TABLE IF NOT EXISTS `TABLE_FILES` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` text COLLATE utf8_unicode_ci NOT NULL,
  `info` text COLLATE utf8_unicode_ci NOT NULL,
  `filename` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `filesize` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `TABLE_GENERAL_DATA`
--

CREATE TABLE IF NOT EXISTS `TABLE_GENERAL_DATA` (
  `id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `data` text COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `TABLE_GENERAL_DATA`
--

INSERT INTO `TABLE_GENERAL_DATA` (`id`, `name`, `data`) VALUES
(1, 'settings', '{"site_name":"Nextpost","site_description":"Nextpost - Auto Post, Schedule & Manage your Instagram Multi Account","site_keywords":"nextpost, instagram, auto post, schedule, multiple accounts, social media","currency":"USD","proxy":true,"user_proxy":true,"geonamesorg_username":"","logomark":"","logotype":""}'),
(2, 'integrations', '{"dropbox":{"api_key":""},"google":{"api_key":"","client_id":"","analytics":{"property_id":""}},"onedrive":{"client_id":""},"paypal":{"client_id":"","client_secret":"","environment":"sandbox"},"stripe":{"environment":"sandbox","publishable_key":"","secret_key":""},"facebook":{"app_id":"","app_secret":""}}'),
(3, 'free-trial', '{"size":7,"storage":{"total":"100.00","file":-1},"max_accounts":1,"file_pickers":{"dropbox":true,"onedrive":true,"google_drive":true},"post_types":{"timeline_photo":true,"timeline_video":true,"story_photo":true,"story_video":true,"album_photo":true,"album_video":true},"spintax":true,"modules":[]}'),
(4, 'email-settings', '{"smtp":{"host":"","port":"","encryption":"","auth":true,"username":"","password":"","from":""},"notifications":{"emails":"","new_user":true,"new_payment":true}}');

-- --------------------------------------------------------

--
-- Table structure for table `TABLE_ORDERS`
--

CREATE TABLE IF NOT EXISTS `TABLE_ORDERS` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `data` text COLLATE utf8_unicode_ci NOT NULL,
  `status` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `payment_gateway` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `payment_id` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `total` double(10,2) NOT NULL,
  `paid` double(10,2) NOT NULL,
  `currency` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `TABLE_PACKAGES`
--

CREATE TABLE IF NOT EXISTS `TABLE_PACKAGES` (
  `id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `monthly_price` double(10,2) NOT NULL,
  `annual_price` float(10,2) NOT NULL,
  `settings` text COLLATE utf8_unicode_ci NOT NULL,
  `is_public` tinyint(1) NOT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `TABLE_PACKAGES`
--

INSERT INTO `TABLE_PACKAGES` (`id`, `title`, `monthly_price`, `annual_price`, `settings`, `is_public`, `date`) VALUES
(1, 'Alpha', 4.99, 49.00, '{"storage":{"total":"150.00","file":"15.00"},"max_accounts":1,"file_pickers":{"dropbox":false,"onedrive":false,"google_drive":false},"post_types":{"timeline_photo":true,"timeline_video":false,"story_photo":true,"story_video":false,"album_photo":true,"album_video":false},"spintax":false}', 1, '2017-03-18 19:22:44'),
(2, 'Beta Pack', 7.99, 79.00, '{"storage":{"total":"250","file":"30.00"},"max_accounts":3,"file_pickers":{"dropbox":true,"onedrive":true,"google_drive":true},"post_types":{"timeline_photo":true,"timeline_video":true,"story_photo":true,"story_video":true,"album_photo":true,"album_video":true},"spintax":true,"modules":[]}', 1, '2017-03-18 19:29:19'),
(3, 'Gamma Pack', 17.99, 165.79, '{"storage":{"total":"300.00","file":"50.00"},"max_accounts":-1,"file_pickers":{"dropbox":true,"onedrive":true,"google_drive":true},"post_types":{"timeline_photo":true,"timeline_video":true,"story_photo":true,"story_video":true,"album_photo":true,"album_video":true},"spintax":true}', 1, '2017-03-18 19:29:43');

-- --------------------------------------------------------

--
-- Table structure for table `TABLE_PLUGINS`
--

CREATE TABLE IF NOT EXISTS `TABLE_PLUGINS` (
  `id` int(11) NOT NULL,
  `idname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `TABLE_POSTS`
--

CREATE TABLE IF NOT EXISTS `TABLE_POSTS` (
  `id` int(11) NOT NULL,
  `status` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `caption` text COLLATE utf8_unicode_ci NOT NULL,
  `first_comment` text COLLATE utf8_unicode_ci NOT NULL,
  `location` text COLLATE utf8_unicode_ci NOT NULL,
  `media_ids` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `remove_media` text COLLATE utf8_unicode_ci NOT NULL,
  `account_id` int(11) NOT NULL,
  `is_scheduled` tinyint(1) NOT NULL,
  `create_date` datetime NOT NULL,
  `schedule_date` datetime NOT NULL,
  `publish_date` datetime NOT NULL,
  `is_hidden` tinyint(1) NOT NULL,
  `data` text COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `TABLE_OPTIONS`
--
CREATE TABLE IF NOT EXISTS `TABLE_OPTIONS` (
  `id` int(10) NOT NULL,
  `option_name` varchar(255) NOT NULL,
  `option_value` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `TABLE_PROXIES`
--

CREATE TABLE IF NOT EXISTS `TABLE_PROXIES` (
  `id` int(11) NOT NULL,
  `proxy` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `country_code` varchar(2) COLLATE utf8_unicode_ci NOT NULL,
  `use_count` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `TABLE_USERS`
--

CREATE TABLE IF NOT EXISTS `TABLE_USERS` (
  `id` int(11) NOT NULL,
  `account_type` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `username` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `firstname` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `lastname` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `package_id` int(11) NOT NULL,
  `package_subscription` tinyint(1) NOT NULL,
  `settings` text COLLATE utf8_unicode_ci NOT NULL,
  `preferences` text COLLATE utf8_unicode_ci NOT NULL,
  `is_active` int(11) NOT NULL,
  `expire_date` datetime NOT NULL,
  `date` datetime NOT NULL,
  `data` text COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `TABLE_USERS`
--

INSERT INTO `TABLE_USERS` (`id`, `account_type`, `email`, `username`, `password`, `firstname`, `lastname`, `package_id`, `package_subscription`, `settings`, `preferences`, `is_active`, `expire_date`, `date`, `data`) VALUES
(1, 'admin', 'ADMIN_EMAIL', 'admin', 'ADMIN_PASSWORD', 'ADMIN_FIRSTNAME', 'ADMIN_LASTNAME', 3, 1, '{"storage":{"total":"300.00","file":"50.00"},"max_accounts":-1,"file_pickers":{"dropbox":true,"onedrive":true,"google_drive":true},"post_types":{"timeline_photo":true,"timeline_video":true,"story_photo":true,"story_video":true,"album_photo":true,"album_video":true},"spintax":true}', '{"timezone":"ADMIN_TIMEZONE","dateformat":"Y-m-d","timeformat":"24","language":"en-US"}', 1, '2030-12-31 23:59:59', 'ADMIN_DATE', '{}');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `TABLE_ACCOUNTS`
--
ALTER TABLE `TABLE_ACCOUNTS`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id_2` (`user_id`,`username`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `TABLE_CAPTIONS`
--
ALTER TABLE `TABLE_CAPTIONS`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `TABLE_FILES`
--
ALTER TABLE `TABLE_FILES`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `TABLE_GENERAL_DATA`
--
ALTER TABLE `TABLE_GENERAL_DATA`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `TABLE_ORDERS`
--
ALTER TABLE `TABLE_ORDERS`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `TABLE_PACKAGES`
--
ALTER TABLE `TABLE_PACKAGES`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `TABLE_PLUGINS`
--
ALTER TABLE `TABLE_PLUGINS`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idname` (`idname`);

--
-- Indexes for table `TABLE_POSTS`
--
ALTER TABLE `TABLE_POSTS`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`),
  ADD KEY `account_id` (`account_id`);
  
--
-- Indexes for table `TABLE_OPTIONS`
--
ALTER TABLE `TABLE_OPTIONS`
  ADD PRIMARY KEY (`id`); 

--
-- Indexes for table `TABLE_PROXIES`
--
ALTER TABLE `TABLE_PROXIES`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `TABLE_USERS`
--
ALTER TABLE `TABLE_USERS`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `TABLE_ACCOUNTS`
--
ALTER TABLE `TABLE_ACCOUNTS`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `TABLE_CAPTIONS`
--
ALTER TABLE `TABLE_CAPTIONS`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `TABLE_FILES`
--
ALTER TABLE `TABLE_FILES`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `TABLE_GENERAL_DATA`
--
ALTER TABLE `TABLE_GENERAL_DATA`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `TABLE_ORDERS`
--
ALTER TABLE `TABLE_ORDERS`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `TABLE_PACKAGES`
--
ALTER TABLE `TABLE_PACKAGES`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `TABLE_PLUGINS`
--
ALTER TABLE `TABLE_PLUGINS`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `TABLE_POSTS`
--
ALTER TABLE `TABLE_POSTS`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `TABLE_OPTIONS`
--
ALTER TABLE `TABLE_OPTIONS`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `TABLE_PROXIES`
--
ALTER TABLE `TABLE_PROXIES`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `TABLE_USERS`
--
ALTER TABLE `TABLE_USERS`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `TABLE_ACCOUNTS`
--
ALTER TABLE `TABLE_ACCOUNTS`
  ADD CONSTRAINT `accounts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `TABLE_USERS` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
--
-- Constraints for table `TABLE_CAPTIONS`
--
ALTER TABLE `TABLE_CAPTIONS`
  ADD CONSTRAINT `captions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `TABLE_USERS` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `TABLE_POSTS`
--
ALTER TABLE `TABLE_POSTS`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `TABLE_USERS` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `TABLE_ACCOUNTS` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
