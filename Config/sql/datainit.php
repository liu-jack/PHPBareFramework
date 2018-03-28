<?php
/**
 * datainit.php
 * 初始化数据
 *
 * @author camfee <camfee@foxmail.com>
 * @date   18-3-27 下午1:51
 *
 */

return <<<EOT
INSERT INTO `AdminMenu` VALUES (1, '首页', 'Admin_Index', 0, 99);
INSERT INTO `AdminMenu` VALUES (2, '用户', 'Admin_User', 0, 77);
INSERT INTO `AdminMenu` VALUES (3, '数据', 'Admin_Data', 0, 88);
INSERT INTO `AdminMenu` VALUES (4, '应用', 'Admin_App', 0, 66);
INSERT INTO `AdminMenu` VALUES (5, '工具', 'Admin_Tool', 0, 55);
INSERT INTO `AdminMenu` VALUES (6, '后台', 'Admin_Admin', 0, 0);
INSERT INTO `AdminMenu` VALUES (7, '首页', 'Admin/Info', 1, 0);
INSERT INTO `AdminMenu` VALUES (8, '欢迎页', 'Admin/Info/index', 7, 1);
INSERT INTO `AdminMenu` VALUES (9, '登录信息', 'Admin/Info/info', 7, 0);
INSERT INTO `AdminMenu` VALUES (10, '用户管理', 'User/Account', 2, 0);
INSERT INTO `AdminMenu` VALUES (11, '用户列表', 'User/Account/index', 10, 0);
INSERT INTO `AdminMenu` VALUES (12, '书本管理', 'Data/Book', 3, 9);
INSERT INTO `AdminMenu` VALUES (13, '书本列表', 'Data/Book/index', 12, 9);
INSERT INTO `AdminMenu` VALUES (14, '应用管理', 'Admin/App', 4, 0);
INSERT INTO `AdminMenu` VALUES (15, '应用列表', 'Admin/App/index', 14, 0);
INSERT INTO `AdminMenu` VALUES (16, '工具管理', 'Admin/Tool', 5, 0);
INSERT INTO `AdminMenu` VALUES (17, '工具列表', 'Admin/Tool/index', 16, 0);
INSERT INTO `AdminMenu` VALUES (18, '后台管理', 'Admin/Admin', 6, 9);
INSERT INTO `AdminMenu` VALUES (19, '菜单管理', 'Admin/Menu/index', 18, 9);
INSERT INTO `AdminMenu` VALUES (20, '管理员管理', 'Admin/Admin/index', 18, 6);
INSERT INTO `AdminMenu` VALUES (21, '权限组管理', 'Admin/Auth/index', 18, 5);
INSERT INTO `AdminMenu` VALUES (22, '操作日志', 'Admin/AdminLog/index', 26, 9);
INSERT INTO `AdminMenu` VALUES (23, '博客管理', 'Data/Blog', 3, 7);
INSERT INTO `AdminMenu` VALUES (24, '博客列表', 'Data/Blog/index', 23, 9);
INSERT INTO `AdminMenu` VALUES (25, '短信日志', 'Admin/SmsLog/index', 26, 0);
INSERT INTO `AdminMenu` VALUES (26, '日志管理', 'Admin/AdminLog', 6, 0);
INSERT INTO `AdminMenu` VALUES (27, '添加书本', 'Data/Book/add', 12, 8);
INSERT INTO `AdminMenu` VALUES (28, '采集列表', 'Data/Collect/index', 12, 3);
INSERT INTO `AdminMenu` VALUES (29, '添加采集', 'Data/Collect/add', 12, 0);
INSERT INTO `AdminMenu` VALUES (30, '手机管理', 'Admin/Mobile', 4, 99);
INSERT INTO `AdminMenu` VALUES (31, '启动图', 'Mobile/Screen/index', 30, 9);
INSERT INTO `AdminMenu` VALUES (32, '版本管理', 'Mobile/Version/index', 30, 7);
INSERT INTO `AdminMenu` VALUES (33, '手机推送', 'Mobile/Push/index', 30, 5);
INSERT INTO `AdminMenu` VALUES (34, '定时任务', 'Mobile/Cron/index', 30, 3);
INSERT INTO `AdminMenu` VALUES (35, '刷新缓存', 'Tool/CacheRefresh/index', 16, 9);
INSERT INTO `AdminMenu` VALUES (36, '队列管理', 'Tool/Queue/index', 16, 7);
INSERT INTO `AdminMenu` VALUES (37, 'Mongodb管理', 'Tool/Mongodb/index', 16, 5);
INSERT INTO `AdminMenu` VALUES (38, '敏感词过滤', 'User/Filter/index', 10, 0);
INSERT INTO `AdminMenu` VALUES (39, '标签管理', 'Data/Tags', 3, 5);
INSERT INTO `AdminMenu` VALUES (40, '标签列表', 'Data/Tags/index', 39, 9);
INSERT INTO `AdminMenu` VALUES (41, '评论管理', 'Data/Comment', 3, 3);
INSERT INTO `AdminMenu` VALUES (42, '评论列表', 'Data/Comment/index', 41, 9);
INSERT INTO `AdminMenu` VALUES (43, '标签新增', 'Data/Tags/add', 39, 7);
INSERT INTO `AdminMenu` VALUES (44, '图片', 'Admin_Picture', 0, 44);
INSERT INTO `AdminMenu` VALUES (45, '相册', 'Picture/Atlas', 44, 9);
INSERT INTO `AdminMenu` VALUES (46, '相册管理', 'Picture/Atlas/index', 45, 9);
INSERT INTO `AdminMenu` VALUES (47, '相片管理', 'Picture/Photo/index', 45, 7);


EOT;
