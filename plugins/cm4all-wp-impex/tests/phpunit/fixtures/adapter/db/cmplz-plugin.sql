-- MariaDB dump 10.19  Distrib 10.5.9-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: wordpress
-- ------------------------------------------------------
-- Server version	10.5.9-MariaDB-1:10.5.9+maria~focal

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `wp_cmplz_cookiebanners`
--

DROP TABLE IF EXISTS `wp_cmplz_cookiebanners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_cmplz_cookiebanners` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `banner_version` int(11) NOT NULL,
  `default` int(11) NOT NULL,
  `archived` int(11) NOT NULL,
  `title` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `theme` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `checkbox_style` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `revoke` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `header` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `dismiss` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `save_preferences` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `view_preferences` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `accept_all` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_functional` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_all` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_stats` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_prefs` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `accept` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `message_optin` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `readmore_optin` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `use_categories` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tagmanager_categories` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `use_categories_optinstats` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hide_revoke` int(11) NOT NULL,
  `disable_cookiebanner` int(11) NOT NULL,
  `banner_width` int(11) NOT NULL,
  `soft_cookiewall` int(11) NOT NULL,
  `dismiss_on_scroll` int(11) NOT NULL,
  `dismiss_on_timeout` int(11) NOT NULL,
  `dismiss_timeout` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `accept_informational` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `message_optout` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `readmore_optout` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `readmore_optout_dnsmpi` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `readmore_privacy` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `readmore_impressum` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `use_custom_cookie_css` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `custom_css` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `statistics` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `colorpalette_background` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `colorpalette_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `colorpalette_toggles` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `colorpalette_border_radius` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `border_width` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `colorpalette_button_accept` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `colorpalette_button_deny` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `colorpalette_button_settings` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `buttons_border_radius` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `animation` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `use_box_shadow` int(11) NOT NULL,
  `hide_preview` int(11) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_cmplz_cookiebanners`
--

LOCK TABLES `wp_cmplz_cookiebanners` WRITE;
/*!40000 ALTER TABLE `wp_cmplz_cookiebanners` DISABLE KEYS */;
INSERT INTO `wp_cmplz_cookiebanners` VALUES (1,9,1,0,'bottom-right minimal','bottom-right','minimal','slider','Manage consent','','Dismiss','Save preferences','Preferences','Accept all','Functional','Marketing','Statistics','Preferences','Accept','We use cookies to optimize our website and our service.','Cookie Policy','hidden','','visible',0,0,476,0,0,0,'10','Accept','We use cookies to optimize our website and our service.','Cookie Policy','Do Not Sell My Personal Information','Privacy Statement','Impressum','','','a:0:{}','a:2:{s:5:\"color\";s:7:\"#f9f9f9\";s:6:\"border\";s:7:\"#f9f9f9\";}','a:2:{s:5:\"color\";s:7:\"#191e23\";s:9:\"hyperlink\";s:7:\"#191e23\";}','a:3:{s:10:\"background\";s:7:\"#21759b\";s:6:\"bullet\";s:7:\"#ffffff\";s:8:\"inactive\";s:7:\"#F56E28\";}','a:5:{s:4:\"type\";s:2:\"px\";s:3:\"top\";i:0;s:5:\"right\";i:0;s:6:\"bottom\";i:0;s:4:\"left\";i:0;}','a:4:{s:3:\"top\";i:1;s:5:\"right\";i:1;s:6:\"bottom\";i:1;s:4:\"left\";i:1;}','a:3:{s:10:\"background\";s:7:\"#21759b\";s:6:\"border\";s:7:\"#21759b\";s:4:\"text\";s:7:\"#ffffff\";}','a:3:{s:10:\"background\";s:7:\"#f1f1f1\";s:6:\"border\";s:7:\"#f1f1f1\";s:4:\"text\";s:7:\"#21759b\";}','a:3:{s:10:\"background\";s:7:\"#f1f1f1\";s:6:\"border\";s:7:\"#21759b\";s:4:\"text\";s:7:\"#21759b\";}','a:5:{s:3:\"top\";i:5;s:5:\"right\";i:5;s:6:\"bottom\";i:5;s:4:\"left\";i:5;s:4:\"type\";s:2:\"px\";}','none',0,0);
/*!40000 ALTER TABLE `wp_cmplz_cookiebanners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_cmplz_cookies`
--

DROP TABLE IF EXISTS `wp_cmplz_cookies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_cmplz_cookies` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sync` int(11) NOT NULL,
  `ignored` int(11) NOT NULL,
  `retention` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `serviceID` int(11) NOT NULL,
  `cookieFunction` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `collectedPersonalData` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `purpose` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `language` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `isTranslationFrom` int(11) NOT NULL,
  `isPersonalData` int(11) NOT NULL,
  `deleted` int(11) NOT NULL,
  `isMembersOnly` int(11) NOT NULL,
  `showOnPolicy` int(11) NOT NULL,
  `lastUpdatedDate` int(11) NOT NULL,
  `lastAddDate` int(11) NOT NULL,
  `firstAddDate` int(11) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_cmplz_cookies`
--

LOCK TABLES `wp_cmplz_cookies` WRITE;
/*!40000 ALTER TABLE `wp_cmplz_cookies` DISABLE KEYS */;
INSERT INTO `wp_cmplz_cookies` VALUES (1,'cmplz_toggle_data_id_*','cmplz_toggle_data_id_',1,1,'365 days','Local Storage',13,'provide functions across pages','none','Functional','en',0,0,0,1,1,1620292148,1620281705,1620281701),(2,'debug','',1,0,'','localstorage',0,'','','','en',0,0,0,0,1,0,1620281705,1620281701),(4,'WP_DATA_USER_*','wp_data_user_-2',1,0,'session','',14,'store user preferences','user ID','Functional','en',0,0,0,1,1,1620292148,1620281705,1620281701),(5,'cmplzFormValues','cmplzformvalues',1,1,'365 days','Cookie',13,'','','','en',0,0,0,0,1,1620292148,1620281705,1620281701),(7,'cmplzDashboardDefaultsSet','cmplzdashboarddefaultsset',1,1,'365 days','Cookie',13,'','','','en',0,0,0,0,1,1620292148,1620281705,1620281701),(8,'cm4all_underberg','',1,0,'','localstorage',0,'','','','en',0,0,0,0,1,0,1620281705,1620281701),(9,'cmplz_layout','cmplz_layout',1,1,'365 days','Local Storage',13,'determine screen resolution','none','Functional','en',0,0,0,1,1,1620292148,1620281705,1620281701),(12,'wp-api-schema-model*','wp-api-schema-model-2',1,0,'session','',14,'','','Functional','en',0,0,0,1,1,1620292148,1620281705,1620281701),(13,'wordpress_test_cookie','wordpress_test_cookie',1,0,'none','',14,'check if cookies can be placed','','Functional','en',0,0,0,1,1,1620292148,1620281705,1620281701),(14,'wp-settings-time-*','wp-settings-time',1,1,'1 year','',14,'store user preferences','','Functional','en',0,0,0,1,1,1620292148,1620281705,1620281701),(15,'wordpress_*','wordpress_',1,1,'3 months','',14,'','none','Functional','en',0,0,0,0,1,1620292148,1620281705,1620281701),(17,'wordpress_logged_in_*','wordpress_logged_in_0fccad09e111918db6363377b39bc8af',1,0,'session','Cookie',14,'keep users logged in','','Functional','en',0,0,0,0,1,1620292148,1620281705,1620281701),(19,'Google Fonts API','tcb_google_fonts',1,0,'none','Resource',1,'request user IP address','IP address','Marketing/Tracking','en',0,1,0,0,1,1620292158,1620292151,1620292151),(20,'rc::c','rcc',1,0,'session','',2,'filter requests from bots','','Marketing/Tracking','en',0,0,0,0,1,1620292158,1620292151,1620292151),(21,'rc::b','rcb',1,0,'session','',2,'filter requests from bots','','Marketing/Tracking','en',0,0,0,0,1,1620292158,1620292151,1620292151),(22,'rc::a','rca',1,0,'persistent','',2,'filter requests from bots','','Marketing/Tracking','en',0,0,0,0,1,1620292158,1620292151,1620292151),(23,'Google Maps API','google-maps-api',1,0,'none','',3,'request user IP address','IP address','Marketing/Tracking','en',0,1,0,0,1,1620292158,1620292151,1620292151),(24,'__utmt_player','__utmt_player',1,0,'10 minutes','',5,'store and track audience reach','browsing behaviour','Statistics','en',0,0,0,0,1,1620292158,1620292151,1620292151),(25,'vuid','vuid',1,0,'2 years','',5,'store the user\'s usage history','browsing behaviour','Statistics','en',0,0,0,0,1,1620292158,1620292151,1620292151),(26,'GPS','gps',1,0,'session','',6,'store location data','location data','Marketing/Tracking','en',0,0,0,0,1,1620292158,1620292151,1620292151),(27,'VISITOR_INFO1_LIVE','visitor_info1_live',1,0,'6 months','',6,'estimate bandwidth','none','Functional','en',0,0,0,0,1,1620292158,1620292151,1620292151),(28,'YSC','ysc',1,0,'session','',6,'store a unique user ID','','Statistics','en',0,0,0,0,1,1620292158,1620292151,1620292151),(29,'PREF','pref',1,0,'1 year','',6,'store and track visits across websites','browsing behaviour','Statistics','en',0,1,0,0,1,1620292158,1620292151,1620292151),(30,'damd','damd',1,0,'session','Cookie',7,'store performed actions on the website','browsing behaviour','Statistics','en',0,0,0,0,1,1620292158,1620292151,1620292151),(31,'v1st','v1st',1,0,'13 months','',7,'','browsing device information','','en',0,0,0,0,1,1620292158,1620292151,1620292151),(32,'ts','auto-draft-2',1,0,'session','Resource',9,'provide fraud prevention','','Functional','en',0,0,0,0,1,1620292158,1620292151,1620292151),(33,'hist','hist',1,0,'','',7,'','','','en',0,0,0,0,1,1620292158,1620292151,1620292151),(34,'dmvk','dmvk',1,0,'session','Cookie',7,'store information for remarketing purposes','anonymous ID','Marketing/Tracking','en',0,1,0,0,1,1620292158,1620292151,1620292151),(35,'sc_anonymous_id','sc_anonymous_id',1,0,'10 years','',8,'provide functions across pages','anonymous ID','Functional','en',0,0,0,0,1,1620292158,1620292151,1620292151),(36,'sclocale','sclocale',1,0,'1 year','',8,'store language settings','','Functional','en',0,0,0,0,1,1620292158,1620292151,1620292151),(37,'ts_c','auto-draft-3',1,0,'3 years','Cookie',9,'provide fraud prevention','','Functional','en',0,0,0,0,1,1620292158,1620292151,1620292151),(39,'paypal','paypal',1,0,'persistent','',9,'provide fraud prevention','','Functional','en',0,0,0,0,1,1620292158,1620292151,1620292151),(40,'__paypal_storage__','__paypal_storage__',1,0,'persistent','Local Storage',9,'store account details','financial data','Functional','en',0,1,0,0,1,1620292158,1620292151,1620292151),(41,'_js_datr','_js_datr',1,1,'2 years','',10,'store user preferences','','Preferences','en',0,0,0,0,1,1620292158,1620292151,1620292151),(42,'actppresence','actppresence-2',1,0,'1 year','',10,'manage ad display frequency','','Marketing/Tracking','en',0,0,0,0,1,1620292158,1620292151,1620292151),(43,'_fbc','_fbc',1,0,'2 years','',10,'store last visit','browsing device information','Marketing/Tracking','en',0,0,0,0,1,1620292158,1620292151,1620292151),(44,'fbm*','fbm_',1,0,'1 year','',10,'store account details','social media account details','Marketing/Tracking','en',0,1,0,0,1,1620292159,1620292151,1620292151),(45,'xs','xs',1,0,'3 months','',10,'store a unique session ID','','Marketing/Tracking','en',0,0,0,0,1,1620292159,1620292151,1620292151),(46,'wd','wd',1,0,'1 week','',10,'determine screen resolution','','Functional','en',0,0,0,0,1,1620292159,1620292151,1620292151),(47,'fr','fr',1,0,'3 months','',10,'Enable ad delivery or retargeting','social media account details','Marketing/Tracking','en',0,1,0,0,1,1620292159,1620292151,1620292151),(48,'act','act',1,0,'90 days','',10,'keep users logged in','','Functional','en',0,0,0,0,1,1620292159,1620292151,1620292151),(49,'_fbp','_fbp',1,0,'3 months','',10,'store and track visits across websites','','Marketing/Tracking','en',0,0,0,0,1,1620292159,1620292151,1620292151),(50,'datr','datr',1,0,'2 years','',10,'provide fraud prevention','browsing device information','Marketing/Tracking','en',0,0,0,0,1,1620292159,1620292151,1620292151),(51,'c_user','c_user',1,0,'90 days','',10,'store a unique user ID','','Functional','en',0,0,0,0,1,1620292159,1620292151,1620292151),(52,'csm','csm',1,0,'90 days','',10,'provide fraud prevention','','Functional','en',0,0,0,0,1,1620292159,1620292151,1620292151),(53,'sb','sb',1,0,'2 years','',10,'store browser details','browsing device information','Marketing/Tracking','en',0,0,0,0,1,1620292159,1620292151,1620292151),(54,'presence','actppresence',1,0,'session','',10,'store and track if the browser tab is active','','Functional','en',0,0,0,0,1,1620292159,1620292151,1620292151),(55,'*_fbm_','_fbm_',1,0,'1 year','',10,'store account details','social media account details','Marketing/Tracking','en',0,1,0,0,1,1620292159,1620292151,1620292151),(56,'local_storage_support_test','local_storage_support_test',1,0,'persistent','',11,'load balancing functionality','','Functional','en',0,0,0,0,1,1620292159,1620292151,1620292151),(57,'metrics_token','metrics_token',1,0,'persistent','',11,'store if the user has seen embedded content','browsing device information','Marketing/Tracking','en',0,0,0,0,1,1620292159,1620292151,1620292151);
/*!40000 ALTER TABLE `wp_cmplz_cookies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_cmplz_services`
--

DROP TABLE IF EXISTS `wp_cmplz_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_cmplz_services` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `serviceType` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `thirdParty` int(11) NOT NULL,
  `sharesData` int(11) NOT NULL,
  `secondParty` int(11) NOT NULL,
  `privacyStatementURL` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `language` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `isTranslationFrom` int(11) NOT NULL,
  `sync` int(11) NOT NULL,
  `lastUpdatedDate` int(11) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_cmplz_services`
--

LOCK TABLES `wp_cmplz_services` WRITE;
/*!40000 ALTER TABLE `wp_cmplz_services` DISABLE KEYS */;
INSERT INTO `wp_cmplz_services` VALUES (1,'Google Fonts','google-fonts','display of webfonts','service',1,1,0,'https://policies.google.com/privacy','en',0,1,1620292151),(2,'Google reCAPTCHA','google-recaptcha','spam prevention','service',1,1,0,'https://policies.google.com/privacy','en',0,1,1620292151),(3,'Google Maps','google-maps','maps display','service',1,1,0,'https://policies.google.com/privacy','en',0,1,1620292151),(4,'OpenStreetMaps','openstreetmaps','maps display','service',0,0,0,'','en',0,1,1620292151),(5,'Vimeo','vimeo','video display','service',1,1,1,'https://vimeo.com/privacy','en',0,1,1620292151),(6,'YouTube','youtube','video display','service',1,1,0,'https://policies.google.com/privacy','en',0,1,1620292151),(7,'Dailymotion','dailymotion','video display','service',1,1,0,'https://www.dailymotion.com/legal/privacy','en',0,1,1620292151),(8,'SoundCloud','soundcloud','audio streaming','service',1,1,0,'https://soundcloud.com/pages/privacy','en',0,1,1620292151),(9,'PayPal','paypal','payment processing','service',1,1,1,'https://www.paypal.com/uk/webapps/mpp/ua/privacy-full','en',0,1,1620292151),(10,'Facebook','facebook','display of recent social posts and/or social share buttons','social',1,1,0,'https://www.facebook.com/policy/cookies','en',0,1,1620292151),(11,'Twitter','twitter','display of recent social posts and/or social share buttons','social',1,1,0,'https://twitter.com/en/privacy','en',0,1,1620292151),(12,'WhatsApp','whatsapp','chat support','social',1,1,0,'https://www.whatsapp.com/legal/','en',0,1,1620292151),(13,'Complianz','complianz','cookie consent management','',0,0,0,'https://complianz.io/privacy-statement/','en',0,1,1620292151),(14,'WordPress','wordpress','website development','',0,0,0,'https://automattic.com/privacy/','en',0,1,1620292151);
/*!40000 ALTER TABLE `wp_cmplz_services` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2021-05-06  9:11:38
