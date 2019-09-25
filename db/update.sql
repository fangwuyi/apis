/* 2019.05.13 【已发布】  */
alter table xw_banner_info  add `type` int(11) NOT NULL DEFAULT '4' COMMENT 'type  1:文章， 2:帖子 3:H5 4:不跳转 5: 跳转社区';
/* 2019.05.30 */
CREATE TABLE `xw_hot_search` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` int(11) NOT NULL DEFAULT '1' COMMENT '类型：1资讯，2帖子',
  `content` varchar(256) NOT NULL COMMENT '搜索内容',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态：-1删除，1显示',
  `hot` int(11) NOT NULL DEFAULT '1' COMMENT '热度',
  `time_create` int(11) NOT NULL DEFAULT '2' COMMENT '创建时间',
  `sort` int(11) NOT NULL DEFAULT '255' COMMENT '排序值，正序',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='热搜榜';

/* 2019.05.31 */
CREATE TABLE `xw_feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL DEFAULT '1' COMMENT '会员ID',
  `imgs` varchar(1025) NOT NULL DEFAULT '' COMMENT '附件',
  `content` varchar(256) NOT NULL COMMENT '反馈建议',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态：1未处理，2已处理',
  `time_modify` int(11) NOT NULL DEFAULT '0' COMMENT '修改时间',
  `time_create` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='反馈建议';

CREATE TABLE `xw_member_eport` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL DEFAULT '0' COMMENT '会员ID',
  `news_id` int(11) NOT NULL DEFAULT '0' COMMENT '资讯ID',
  `community_id` int(11) NOT NULL DEFAULT '0' COMMENT '帖子ID',
  `comment_id` int(11) NOT NULL DEFAULT '0' COMMENT '评论ID',
  `time_modify` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `time_create` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态：1未处理，2已处理',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='评论举报';

CREATE TABLE `xw_member_subscribe` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL DEFAULT '0' COMMENT '会员ID',
  `subscribe_id` int(11) NOT NULL DEFAULT '0' COMMENT '被关注者ID',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1已关注，2取消',
  `time_modify` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `time_create` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用户关注表';

CREATE TABLE `xw_member_letter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_id` int(11) NOT NULL DEFAULT '0' COMMENT '会员ID',
  `receive_id` int(11) NOT NULL DEFAULT '0' COMMENT '被关注者ID',
  `content` varchar(256) NOT NULL COMMENT '消息内容json',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '-1删除，1未读，2已读',
  `time_modify` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `time_create` int(11) NOT NULL DEFAULT '0' COMMENT '新增时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='私信表';

CREATE TABLE `xw_message push` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` int(11) NOT NULL DEFAULT '1' COMMENT '消息类型',
  `title` varchar(256) NOT NULL COMMENT '推送标题',
  `content` varchar(1024) NOT NULL COMMENT '消息内容',
  `push_time` int(11) NOT NULL DEFAULT '0' COMMENT '推送时间',
  `push_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '推送状态：-1删除，1未推送，2已推送',
  `push_users` varchar(256) NOT NULL COMMENT '@all所有用户，指定用户为用户ID，多个逗号分隔',
  `time_modify` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `time_create` int(11) NOT NULL DEFAULT '0' COMMENT '新增时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='消息推送';

CREATE TABLE `xw_member_loves` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL COMMENT '会员ID',
  `fans_id` int(11) NOT NULL COMMENT '粉丝ID',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态：1点赞，2取消点赞',
  `time_modify` int(11) NOT NULL DEFAULT '0' COMMENT '修改时间',
  `time_create` int(11) NOT NULL COMMENT '时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='粉丝会员点赞';

alter table `xw_member_login` ADD `type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '账号类型：1账号密码，2QQ授权，3微信授权，4微博授权';

alter table xw_member_info ADD `apptoken` varchar(128) NOT NULL DEFAULT '' COMMENT 'APP token用于发消息';

CREATE TABLE `xw_message_subscribe` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `channel` varchar(256) NOT NULL COMMENT '渠道：umeng，expo',
  `device_token` varchar(256) NOT NULL COMMENT '设备token',
  `device_type` int(11) NOT NULL DEFAULT '0' COMMENT '设备类型：1安卓，2苹果',
  `subscribe` tinyint(4) NOT NULL DEFAULT '0' COMMENT '默认0,订阅1，不订阅2',
  `time_modify` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `time_create` int(11) NOT NULL DEFAULT '0' COMMENT '新增时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='消息订阅';