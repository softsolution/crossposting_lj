ALTER TABLE `cms_blogs`  ADD `crosspost` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `cms_blogs`  ADD `journal` varchar(30) NOT NULL DEFAULT 'www.livejournal.com';
ALTER TABLE `cms_blogs`  ADD `loginlj` varchar(50) NOT NULL;
ALTER TABLE `cms_blogs`  ADD `passlj` varchar(50) NOT NULL;
ALTER TABLE `cms_blogs`  ADD `ñommunity` varchar(50) NOT NULL;
ALTER TABLE `cms_blogs`  ADD `header_loc` int(1) NOT NULL DEFAULT '0';
ALTER TABLE `cms_blogs`  ADD `custom_name_on` int(1) NOT NULL DEFAULT '0';
ALTER TABLE `cms_blogs`  ADD `custom_name` varchar(100) NOT NULL;
ALTER TABLE `cms_blogs`  ADD `privacy` varchar(20) NOT NULL DEFAULT 'public';
ALTER TABLE `cms_blogs`  ADD `more` varchar(20) NOT NULL DEFAULT 'lj-cut';

ALTER TABLE `cms_blog_posts` ADD `ljID` varchar(200) NOT NULL;

ALTER TABLE `cms_components` ADD `tuning` text NOT NULL;