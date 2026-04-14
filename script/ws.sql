--01-28
CREATE TABLE `ks_mv_tg` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `messae_id` int DEFAULT NULL,
    `title` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0' COMMENT '影片标题',
    `duration` int DEFAULT '0',
    `local_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '',
    `status` tinyint DEFAULT '0',
    `m3u8` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '',
    `cover` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '',
    `height` int DEFAULT '0',
    `width` int DEFAULT '0',
    `created_at` datetime DEFAULT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

ALTER TABLE `ks_mv`
ADD COLUMN `type` tinyint NULL DEFAULT 0 COMMENT '视频类型 0长视频 1短视频',
ADD INDEX `type`(`type`) USING BTREE;

ALTER TABLE `ks_user_likes`
ADD COLUMN `type` tinyint NULL DEFAULT 0 AFTER `uid`;

CREATE TABLE `ks_picture_favorites` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `uid` int NOT NULL DEFAULT '0' COMMENT '用户id',
    `zy_id` int NOT NULL DEFAULT '0' COMMENT '小说id',
    `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `user_id` (`uid`),
    KEY `comics_id` (`zy_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='图集收藏表';

CREATE TABLE `ks_mh_favorites` (
   `id` int unsigned NOT NULL AUTO_INCREMENT,
   `uid` int NOT NULL DEFAULT '0' COMMENT '用户id',
   `mh_id` int NOT NULL DEFAULT '0' COMMENT '漫画id',
   `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
   PRIMARY KEY (`id`),
   KEY `user_id` (`uid`),
   KEY `comics_id` (`mh_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='漫画收藏表';

CREATE TABLE `ks_story_favorites` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `uid` int NOT NULL DEFAULT '0' COMMENT '用户id',
  `zy_id` int NOT NULL DEFAULT '0' COMMENT '小说id',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`uid`),
  KEY `comics_id` (`zy_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci COMMENT='小说收藏表';

--权限模块
CREATE TABLE `ks_privilege` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `resource_type` tinyint NOT NULL COMMENT '资源类型',
    `privilege_type` tinyint NOT NULL COMMENT '权限类型',
    `value` int NOT NULL DEFAULT '0',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `sort` int DEFAULT '0' COMMENT '排序',
    `day_value` int NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='权限配置项表';

CREATE TABLE `ks_product_privilege` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `product_id` int NOT NULL COMMENT '产品',
    `privilege_id` int NOT NULL COMMENT '权限',
    `value` int NOT NULL DEFAULT '0' COMMENT '值',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '时间',
    PRIMARY KEY (`id`),
    KEY `product_id` (`product_id`),
    KEY `privilege_id` (`privilege_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='产品权限表';

CREATE TABLE `ks_users_product_privilege` (
      `id` int unsigned NOT NULL AUTO_INCREMENT,
      `aff` int NOT NULL,
      `product_id` int NOT NULL DEFAULT '0',
      `privilege_id` int NOT NULL,
      `resource_type` tinyint NOT NULL,
      `privilege_type` tinyint NOT NULL,
      `value` int NOT NULL,
      `status` tinyint NOT NULL,
      `expired_date` datetime NOT NULL,
      `created_at` timestamp NOT NULL,
      `day_value` int NOT NULL DEFAULT '0',
      PRIMARY KEY (`id`),
      KEY `aff` (`aff`),
      KEY `product_id` (`product_id`),
      KEY `privilege_id` (`privilege_id`),
      KEY `status` (`status`),
      KEY `resource_type` (`resource_type`),
      KEY `privilege_type` (`privilege_type`),
      KEY `expired_date` (`expired_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='用户购买的产品权限表';


CREATE TABLE `ks_product_user` (
   `id` int unsigned NOT NULL AUTO_INCREMENT,
   `aff` int unsigned NOT NULL,
   `product_id` int unsigned NOT NULL,
   `vip_level` tinyint NOT NULL,
   `type` tinyint NOT NULL DEFAULT '0',
   `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '时间',
   `expired_date` datetime NOT NULL,
   `status` tinyint(1) NOT NULL DEFAULT '0',
   PRIMARY KEY (`id`),
   UNIQUE KEY `aff` (`aff`,`product_id`),
   KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='用户vip卡包';

--AI模块
CREATE TABLE `ks_face_cate` (
    `id` int NOT NULL AUTO_INCREMENT,
    `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '分类名',
    `status` tinyint DEFAULT '0',
    `sort` int DEFAULT '0' COMMENT '排序',
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    `type` tinyint DEFAULT '0' COMMENT '类型 0普通 1最新',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='图片换头分类表';

CREATE TABLE `ks_face_material` (
    `id` int NOT NULL AUTO_INCREMENT,
    `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '素材标题',
    `thumb` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '素材图片',
    `thumb_w` int DEFAULT '0',
    `thumb_h` int DEFAULT '0',
    `sort` int DEFAULT '0',
    `use_ct` int DEFAULT '0' COMMENT '使用数',
    `status` int DEFAULT '0' COMMENT '状态',
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    `cate_id` int DEFAULT '0' COMMENT '分类ID',
    `type` tinyint DEFAULT '0' COMMENT '类型 0 VIP 1金币 ',
    `coins` int DEFAULT '0' COMMENT '金币数',
    PRIMARY KEY (`id`),
    KEY `cate_id` (`cate_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='换头素材表';

CREATE TABLE `ks_member_face` (
  `id` int NOT NULL AUTO_INCREMENT,
  `aff` int NOT NULL DEFAULT '0' COMMENT 'Aff',
  `material_id` int DEFAULT '0' COMMENT '素材ID',
  `ground` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '用户底板',
  `ground_w` int DEFAULT '0',
  `ground_h` int DEFAULT '0',
  `thumb` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '用户上传头像',
  `thumb_w` int DEFAULT '0',
  `thumb_h` int DEFAULT '0',
  `face_thumb` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '处理之后图片',
  `face_thumb_w` int DEFAULT '0',
  `face_thumb_h` int DEFAULT '0',
  `is_delete` tinyint NOT NULL DEFAULT '0' COMMENT '是否删除',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态',
  `reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '处理异常描述',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `type` tinyint DEFAULT '0' COMMENT '0 金币 1次数',
  `coins` int DEFAULT '0' COMMENT '金币数',
  PRIMARY KEY (`id`),
  KEY `aff` (`aff`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='用户换头表';

--种子模块
CREATE TABLE `ks_seed_like` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `aff` int DEFAULT '0',
    `related_id` int DEFAULT '0',
    `type` int DEFAULT '0',
    `created_at` datetime DEFAULT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `aff` (`aff`),
    KEY `related_id` (`related_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='种子点赞表';

CREATE TABLE `ks_seed_post` (
    `id` int NOT NULL AUTO_INCREMENT,
    `p_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '原始ID',
    `type` int NOT NULL DEFAULT '0' COMMENT '类型',
    `coins` int NOT NULL DEFAULT '0' COMMENT '金币',
    `payed_ct` int NOT NULL DEFAULT '0' COMMENT '已购买次数',
    `payed_coins` int NOT NULL DEFAULT '0' COMMENT '已购买金币数',
    `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '标题',
    `topic_id` int NOT NULL DEFAULT '0' COMMENT '板块ID',
    `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '内容',
    `link` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '链接地址',
    `photo_ct` int NOT NULL DEFAULT '0' COMMENT '图片数',
    `video_ct` int NOT NULL DEFAULT '0' COMMENT '视频数',
    `like_ct` int NOT NULL DEFAULT '0' COMMENT '点赞数',
    `favorite_ct` int NOT NULL DEFAULT '0' COMMENT '收藏数',
    `set_top` int NOT NULL DEFAULT '0' COMMENT '置顶',
    `is_finished` int NOT NULL DEFAULT '0' COMMENT '资源是否完成',
    `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
    `view_ct` int DEFAULT '0' COMMENT '浏览数',
    `comment_ct` int DEFAULT '0' COMMENT '评论数',
    `fake_view_ct` int NOT NULL DEFAULT '0' COMMENT '假浏览量',
    `status` int NOT NULL DEFAULT '0' COMMENT '审核状态',
    `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
    `updated_at` timestamp NULL DEFAULT NULL COMMENT '修改时间',
    `secret` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT '' COMMENT '解压密码',
    `fake_like_ct` int DEFAULT '0' COMMENT '点赞（假）',
    `favorite_count` int DEFAULT '0',
    `rec_sort` int DEFAULT '0' COMMENT '3个月内点赞/收藏',
    `hot_sort` int DEFAULT '0' COMMENT '本月浏览量',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `p_id` (`p_id`),
    KEY `title` (`title`),
    KEY `type` (`type`),
    KEY `is_finished` (`is_finished`),
    KEY `set_top` (`set_top`),
    KEY `sort` (`sort`),
    KEY `video_ct` (`video_ct`),
    KEY `idx_sitsi` (`status`,`is_finished`,`rec_sort`,`id`) USING BTREE,
    KEY `idx_sitfi` (`status`,`is_finished`,`hot_sort`,`id`) USING BTREE,
    KEY `idx_sitci` (`status`,`is_finished`,`updated_at`,`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC COMMENT='种子文章列表';

CREATE TABLE `ks_seed_post_buy_log` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `aff` int DEFAULT '0' COMMENT '用户aff',
    `seed_id` int DEFAULT '0' COMMENT '帖子ID',
    `coins` int DEFAULT '0' COMMENT '解锁金额',
    `created_at` datetime DEFAULT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `aff` (`aff`),
    KEY `seed_id` (`seed_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='种子帖子购买记录';

CREATE TABLE `ks_seed_post_comment` (
    `id` int NOT NULL AUTO_INCREMENT,
    `seed_id` int unsigned NOT NULL DEFAULT '0' COMMENT '种子ID',
    `pid` int NOT NULL DEFAULT '0' COMMENT '评论ID,默认0(第一层评论)',
    `aff` int NOT NULL DEFAULT '0' COMMENT '用户aff',
    `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '留言内容',
    `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '0:待审核\n1:审核通过\n2.未通过\n3.禁言\n',
    `is_read` tinyint NOT NULL DEFAULT '0' COMMENT '被回复者是否已读',
    `like_num` int NOT NULL DEFAULT '0' COMMENT '此条评论点赞数量',
    `video_num` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '视频数量',
    `photo_num` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '图片数量',
    `ipstr` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户ip',
    `cityname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '定位城市',
    `complain_num` int unsigned NOT NULL DEFAULT '0' COMMENT '被举报次数',
    `refuse_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '拒绝通过原因',
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    `is_finished` int NOT NULL DEFAULT '0' COMMENT '资源是否处理完 0未处理 1已处理',
    `is_top` int DEFAULT '0' COMMENT '是否置顶 0未置顶 1已置顶',
    PRIMARY KEY (`id`),
    KEY `pid` (`pid`),
    KEY `aff` (`aff`),
    KEY `status` (`status`),
    KEY `seed_id` (`seed_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='种子评论表';

CREATE TABLE `ks_seed_post_media` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `media_url` varchar(255) NOT NULL DEFAULT '' COMMENT '视频或图片地址',
  `cover` varchar(255) NOT NULL DEFAULT '' COMMENT '视频封面',
  `thumb_width` int NOT NULL DEFAULT '0' COMMENT '封面宽',
  `thumb_height` int NOT NULL DEFAULT '0' COMMENT '封面高',
  `pid` int NOT NULL DEFAULT '0' COMMENT '帖子ID',
  `type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '类型 1图片 2视频',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '0 未转换 1 已转换 2 转换中',
  `duration` int NOT NULL DEFAULT '0' COMMENT '视频持续时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `relate_type` tinyint(1) DEFAULT NULL COMMENT '关联类型 1帖子 2评论',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`) USING BTREE,
  KEY `type` (`type`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='种子帖子媒体表';

CREATE TABLE `ks_seed_favorites` (
 `id` int unsigned NOT NULL AUTO_INCREMENT,
 `uid` int NOT NULL DEFAULT '0' COMMENT '用户id',
 `zy_id` int NOT NULL DEFAULT '0' COMMENT '种子id',
 `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
 PRIMARY KEY (`id`),
 KEY `user_id` (`uid`),
 KEY `seed_id` (`zy_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='种子收藏表';

--黄游戏
CREATE TABLE `ks_porn_category` (
    `id` int NOT NULL AUTO_INCREMENT,
    `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '标题',
    `sub_title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '副标题',
    `thumb` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '封面',
    `rating` int NOT NULL COMMENT '点击量',
    `type` tinyint NOT NULL COMMENT '类型 0普通 1最多喜欢 2畅销榜 3最新 4手游',
    `is_recommend` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 推荐',
    `show_style` tinyint NOT NULL COMMENT '0:1*3 1:1*2 2:1*1 3:1*N',
    `show_max` tinyint NOT NULL DEFAULT '0' COMMENT '默认最大展示数量',
    `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态 ',
    `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
    `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
    `sort` int DEFAULT '0' COMMENT '排序',
    `works_num` int DEFAULT '0' COMMENT '作品数',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC COMMENT='黄游分类';

CREATE TABLE `ks_porn_game` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT '' COMMENT '资源ID',
    `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT '' COMMENT '标题',
    `category_id` int DEFAULT '0' COMMENT '分类ID',
    `tags` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT '' COMMENT '标签',
    `thumb` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT '' COMMENT '封面',
    `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '备注',
    `intro` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '简介',
    `play_intro` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '游戏玩法',
    `desc` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '说明',
    `type` int DEFAULT '0' COMMENT '类型 0免费 1次数 2金币 3次数和金币',
    `coins` int DEFAULT '0' COMMENT '金币',
    `is_recommend` tinyint DEFAULT '0' COMMENT '是否推荐',
    `is_hot` tinyint DEFAULT '0' COMMENT '是否热门',
    `real_like_count` int DEFAULT '0' COMMENT '真实喜欢数',
    `like_count` int DEFAULT '0' COMMENT '喜欢数',
    `comment_count` int DEFAULT '0' COMMENT '评论数',
    `view_count` int DEFAULT '0' COMMENT '浏览数',
    `real_view_count` int DEFAULT '0' COMMENT '真实浏览数',
    `buy_num` int DEFAULT '0' COMMENT '购买次数',
    `buy_coins` int DEFAULT '0' COMMENT '购买总金币',
    `buy_fake` int DEFAULT '0' COMMENT '显示解锁量',
    `score` int DEFAULT '0' COMMENT '评分',
    `sort` int DEFAULT '0' COMMENT '排序',
    `status` tinyint DEFAULT '1' COMMENT '状态',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `refresh_at` datetime DEFAULT NULL COMMENT '刷新时间',
    `favorite_ct` int DEFAULT '0' COMMENT '收藏数',
    `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '下载地址及密码',
    `real_favorite` int DEFAULT '0' COMMENT '真实收藏数',
    PRIMARY KEY (`id`),
    KEY `ft_categoy` (`category_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='黄游表';

CREATE TABLE `ks_porn_media` (
     `id` int unsigned NOT NULL AUTO_INCREMENT,
     `media_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '视频或图片地址',
     `cover` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '视频封面',
     `thumb_width` int NOT NULL DEFAULT '0' COMMENT '封面宽',
     `thumb_height` int NOT NULL DEFAULT '0' COMMENT '封面高',
     `pid` int NOT NULL DEFAULT '0' COMMENT '黄游ID',
     `aff` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0' COMMENT '上传用户AFF',
     `type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '类型 1图片 2视频',
     `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
     `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '0 未转换 1 已转换 2 转换中',
     `duration` int NOT NULL DEFAULT '0' COMMENT '视频持续时间',
     `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
     `relate_type` tinyint(1) DEFAULT NULL COMMENT '关联类型 1黄游 2评论',
     PRIMARY KEY (`id`),
     KEY `pid` (`pid`) USING BTREE,
     KEY `type` (`type`) USING BTREE,
     KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='黄游媒体表';

CREATE TABLE `ks_porn_like` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `aff` int DEFAULT '0' COMMENT '用户aff',
    `porn_id` int DEFAULT '0' COMMENT '图集id',
    `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
    `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='黄游点赞表';

CREATE TABLE `ks_porn_comment` (
   `id` int NOT NULL AUTO_INCREMENT,
   `porn_id` int unsigned NOT NULL DEFAULT '0' COMMENT '帖子ID',
   `pid` int NOT NULL DEFAULT '0' COMMENT '评论ID,默认0(第一层评论)',
   `aff` int NOT NULL DEFAULT '0' COMMENT '用户aff',
   `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '留言内容',
   `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '0:待审核 1:审核通过 2.未通过',
   `ipstr` varchar(60) NOT NULL DEFAULT '' COMMENT '用户ip',
   `cityname` varchar(100) NOT NULL DEFAULT '' COMMENT '定位城市',
   `refuse_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '拒绝通过原因',
   `created_at` datetime DEFAULT NULL,
   `updated_at` datetime DEFAULT NULL,
   `is_top` int DEFAULT '0' COMMENT '是否置顶 0未置顶 1已置顶',
   PRIMARY KEY (`id`),
   KEY `porn_id` (`porn_id`),
   KEY `pid` (`pid`),
   KEY `aff` (`aff`),
   KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='黄游评论表';

CREATE TABLE `ks_porn_pay` (
   `id` int NOT NULL AUTO_INCREMENT,
   `aff` int NOT NULL COMMENT '用户aff',
   `coins` int NOT NULL DEFAULT '0' COMMENT '购买时的价格',
   `porn_id` int NOT NULL COMMENT '黄游id',
   `type` tinyint NOT NULL DEFAULT '1' COMMENT '类型 1 购买',
   `status` tinyint(1) DEFAULT '0' COMMENT '状态 0 未支付 1已完成',
   `created_at` timestamp NOT NULL COMMENT '购买时间',
   `updated_at` timestamp NOT NULL COMMENT '更新时间',
   PRIMARY KEY (`id`) USING BTREE,
   KEY `aff` (`aff`,`porn_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci ROW_FORMAT=COMPACT COMMENT='黄游购买记录';

CREATE TABLE `ks_porn_tags` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '标签',
    `sort` int NOT NULL COMMENT '排序',
    `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '列表显示状态',
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE KEY `name` (`name`),
    KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC COMMENT='黄游标签表';


--动漫模块
CREATE TABLE `ks_cartoon_category` (
   `id` int NOT NULL AUTO_INCREMENT,
   `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '标题',
   `sub_title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '副标题',
   `thumb` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '封面',
   `rating` int NOT NULL COMMENT '点击量',
   `type` tinyint NOT NULL COMMENT '类型 0普通 1最多喜欢 2畅销榜 3最新',
   `is_recommend` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 推荐',
   `show_style` tinyint NOT NULL COMMENT '0:1*3 1:1*2 2:1*1 3:1*N',
   `show_max` tinyint NOT NULL DEFAULT '0' COMMENT '默认最大展示数量',
   `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态 ',
   `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
   `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
   `sort` int DEFAULT '0' COMMENT '排序',
   PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC COMMENT='动漫分类';

CREATE TABLE `ks_cartoon` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_id` int DEFAULT '0' COMMENT '分类ID',
  `title` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '影片标题',
  `desc` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT '' COMMENT '简介',
  `actors` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '演员',
  `category` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '分类',
  `country` varchar(255) NOT NULL DEFAULT '' COMMENT '国家',
  `directors` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '导演',
  `is_series` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 电影 2电视剧',
  `cover` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '封面',
  `tags` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '影片标签',
  `langs` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '语言',
  `year_released` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '影片上映年',
  `video_num` int NOT NULL DEFAULT '0' COMMENT '视频数量',
  `like_count` int unsigned NOT NULL DEFAULT '0' COMMENT '点赞数',
  `play_count` int unsigned NOT NULL DEFAULT '0' COMMENT '播放数',
  `com_count` int unsigned NOT NULL DEFAULT '0' COMMENT '评论数',
  `pay_count` int NOT NULL DEFAULT '0' COMMENT '售卖次数',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '0下架1上架',
  `refresh_at` datetime DEFAULT NULL COMMENT '刷新时间',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `source_id` varchar(32) NOT NULL COMMENT '采集资源ID 采集识别',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `refresh_at` (`refresh_at`) USING BTREE,
  KEY `title` (`title`(255)) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `source_id` (`source_id`) USING BTREE,
  KEY `ft_category` (`category_id`) USING BTREE,
  FULLTEXT KEY `full_tags` (`tags`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='动漫表';

CREATE TABLE `ks_cartoon_chapters` (
   `id` int NOT NULL AUTO_INCREMENT,
   `pid` int NOT NULL DEFAULT '0' COMMENT '动漫ID',
   `cover` varchar(255) NOT NULL DEFAULT '' COMMENT '封面',
   `source` varchar(255) NOT NULL DEFAULT '' COMMENT '影片资源 电影',
   `duration` int unsigned NOT NULL DEFAULT '0' COMMENT '时长秒',
   `width` int NOT NULL DEFAULT '0' COMMENT '宽度',
   `height` int NOT NULL DEFAULT '0' COMMENT '高度',
   `sort` int NOT NULL DEFAULT '0' COMMENT '剧集排序',
   `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 电影 2电视剧',
   `coins` int unsigned NOT NULL DEFAULT '0' COMMENT '定价',
   `is_free` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否免费 0 收费 1 免费',
   `like_count` int NOT NULL DEFAULT '0' COMMENT '点赞数',
   `play_count` int NOT NULL DEFAULT '0' COMMENT '播放数',
   `com_count` int NOT NULL DEFAULT '0' COMMENT '评论数',
   `pay_count` int DEFAULT '0' COMMENT '售卖次数',
   `status` tinyint NOT NULL DEFAULT '0' COMMENT '0下架1上架',
   `refresh_at` datetime DEFAULT NULL COMMENT '刷新时间',
   `created_at` datetime DEFAULT NULL COMMENT '创建时间',
   `source_id` int NOT NULL DEFAULT '0' COMMENT '资源ID 采集识别',
   `source_video_id` varchar(32) NOT NULL DEFAULT '0' COMMENT '资源视频ID 采集识别',
   `title` varchar(255) NOT NULL DEFAULT '' COMMENT '标题',
   PRIMARY KEY (`id`) USING BTREE,
   KEY `status` (`status`) USING BTREE,
   KEY `pid` (`pid`) USING BTREE,
   KEY `source_id` (`source_id`) USING BTREE,
   KEY `source_video_id` (`source_video_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='动漫视频表';

CREATE TABLE `ks_cartoon_like` (
   `id` int unsigned NOT NULL AUTO_INCREMENT,
   `aff` int DEFAULT '0' COMMENT '用户aff',
   `cartoon_id` int DEFAULT '0' COMMENT '动漫id',
   `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
   `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='动漫点赞表';

CREATE TABLE `ks_cartoon_pay` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uid` int NOT NULL COMMENT '用户id',
  `coins` int NOT NULL DEFAULT '0' COMMENT '购买时的价格',
  `video_id` int NOT NULL COMMENT '视频ID',
  `created_at` datetime NOT NULL COMMENT '购买时间',
  `cartoon_id` int NOT NULL DEFAULT '0' COMMENT '动漫ID',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `uid` (`uid`,`video_id`) USING BTREE,
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='动漫视频购买记录';

CREATE TABLE `ks_cartoon_comment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cartoon_id` int unsigned NOT NULL DEFAULT '0' COMMENT '动漫ID',
  `pid` int NOT NULL DEFAULT '0' COMMENT '评论ID,默认0(第一层评论)',
  `aff` int NOT NULL DEFAULT '0' COMMENT '用户aff',
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '留言内容',
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '0:待审核 1:审核通过 2.未通过',
  `ipstr` varchar(60) NOT NULL DEFAULT '' COMMENT '用户ip',
  `cityname` varchar(100) NOT NULL DEFAULT '' COMMENT '定位城市',
  `refuse_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '拒绝通过原因',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `is_top` int DEFAULT '0' COMMENT '是否置顶 0未置顶 1已置顶',
  PRIMARY KEY (`id`),
  KEY `cartoon_id` (`cartoon_id`),
  KEY `pid` (`pid`),
  KEY `aff` (`aff`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='动漫评论表';

CREATE TABLE `ks_cartoon_comment_likes` (
    `id` int NOT NULL AUTO_INCREMENT,
    `uid` int NOT NULL COMMENT '用户ID',
    `comment_id` int NOT NULL COMMENT '评论ID',
    `created_at` datetime DEFAULT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `uid` (`uid`),
    KEY `comment_id` (`comment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='动漫评论点赞记录';


--直播

CREATE TABLE `ks_live` (
   `id` int NOT NULL AUTO_INCREMENT,
   `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
   `thumb` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
   `gender` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '主播性别',
   `country` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '主播国家',
   `hls` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
   `cover` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
   `created_at` datetime DEFAULT NULL,
   `updated_at` datetime DEFAULT NULL,
   `model_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '主播ID',
   `show` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '状态',
   `tag` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '标签',
   `status` int DEFAULT '1' COMMENT '状态',
   `favorite_oct` int DEFAULT '0' COMMENT '原站收藏数',
   `view_oct` int DEFAULT '0' COMMENT '原站观众数',
   `view_count` int DEFAULT '0' COMMENT '随机浏览',
   `real_view_count` int DEFAULT '0' COMMENT '真浏览',
   `favorite_count` int DEFAULT '0' COMMENT '随机收藏',
   `real_favorite_count` int DEFAULT '0' COMMENT '真收藏',
   `language` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '语言',
   `comment_ct` int DEFAULT '0' COMMENT '评论数',
   `type` int DEFAULT '0' COMMENT '收费类型',
   `coins` int DEFAULT '0' COMMENT '收费金币',
   `fr_width` int DEFAULT '0' COMMENT '帧宽',
   `fr_height` int DEFAULT '0' COMMENT '帧高',
   `f_cover` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '原始封面地址',
   `pay_ct` int DEFAULT '0' COMMENT '支付次数',
   `pay_coins` int DEFAULT '0' COMMENT '已支付金币数',
   `intro` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '描述',
   `reward_ct` int DEFAULT '0' COMMENT '打赏次数',
   `reward_coins` int DEFAULT '0' COMMENT '打赏金额',
   `sort` int DEFAULT '0' COMMENT '排序',
   `like_count` int DEFAULT '0' COMMENT '显示点赞数',
   `real_like_count` int DEFAULT '0' COMMENT '点赞数',
   PRIMARY KEY (`id`),
   KEY `idx_1` (`username`),
   KEY `idx_4` (`model_id`),
   KEY `idx_5` (`show`),
   KEY `mix_idx1` (`tag`(255),`show`,`status`,`view_oct`,`view_count`,`real_view_count`,`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `ks_live_comment` (
   `id` int NOT NULL AUTO_INCREMENT,
   `live_id` int unsigned NOT NULL DEFAULT '0' COMMENT '直播ID',
   `pid` int NOT NULL DEFAULT '0' COMMENT '评论ID,默认0(第一层评论)',
   `aff` int NOT NULL DEFAULT '0' COMMENT '用户aff',
   `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '留言内容',
   `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '0:待审核 1:审核通过 2.未通过',
   `ipstr` varchar(60) NOT NULL DEFAULT '' COMMENT '用户ip',
   `cityname` varchar(100) NOT NULL DEFAULT '' COMMENT '定位城市',
   `refuse_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '拒绝通过原因',
   `created_at` datetime DEFAULT NULL,
   `updated_at` datetime DEFAULT NULL,
   `is_top` int DEFAULT '0' COMMENT '是否置顶 0未置顶 1已置顶',
   PRIMARY KEY (`id`),
   KEY `live_id` (`live_id`),
   KEY `pid` (`pid`),
   KEY `aff` (`aff`),
   KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='直播评论表';

CREATE TABLE `ks_live_favorites` (
 `id` int unsigned NOT NULL AUTO_INCREMENT,
 `aff` int DEFAULT '0' COMMENT '用户aff',
 `live_id` int DEFAULT '0' COMMENT '直播id',
 `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
 `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
 PRIMARY KEY (`id`),
 KEY `aff` (`aff`) USING BTREE,
 KEY `live_id` (`live_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='直播收藏表';

CREATE TABLE `ks_live_like` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `aff` int DEFAULT '0' COMMENT '用户aff',
    `live_id` int DEFAULT '0' COMMENT '直播id',
    `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
    `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='直播点赞表';

CREATE TABLE `ks_live_pay` (
   `id` int NOT NULL AUTO_INCREMENT,
   `aff` int NOT NULL COMMENT '用户aff',
   `live_id` int NOT NULL COMMENT '直播id',
   `coins` int NOT NULL DEFAULT '0' COMMENT '购买时的价格',
   `created_at` timestamp NOT NULL COMMENT '购买时间',
   `updated_at` timestamp NOT NULL COMMENT '更新时间',
   PRIMARY KEY (`id`) USING BTREE,
   KEY `aff` (`aff`,`live_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='直播购买记录';

CREATE TABLE `ks_live_related` (
   `id` int NOT NULL AUTO_INCREMENT,
   `theme_id` int DEFAULT '0',
   `live_id` int DEFAULT '0',
   `created_at` datetime DEFAULT NULL,
   `updated_at` datetime DEFAULT NULL,
   PRIMARY KEY (`id`),
   KEY `sin_idx1` (`theme_id`),
   KEY `sin_idx2` (`live_id`),
   KEY `mix_idx1` (`theme_id`,`live_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `ks_live_theme` (
     `id` int unsigned NOT NULL AUTO_INCREMENT,
     `f_id` varchar(255) NOT NULL DEFAULT '' COMMENT '同步标识',
     `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '名字',
     `type` int NOT NULL DEFAULT '1' COMMENT '关联类型',
     `value` text COMMENT '值',
     `desc` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '描述',
     `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
     `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '0禁用 1启用',
     `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
     `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
     `symbol` int DEFAULT '1' COMMENT '符号类型',
     PRIMARY KEY (`id`),
     KEY `status` (`status`) USING BTREE,
     KEY `mix_idx1` (`f_id`),
     KEY `mix_idx3` (`f_id`),
     KEY `mix_idx2` (`status`,`sort`,`created_at`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='直播主题';

CREATE TABLE `ks_story_pay` (
    `id` int NOT NULL AUTO_INCREMENT,
    `uid` int NOT NULL COMMENT '用户id',
    `coins` int NOT NULL DEFAULT '0' COMMENT '购买时的价格',
    `zy_id` int NOT NULL COMMENT '资源小说编号id',
    `type` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '1' COMMENT '类型 购买 次数 赠送',
    `created_at` datetime DEFAULT NULL COMMENT '购买时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `uid` (`uid`,`zy_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPACT COMMENT='小说购买记录';

CREATE TABLE `ks_story_series` (
    `id` int NOT NULL AUTO_INCREMENT,
    `story_id` int NOT NULL DEFAULT '0',
    `series` int NOT NULL DEFAULT '1' COMMENT '章节',
    `title` varchar(255) NOT NULL DEFAULT '' COMMENT '名称',
    `is_free` tinyint NOT NULL DEFAULT '0' COMMENT '是否限免 0 免费 1 vip 2钻石',
    `views_count` int NOT NULL DEFAULT '0' COMMENT '总历史点击数',
    `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1上架0下架',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
    `url` varchar(255) NOT NULL COMMENT '小说cdn路径',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_story_id` (`story_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 ROW_FORMAT=COMPACT COMMENT='小说章节表';

CREATE TABLE `ks_picture_pay` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uid` int NOT NULL COMMENT '用户id',
  `coins` int NOT NULL DEFAULT '0' COMMENT '购买时的价格',
  `zy_id` int NOT NULL COMMENT '资源小说编号id',
  `type` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '1' COMMENT '类型 购买 次数 赠送',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '购买时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `uid` (`uid`,`zy_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci ROW_FORMAT=COMPACT COMMENT='小说购买记录';

CREATE TABLE `ks_mh_pay` (
     `id` int NOT NULL AUTO_INCREMENT,
     `uid` int NOT NULL COMMENT '用户id',
     `coins` int NOT NULL DEFAULT '0' COMMENT '购买时的价格',
     `mh_id` int NOT NULL COMMENT '漫画id',
     `type` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '1' COMMENT '类型 购买 次数 赠送',
     `created_at` timestamp NULL DEFAULT NULL COMMENT '购买时间',
     PRIMARY KEY (`id`) USING BTREE,
     KEY `uid` (`uid`,`mh_id`) USING BTREE,
     KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci ROW_FORMAT=COMPACT COMMENT='漫画购买记录';

ALTER TABLE `soutong`.`ks_mv_pay`
    ADD COLUMN `show_type` tinyint NULL DEFAULT 0 COMMENT '长短视频' AFTER `is_refund`,
ADD INDEX `ft_uid_stype`(`uid`, `show_type`) USING BTREE;


ALTER TABLE `ks_seed_post`
    ADD COLUMN `extract_code` varchar(255) NULL DEFAULT '' COMMENT '提取码';


ALTER TABLE ks_members
    ADD COLUMN `short_videos_count` int NULL DEFAULT 0 COMMENT '短视频作品数';


ALTER TABLE `ks_product`
    ADD COLUMN `vip_icon` varchar(255) NULL DEFAULT '' COMMENT 'VIP图标';

CREATE TABLE `ks_chat_log` (
   `id` int NOT NULL AUTO_INCREMENT,
   `window_id` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '此聊天窗，窗口id//如果是群聊这个是群id',
   `from_uuid` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '会话来源uuid',
   `from_avater` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
   `from_nickname` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
   `to_uuid` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '会话发送的 uuid 或 groupid',
   `to_avater` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
   `to_nickname` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '消息动作，这条消息是什么动作类型的消息',
   `content` varchar(1024) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
   `images` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
   `ext` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci COMMENT '扩展字段',
   `created_at` timestamp NULL DEFAULT NULL COMMENT '记录时间',
   PRIMARY KEY (`id`),
   KEY `from_uuid` (`from_uuid`),
   KEY `window_id` (`window_id`),
   KEY `to_id` (`to_uuid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci COMMENT='私信聊天日志';

CREATE TABLE `ks_chat_friends` (
    `id` int NOT NULL AUTO_INCREMENT,
    `uuid` char(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '',
    `to_uuid` char(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '',
    `update` timestamp NULL DEFAULT NULL,
    `window_id` char(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
    `count` int NOT NULL DEFAULT '0' COMMENT '未读消息计数 a->b a',
    `t_count` int NOT NULL COMMENT '未读消息计数 a->b b',
    PRIMARY KEY (`id`),
    KEY `uuid` (`uuid`,`to_uuid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci COMMENT='私信朋友列表';

ALTER TABLE `ks_cartoon_comment`
    ADD COLUMN `like_num` int NULL DEFAULT 0 COMMENT '点赞数';


CREATE TABLE `ks_mv_back_user` (
   `id` int NOT NULL AUTO_INCREMENT,
   `uid` int NOT NULL,
   `type` tinyint NOT NULL DEFAULT '0' COMMENT '0 默认上传',
   `note` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
   `created_at` datetime DEFAULT NULL,
   PRIMARY KEY (`id`),
   UNIQUE KEY `uid` (`uid`) USING BTREE,
   KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci COMMENT='上传黑名单用户';

CREATE TABLE ks_member_snapshot (
  id int NOT NULL AUTO_INCREMENT,
  uid int NOT NULL DEFAULT '0',
  data text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  status tinyint NOT NULL DEFAULT '0',
  created_at datetime DEFAULT NULL,
  updated_at datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY uid (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci COMMENT='用户收割快照表';