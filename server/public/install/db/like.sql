SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for la_admin
-- ----------------------------
DROP TABLE IF EXISTS `la_admin`;
CREATE TABLE `la_admin`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `root` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否超级管理员 0-否 1-是',
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '名称',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户头像',
  `account` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '账号',
  `password` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '密码',
  `login_time` int(10) NULL DEFAULT NULL COMMENT '最后登录时间',
  `login_ip` varchar(39) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '最后登录ip',
  `multipoint_login` tinyint(1) UNSIGNED NULL DEFAULT 1 COMMENT '是否支持多处登录：1-是；0-否；',
  `disable` tinyint(1) UNSIGNED NULL DEFAULT 0 COMMENT '是否禁用：0-否；1-是；',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '修改时间',
  `delete_time` int(10) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '管理员表';

-- ----------------------------
-- Table structure for la_admin_dept
-- ----------------------------
DROP TABLE IF EXISTS `la_admin_dept`;
CREATE TABLE `la_admin_dept`  (
  `admin_id` int(10) NOT NULL DEFAULT 0 COMMENT '管理员id',
  `dept_id` int(10) NOT NULL DEFAULT 0 COMMENT '部门id',
  PRIMARY KEY (`admin_id`, `dept_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '部门关联表';

-- ----------------------------
-- Table structure for la_admin_jobs
-- ----------------------------
DROP TABLE IF EXISTS `la_admin_jobs`;
CREATE TABLE `la_admin_jobs`  (
  `admin_id` int(10) NOT NULL COMMENT '管理员id',
  `jobs_id` int(10) NOT NULL COMMENT '岗位id',
  PRIMARY KEY (`admin_id`, `jobs_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '岗位关联表';

-- ----------------------------
-- Table structure for la_admin_role
-- ----------------------------
DROP TABLE IF EXISTS `la_admin_role`;
CREATE TABLE `la_admin_role`  (
  `admin_id` int(10) NOT NULL COMMENT '管理员id',
  `role_id` int(10) NOT NULL COMMENT '角色id',
  PRIMARY KEY (`admin_id`, `role_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '角色关联表';

-- ----------------------------
-- Table structure for la_admin_session
-- ----------------------------
DROP TABLE IF EXISTS `la_admin_session`;
CREATE TABLE `la_admin_session`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) UNSIGNED NOT NULL COMMENT '用户id',
  `terminal` tinyint(1) NOT NULL DEFAULT 1 COMMENT '客户端类型：1-pc管理后台 2-mobile手机管理后台',
  `token` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '令牌',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  `expire_time` int(10) NOT NULL COMMENT '到期时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `admin_id_client`(`admin_id`, `terminal`) USING BTREE COMMENT '一个用户在一个终端只有一个token',
  UNIQUE INDEX `token`(`token`) USING BTREE COMMENT 'token是唯一的'
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '管理员会话表';

-- ----------------------------
-- Table structure for la_article
-- ----------------------------
DROP TABLE IF EXISTS `la_article`;
CREATE TABLE `la_article`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '文章id',
  `cid` int(11) NOT NULL COMMENT '文章分类',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '文章标题',
  `desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '简介',
  `abstract` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '文章摘要',
  `image` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '文章图片',
  `author` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '作者',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '文章内容',
  `click_virtual` int(10) NULL DEFAULT 0 COMMENT '虚拟浏览量',
  `click_actual` int(11) NULL DEFAULT 0 COMMENT '实际浏览量',
  `is_show` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否显示:1-是.0-否',
  `sort` int(5) NULL DEFAULT 0 COMMENT '排序',
  `create_time` int(11) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) NULL DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '文章表';

-- ----------------------------
-- Records of la_article
-- ----------------------------
BEGIN;
INSERT INTO `la_article` VALUES (1, 3, '让生活更精致！五款居家好物推荐，实用性超高', '##好物推荐🔥', '随着当代生活节奏的忙碌，很多人在闲暇之余都想好好的享受生活。随着科技的发展，也出现了越来越多可以帮助我们提升幸福感，让生活变得更精致的产品，下面周周就给大家盘点五款居家必备的好物，都是实用性很高的产品，周周可以保证大家买了肯定会喜欢。', 'resource/image/adminapi/default/article01.png', '红花', '<p>拥有一台投影仪，闲暇时可以在家里直接看影院级别的大片，光是想想都觉得超级爽。市面上很多投影仪大几千，其实周周觉得没必要，选泰捷这款一千多的足够了，性价比非常高。</p><p>泰捷的专业度很高，在电视TV领域研发已经十年，有诸多专利和技术创新，荣获国内外多项技术奖项，拿下了腾讯创新工场投资，打造的泰捷视频TV端和泰捷电视盒子都获得了极高评价。</p><p>这款投影仪的分辨率在3000元内无敌，做到了真1080P高分辨率，也就是跟市场售价三千DLP投影仪一样的分辨率，真正做到了分毫毕现，像桌布的花纹、天空的云彩等，这些细节都清晰可见。</p><p>亮度方面，泰捷达到了850ANSI流明，同价位一般是200ANSI。这是因为泰捷为了提升亮度和LCD技术透射率低的问题，首创高功率LED灯源，让其亮度做到同价位最好。专业媒体也进行了多次对比，效果与3000元价位投影仪相当。</p><p>操作系统周周也很喜欢，完全不卡。泰捷作为资深音视频品牌，在系统优化方面有十年的研发经验，打造出的“零极”系统是业内公认效率最高、速度最快的系统，用户也评价它流畅度能一台顶三台，而且为了解决行业广告多这一痛点，系统内不植入任何广告。</p>', 1, 2, 1, 0, 1663317759, 1727070911, NULL), (2, 2, '埋葬UI设计师的坟墓不是内卷，而是免费模式', '', '本文从另外一个角度，聊聊作者对UI设计师职业发展前景的担忧，欢迎从事UI设计的同学来参与讨论，会有赠书哦', 'resource/image/adminapi/default/article02.jpeg', '小明', '<p><br></p><p style=\"text-align: justify;\">一个职业，卷，根本就没什么大不了的，尤其是成熟且收入高的职业，不卷才不符合事物发展的规律。何况 UI 设计师的人力市场到今天也和 5 年前一样，还是停留在大型菜鸡互啄的场面。远不能和医疗、证券、教师或者演艺练习生相提并论。</p><p style=\"text-align: justify;\">真正会让我对UI设计师发展前景觉得悲观的事情就只有一件 —— 国内的互联网产品免费机制。这也是一个我一直以来想讨论的话题，就在这次写一写。</p><p style=\"text-align: justify;\">国内互联网市场的发展，是一部浩瀚的 “免费经济” 发展史。虽然今天免费已经是深入国内民众骨髓的认知，但最早的中文互联网也是需要付费的，网游也都是要花钱的。</p><p style=\"text-align: justify;\">只是自有国情在此，付费确实阻碍了互联网行业的扩张和普及，一批创业家就开始通过免费的模式为用户提供服务，从而扩大了自己的产品覆盖面和普及程度。</p><p style=\"text-align: justify;\">印象最深的就是免费急先锋周鸿祎，和现在鲜少出现在公众视野不同，一零年前他是当之无愧的互联网教主，因为他开发出了符合中国国情的互联网产品 “打法”，让 360 的发展如日中天。</p><p style=\"text-align: justify;\">就是他在自传中提到：</p><p style=\"text-align: justify;\">只要是在互联网上每个人都需要的服务，我们就认为它是基础服务，基础服务一定是免费的，这样的话不会形成价值歧视。就是说，只要这种服务是每个人都一定要用的，我一定免费提供，而且是无条件免费。增值服务不是所有人都需要的，这个比例可能会相当低，它只是百分之几甚至更少比例的人需要，所以这种服务一定要收费……</p><p style=\"text-align: justify;\">这就是互联网的游戏规则，它决定了要想建立一个有效的商业模式，就一定要有海量的用户基数……</p>', 2, 4, 1, 0, 1663322854, 1727071178, NULL), (3, 1, '金山电池公布“沪广深市民绿色生活方式”调查结果', '', '60%以上受访者认为高质量的10分钟足以完成“自我充电”', 'resource/image/adminapi/default/article03.png', '中网资讯科技', '<p style=\"text-align: left;\"><strong>深圳，2021年10月22日）</strong>生活在一线城市的沪广深市民一向以效率见称，工作繁忙和快节奏的生活容易缺乏充足的休息。近日，一项针对沪广深市民绿色生活方式而展开的网络问卷调查引起了大家的注意。问卷的问题设定集中于市民对休息时间的看法，以及从对循环充电电池的使用方面了解其对绿色生活方式的态度。该调查采用随机抽样的模式，并对最终收集的1,500份有效问卷进行专业分析后发现，超过60%的受访者表示，在每天的工作时段能拥有10分钟高质量的休息时间，就可以高效“自我充电”。该调查结果反映出，在快节奏时代下，人们需要高质量的休息时间，也要学会利用高效率的休息方式和工具来应对快节奏的生活，以时刻保持“满电”状态。</p><p style=\"text-align: left;\">　　<strong>60%以上受访者认为高质量的10分钟足以完成“自我充电”</strong></p><p style=\"text-align: left;\">　　这次调查超过1,500人，主要聚焦18至85岁的沪广深市民，了解他们对于休息时间的观念及使用充电电池的习惯，结果发现：</p><p style=\"text-align: left;\">　　· 90%以上有工作受访者每天工作时间在7小时以上，平均工作时间为8小时，其中43%以上的受访者工作时间超过9小时</p><p style=\"text-align: left;\">　　· 70%受访者认为在工作期间拥有10分钟“自我充电”时间不是一件困难的事情</p><p style=\"text-align: left;\">　　· 60%受访者认为在工作期间有10分钟休息时间足以为自己快速充电</p><p style=\"text-align: left;\">　　临床心理学家黄咏诗女士在发布会上分享为自己快速充电的实用技巧，她表示：“事实上，只要选择正确的休息方法，10分钟也足以为自己充电。以喝咖啡为例，我们可以使用心灵休息法 ── 静观呼吸，慢慢感受咖啡的温度和气味，如果能配合着聆听流水或海洋的声音，能够有效放松大脑及心灵。”</p><p style=\"text-align: left;\">　　这次调查结果反映出沪广深市民的希望在繁忙的工作中适时停下来，抽出10分钟喝杯咖啡、聆听音乐或小睡片刻，为自己充电。金山电池全新推出的“绿再十分充”超快速充电器仅需10分钟就能充好电，喝一杯咖啡的时间既能完成“自我充电”，也满足设备使用的用电需求，为提升工作效率和放松身心注入新能量。</p><p style=\"text-align: left;\">　　<strong>金山电池推出10分钟超快电池充电器*绿再十分充，以创新科技为市场带来革新体验</strong></p><p style=\"text-align: left;\">　　该问卷同时从沪广深市民对循环充电电池的使用方面进行了调查，以了解其对绿色生活方式的态度：</p><p style=\"text-align: left;\">　　· 87%受访者目前没有使用充电电池，其中61%表示会考虑使用充电电池</p><p style=\"text-align: left;\">　　· 58%受访者过往曾使用过充电电池，却只有20%左右市民仍在使用</p><p style=\"text-align: left;\">　　· 60%左右受访者认为充电电池尚未被广泛使用，主要障碍来自于充电时间过长、缺乏相关教育</p><p style=\"text-align: left;\">　　· 90%以上受访者认为充电电池充满电需要1小时或更长的时间</p><p style=\"text-align: left;\">　　金山电池一直致力于为大众提供安全可靠的充电电池，并与消费者的需求和生活方式一起演变及进步。今天，金山电池宣布推出10分钟超快电池充电器*绿再十分充，只需10分钟*即可将4粒绿再十分充充电电池充好电，充电速度比其他品牌提升3倍**。充电器的LED灯可以显示每粒电池的充电状态和模式，并提示用户是否错误插入已损坏电池或一次性电池。尽管其体型小巧，却具备多项创新科技 ，如拥有独特的充电算法以优化充电电流，并能根据各个电池类型、状况和温度用最短的时间为充电电池充好电;绿再十分充内置横流扇，有效防止电池温度过热和提供低噪音的充电环境等。<br></p>', 11, 4, 1, 0, 1663322665, 1727071154, NULL);
COMMIT;

-- ----------------------------
-- Table structure for la_article_cate
-- ----------------------------
DROP TABLE IF EXISTS `la_article_cate`;
CREATE TABLE `la_article_cate`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '文章分类id',
  `name` varchar(90) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '分类名称',
  `sort` int(11) NULL DEFAULT 0 COMMENT '排序',
  `is_show` tinyint(1) NULL DEFAULT 1 COMMENT '是否显示:1-是;0-否',
  `create_time` int(10) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '文章分类表';

-- ----------------------------
-- Records of la_article_cate
-- ----------------------------
BEGIN;
INSERT INTO `la_article_cate` VALUES (1, '科技', 0, 1, 1663317280, 1663317280, NULL), (2, '生活', 0, 1, 1663317280, 1663321464, NULL), (3, '好物', 0, 1, 1727070858, 1727070858, NULL);
COMMIT;

-- ----------------------------
-- Table structure for la_article_collect
-- ----------------------------
DROP TABLE IF EXISTS `la_article_collect`;
CREATE TABLE `la_article_collect`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `article_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '文章ID',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '收藏状态 0-未收藏 1-已收藏',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `delete_time` int(10) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '文章收藏表';

-- ----------------------------
-- Table structure for la_config
-- ----------------------------
DROP TABLE IF EXISTS `la_config`;
CREATE TABLE `la_config`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '类型',
  `name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '名称',
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '值',
  `create_time` int(10) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '配置表';

-- ----------------------------
-- Table structure for la_decorate_page
-- ----------------------------
DROP TABLE IF EXISTS `la_decorate_page`;
CREATE TABLE `la_decorate_page`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `type` tinyint(2) UNSIGNED NOT NULL DEFAULT 10 COMMENT '页面类型 1=商城首页, 2=个人中心, 3=客服设置 4-PC首页',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '页面名称',
  `data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '页面数据',
  `meta` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '页面设置',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '装修页面配置表';

-- ----------------------------
-- Records of la_decorate_page
-- ----------------------------
BEGIN;
INSERT INTO `la_decorate_page` VALUES (1, 1, '商城首页', '[{\"title\":\"搜索\",\"name\":\"search\",\"disabled\":1,\"content\":{},\"styles\":{}},{\"title\":\"首页轮播图\",\"name\":\"banner\",\"content\":{\"enabled\":1,\"data\":[{\"image\":\"/resource/image/adminapi/default/banner001.png\",\"name\":\"\",\"link\":{\"id\":6,\"name\":\"来自瓷器的爱\",\"path\":\"/pages/news_detail/news_detail\",\"query\":{\"id\":6},\"type\":\"article\"},\"is_show\":\"1\",\"bg\":\"/resource/image/adminapi/default/banner001_bg.png\"},{\"image\":\"/resource/image/adminapi/default/banner002.png\",\"name\":\"\",\"link\":{\"id\":3,\"name\":\"金山电池公布“沪广深市民绿色生活方式”调查结果\",\"path\":\"/pages/news_detail/news_detail\",\"query\":{\"id\":3},\"type\":\"article\"},\"is_show\":\"1\",\"bg\":\"/resource/image/adminapi/default/banner002_bg.png\"},{\"is_show\":\"1\",\"image\":\"/resource/image/adminapi/default/banner003.png\",\"name\":\"\",\"link\":{\"id\":1,\"name\":\"让生活更精致！五款居家好物推荐，实用性超高\",\"path\":\"/pages/news_detail/news_detail\",\"query\":{\"id\":1},\"type\":\"article\"},\"bg\":\"/resource/image/adminapi/default/banner003_bg.png\"}],\"style\":1,\"bg_style\":1},\"styles\":{}},{\"title\":\"导航菜单\",\"name\":\"nav\",\"content\":{\"enabled\":1,\"data\":[{\"image\":\"/resource/image/adminapi/default/nav01.png\",\"name\":\"资讯中心\",\"link\":{\"path\":\"/pages/news/news\",\"name\":\"文章资讯\",\"type\":\"shop\",\"canTab\":true},\"is_show\":\"1\"},{\"image\":\"/resource/image/adminapi/default/nav03.png\",\"name\":\"个人设置\",\"link\":{\"path\":\"/pages/user_set/user_set\",\"name\":\"个人设置\",\"type\":\"shop\"},\"is_show\":\"1\"},{\"image\":\"/resource/image/adminapi/default/nav02.png\",\"name\":\"我的收藏\",\"link\":{\"path\":\"/pages/collection/collection\",\"name\":\"我的收藏\",\"type\":\"shop\"},\"is_show\":\"1\"},{\"image\":\"/resource/image/adminapi/default/nav05.png\",\"name\":\"关于我们\",\"link\":{\"path\":\"/pages/as_us/as_us\",\"name\":\"关于我们\",\"type\":\"shop\"},\"is_show\":\"1\"},{\"image\":\"/resource/image/adminapi/default/nav04.png\",\"name\":\"联系客服\",\"link\":{\"path\":\"/pages/customer_service/customer_service\",\"name\":\"联系客服\",\"type\":\"shop\"},\"is_show\":\"1\"}],\"style\":2,\"per_line\":5,\"show_line\":2},\"styles\":{}},{\"title\":\"首页中部轮播图\",\"name\":\"middle-banner\",\"content\":{\"enabled\":1,\"data\":[{\"is_show\":\"1\",\"image\":\"/resource/image/adminapi/default/index_ad01.png\",\"name\":\"\",\"link\":{\"path\":\"/pages/agreement/agreement\",\"name\":\"隐私政策\",\"query\":{\"type\":\"privacy\"},\"type\":\"shop\"}}]},\"styles\":{}},{\"id\":\"l84almsk2uhyf\",\"title\":\"资讯\",\"name\":\"news\",\"disabled\":1,\"content\":{},\"styles\":{}}]', '[{\"title\":\"页面设置\",\"name\":\"page-meta\",\"content\":{\"title\":\"首页\",\"bg_type\":\"2\",\"bg_color\":\"#2F80ED\",\"bg_image\":\"/resource/image/adminapi/default/page_meta_bg01.png\",\"text_color\":\"2\",\"title_type\":\"2\",\"title_img\":\"/resource/image/adminapi/default/page_mate_title.png\"},\"styles\":{}}]', 1661757188, 1710989700), (2, 2, '个人中心', '[{\"title\":\"用户信息\",\"name\":\"user-info\",\"disabled\":1,\"content\":{},\"styles\":{}},{\"title\":\"我的服务\",\"name\":\"my-service\",\"content\":{\"style\":1,\"title\":\"我的服务\",\"data\":[{\"image\":\"/resource/image/adminapi/default/user_collect.png\",\"name\":\"我的收藏\",\"link\":{\"path\":\"/pages/collection/collection\",\"name\":\"我的收藏\",\"type\":\"shop\"},\"is_show\":\"1\"},{\"image\":\"/resource/image/adminapi/default/user_setting.png\",\"name\":\"个人设置\",\"link\":{\"path\":\"/pages/user_set/user_set\",\"name\":\"个人设置\",\"type\":\"shop\"},\"is_show\":\"1\"},{\"image\":\"/resource/image/adminapi/default/user_kefu.png\",\"name\":\"联系客服\",\"link\":{\"path\":\"/pages/customer_service/customer_service\",\"name\":\"联系客服\",\"type\":\"shop\"},\"is_show\":\"1\"},{\"image\":\"/resource/image/adminapi/default/wallet.png\",\"name\":\"我的钱包\",\"link\":{\"path\":\"/packages/pages/user_wallet/user_wallet\",\"name\":\"我的钱包\",\"type\":\"shop\"},\"is_show\":\"1\"}],\"enabled\":1},\"styles\":{}},{\"title\":\"个人中心广告图\",\"name\":\"user-banner\",\"content\":{\"enabled\":1,\"data\":[{\"image\":\"/resource/image/adminapi/default/user_ad01.png\",\"name\":\"\",\"link\":{\"path\":\"/pages/customer_service/customer_service\",\"name\":\"联系客服\",\"type\":\"shop\"},\"is_show\":\"1\"},{\"image\":\"/resource/image/adminapi/default/user_ad02.png\",\"name\":\"\",\"link\":{\"path\":\"/pages/customer_service/customer_service\",\"name\":\"联系客服\",\"type\":\"shop\"},\"is_show\":\"1\"}]},\"styles\":{}}]', '[{\"title\":\"页面设置\",\"name\":\"page-meta\",\"content\":{\"title\":\"个人中心\",\"bg_type\":\"1\",\"bg_color\":\"#2F80ED\",\"bg_image\":\"\",\"text_color\":\"1\",\"title_type\":\"2\",\"title_img\":\"/resource/image/adminapi/default/page_mate_title.png\"},\"styles\":{}}]', 1661757188, 1710933097), (3, 3, '客服设置', '[{\"title\":\"客服设置\",\"name\":\"customer-service\",\"content\":{\"title\":\"添加客服二维码\",\"time\":\"早上 9:30 - 19:00\",\"mobile\":\"1888888888\",\"qrcode\":\"/resource/image/adminapi/default/kefu01.png\",\"remark\":\"长按添加客服或拨打客服热线\"},\"styles\":{}}]', '', 1661757188, 1710929953), (4, 4, 'PC设置', '[{\"id\":\"lajcn8d0hzhed\",\"title\":\"首页轮播图\",\"name\":\"pc-banner\",\"content\":{\"enabled\":1,\"data\":[{\"image\":\"/resource/image/adminapi/default/banner003.png\",\"name\":\"\",\"link\":{\"path\":\"/pages/news/news\",\"name\":\"文章资讯\",\"type\":\"shop\"}},{\"image\":\"/resource/image/adminapi/default/banner002.png\",\"name\":\"\",\"link\":{\"path\":\"/pages/collection/collection\",\"name\":\"我的收藏\",\"type\":\"shop\"}},{\"image\":\"/resource/image/adminapi/default/banner001.png\",\"name\":\"\",\"link\":{}}]},\"styles\":{\"position\":\"absolute\",\"left\":\"40\",\"top\":\"75px\",\"width\":\"750px\",\"height\":\"340px\"}}]', '', 1661757188, 1710990175), (5, 5, '系统风格', '{\"themeColorId\":3,\"topTextColor\":\"white\",\"navigationBarColor\":\"#A74BFD\",\"themeColor1\":\"#A74BFD\",\"themeColor2\":\"#CB60FF\",\"buttonColor\":\"white\"}', '', 1710410915, 1710990415);
COMMIT;

-- ----------------------------
-- Table structure for la_decorate_tabbar
-- ----------------------------
DROP TABLE IF EXISTS `la_decorate_tabbar`;
CREATE TABLE `la_decorate_tabbar`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '导航名称',
  `selected` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '未选图标',
  `unselected` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '已选图标',
  `link` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '链接地址',
  `is_show` tinyint(255) UNSIGNED NOT NULL DEFAULT 1 COMMENT '显示状态',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '装修底部导航表';

-- ----------------------------
-- Records of la_decorate_tabbar
-- ----------------------------
BEGIN;
INSERT INTO `la_decorate_tabbar` VALUES (1, '首页', 'resource/image/adminapi/default/tabbar_home_sel.png', 'resource/image/adminapi/default/tabbar_home.png', '{\"path\":\"/pages/index/index\",\"name\":\"商城首页\",\"type\":\"shop\"}', 1, 1662688157, 1662688157), (2, '资讯', 'resource/image/adminapi/default/tabbar_text_sel.png', 'resource/image/adminapi/default/tabbar_text.png', '{\"path\":\"/pages/news/news\",\"name\":\"文章资讯\",\"type\":\"shop\",\"canTab\":\"1\"}', 1, 1662688157, 1662688157), (3, '我的', 'resource/image/adminapi/default/tabbar_me_sel.png', 'resource/image/adminapi/default/tabbar_me.png', '{\"path\":\"/pages/user/user\",\"name\":\"个人中心\",\"type\":\"shop\",\"canTab\":\"1\"}', 1, 1662688157, 1662688157);
COMMIT;

-- ----------------------------
-- Table structure for la_dept
-- ----------------------------
DROP TABLE IF EXISTS `la_dept`;
CREATE TABLE `la_dept`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '部门名称',
  `pid` bigint(20) NOT NULL DEFAULT 0 COMMENT '上级部门id',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `leader` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '负责人',
  `mobile` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '联系电话',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '部门状态（0停用 1正常）',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '修改时间',
  `delete_time` int(10) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '部门表';

-- ----------------------------
-- Records of la_dept
-- ----------------------------
BEGIN;
INSERT INTO `la_dept` VALUES (1, '公司', 0, 0, 'boss', '12345698745', 1, 1650592684, 1653640368, NULL);
COMMIT;

-- ----------------------------
-- Table structure for la_dev_crontab
-- ----------------------------
DROP TABLE IF EXISTS `la_dev_crontab`;
CREATE TABLE `la_dev_crontab`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '定时任务名称',
  `type` tinyint(1) NOT NULL COMMENT '类型 1-定时任务',
  `system` tinyint(4) NULL DEFAULT 0 COMMENT '是否系统任务 0-否 1-是',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '备注',
  `command` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '命令内容',
  `params` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '参数',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态 1-运行 2-停止 3-错误',
  `expression` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '运行规则',
  `error` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '运行失败原因',
  `last_time` int(11) NULL DEFAULT NULL COMMENT '最后执行时间',
  `time` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '0' COMMENT '实时执行时长',
  `max_time` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '0' COMMENT '最大执行时长',
  `create_time` int(10) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '计划任务表';

-- ----------------------------
-- Table structure for la_dev_pay_config
-- ----------------------------
DROP TABLE IF EXISTS `la_dev_pay_config`;
CREATE TABLE `la_dev_pay_config`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '模版名称',
  `pay_way` tinyint(1) NOT NULL COMMENT '支付方式:1-余额支付;2-微信支付;3-支付宝支付;',
  `config` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '对应支付配置(json字符串)',
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '图标',
  `sort` int(5) NULL DEFAULT NULL COMMENT '排序',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '备注',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '支付配置表';

-- ----------------------------
-- Records of la_dev_pay_config
-- ----------------------------
BEGIN;
INSERT INTO `la_dev_pay_config` VALUES (1, '余额支付', 1, '', '/resource/image/adminapi/default/balance_pay.png', 128, '余额支付备注'), (2, '微信支付', 2, '{\"interface_version\":\"v3\",\"merchant_type\":\"ordinary_merchant\",\"mch_id\":\"\",\"pay_sign_key\":\"\",\"apiclient_cert\":\"\",\"apiclient_key\":\"\"}', '/resource/image/adminapi/default/wechat_pay.png', 123, '微信支付备注'), (3, '支付宝支付', 3, '{\"mode\":\"normal_mode\",\"merchant_type\":\"ordinary_merchant\",\"app_id\":\"\",\"private_key\":\"\",\"ali_public_key\":\"\"}', '/resource/image/adminapi/default/ali_pay.png', 123, '支付宝支付');
COMMIT;

-- ----------------------------
-- Table structure for la_dev_pay_way
-- ----------------------------
DROP TABLE IF EXISTS `la_dev_pay_way`;
CREATE TABLE `la_dev_pay_way`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'id',
  `pay_config_id` int(11) NOT NULL COMMENT '支付配置ID',
  `scene` tinyint(1) NOT NULL COMMENT '场景:1-微信小程序;2-微信公众号;3-H5;4-PC;5-APP;',
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否默认支付:0-否;1-是;',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态:0-关闭;1-开启;',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 8 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '支付方式表';

-- ----------------------------
-- Records of la_dev_pay_way
-- ----------------------------
BEGIN;
INSERT INTO `la_dev_pay_way` VALUES (1, 1, 1, 0, 1), (2, 2, 1, 1, 1), (3, 1, 2, 0, 1), (4, 2, 2, 1, 1), (5, 1, 3, 0, 1), (6, 2, 3, 1, 1), (7, 3, 3, 0, 1);
COMMIT;

-- ----------------------------
-- Table structure for la_dict_data
-- ----------------------------
DROP TABLE IF EXISTS `la_dict_data`;
CREATE TABLE `la_dict_data`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '数据名称',
  `value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '数据值',
  `type_id` int(11) NOT NULL COMMENT '字典类型id',
  `type_value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '字典类型',
  `sort` int(10) NULL DEFAULT 0 COMMENT '排序值',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态 0-停用 1-正常',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '备注',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '修改时间',
  `delete_time` int(10) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 14 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '字典数据表';

-- ----------------------------
-- Records of la_dict_data
-- ----------------------------
BEGIN;
INSERT INTO `la_dict_data` VALUES (1, '隐藏', '0', 1, 'show_status', 0, 1, '', 1656381543, 1656381543, NULL), (2, '显示', '1', 1, 'show_status', 0, 1, '', 1656381550, 1656381550, NULL), (3, '进行中', '0', 2, 'business_status', 0, 1, '', 1656381410, 1656381410, NULL), (4, '成功', '1', 2, 'business_status', 0, 1, '', 1656381437, 1656381437, NULL), (5, '失败', '2', 2, 'business_status', 0, 1, '', 1656381449, 1656381449, NULL), (6, '待处理', '0', 3, 'event_status', 0, 1, '', 1656381212, 1656381212, NULL), (7, '已处理', '1', 3, 'event_status', 0, 1, '', 1656381315, 1656381315, NULL), (8, '拒绝处理', '2', 3, 'event_status', 0, 1, '', 1656381331, 1656381331, NULL), (9, '禁用', '1', 4, 'system_disable', 0, 1, '', 1656312030, 1656312030, NULL), (10, '正常', '0', 4, 'system_disable', 0, 1, '', 1656312040, 1656312040, NULL), (11, '未知', '0', 5, 'sex', 0, 1, '', 1656062988, 1656062988, NULL), (12, '男', '1', 5, 'sex', 0, 1, '', 1656062999, 1656062999, NULL), (13, '女', '2', 5, 'sex', 0, 1, '', 1656063009, 1656063009, NULL);
COMMIT;

-- ----------------------------
-- Table structure for la_dict_type
-- ----------------------------
DROP TABLE IF EXISTS `la_dict_type`;
CREATE TABLE `la_dict_type`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '字典名称',
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '字典类型名称',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态 0-停用 1-正常',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '备注',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '修改时间',
  `delete_time` int(10) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '字典类型表';

-- ----------------------------
-- Records of la_dict_type
-- ----------------------------
BEGIN;
INSERT INTO `la_dict_type` VALUES (1, '显示状态', 'show_status', 1, '', 1656381520, 1656381520, NULL), (2, '业务状态', 'business_status', 1, '', 1656381393, 1656381393, NULL), (3, '事件状态', 'event_status', 1, '', 1656381075, 1656381075, NULL), (4, '禁用状态', 'system_disable', 1, '', 1656311838, 1656311838, NULL), (5, '用户性别', 'sex', 1, '', 1656062946, 1656380925, NULL), (6, '系统配置', 'system_config', 1, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL);
COMMIT;

-- ----------------------------
-- Table structure for la_file
-- ----------------------------
DROP TABLE IF EXISTS `la_file`;
CREATE TABLE `la_file`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `cid` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '类目ID',
  `source_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '上传者id',
  `source` tinyint(1) NOT NULL DEFAULT 0 COMMENT '来源类型[0-后台,1-用户]',
  `type` tinyint(2) UNSIGNED NOT NULL DEFAULT 10 COMMENT '类型[10=图片, 20=视频]',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '文件名称',
  `uri` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '文件路径',
  `create_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '文件表';

-- ----------------------------
-- Table structure for la_file_cate
-- ----------------------------
DROP TABLE IF EXISTS `la_file_cate`;
CREATE TABLE `la_file_cate`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `pid` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '父级ID',
  `type` tinyint(2) UNSIGNED NOT NULL DEFAULT 10 COMMENT '类型[10=图片，20=视频，30=文件]',
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  `create_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '文件分类表';

-- ----------------------------
-- Table structure for la_generate_column
-- ----------------------------
DROP TABLE IF EXISTS `la_generate_column`;
CREATE TABLE `la_generate_column`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `table_id` int(11) NOT NULL DEFAULT 0 COMMENT '表id',
  `column_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '字段名称',
  `column_comment` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '字段描述',
  `column_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '字段类型',
  `is_required` tinyint(1) NULL DEFAULT 0 COMMENT '是否必填 0-非必填 1-必填',
  `is_pk` tinyint(1) NULL DEFAULT 0 COMMENT '是否为主键 0-不是 1-是',
  `is_insert` tinyint(1) NULL DEFAULT 0 COMMENT '是否为插入字段 0-不是 1-是',
  `is_update` tinyint(1) NULL DEFAULT 0 COMMENT '是否为更新字段 0-不是 1-是',
  `is_lists` tinyint(1) NULL DEFAULT 0 COMMENT '是否为列表字段 0-不是 1-是',
  `is_query` tinyint(1) NULL DEFAULT 0 COMMENT '是否为查询字段 0-不是 1-是',
  `query_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '=' COMMENT '查询类型',
  `view_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'input' COMMENT '显示类型',
  `dict_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '字典类型',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '代码生成表字段信息表';

-- ----------------------------
-- Table structure for la_generate_table
-- ----------------------------
DROP TABLE IF EXISTS `la_generate_table`;
CREATE TABLE `la_generate_table`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `table_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '表名称',
  `table_comment` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '表描述',
  `template_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '模板类型 0-单表(curd) 1-树表(curd)',
  `author` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '作者',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '备注',
  `generate_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '生成方式  0-压缩包下载 1-生成到模块',
  `module_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '模块名',
  `class_dir` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '类目录名',
  `class_comment` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '类描述',
  `admin_id` int(11) NULL DEFAULT 0 COMMENT '管理员id',
  `menu` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '菜单配置',
  `delete` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '删除配置',
  `tree` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '树表配置',
  `relations` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '关联配置',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '代码生成表信息表';

-- ----------------------------
-- Table structure for la_hot_search
-- ----------------------------
DROP TABLE IF EXISTS `la_hot_search`;
CREATE TABLE `la_hot_search`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '关键词',
  `sort` smallint(5) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序号',
  `create_time` int(10) NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '热门搜索表';

-- ----------------------------
-- Table structure for la_jobs
-- ----------------------------
DROP TABLE IF EXISTS `la_jobs`;
CREATE TABLE `la_jobs`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '岗位名称',
  `code` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '岗位编码',
  `sort` int(11) NULL DEFAULT 0 COMMENT '显示顺序',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态（0停用 1正常）',
  `remark` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '修改时间',
  `delete_time` int(10) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '岗位表';

-- ----------------------------
-- Table structure for la_notice_record
-- ----------------------------
DROP TABLE IF EXISTS `la_notice_record`;
CREATE TABLE `la_notice_record`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户id',
  `title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '标题',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '内容',
  `scene_id` int(10) UNSIGNED NULL DEFAULT 0 COMMENT '场景',
  `read` tinyint(1) NULL DEFAULT 0 COMMENT '已读状态;0-未读,1-已读',
  `recipient` tinyint(1) NULL DEFAULT 0 COMMENT '通知接收对象类型;1-会员;2-商家;3-平台;4-游客(未注册用户)',
  `send_type` tinyint(1) NULL DEFAULT 0 COMMENT '通知发送类型 1-系统通知 2-短信通知 3-微信模板 4-微信小程序',
  `notice_type` tinyint(1) NULL DEFAULT NULL COMMENT '通知类型 1-业务通知 2-验证码',
  `extra` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '其他',
  `create_time` int(10) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '通知记录表';

-- ----------------------------
-- Table structure for la_notice_setting
-- ----------------------------
DROP TABLE IF EXISTS `la_notice_setting`;
CREATE TABLE `la_notice_setting`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `scene_id` int(10) NOT NULL COMMENT '场景id',
  `scene_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '场景名称',
  `scene_desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '场景描述',
  `recipient` tinyint(1) NOT NULL DEFAULT 1 COMMENT '接收者 1-用户 2-平台',
  `type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '通知类型: 1-业务通知 2-验证码',
  `system_notice` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '系统通知设置',
  `sms_notice` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '短信通知设置',
  `oa_notice` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '公众号通知设置',
  `mnp_notice` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '小程序通知设置',
  `support` char(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '支持的发送类型 1-系统通知 2-短信通知 3-微信模板消息 4-小程序提醒',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '通知设置表';

-- ----------------------------
-- Records of la_notice_setting
-- ----------------------------
BEGIN;
INSERT INTO `la_notice_setting` VALUES (1, 101, '登录验证码', '用户手机号码登录时发送', 1, 2, '{\"type\":\"system\",\"title\":\"\",\"content\":\"\",\"status\":\"0\",\"is_show\":\"\",\"tips\":[\"可选变量 验证码:code\"]}', '{\"type\":\"sms\",\"template_id\":\"SMS_123456\",\"content\":\"您正在登录，验证码${code}，切勿将验证码泄露于他人，本条验证码有效期5分钟。\",\"status\":\"1\",\"is_show\":\"1\"}', '{\"type\":\"oa\",\"template_id\":\"\",\"template_sn\":\"\",\"name\":\"\",\"first\":\"\",\"remark\":\"\",\"tpl\":[],\"status\":\"0\",\"is_show\":\"\",\"tips\":[\"可选变量 验证码:code\",\"配置路径：小程序后台 > 功能 > 订阅消息\"]}', '{\"type\":\"mnp\",\"template_id\":\"\",\"template_sn\":\"\",\"name\":\"\",\"tpl\":[],\"status\":\"0\",\"is_show\":\"\",\"tips\":[\"可选变量 验证码:code\",\"配置路径：小程序后台 > 功能 > 订阅消息\"]}', '2', NULL), (2, 102, '绑定手机验证码', '用户绑定手机号码时发送', 1, 2, '{\"type\":\"system\",\"title\":\"\",\"content\":\"\",\"status\":\"0\",\"is_show\":\"\"}', '{\"type\":\"sms\",\"template_id\":\"SMS_123456\",\"content\":\"您正在绑定手机号，验证码${code}，切勿将验证码泄露于他人，本条验证码有效期5分钟。\",\"status\":\"1\",\"is_show\":\"1\"}', '{\"type\":\"oa\",\"template_id\":\"\",\"template_sn\":\"\",\"name\":\"\",\"first\":\"\",\"remark\":\"\",\"tpl\":[],\"status\":\"0\",\"is_show\":\"\"}', '{\"type\":\"mnp\",\"template_id\":\"\",\"template_sn\":\"\",\"name\":\"\",\"tpl\":[],\"status\":\"0\",\"is_show\":\"\"}', '2', NULL), (3, 103, '变更手机验证码', '用户变更手机号码时发送', 1, 2, '{\"type\":\"system\",\"title\":\"\",\"content\":\"\",\"status\":\"0\",\"is_show\":\"\",\"tips\":[\"可选变量 验证码:code\"]}', '{\"type\":\"sms\",\"template_id\":\"SMS_123456\",\"content\":\"您正在变更手机号，验证码${code}，切勿将验证码泄露于他人，本条验证码有效期5分钟。\",\"status\":\"1\",\"is_show\":\"1\"}', '{\"type\":\"oa\",\"template_id\":\"\",\"template_sn\":\"\",\"name\":\"\",\"first\":\"\",\"remark\":\"\",\"tpl\":[],\"status\":\"0\",\"is_show\":\"\",\"tips\":[\"可选变量 验证码:code\",\"配置路径：小程序后台 > 功能 > 订阅消息\"]}', '{\"type\":\"mnp\",\"template_id\":\"\",\"template_sn\":\"\",\"name\":\"\",\"tpl\":[],\"status\":\"0\",\"is_show\":\"\",\"tips\":[\"可选变量 验证码:code\",\"配置路径：小程序后台 > 功能 > 订阅消息\"]}', '2', NULL), (4, 104, '找回登录密码验证码', '用户找回登录密码号码时发送', 1, 2, '{\"type\":\"system\",\"title\":\"\",\"content\":\"\",\"status\":\"0\",\"is_show\":\"\",\"tips\":[\"可选变量 验证码:code\"]}', '{\"type\":\"sms\",\"template_id\":\"SMS_123456\",\"content\":\"您正在找回登录密码，验证码${code}，切勿将验证码泄露于他人，本条验证码有效期5分钟。\",\"status\":\"1\",\"is_show\":\"1\"}', '{\"type\":\"oa\",\"template_id\":\"\",\"template_sn\":\"\",\"name\":\"\",\"first\":\"\",\"remark\":\"\",\"tpl\":[],\"status\":\"0\",\"is_show\":\"\",\"tips\":[\"可选变量 验证码:code\",\"配置路径：小程序后台 > 功能 > 订阅消息\"]}', '{\"type\":\"mnp\",\"template_id\":\"\",\"template_sn\":\"\",\"name\":\"\",\"tpl\":[],\"status\":\"0\",\"is_show\":\"\",\"tips\":[\"可选变量 验证码:code\",\"配置路径：小程序后台 > 功能 > 订阅消息\"]}', '2', NULL);
COMMIT;

-- ----------------------------
-- Table structure for la_official_account_reply
-- ----------------------------
DROP TABLE IF EXISTS `la_official_account_reply`;
CREATE TABLE `la_official_account_reply`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '规则名称',
  `keyword` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '关键词',
  `reply_type` tinyint(1) NOT NULL COMMENT '回复类型 1-关注回复 2-关键字回复 3-默认回复',
  `matching_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '匹配方式：1-全匹配；2-模糊匹配',
  `content_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '内容类型：1-文本',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '回复内容',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '启动状态：1-启动；0-关闭',
  `sort` int(11) UNSIGNED NOT NULL DEFAULT 50 COMMENT '排序',
  `create_time` int(10) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '公众号消息回调表';

-- ----------------------------
-- Table structure for la_operation_log
-- ----------------------------
DROP TABLE IF EXISTS `la_operation_log`;
CREATE TABLE `la_operation_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `admin_id` int(11) NOT NULL COMMENT '管理员ID',
  `admin_name` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '管理员名称',
  `account` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '管理员账号',
  `action` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '操作名称',
  `type` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '请求方式',
  `url` varchar(600) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '访问链接',
  `params` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '请求数据',
  `result` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '请求结果',
  `ip` varchar(39) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'ip地址',
  `create_time` int(10) NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '系统日志表';

-- ----------------------------
-- Table structure for la_recharge_order
-- ----------------------------
DROP TABLE IF EXISTS `la_recharge_order`;
CREATE TABLE `la_recharge_order`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `sn` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '订单编号',
  `user_id` int(11) NOT NULL COMMENT '用户id',
  `pay_sn` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '支付编号-冗余字段，针对微信同一主体不同客户端支付需用不同订单号预留。',
  `pay_way` tinyint(2) NOT NULL DEFAULT 2 COMMENT '支付方式 2-微信支付 3-支付宝支付',
  `pay_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '支付状态：0-待支付；1-已支付',
  `pay_time` int(10) NULL DEFAULT NULL COMMENT '支付时间',
  `order_amount` decimal(10, 2) NOT NULL COMMENT '充值金额',
  `order_terminal` tinyint(1) NULL DEFAULT 1 COMMENT '终端 1-PC端 2-移动端 3-小程序',
  `transaction_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '第三方平台交易流水号',
  `refund_status` tinyint(1) NULL DEFAULT 0 COMMENT '退款状态 0-未退款 1-已退款',
  `refund_transaction_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '退款交易流水号',
  `create_time` int(10) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '充值订单表';

-- ----------------------------
-- Table structure for la_refund_log
-- ----------------------------
DROP TABLE IF EXISTS `la_refund_log`;
CREATE TABLE `la_refund_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `sn` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '编号',
  `record_id` int(11) NOT NULL COMMENT '退款记录id',
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '关联用户',
  `handle_id` int(11) NOT NULL DEFAULT 0 COMMENT '处理人id（管理员id）',
  `order_amount` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '订单总的应付款金额，冗余字段',
  `refund_amount` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '本次退款金额',
  `refund_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '退款状态 0-退款中 1-退款成功 2-退款失败',
  `refund_msg` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '退款信息',
  `create_time` int(10) UNSIGNED NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '退款日志表';

-- ----------------------------
-- Table structure for la_refund_record
-- ----------------------------
DROP TABLE IF EXISTS `la_refund_record`;
CREATE TABLE `la_refund_record`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `sn` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '退款编号',
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '关联用户',
  `order_id` int(11) NOT NULL DEFAULT 0 COMMENT '来源订单id',
  `order_sn` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '来源单号',
  `order_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'order' COMMENT '订单来源 order-商品订单 recharge-充值订单',
  `order_amount` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '订单总的应付款金额，冗余字段',
  `refund_amount` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '本次退款金额',
  `transaction_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '第三方平台交易流水号',
  `refund_way` tinyint(1) NOT NULL DEFAULT 1 COMMENT '退款方式 1-线上退款 2-线下退款',
  `refund_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '退款类型 1-后台退款',
  `refund_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '退款状态 0-退款中 1-退款成功 2-退款失败',
  `create_time` int(10) UNSIGNED NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '退款记录表';

-- ----------------------------
-- Table structure for la_sms_log
-- ----------------------------
DROP TABLE IF EXISTS `la_sms_log`;
CREATE TABLE `la_sms_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `scene_id` int(11) NOT NULL COMMENT '场景id',
  `mobile` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '手机号码',
  `content` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '发送内容',
  `code` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '发送关键字（注册、找回密码）',
  `is_verify` tinyint(1) NULL DEFAULT 0 COMMENT '是否已验证；0-否；1-是',
  `check_num` int(5) NULL DEFAULT 0 COMMENT '验证次数',
  `send_status` tinyint(1) NOT NULL COMMENT '发送状态：0-发送中；1-发送成功；2-发送失败',
  `send_time` int(10) NOT NULL COMMENT '发送时间',
  `results` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '短信结果',
  `create_time` int(10) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '短信记录表';

-- ----------------------------
-- Table structure for la_system_menu
-- ----------------------------
DROP TABLE IF EXISTS `la_system_menu`;
CREATE TABLE `la_system_menu`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `pid` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '上级菜单',
  `type` char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '权限类型: M=目录，C=菜单，A=按钮',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '菜单名称',
  `icon` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '菜单图标',
  `sort` smallint(5) UNSIGNED NOT NULL DEFAULT 0 COMMENT '菜单排序',
  `perms` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '权限标识',
  `paths` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '路由地址',
  `component` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '前端组件',
  `selected` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '选中路径',
  `params` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '路由参数',
  `is_cache` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否缓存: 0=否, 1=是',
  `is_show` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否显示: 0=否, 1=是',
  `is_disable` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否禁用: 0=否, 1=是',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 177 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '系统菜单表';

-- ----------------------------
-- Records of la_system_menu
-- ----------------------------
BEGIN;
INSERT INTO `la_system_menu` VALUES (4, 0, 'M', '权限管理', 'el-icon-Lock', 300, '', 'permission', '', '', '', 0, 1, 0, 1656664556, 1710472802), (5, 0, 'C', '工作台', 'el-icon-Monitor', 1000, 'workbench/index', 'workbench', 'workbench/index', '', '', 0, 1, 0, 1656664793, 1664354981), (6, 4, 'C', '菜单', 'el-icon-Operation', 100, 'auth.menu/lists', 'menu', 'permission/menu/index', '', '', 1, 1, 0, 1656664960, 1710472994), (7, 4, 'C', '管理员', 'local-icon-shouyiren', 80, 'auth.admin/lists', 'admin', 'permission/admin/index', '', '', 0, 1, 0, 1656901567, 1710473013), (8, 4, 'C', '角色', 'el-icon-Female', 90, 'auth.role/lists', 'role', 'permission/role/index', '', '', 0, 1, 0, 1656901660, 1710473000), (12, 8, 'A', '新增', '', 1, 'auth.role/add', '', '', '', '', 0, 1, 0, 1657001790, 1663750625), (14, 8, 'A', '编辑', '', 1, 'auth.role/edit', '', '', '', '', 0, 1, 0, 1657001924, 1663750631), (15, 8, 'A', '删除', '', 1, 'auth.role/delete', '', '', '', '', 0, 1, 0, 1657001982, 1663750637), (16, 6, 'A', '新增', '', 1, 'auth.menu/add', '', '', '', '', 0, 1, 0, 1657072523, 1663750565), (17, 6, 'A', '编辑', '', 1, 'auth.menu/edit', '', '', '', '', 0, 1, 0, 1657073955, 1663750570), (18, 6, 'A', '删除', '', 1, 'auth.menu/delete', '', '', '', '', 0, 1, 0, 1657073987, 1663750578), (19, 7, 'A', '新增', '', 1, 'auth.admin/add', '', '', '', '', 0, 1, 0, 1657074035, 1663750596), (20, 7, 'A', '编辑', '', 1, 'auth.admin/edit', '', '', '', '', 0, 1, 0, 1657074071, 1663750603), (21, 7, 'A', '删除', '', 1, 'auth.admin/delete', '', '', '', '', 0, 1, 0, 1657074108, 1663750609), (23, 28, 'M', '开发工具', 'el-icon-EditPen', 40, '', 'dev_tools', '', '', '', 0, 1, 0, 1657097744, 1710473127), (24, 23, 'C', '代码生成器', 'el-icon-DocumentAdd', 1, 'tools.generator/generateTable', 'code', 'dev_tools/code/index', '', '', 0, 1, 0, 1657098110, 1658989423), (25, 0, 'M', '组织管理', 'el-icon-OfficeBuilding', 400, '', 'organization', '', '', '', 0, 1, 0, 1657099914, 1710472797), (26, 25, 'C', '部门管理', 'el-icon-Coordinate', 100, 'dept.dept/lists', 'department', 'organization/department/index', '', '', 1, 1, 0, 1657099989, 1710472962), (27, 25, 'C', '岗位管理', 'el-icon-PriceTag', 90, 'dept.jobs/lists', 'post', 'organization/post/index', '', '', 0, 1, 0, 1657100044, 1710472967), (28, 0, 'M', '系统设置', 'el-icon-Setting', 200, '', 'setting', '', '', '', 0, 1, 0, 1657100164, 1710472807), (29, 28, 'M', '网站设置', 'el-icon-Basketball', 100, '', 'website', '', '', '', 0, 1, 0, 1657100230, 1710473049), (30, 29, 'C', '网站信息', '', 1, 'setting.web.web_setting/getWebsite', 'information', 'setting/website/information', '', '', 0, 1, 0, 1657100306, 1657164412), (31, 29, 'C', '网站备案', '', 1, 'setting.web.web_setting/getCopyright', 'filing', 'setting/website/filing', '', '', 0, 1, 0, 1657100434, 1657164723), (32, 29, 'C', '政策协议', '', 1, 'setting.web.web_setting/getAgreement', 'protocol', 'setting/website/protocol', '', '', 0, 1, 0, 1657100571, 1657164770), (33, 28, 'C', '存储设置', 'el-icon-FolderOpened', 70, 'setting.storage/lists', 'storage', 'setting/storage/index', '', '', 0, 1, 0, 1657160959, 1710473095), (34, 23, 'C', '字典管理', 'el-icon-Box', 1, 'setting.dict.dict_type/lists', 'dict', 'setting/dict/type/index', '', '', 0, 1, 0, 1657161211, 1663225935), (35, 28, 'M', '系统维护', 'el-icon-SetUp', 50, '', 'system', '', '', '', 0, 1, 0, 1657161569, 1710473122), (36, 35, 'C', '系统日志', '', 90, 'setting.system.log/lists', 'journal', 'setting/system/journal', '', '', 0, 1, 0, 1657161696, 1710473253), (37, 35, 'C', '系统缓存', '', 80, '', 'cache', 'setting/system/cache', '', '', 0, 1, 0, 1657161896, 1710473258), (38, 35, 'C', '系统环境', '', 70, 'setting.system.system/info', 'environment', 'setting/system/environment', '', '', 0, 1, 0, 1657162000, 1710473265), (39, 24, 'A', '导入数据表', '', 1, 'tools.generator/selectTable', '', '', '', '', 0, 1, 0, 1657162736, 1657162736), (40, 24, 'A', '代码生成', '', 1, 'tools.generator/generate', '', '', '', '', 0, 1, 0, 1657162806, 1657162806), (41, 23, 'C', '编辑数据表', '', 1, 'tools.generator/edit', 'code/edit', 'dev_tools/code/edit', '/dev_tools/code', '', 1, 0, 0, 1657162866, 1663748668), (42, 24, 'A', '同步表结构', '', 1, 'tools.generator/syncColumn', '', '', '', '', 0, 1, 0, 1657162934, 1657162934), (43, 24, 'A', '删除数据表', '', 1, 'tools.generator/delete', '', '', '', '', 0, 1, 0, 1657163015, 1657163015), (44, 24, 'A', '预览代码', '', 1, 'tools.generator/preview', '', '', '', '', 0, 1, 0, 1657163263, 1657163263), (45, 26, 'A', '新增', '', 1, 'dept.dept/add', '', '', '', '', 0, 1, 0, 1657163548, 1663750492), (46, 26, 'A', '编辑', '', 1, 'dept.dept/edit', '', '', '', '', 0, 1, 0, 1657163599, 1663750498), (47, 26, 'A', '删除', '', 1, 'dept.dept/delete', '', '', '', '', 0, 1, 0, 1657163687, 1663750504), (48, 27, 'A', '新增', '', 1, 'dept.jobs/add', '', '', '', '', 0, 1, 0, 1657163778, 1663750524), (49, 27, 'A', '编辑', '', 1, 'dept.jobs/edit', '', '', '', '', 0, 1, 0, 1657163800, 1663750530), (50, 27, 'A', '删除', '', 1, 'dept.jobs/delete', '', '', '', '', 0, 1, 0, 1657163820, 1663750535), (51, 30, 'A', '保存', '', 1, 'setting.web.web_setting/setWebsite', '', '', '', '', 0, 1, 0, 1657164469, 1663750649), (52, 31, 'A', '保存', '', 1, 'setting.web.web_setting/setCopyright', '', '', '', '', 0, 1, 0, 1657164692, 1663750657), (53, 32, 'A', '保存', '', 1, 'setting.web.web_setting/setAgreement', '', '', '', '', 0, 1, 0, 1657164824, 1663750665), (54, 33, 'A', '设置', '', 1, 'setting.storage/setup', '', '', '', '', 0, 1, 0, 1657165303, 1663750673), (55, 34, 'A', '新增', '', 1, 'setting.dict.dict_type/add', '', '', '', '', 0, 1, 0, 1657166966, 1663750783), (56, 34, 'A', '编辑', '', 1, 'setting.dict.dict_type/edit', '', '', '', '', 0, 1, 0, 1657166997, 1663750789), (57, 34, 'A', '删除', '', 1, 'setting.dict.dict_type/delete', '', '', '', '', 0, 1, 0, 1657167038, 1663750796), (58, 62, 'A', '新增', '', 1, 'setting.dict.dict_data/add', '', '', '', '', 0, 1, 0, 1657167317, 1663750758), (59, 62, 'A', '编辑', '', 1, 'setting.dict.dict_data/edit', '', '', '', '', 0, 1, 0, 1657167371, 1663750751), (60, 62, 'A', '删除', '', 1, 'setting.dict.dict_data/delete', '', '', '', '', 0, 1, 0, 1657167397, 1663750768), (61, 37, 'A', '清除系统缓存', '', 1, 'setting.system.cache/clear', '', '', '', '', 0, 1, 0, 1657173837, 1657173939), (62, 23, 'C', '字典数据管理', '', 1, 'setting.dict.dict_data/lists', 'dict/data', 'setting/dict/data/index', '/dev_tools/dict', '', 1, 0, 0, 1657174351, 1663745617), (63, 158, 'M', '素材管理', 'el-icon-Picture', 0, '', 'material', '', '', '', 0, 1, 0, 1657507133, 1710472243), (64, 63, 'C', '素材中心', 'el-icon-PictureRounded', 0, '', 'index', 'material/index', '', '', 0, 1, 0, 1657507296, 1664355653), (66, 26, 'A', '详情', '', 0, 'dept.dept/detail', '', '', '', '', 0, 1, 0, 1663725459, 1663750516), (67, 27, 'A', '详情', '', 0, 'dept.jobs/detail', '', '', '', '', 0, 1, 0, 1663725514, 1663750559), (68, 6, 'A', '详情', '', 0, 'auth.menu/detail', '', '', '', '', 0, 1, 0, 1663725564, 1663750584), (69, 7, 'A', '详情', '', 0, 'auth.admin/detail', '', '', '', '', 0, 1, 0, 1663725623, 1663750615), (70, 158, 'M', '文章资讯', 'el-icon-ChatLineSquare', 90, '', 'article', '', '', '', 0, 1, 0, 1663749965, 1710471867), (71, 70, 'C', '文章管理', 'el-icon-ChatDotSquare', 0, 'article.article/lists', 'lists', 'article/lists/index', '', '', 0, 1, 0, 1663750101, 1664354615), (72, 70, 'C', '文章添加/编辑', '', 0, 'article.article/add:edit', 'lists/edit', 'article/lists/edit', '/article/lists', '', 0, 0, 0, 1663750153, 1664356275), (73, 70, 'C', '文章栏目', 'el-icon-CollectionTag', 0, 'article.articleCate/lists', 'column', 'article/column/index', '', '', 1, 1, 0, 1663750287, 1664354678), (74, 71, 'A', '新增', '', 0, 'article.article/add', '', '', '', '', 0, 1, 0, 1663750335, 1663750335), (75, 71, 'A', '详情', '', 0, 'article.article/detail', '', '', '', '', 0, 1, 0, 1663750354, 1663750383), (76, 71, 'A', '删除', '', 0, 'article.article/delete', '', '', '', '', 0, 1, 0, 1663750413, 1663750413), (77, 71, 'A', '修改状态', '', 0, 'article.article/updateStatus', '', '', '', '', 0, 1, 0, 1663750442, 1663750442), (78, 73, 'A', '添加', '', 0, 'article.articleCate/add', '', '', '', '', 0, 1, 0, 1663750483, 1663750483), (79, 73, 'A', '删除', '', 0, 'article.articleCate/delete', '', '', '', '', 0, 1, 0, 1663750895, 1663750895), (80, 73, 'A', '详情', '', 0, 'article.articleCate/detail', '', '', '', '', 0, 1, 0, 1663750913, 1663750913), (81, 73, 'A', '修改状态', '', 0, 'article.articleCate/updateStatus', '', '', '', '', 0, 1, 0, 1663750936, 1663750936), (82, 0, 'M', '渠道设置', 'el-icon-Message', 500, '', 'channel', '', '', '', 0, 1, 0, 1663754084, 1710472649), (83, 82, 'C', 'h5设置', 'el-icon-Cellphone', 100, 'channel.web_page_setting/getConfig', 'h5', 'channel/h5', '', '', 0, 1, 0, 1663754158, 1710472929), (84, 83, 'A', '保存', '', 0, 'channel.web_page_setting/setConfig', '', '', '', '', 0, 1, 0, 1663754259, 1663754259), (85, 82, 'M', '微信公众号', 'local-icon-dingdan', 80, '', 'wx_oa', '', '', '', 0, 1, 0, 1663755470, 1710472946), (86, 85, 'C', '公众号配置', '', 0, 'channel.official_account_setting/getConfig', 'config', 'channel/wx_oa/config', '', '', 0, 1, 0, 1663755663, 1664355450), (87, 85, 'C', '菜单管理', '', 0, 'channel.official_account_menu/detail', 'menu', 'channel/wx_oa/menu', '', '', 0, 1, 0, 1663755767, 1664355456), (88, 86, 'A', '保存', '', 0, 'channel.official_account_setting/setConfig', '', '', '', '', 0, 1, 0, 1663755799, 1663755799), (89, 86, 'A', '保存并发布', '', 0, 'channel.official_account_menu/save', '', '', '', '', 0, 1, 0, 1663756490, 1663756490), (90, 85, 'C', '关注回复', '', 0, 'channel.official_account_reply/lists', 'follow', 'channel/wx_oa/reply/follow_reply', '', '', 0, 1, 0, 1663818358, 1663818366), (91, 85, 'C', '关键字回复', '', 0, '', 'keyword', 'channel/wx_oa/reply/keyword_reply', '', '', 0, 1, 0, 1663818445, 1663818445), (93, 85, 'C', '默认回复', '', 0, '', 'default', 'channel/wx_oa/reply/default_reply', '', '', 0, 1, 0, 1663818580, 1663818580), (94, 82, 'C', '微信小程序', 'local-icon-weixin', 90, 'channel.mnp_settings/getConfig', 'weapp', 'channel/weapp', '', '', 0, 1, 0, 1663831396, 1710472941), (95, 94, 'A', '保存', '', 0, 'channel.mnp_settings/setConfig', '', '', '', '', 0, 1, 0, 1663831436, 1663831436), (96, 0, 'M', '装修管理', 'el-icon-Brush', 600, '', 'decoration', '', '', '', 0, 1, 0, 1663834825, 1710472099), (97, 175, 'C', '页面装修', 'el-icon-CopyDocument', 100, 'decorate.page/detail', 'pages', 'decoration/pages/index', '', '', 0, 1, 0, 1663834879, 1710929256), (98, 97, 'A', '保存', '', 0, 'decorate.page/save', '', '', '', '', 0, 1, 0, 1663834956, 1663834956), (99, 175, 'C', '底部导航', 'el-icon-Position', 90, 'decorate.tabbar/detail', 'tabbar', 'decoration/tabbar', '', '', 0, 1, 0, 1663835004, 1710929262), (100, 99, 'A', '保存', '', 0, 'decorate.tabbar/save', '', '', '', '', 0, 1, 0, 1663835018, 1663835018), (101, 158, 'M', '消息管理', 'el-icon-ChatDotRound', 80, '', 'message', '', '', '', 0, 1, 0, 1663838602, 1710471874), (102, 101, 'C', '通知设置', '', 0, 'notice.notice/settingLists', 'notice', 'message/notice/index', '', '', 0, 1, 0, 1663839195, 1663839195), (103, 102, 'A', '详情', '', 0, 'notice.notice/detail', '', '', '', '', 0, 1, 0, 1663839537, 1663839537), (104, 101, 'C', '通知设置编辑', '', 0, 'notice.notice/set', 'notice/edit', 'message/notice/edit', '/message/notice', '', 0, 0, 0, 1663839873, 1663898477), (105, 71, 'A', '编辑', '', 0, 'article.article/edit', '', '', '', '', 0, 1, 0, 1663840043, 1663840053),  (107, 101, 'C', '短信设置', '', 0, 'notice.sms_config/getConfig', 'short_letter', 'message/short_letter/index', '', '', 0, 1, 0, 1663898591, 1664355708), (108, 107, 'A', '设置', '', 0, 'notice.sms_config/setConfig', '', '', '', '', 0, 1, 0, 1663898644, 1663898644), (109, 107, 'A', '详情', '', 0, 'notice.sms_config/detail', '', '', '', '', 0, 1, 0, 1663898661, 1663898661), (110, 28, 'C', '热门搜索', 'el-icon-Search', 60, 'setting.hot_search/getConfig', 'search', 'setting/search/index', '', '', 0, 1, 0, 1663901821, 1710473109), (111, 110, 'A', '保存', '', 0, 'setting.hot_search/setConfig', '', '', '', '', 0, 1, 0, 1663901856, 1663901856), (112, 28, 'M', '用户设置', 'local-icon-keziyuyue', 90, '', 'user', '', '', '', 0, 1, 0, 1663903302, 1710473056), (113, 112, 'C', '用户设置', '', 0, 'setting.user.user/getConfig', 'setup', 'setting/user/setup', '', '', 0, 1, 0, 1663903506, 1663903506), (114, 113, 'A', '保存', '', 0, 'setting.user.user/setConfig', '', '', '', '', 0, 1, 0, 1663903522, 1663903522), (115, 112, 'C', '登录注册', '', 0, 'setting.user.user/getRegisterConfig', 'login_register', 'setting/user/login_register', '', '', 0, 1, 0, 1663903832, 1663903832), (116, 115, 'A', '保存', '', 0, 'setting.user.user/setRegisterConfig', '', '', '', '', 0, 1, 0, 1663903852, 1663903852), (117, 0, 'M', '用户管理', 'el-icon-User', 900, '', 'consumer', '', '', '', 0, 1, 0, 1663904351, 1710472074), (118, 117, 'C', '用户列表', 'local-icon-user_guanli', 100, 'user.user/lists', 'lists', 'consumer/lists/index', '', '', 0, 1, 0, 1663904392, 1710471845), (119, 117, 'C', '用户详情', '', 90, 'user.user/detail', 'lists/detail', 'consumer/lists/detail', '/consumer/lists', '', 0, 0, 0, 1663904470, 1710471851), (120, 119, 'A', '编辑', '', 0, 'user.user/edit', '', '', '', '', 0, 1, 0, 1663904499, 1663904499), (140, 82, 'C', '微信开发平台', 'local-icon-notice_buyer', 70, 'channel.open_setting/getConfig', 'open_setting', 'channel/open_setting', '', '', 0, 1, 0, 1666085713, 1710472951), (141, 140, 'A', '保存', '', 0, 'channel.open_setting/setConfig', '', '', '', '', 0, 1, 0, 1666085751, 1666085776), (142, 176, 'C', 'PC端装修', 'el-icon-Monitor', 8, '', 'pc', 'decoration/pc', '', '', 0, 1, 0, 1668423284, 1710901602), (143, 35, 'C', '定时任务', '', 100, 'crontab.crontab/lists', 'scheduled_task', 'setting/system/scheduled_task/index', '', '', 0, 1, 0, 1669357509, 1710473246), (144, 35, 'C', '定时任务添加/编辑', '', 0, 'crontab.crontab/add:edit', 'scheduled_task/edit', 'setting/system/scheduled_task/edit', '/setting/system/scheduled_task', '', 0, 0, 0, 1669357670, 1669357765), (145, 143, 'A', '添加', '', 0, 'crontab.crontab/add', '', '', '', '', 0, 1, 0, 1669358282, 1669358282), (146, 143, 'A', '编辑', '', 0, 'crontab.crontab/edit', '', '', '', '', 0, 1, 0, 1669358303, 1669358303), (147, 143, 'A', '删除', '', 0, 'crontab.crontab/delete', '', '', '', '', 0, 1, 0, 1669358334, 1669358334), (148, 0, 'M', '模板示例', 'el-icon-SetUp', 100, '', 'template', '', '', '', 0, 1, 0, 1670206819, 1710472811), (149, 148, 'M', '组件示例', 'el-icon-Coin', 0, '', 'component', '', '', '', 0, 1, 0, 1670207182, 1670207244), (150, 149, 'C', '富文本', '', 90, '', 'rich_text', 'template/component/rich_text', '', '', 0, 1, 0, 1670207751, 1710473315), (151, 149, 'C', '上传文件', '', 80, '', 'upload', 'template/component/upload', '', '', 0, 1, 0, 1670208925, 1710473322), (152, 149, 'C', '图标', '', 100, '', 'icon', 'template/component/icon', '', '', 0, 1, 0, 1670230069, 1710473306), (153, 149, 'C', '文件选择器', '', 60, '', 'file', 'template/component/file', '', '', 0, 1, 0, 1670232129, 1710473341), (154, 149, 'C', '链接选择器', '', 50, '', 'link', 'template/component/link', '', '', 0, 1, 0, 1670292636, 1710473346), (155, 149, 'C', '超出自动打点', '', 40, '', 'overflow', 'template/component/overflow', '', '', 0, 1, 0, 1670292883, 1710473351), (156, 149, 'C', '悬浮input', '', 70, '', 'popover_input', 'template/component/popover_input', '', '', 0, 1, 0, 1670293336, 1710473329), (157, 119, 'A', '余额调整', '', 0, 'user.user/adjustMoney', '', '', '', '', 0, 1, 0, 1677143088, 1677143088), (158, 0, 'M', '应用管理', 'el-icon-Postcard', 800, '', 'app', '', '', '', 0, 1, 0, 1677143430, 1710472079), (159, 158, 'C', '用户充值', 'local-icon-fukuan', 100, 'recharge.recharge/getConfig', 'recharge', 'app/recharge/index', '', '', 0, 1, 0, 1677144284, 1710471860), (160, 159, 'A', '保存', '', 0, 'recharge.recharge/setConfig', '', '', '', '', 0, 1, 0, 1677145012, 1677145012), (161, 28, 'M', '支付设置', 'local-icon-set_pay', 80, '', 'pay', '', '', '', 0, 1, 0, 1677148075, 1710473061), (162, 161, 'C', '支付方式', '', 0, 'setting.pay.pay_way/getPayWay', 'method', 'setting/pay/method/index', '', '', 0, 1, 0, 1677148207, 1677148207), (163, 161, 'C', '支付配置', '', 0, 'setting.pay.pay_config/lists', 'config', 'setting/pay/config/index', '', '', 0, 1, 0, 1677148260, 1677148374), (164, 162, 'A', '设置支付方式', '', 0, 'setting.pay.pay_way/setPayWay', '', '', '', '', 0, 1, 0, 1677219624, 1677219624), (165, 163, 'A', '配置', '', 0, 'setting.pay.pay_config/setConfig', '', '', '', '', 0, 1, 0, 1677219655, 1677219655), (166, 0, 'M', '财务管理', 'local-icon-user_gaikuang', 700, '', 'finance', '', '', '', 0, 1, 0, 1677552269, 1710472085), (167, 166, 'C', '充值记录', 'el-icon-Wallet', 90, 'recharge.recharge/lists', 'recharge_record', 'finance/recharge_record', '', '', 0, 1, 0, 1677552757, 1710472902), (168, 166, 'C', '余额明细', 'local-icon-qianbao', 100, 'finance.account_log/lists', 'balance_details', 'finance/balance_details', '', '', 0, 1, 0, 1677552976, 1710472894), (169, 167, 'A', '退款', '', 0, 'recharge.recharge/refund', '', '', '', '', 0, 1, 0, 1677809715, 1677809715), (170, 166, 'C', '退款记录', 'local-icon-heshoujilu', 0, 'finance.refund/record', 'refund_record', 'finance/refund_record', '', '', 0, 1, 0, 1677811271, 1677811271), (171, 170, 'A', '重新退款', '', 0, 'recharge.recharge/refundAgain', '', '', '', '', 0, 1, 0, 1677811295, 1677811295), (172, 170, 'A', '退款日志', '', 0, 'finance.refund/log', '', '', '', '', 0, 1, 0, 1677811361, 1677811361), (173, 175, 'C', '系统风格', 'el-icon-Brush', 80, '', 'style', 'decoration/style/style', '', '', 0, 1, 0, 1681635044, 1710929278), (175, 96, 'M', '移动端', '', 100, '', 'mobile', '', '', '', 0, 1, 0, 1710901543, 1710929294), (176, 96, 'M', 'PC端', '', 90, '', 'pc', '', '', '', 0, 1, 0, 1710901592, 1710929299),(177, 29, 'C', '站点统计', '', 0, 'setting.web.web_setting/getSiteStatistics', 'statistics', 'setting/website/statistics', '', '', 0, 1, 0, 1726841481, 1726843434),(178, 177, 'A', '保存', '', 0, 'setting.web.web_setting/saveSiteStatistics', '', '', '', '', 1, 1, 0, 1726841507, 1726841507);
COMMIT;

-- ----------------------------
-- Table structure for la_system_role
-- ----------------------------
DROP TABLE IF EXISTS `la_system_role`;
CREATE TABLE `la_system_role`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '名称',
  `desc` varchar(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '描述',
  `sort` int(11) NULL DEFAULT 0 COMMENT '排序',
  `create_time` int(10) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '角色表';

-- ----------------------------
-- Table structure for la_system_role_menu
-- ----------------------------
DROP TABLE IF EXISTS `la_system_role_menu`;
CREATE TABLE `la_system_role_menu`  (
  `role_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '角色ID',
  `menu_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '菜单ID',
  PRIMARY KEY (`role_id`, `menu_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '角色菜单关系表';

-- ----------------------------
-- Table structure for la_user
-- ----------------------------
DROP TABLE IF EXISTS `la_user`;
CREATE TABLE `la_user`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `sn` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '编号',
  `avatar` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '头像',
  `real_name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '真实姓名',
  `nickname` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户昵称',
  `account` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户账号',
  `password` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户密码',
  `mobile` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户电话',
  `sex` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户性别: [1=男, 2=女]',
  `channel` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '注册渠道: [1-微信小程序 2-微信公众号 3-手机H5 4-电脑PC 5-苹果APP 6-安卓APP]',
  `is_disable` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否禁用: [0=否, 1=是]',
  `login_ip` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '最后登录IP',
  `login_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后登录时间',
  `is_new_user` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否是新注册用户: [1-是, 0-否]',
  `user_money` decimal(10, 2) UNSIGNED NULL DEFAULT 0.00 COMMENT '用户余额',
  `total_recharge_amount` decimal(10, 2) UNSIGNED NULL DEFAULT 0.00 COMMENT '累计充值',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `delete_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `sn`(`sn`) USING BTREE COMMENT '编号唯一',
  UNIQUE INDEX `account`(`account`) USING BTREE COMMENT '账号唯一'
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '用户表';

-- ----------------------------
-- Table structure for la_user_account_log
-- ----------------------------
DROP TABLE IF EXISTS `la_user_account_log`;
CREATE TABLE `la_user_account_log`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'id',
  `sn` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '流水号',
  `user_id` int(11) NOT NULL COMMENT '用户id',
  `change_object` tinyint(1) NOT NULL DEFAULT 0 COMMENT '变动对象',
  `change_type` smallint(5) NOT NULL COMMENT '变动类型',
  `action` tinyint(1) NOT NULL DEFAULT 0 COMMENT '动作 1-增加 2-减少',
  `change_amount` decimal(10, 2) NOT NULL COMMENT '变动数量',
  `left_amount` decimal(10, 2) NOT NULL DEFAULT 100.00 COMMENT '变动后数量',
  `source_sn` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '关联单号',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '备注',
  `extra` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '预留扩展字段',
  `create_time` int(10) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '用户账户变动日志表';

-- ----------------------------
-- Table structure for la_user_auth
-- ----------------------------
DROP TABLE IF EXISTS `la_user_auth`;
CREATE TABLE `la_user_auth`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `user_id` int(11) NOT NULL COMMENT '用户id',
  `openid` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '微信openid',
  `unionid` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '微信unionid',
  `terminal` tinyint(1) NOT NULL DEFAULT 1 COMMENT '客户端类型：1-微信小程序；2-微信公众号；3-手机H5；4-电脑PC；5-苹果APP；6-安卓APP',
  `create_time` int(10) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `openid`(`openid`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '用户授权表';

-- ----------------------------
-- Table structure for la_user_session
-- ----------------------------
DROP TABLE IF EXISTS `la_user_session`;
CREATE TABLE `la_user_session`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `user_id` int(11) NOT NULL COMMENT '用户id',
  `terminal` tinyint(1) NOT NULL DEFAULT 1 COMMENT '客户端类型：1-微信小程序；2-微信公众号；3-手机H5；4-电脑PC；5-苹果APP；6-安卓APP',
  `token` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '令牌',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  `expire_time` int(10) NOT NULL COMMENT '到期时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `admin_id_client`(`user_id`, `terminal`) USING BTREE COMMENT '一个用户在一个终端只有一个token',
  UNIQUE INDEX `token`(`token`) USING BTREE COMMENT 'token是唯一的'
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '用户会话表';

SET FOREIGN_KEY_CHECKS=0;


DROP TABLE IF EXISTS `la_service_category`;
CREATE TABLE `la_service_category` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '分类ID',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '分类名称',
  `pid` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '上级分类ID',
  `icon` varchar(255) NOT NULL DEFAULT '' COMMENT '分类图标',
  `image` varchar(255) NOT NULL DEFAULT '' COMMENT '分类图片',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序(数值越大越靠前)',
  `is_show` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否显示:0-否,1-是',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `delete_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_pid` (`pid`) USING BTREE,
  KEY `idx_is_show` (`is_show`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='服务分类表';

BEGIN;
INSERT INTO `la_service_category` VALUES 
(1, '摄影师', 0, '', '/resource/image/wedding/category/photographer.png', 100, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(2, '摄像师', 0, '', '/resource/image/wedding/category/videographer.png', 90, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(3, '化妆师', 0, '', '/resource/image/wedding/category/makeup.png', 80, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(4, '司仪主持', 0, '', '/resource/image/wedding/category/host.png', 70, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(5, '婚礼策划', 0, '', '/resource/image/wedding/category/planner.png', 60, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(6, '花艺师', 0, '', '/resource/image/wedding/category/florist.png', 50, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(7, '跟妆师', 0, '', '/resource/image/wedding/category/stylist.png', 40, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(8, '灯光师', 0, '', '/resource/image/wedding/category/lighting.png', 30, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL);
COMMIT;

DROP TABLE IF EXISTS `la_style_tag`;
CREATE TABLE `la_style_tag` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '标签ID',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '标签名称',
  `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '标签类型:1-风格,2-特长,3-其他',
  `category_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务分类ID',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `is_show` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否显示:0-否,1-是',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `delete_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_type` (`type`) USING BTREE,
  KEY `idx_category_id` (`category_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='风格标签表';

BEGIN;
INSERT INTO `la_style_tag` VALUES 
(1, '韩式清新', 1, 0, 100, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(2, '中式古典', 1, 0, 90, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(3, '欧式浪漫', 1, 0, 80, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(4, '日系森系', 1, 0, 70, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(5, '简约现代', 1, 0, 60, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(6, '复古怀旧', 1, 0, 50, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(7, '户外自然', 2, 0, 100, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(8, '室内棚拍', 2, 0, 90, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(9, '旅拍跟拍', 2, 0, 80, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(10, '纪实风格', 2, 0, 70, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL);
COMMIT;

DROP TABLE IF EXISTS `la_service_package`;
CREATE TABLE `la_service_package` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '套餐ID',
  `category_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务分类ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '套餐名称',
  `price` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '套餐价格',
  `original_price` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '原价',
  `duration` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务时长(小时)',
  `description` text COMMENT '套餐描述',
  `content` text COMMENT '套餐内容(JSON格式)',
  `image` varchar(255) NOT NULL DEFAULT '' COMMENT '套餐图片',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `is_recommend` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否推荐:0-否,1-是',
  `is_show` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否显示:0-否,1-是',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `delete_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_category_id` (`category_id`) USING BTREE,
  KEY `idx_is_recommend` (`is_recommend`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='服务套餐表';

BEGIN;
INSERT INTO `la_service_package` VALUES 
(1, 1, '婚礼跟拍-基础套餐', 2999.00, 3999.00, 8, '8小时婚礼全程跟拍', '["精修照片50张","原片全送","专业设备"]', '', 100, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(2, 1, '婚礼跟拍-标准套餐', 4999.00, 5999.00, 10, '10小时婚礼全程跟拍', '["精修照片80张","原片全送","专业设备","相册一本"]', '', 90, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(3, 1, '婚礼跟拍-豪华套餐', 7999.00, 9999.00, 12, '12小时婚礼全程跟拍+晚宴', '["精修照片120张","原片全送","专业设备","相册两本","视频花絮"]', '', 80, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(4, 2, '婚礼摄像-基础套餐', 3999.00, 4999.00, 8, '8小时婚礼全程摄像', '["成片15分钟","原素材","4K画质"]', '', 100, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(5, 2, '婚礼摄像-标准套餐', 5999.00, 7999.00, 10, '10小时双机位摄像', '["成片20分钟","原素材","4K画质","快剪"]', '', 90, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(6, 3, '新娘跟妆-全天', 1999.00, 2499.00, 10, '全天新娘妆容服务', '["早妆","晚宴补妆","造型2套"]', '', 100, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(7, 3, '新娘跟妆-半天', 999.00, 1299.00, 5, '半天新娘妆容服务', '["早妆或晚宴妆","造型1套"]', '', 90, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL),
(8, 4, '婚礼主持-标准', 2999.00, 3999.00, 4, '婚礼仪式主持', '["仪式主持","互动环节","专业设备"]', '', 100, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL);
COMMIT;

DROP TABLE IF EXISTS `la_staff`;
CREATE TABLE `la_staff` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '工作人员ID',
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联用户ID(用于登录)',
  `sn` varchar(32) NOT NULL DEFAULT '' COMMENT '工号',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '姓名',
  `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
  `mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号(脱敏显示)',
  `mobile_full` varchar(20) NOT NULL DEFAULT '' COMMENT '完整手机号',
  `category_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务分类ID',
  `price` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '起步价格',
  `experience_years` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '从业年限',
  `profile` text COMMENT '个人简介',
  `service_desc` text COMMENT '服务说明',
  `rating` decimal(3,2) UNSIGNED NOT NULL DEFAULT 5.00 COMMENT '综合评分(1-5)',
  `rating_service` decimal(3,2) UNSIGNED NOT NULL DEFAULT 5.00 COMMENT '服务态度评分',
  `rating_skill` decimal(3,2) UNSIGNED NOT NULL DEFAULT 5.00 COMMENT '专业水平评分',
  `rating_price` decimal(3,2) UNSIGNED NOT NULL DEFAULT 5.00 COMMENT '性价比评分',
  `order_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '接单数量',
  `review_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '评价数量',
  `favorite_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '收藏数量',
  `view_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '浏览数量',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序(数值越大越靠前)',
  `is_recommend` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否推荐:0-否,1-是',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态:0-禁用,1-启用',
  `audit_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '审核状态:0-待审核,1-已通过,2-已拒绝',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `delete_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_sn` (`sn`) USING BTREE,
  KEY `idx_user_id` (`user_id`) USING BTREE,
  KEY `idx_category_id` (`category_id`) USING BTREE,
  KEY `idx_status` (`status`) USING BTREE,
  KEY `idx_is_recommend` (`is_recommend`) USING BTREE,
  KEY `idx_rating` (`rating`) USING BTREE,
  KEY `idx_price` (`price`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='工作人员表';

DROP TABLE IF EXISTS `la_staff_work`;
CREATE TABLE `la_staff_work` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '作品ID',
  `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '工作人员ID',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '作品标题',
  `cover` varchar(255) NOT NULL DEFAULT '' COMMENT '封面图片',
  `images` text COMMENT '作品图片(JSON数组)',
  `video` varchar(255) NOT NULL DEFAULT '' COMMENT '作品视频',
  `description` text COMMENT '作品描述',
  `shoot_date` date NULL DEFAULT NULL COMMENT '拍摄日期',
  `location` varchar(100) NOT NULL DEFAULT '' COMMENT '拍摄地点',
  `view_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '浏览数',
  `like_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '点赞数',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `is_show` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否显示:0-否,1-是',
  `audit_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '审核状态:0-待审核,1-已通过,2-已拒绝',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `delete_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_staff_id` (`staff_id`) USING BTREE,
  KEY `idx_audit_status` (`audit_status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='工作人员作品表';

DROP TABLE IF EXISTS `la_staff_certificate`;
CREATE TABLE `la_staff_certificate` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '证书ID',
  `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '工作人员ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '证书名称',
  `image` varchar(255) NOT NULL DEFAULT '' COMMENT '证书图片',
  `issue_org` varchar(100) NOT NULL DEFAULT '' COMMENT '颁发机构',
  `issue_date` date NULL DEFAULT NULL COMMENT '颁发日期',
  `expire_date` date NULL DEFAULT NULL COMMENT '有效期至',
  `certificate_no` varchar(100) NOT NULL DEFAULT '' COMMENT '证书编号',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `audit_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '审核状态:0-待审核,1-已通过,2-已拒绝',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `delete_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_staff_id` (`staff_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='工作人员证书表';

DROP TABLE IF EXISTS `la_staff_tag`;
CREATE TABLE `la_staff_tag` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '工作人员ID',
  `tag_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '标签ID',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_staff_tag` (`staff_id`, `tag_id`) USING BTREE,
  KEY `idx_tag_id` (`tag_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='工作人员标签关联表';

DROP TABLE IF EXISTS `la_staff_package`;
CREATE TABLE `la_staff_package` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '工作人员ID',
  `package_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '套餐ID',
  `price` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '该人员的套餐价格(可覆盖默认价格)',
  `is_default` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否默认套餐:0-否,1-是',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_staff_package` (`staff_id`, `package_id`) USING BTREE,
  KEY `idx_package_id` (`package_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='工作人员套餐关联表';

DROP TABLE IF EXISTS `la_favorite`;
CREATE TABLE `la_favorite` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '工作人员ID',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_user_staff` (`user_id`, `staff_id`) USING BTREE,
  KEY `idx_staff_id` (`staff_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='收藏表';


DROP TABLE IF EXISTS `la_schedule_rule`;
CREATE TABLE `la_schedule_rule` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `staff_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '工作人员ID（0=全局规则）',
    `advance_days` INT UNSIGNED NOT NULL DEFAULT 3 COMMENT '提前预约天数',
    `max_orders_per_day` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '单日最大接单数',
    `interval_hours` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单间隔时间（小时）',
    `work_start_time` VARCHAR(10) NOT NULL DEFAULT '09:00' COMMENT '工作开始时间',
    `work_end_time` VARCHAR(10) NOT NULL DEFAULT '18:00' COMMENT '工作结束时间',
    `rest_days` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '休息日（逗号分隔，0=周日,1=周一...）',
    `is_enabled` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否启用：0=否,1=是',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='档期规则表';

DROP TABLE IF EXISTS `la_schedule`;
CREATE TABLE `la_schedule` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `staff_id` INT UNSIGNED NOT NULL COMMENT '工作人员ID',
    `schedule_date` DATE NOT NULL COMMENT '档期日期',
    `time_slot` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '时间段：0=全天,1=早礼,2=午宴,3=晚宴',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=不可用,1=可预约,2=已预约,3=已锁定,4=内部预留',
    `order_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
    `lock_type` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '锁定类型：0=正常,1=VIP锁定,2=内部预留',
    `lock_user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '锁定用户ID',
    `lock_expire_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '锁定到期时间',
    `lock_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '锁定/预留原因',
    `queue_lock_until` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '候补锁档截止时间',
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '当日价格（0=使用默认价格）',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `version` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '乐观锁版本号',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_staff_date_slot` (`staff_id`, `schedule_date`, `time_slot`),
    KEY `idx_schedule_date` (`schedule_date`),
    KEY `idx_status` (`status`),
    KEY `idx_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='档期表';

DROP TABLE IF EXISTS `la_schedule_lock`;
CREATE TABLE `la_schedule_lock` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `schedule_id` INT UNSIGNED NOT NULL COMMENT '档期ID',
    `staff_id` INT UNSIGNED NOT NULL COMMENT '工作人员ID',
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '锁定用户ID',
    `lock_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '锁定类型：1=VIP锁定,2=内部预留,3=临时锁定',
    `lock_start_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '锁定开始时间',
    `lock_end_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '锁定结束时间',
    `lock_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '锁定原因',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=已释放,1=锁定中,2=已转订单',
    `release_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '释放时间',
    `release_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '释放原因',
    `admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作管理员ID',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_schedule_id` (`schedule_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='档期锁定记录表';

DROP TABLE IF EXISTS `la_schedule_share`;
CREATE TABLE `la_schedule_share` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `group_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '组合名称',
    `staff_ids` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '工作人员ID列表（逗号分隔）',
    `share_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '共享类型：1=档期同步,2=组合套餐',
    `discount_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT '组合折扣率（如95表示95折）',
    `is_enabled` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否启用：0=否,1=是',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='档期共享表';

DROP TABLE IF EXISTS `la_cart`;
CREATE TABLE `la_cart` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `staff_id` INT UNSIGNED NOT NULL COMMENT '工作人员ID',
    `package_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '套餐ID（0=仅预约人员）',
    `schedule_date` DATE NOT NULL COMMENT '预约日期',
    `time_slot` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '时间段：0=全天,1=早礼,2=午宴,3=晚宴',
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '价格',
    `quantity` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '数量',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `is_selected` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否选中：0=否,1=是',
    `share_code` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '分享码',
    `share_user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '分享来源用户ID',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_schedule_date` (`schedule_date`),
    KEY `idx_share_code` (`share_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='购物车表';

DROP TABLE IF EXISTS `la_cart_plan`;
CREATE TABLE `la_cart_plan` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `plan_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '方案名称',
    `cart_ids` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '购物车项ID列表（JSON）',
    `items_snapshot` TEXT NULL COMMENT '方案明细快照（JSON）',
    `total_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '方案总价',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '方案备注',
    `share_code` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '分享码',
    `is_default` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否默认方案：0=否,1=是',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_share_code` (`share_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='购物车方案表';

DROP TABLE IF EXISTS `la_waitlist`;
CREATE TABLE `la_waitlist` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `staff_id` INT UNSIGNED NOT NULL COMMENT '工作人员ID',
    `schedule_date` DATE NOT NULL COMMENT '预约日期',
    `time_slot` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '时间段',
    `package_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '套餐ID',
    `notify_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '通知状态：0=未通知,1=已通知,2=已下单,3=已过期',
    `notify_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '通知时间',
    `expire_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '过期时间',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_schedule_date` (`schedule_date`),
    KEY `idx_notify_status` (`notify_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='候补订单表';

DROP TABLE IF EXISTS `la_calendar_event`;
CREATE TABLE `la_calendar_event` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `event_date` DATE NOT NULL COMMENT '日期',
    `lunar_date` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '农历日期',
    `is_lucky_day` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否吉日：0=否,1=是',
    `lucky_events` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '宜（如：结婚,订婚）',
    `unlucky_events` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '忌',
    `is_holiday` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否节假日：0=否,1=是',
    `holiday_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '节假日名称',
    `congestion_level` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '拥堵等级：0=未知,1=低,2=中,3=高',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_event_date` (`event_date`),
    KEY `idx_is_lucky_day` (`is_lucky_day`),
    KEY `idx_is_holiday` (`is_holiday`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='黄历/吉日表';


INSERT INTO `la_schedule_rule` (`staff_id`, `advance_days`, `max_orders_per_day`, `interval_hours`, `work_start_time`, `work_end_time`, `rest_days`, `is_enabled`, `create_time`, `update_time`) VALUES
(0, 3, 1, 0, '08:00', '20:00', '', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

INSERT INTO `la_calendar_event` (`event_date`, `lunar_date`, `is_lucky_day`, `lucky_events`, `unlucky_events`, `is_holiday`, `holiday_name`, `congestion_level`, `create_time`, `update_time`) VALUES
('2026-01-01', '十一月十二', 0, '', '', 1, '元旦', 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('2026-02-17', '正月初一', 1, '祈福,嫁娶,订盟', '动土,破土', 1, '春节', 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('2026-05-01', '三月十五', 1, '嫁娶,订盟,纳采', '开市,动土', 1, '劳动节', 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('2026-05-20', '四月初四', 1, '嫁娶,订盟,纳采', '安葬,破土', 0, '', 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('2026-10-01', '八月廿一', 1, '嫁娶,祈福,订盟', '安葬,动土', 1, '国庆节', 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


DROP TABLE IF EXISTS `la_order`;
CREATE TABLE `la_order` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `order_sn` VARCHAR(32) NOT NULL COMMENT '订单编号',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `order_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '订单类型：1=普通订单,2=套餐订单,3=组合订单',
    `order_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单状态：0=待确认,1=待支付,2=已支付,3=服务中,4=已完成,5=已评价,6=已取消,7=已暂停,8=已退款',
    `pay_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付状态：0=未支付,1=已支付,2=部分退款,3=全额退款',
    `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '订单总额',
    `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '优惠金额',
    `coupon_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '优惠券抵扣',
    `pay_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '实付金额',
    `paid_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '已支付金额',
    `deposit_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '定金金额',
    `deposit_paid` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '定金是否支付：0=否,1=是',
    `balance_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '尾款金额',
    `balance_paid` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '尾款是否支付：0=否,1=是',
    `pay_type` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付方式：0=未支付,1=微信,2=支付宝,3=余额,4=线下,5=组合支付',
    `pay_voucher` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '线下支付凭证',
    `pay_voucher_status` TINYINT UNSIGNED DEFAULT NULL COMMENT '凭证审核状态：0=待审核,1=已通过,2=已拒绝',
    `pay_voucher_audit_admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '凭证审核管理员ID',
    `pay_voucher_audit_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '凭证审核时间',
    `pay_voucher_audit_remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '凭证审核备注',
    `pay_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付时间',
    `service_date` DATE DEFAULT NULL COMMENT '服务日期',
    `service_time_slot` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务时间段',
    `service_address` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '服务地址',
    `contact_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '联系人姓名',
    `contact_mobile` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '联系人电话',
    `wedding_date` DATE DEFAULT NULL COMMENT '婚礼日期',
    `wedding_venue` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '婚礼场地',
    `user_remark` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '用户备注',
    `admin_remark` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '管理员备注',
    `coupon_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '使用的优惠券ID',
    `cancel_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '取消原因',
    `cancel_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '取消时间',
    `complete_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '完成时间',
    `is_reviewed` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否已评价：0=否,1=是',
    `source` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '订单来源：1=小程序,2=H5,3=后台',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_order_sn` (`order_sn`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_order_status` (`order_status`),
    KEY `idx_pay_status` (`pay_status`),
    KEY `idx_service_date` (`service_date`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单表';

DROP TABLE IF EXISTS `la_order_item`;
CREATE TABLE `la_order_item` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
    `staff_id` INT UNSIGNED NOT NULL COMMENT '工作人员ID',
    `package_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '套餐ID',
    `schedule_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '档期ID',
    `service_date` DATE NOT NULL COMMENT '服务日期',
    `time_slot` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '时间段',
    `staff_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '人员姓名(冗余)',
    `package_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '套餐名称(冗余)',
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '单价',
    `quantity` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '数量',
    `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '小计',
    `item_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '项状态：0=待服务,1=服务中,2=已完成,3=已取消',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_service_date` (`service_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单项表';

DROP TABLE IF EXISTS `la_order_log`;
CREATE TABLE `la_order_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
    `operator_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '操作者类型：1=用户,2=管理员,3=系统',
    `operator_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作者ID',
    `action` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '操作动作',
    `before_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作前状态',
    `after_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作后状态',
    `content` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '日志内容',
    `ip` VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'IP地址',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单日志表';

DROP TABLE IF EXISTS `la_payment`;
CREATE TABLE `la_payment` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `payment_sn` VARCHAR(32) NOT NULL COMMENT '支付流水号',
    `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
    `order_sn` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '订单编号',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `pay_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '支付类型：1=定金,2=尾款,3=全款',
    `pay_way` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '支付方式：1=微信,2=支付宝,3=余额,4=线下',
    `pay_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '支付金额',
    `pay_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付状态：0=待支付,1=已支付,2=已退款,3=支付失败',
    `transaction_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '第三方交易号',
    `pay_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付时间',
    `expire_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '过期时间',
    `callback_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '回调时间',
    `callback_data` TEXT COMMENT '回调数据',
    `refund_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '退款金额',
    `refund_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '退款时间',
    `refund_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '退款原因',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_payment_sn` (`payment_sn`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_pay_status` (`pay_status`),
    KEY `idx_transaction_id` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='支付记录表';

DROP TABLE IF EXISTS `la_refund`;
CREATE TABLE `la_refund` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `refund_sn` VARCHAR(32) NOT NULL COMMENT '退款编号',
    `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
    `payment_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付记录ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `refund_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '退款类型：1=用户申请,2=管理员操作,3=系统自动',
    `refund_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '退款金额',
    `refund_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '退款原因',
    `refund_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '退款状态：0=待审核,1=审核通过,2=退款中,3=已退款,4=已拒绝',
    `audit_admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核管理员ID',
    `audit_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核时间',
    `audit_remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '审核备注',
    `refund_transaction_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '退款交易号',
    `refund_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '实际退款时间',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_refund_sn` (`refund_sn`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_refund_status` (`refund_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='退款记录表';

DROP TABLE IF EXISTS `la_dynamic`;
CREATE TABLE `la_dynamic` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '发布者ID',
    `user_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '发布者类型：1=用户,2=工作人员,3=官方',
    `staff_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '工作人员ID(user_type=2时)',
    `dynamic_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '动态类型：1=图文,2=视频,3=案例分享,4=活动',
    `title` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '标题',
    `content` TEXT COMMENT '内容',
    `images` TEXT COMMENT '图片列表(JSON)',
    `video_url` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '视频地址',
    `video_cover` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '视频封面',
    `location` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '位置信息',
    `latitude` DECIMAL(10,7) NOT NULL DEFAULT 0 COMMENT '纬度',
    `longitude` DECIMAL(10,7) NOT NULL DEFAULT 0 COMMENT '经度',
    `tags` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '标签(逗号分隔)',
    `order_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID(晒单)',
    `view_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '浏览量',
    `like_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '点赞数',
    `comment_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '评论数',
    `share_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '分享数',
    `collect_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '收藏数',
    `is_top` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否置顶：0=否,1=是',
    `is_hot` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否热门：0=否,1=是',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=待审核,1=已发布,2=已下架,3=审核拒绝',
    `audit_admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核管理员ID',
    `audit_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核时间',
    `audit_remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '审核备注',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_dynamic_type` (`dynamic_type`),
    KEY `idx_status` (`status`),
    KEY `idx_create_time` (`create_time`),
    KEY `idx_is_top_hot` (`is_top`, `is_hot`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='动态表';

DROP TABLE IF EXISTS `la_dynamic_comment`;
CREATE TABLE `la_dynamic_comment` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `dynamic_id` INT UNSIGNED NOT NULL COMMENT '动态ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '评论者ID',
    `parent_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '父评论ID(0=一级评论)',
    `reply_user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '回复的用户ID',
    `content` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '评论内容',
    `images` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '评论图片(JSON)',
    `like_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '点赞数',
    `reply_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '回复数',
    `is_top` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否置顶：0=否,1=是',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=待审核,1=正常,2=已删除,3=审核拒绝',
    `ip` VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'IP地址',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_dynamic_id` (`dynamic_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_parent_id` (`parent_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='动态评论表';

DROP TABLE IF EXISTS `la_dynamic_like`;
CREATE TABLE `la_dynamic_like` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `target_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '目标类型：1=动态,2=评论',
    `target_id` INT UNSIGNED NOT NULL COMMENT '目标ID',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_target` (`user_id`, `target_type`, `target_id`),
    KEY `idx_target` (`target_type`, `target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='动态点赞表';

DROP TABLE IF EXISTS `la_dynamic_collect`;
CREATE TABLE `la_dynamic_collect` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `dynamic_id` INT UNSIGNED NOT NULL COMMENT '动态ID',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_dynamic` (`user_id`, `dynamic_id`),
    KEY `idx_dynamic_id` (`dynamic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='动态收藏表';

DROP TABLE IF EXISTS `la_follow`;
CREATE TABLE `la_follow` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '关注者ID',
    `follow_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '关注类型：1=用户,2=工作人员',
    `follow_id` INT UNSIGNED NOT NULL COMMENT '被关注者ID',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_follow` (`user_id`, `follow_type`, `follow_id`),
    KEY `idx_follow` (`follow_type`, `follow_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='关注表';

DROP TABLE IF EXISTS `la_notification`;
CREATE TABLE `la_notification` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '接收者ID',
    `sender_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '发送者ID(0=系统)',
    `notify_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '通知类型：1=系统通知,2=订单通知,3=互动通知,4=活动通知',
    `title` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '标题',
    `content` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '内容',
    `target_type` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '目标类型(order/dynamic/comment等)',
    `target_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '目标ID',
    `is_read` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否已读：0=否,1=是',
    `read_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '阅读时间',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_notify_type` (`notify_type`),
    KEY `idx_is_read` (`is_read`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='消息通知表';

DROP TABLE IF EXISTS `la_coupon`;
CREATE TABLE `la_coupon` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '优惠券名称',
    `coupon_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '类型：1=满减券,2=折扣券,3=立减券',
    `threshold_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '使用门槛金额(0=无门槛)',
    `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '优惠金额/折扣率',
    `max_discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '最大优惠金额(折扣券用)',
    `total_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '发放总量(0=不限)',
    `receive_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '已领取数量',
    `used_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '已使用数量',
    `per_limit` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '每人限领数量',
    `receive_start_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '领取开始时间',
    `receive_end_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '领取结束时间',
    `valid_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '有效期类型：1=固定日期,2=领取后N天',
    `valid_start_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '有效期开始',
    `valid_end_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '有效期结束',
    `valid_days` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '领取后有效天数',
    `use_scope` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '使用范围：1=全部,2=指定分类,3=指定人员',
    `scope_ids` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '适用范围ID(JSON)',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=禁用,1=启用',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`),
    KEY `idx_receive_time` (`receive_start_time`, `receive_end_time`),
    KEY `idx_valid_time` (`valid_start_time`, `valid_end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='优惠券表';

DROP TABLE IF EXISTS `la_user_coupon`;
CREATE TABLE `la_user_coupon` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `coupon_id` INT UNSIGNED NOT NULL COMMENT '优惠券ID',
    `coupon_sn` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '优惠券码',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态：0=未使用,1=已使用,2=已过期',
    `order_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '使用订单ID',
    `use_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '使用时间',
    `valid_start_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '有效期开始',
    `valid_end_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '有效期结束',
    `receive_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '领取时间',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_coupon_sn` (`coupon_sn`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_coupon_id` (`coupon_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='用户优惠券表';


INSERT INTO `la_coupon` (`name`, `coupon_type`, `threshold_amount`, `discount_amount`, `max_discount`, `total_count`, `per_limit`, `valid_type`, `valid_days`, `use_scope`, `status`, `remark`, `create_time`, `update_time`) VALUES
('新人专享券', 1, 1000.00, 100.00, 0.00, 0, 1, 2, 30, 1, 1, '新用户注册赠送', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('满2000减200', 1, 2000.00, 200.00, 0.00, 1000, 1, 1, 0, 1, 1, '限时活动', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('9折优惠券', 2, 500.00, 90.00, 500.00, 500, 2, 2, 15, 1, 1, '会员专享', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


DROP TABLE IF EXISTS `la_order_change`;
CREATE TABLE `la_order_change` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `change_sn` VARCHAR(32) NOT NULL COMMENT '变更单号',
    `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
    `order_sn` VARCHAR(32) NOT NULL COMMENT '订单编号(冗余)',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `change_type` TINYINT UNSIGNED NOT NULL COMMENT '变更类型：1=改期,2=换人,3=加项',
    `change_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '变更状态：0=待审核,1=审核通过,2=审核拒绝,3=已执行,4=已取消',
    
    `old_service_date` DATE DEFAULT NULL COMMENT '原服务日期',
    `new_service_date` DATE DEFAULT NULL COMMENT '新服务日期',
    `old_time_slot` TINYINT UNSIGNED DEFAULT 0 COMMENT '原时间段：0=全天,1=早礼,2=午宴,3=晚宴',
    `new_time_slot` TINYINT UNSIGNED DEFAULT 0 COMMENT '新时间段',
    
    `order_item_id` INT UNSIGNED DEFAULT 0 COMMENT '订单项ID（换人/加项用）',
    `old_staff_id` INT UNSIGNED DEFAULT 0 COMMENT '原工作人员ID',
    `new_staff_id` INT UNSIGNED DEFAULT 0 COMMENT '新工作人员ID',
    `old_staff_name` VARCHAR(50) DEFAULT '' COMMENT '原人员姓名(冗余)',
    `new_staff_name` VARCHAR(50) DEFAULT '' COMMENT '新人员姓名(冗余)',
    `old_schedule_id` INT UNSIGNED DEFAULT 0 COMMENT '原档期ID',
    `new_schedule_id` INT UNSIGNED DEFAULT 0 COMMENT '新档期ID',
    `old_price` DECIMAL(10,2) DEFAULT 0.00 COMMENT '原价格',
    `new_price` DECIMAL(10,2) DEFAULT 0.00 COMMENT '新价格',
    `price_diff` DECIMAL(10,2) DEFAULT 0.00 COMMENT '差价(正数补付,负数退款)',
    `diff_paid` TINYINT UNSIGNED DEFAULT 0 COMMENT '差价是否已处理：0=否,1=是',
    `diff_payment_id` INT UNSIGNED DEFAULT 0 COMMENT '差价支付记录ID',
    `diff_refund_id` INT UNSIGNED DEFAULT 0 COMMENT '差价退款记录ID',
    
    `add_staff_id` INT UNSIGNED DEFAULT 0 COMMENT '新增工作人员ID',
    `add_package_id` INT UNSIGNED DEFAULT 0 COMMENT '新增套餐ID',
    `add_staff_name` VARCHAR(50) DEFAULT '' COMMENT '新增人员姓名',
    `add_package_name` VARCHAR(100) DEFAULT '' COMMENT '新增套餐名称',
    `add_service_date` DATE DEFAULT NULL COMMENT '新增服务日期',
    `add_time_slot` TINYINT UNSIGNED DEFAULT 0 COMMENT '新增时间段',
    `add_price` DECIMAL(10,2) DEFAULT 0.00 COMMENT '新增价格',
    `add_schedule_id` INT UNSIGNED DEFAULT 0 COMMENT '新增档期ID',
    `add_order_item_id` INT UNSIGNED DEFAULT 0 COMMENT '新增订单项ID',
    
    `apply_reason` VARCHAR(500) DEFAULT '' COMMENT '申请原因',
    `user_remark` VARCHAR(500) DEFAULT '' COMMENT '用户备注',
    `audit_admin_id` INT UNSIGNED DEFAULT 0 COMMENT '审核管理员ID',
    `audit_time` INT UNSIGNED DEFAULT 0 COMMENT '审核时间',
    `audit_remark` VARCHAR(500) DEFAULT '' COMMENT '审核备注',
    `reject_reason` VARCHAR(500) DEFAULT '' COMMENT '拒绝原因',
    
    `execute_time` INT UNSIGNED DEFAULT 0 COMMENT '执行时间',
    `execute_admin_id` INT UNSIGNED DEFAULT 0 COMMENT '执行管理员ID',
    
    `attach_images` TEXT COMMENT '附件图片(JSON数组)',
    
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_change_sn` (`change_sn`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_change_type` (`change_type`),
    KEY `idx_change_status` (`change_status`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单变更申请表';

DROP TABLE IF EXISTS `la_order_transfer`;
CREATE TABLE `la_order_transfer` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `transfer_sn` VARCHAR(32) NOT NULL COMMENT '转让单号',
    `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
    `order_sn` VARCHAR(32) NOT NULL COMMENT '订单编号(冗余)',
    
    `from_user_id` INT UNSIGNED NOT NULL COMMENT '转让方用户ID',
    `from_user_name` VARCHAR(50) DEFAULT '' COMMENT '转让方姓名',
    `from_user_mobile` VARCHAR(20) DEFAULT '' COMMENT '转让方电话',
    
    `to_user_id` INT UNSIGNED DEFAULT 0 COMMENT '接收方用户ID（注册用户）',
    `to_user_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '接收方姓名',
    `to_user_mobile` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '接收方电话',
    `to_user_verified` TINYINT UNSIGNED DEFAULT 0 COMMENT '接收方是否验证：0=否,1=是',
    
    `transfer_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '转让状态：0=待审核,1=待接收,2=接收确认,3=转让完成,4=已拒绝,5=已取消',
    `transfer_reason` VARCHAR(500) DEFAULT '' COMMENT '转让原因',
    `transfer_fee` DECIMAL(10,2) DEFAULT 0.00 COMMENT '转让手续费',
    `fee_paid` TINYINT UNSIGNED DEFAULT 0 COMMENT '手续费是否支付：0=否,1=是',
    
    `audit_admin_id` INT UNSIGNED DEFAULT 0 COMMENT '审核管理员ID',
    `audit_time` INT UNSIGNED DEFAULT 0 COMMENT '审核时间',
    `audit_remark` VARCHAR(500) DEFAULT '' COMMENT '审核备注',
    `reject_reason` VARCHAR(500) DEFAULT '' COMMENT '拒绝原因',
    
    `accept_code` VARCHAR(10) DEFAULT '' COMMENT '接收验证码',
    `accept_code_expire` INT UNSIGNED DEFAULT 0 COMMENT '验证码过期时间',
    `accept_code_send_time` INT UNSIGNED DEFAULT 0 COMMENT '验证码发送时间',
    `accept_code_send_count` TINYINT UNSIGNED DEFAULT 0 COMMENT '验证码发送次数',
    `accept_time` INT UNSIGNED DEFAULT 0 COMMENT '接收时间',
    
    `complete_time` INT UNSIGNED DEFAULT 0 COMMENT '完成时间',
    `complete_admin_id` INT UNSIGNED DEFAULT 0 COMMENT '完成操作管理员ID',
    
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_transfer_sn` (`transfer_sn`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_from_user` (`from_user_id`),
    KEY `idx_to_user` (`to_user_id`),
    KEY `idx_to_mobile` (`to_user_mobile`),
    KEY `idx_transfer_status` (`transfer_status`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单转让表';

DROP TABLE IF EXISTS `la_order_pause`;
CREATE TABLE `la_order_pause` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `pause_sn` VARCHAR(32) NOT NULL COMMENT '暂停单号',
    `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
    `order_sn` VARCHAR(32) NOT NULL COMMENT '订单编号(冗余)',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    
    `pause_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '暂停状态：0=待审核,1=暂停中,2=已恢复,3=已拒绝,4=已取消',
    `pause_type` TINYINT UNSIGNED NOT NULL COMMENT '暂停类型：1=疫情,2=突发事件,3=个人原因,4=其他',
    `pause_reason` VARCHAR(500) NOT NULL COMMENT '暂停原因',
    
    `pause_start_date` DATE DEFAULT NULL COMMENT '暂停开始日期',
    `pause_end_date` DATE DEFAULT NULL COMMENT '暂停结束日期(预计)',
    `pause_days` INT UNSIGNED DEFAULT 0 COMMENT '暂停天数',
    `original_service_date` DATE DEFAULT NULL COMMENT '原服务日期',
    
    `audit_admin_id` INT UNSIGNED DEFAULT 0 COMMENT '审核管理员ID',
    `audit_time` INT UNSIGNED DEFAULT 0 COMMENT '审核时间',
    `audit_remark` VARCHAR(500) DEFAULT '' COMMENT '审核备注',
    `reject_reason` VARCHAR(500) DEFAULT '' COMMENT '拒绝原因',
    
    `resume_time` INT UNSIGNED DEFAULT 0 COMMENT '恢复时间',
    `resume_admin_id` INT UNSIGNED DEFAULT 0 COMMENT '恢复操作管理员ID',
    `resume_remark` VARCHAR(500) DEFAULT '' COMMENT '恢复备注',
    `actual_pause_days` INT UNSIGNED DEFAULT 0 COMMENT '实际暂停天数',
    `new_service_date` DATE DEFAULT NULL COMMENT '恢复后新服务日期',
    
    `remind_before_days` INT UNSIGNED DEFAULT 3 COMMENT '提前提醒天数',
    `reminded` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否已提醒：0=否,1=是',
    `remind_time` INT UNSIGNED DEFAULT 0 COMMENT '提醒时间',
    
    `proof_images` TEXT COMMENT '证明材料图片(JSON数组)',
    
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_pause_sn` (`pause_sn`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_pause_status` (`pause_status`),
    KEY `idx_pause_end_date` (`pause_end_date`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单暂停表';

DROP TABLE IF EXISTS `la_order_change_log`;
CREATE TABLE `la_order_change_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
    `related_type` TINYINT UNSIGNED NOT NULL COMMENT '关联类型：1=变更,2=转让,3=暂停',
    `related_id` INT UNSIGNED NOT NULL COMMENT '关联记录ID',
    `operator_type` TINYINT UNSIGNED NOT NULL COMMENT '操作者类型：1=用户,2=管理员,3=系统',
    `operator_id` INT UNSIGNED DEFAULT 0 COMMENT '操作者ID',
    `operator_name` VARCHAR(50) DEFAULT '' COMMENT '操作者名称',
    `action` VARCHAR(50) NOT NULL COMMENT '操作动作',
    `before_status` TINYINT UNSIGNED DEFAULT 0 COMMENT '操作前状态',
    `after_status` TINYINT UNSIGNED DEFAULT 0 COMMENT '操作后状态',
    `before_data` TEXT COMMENT '变更前数据(JSON)',
    `after_data` TEXT COMMENT '变更后数据(JSON)',
    `content` VARCHAR(500) DEFAULT '' COMMENT '日志内容',
    `ip` VARCHAR(50) DEFAULT '' COMMENT 'IP地址',
    `user_agent` VARCHAR(500) DEFAULT '' COMMENT '用户代理',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_related` (`related_type`, `related_id`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单变更日志表';

ALTER TABLE `la_order` 
ADD COLUMN `is_paused` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否暂停：0=否,1=是' AFTER `is_reviewed`,
ADD COLUMN `pause_id` INT UNSIGNED DEFAULT 0 COMMENT '关联暂停记录ID' AFTER `is_paused`,
ADD COLUMN `has_changed` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否有变更记录：0=否,1=是' AFTER `pause_id`,
ADD COLUMN `change_count` INT UNSIGNED DEFAULT 0 COMMENT '变更次数' AFTER `has_changed`,
ADD COLUMN `is_transferred` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否已转让：0=否,1=是' AFTER `change_count`,
ADD COLUMN `transfer_id` INT UNSIGNED DEFAULT 0 COMMENT '关联转让记录ID' AFTER `is_transferred`,
ADD COLUMN `original_user_id` INT UNSIGNED DEFAULT 0 COMMENT '原用户ID(转让前)' AFTER `transfer_id`;

ALTER TABLE `la_order`
ADD KEY `idx_is_paused` (`is_paused`),
ADD KEY `idx_is_transferred` (`is_transferred`);

ALTER TABLE `la_order_item`
ADD COLUMN `is_changed` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否变更过：0=否,1=是' AFTER `item_status`,
ADD COLUMN `change_id` INT UNSIGNED DEFAULT 0 COMMENT '关联变更记录ID' AFTER `is_changed`,
ADD COLUMN `original_staff_id` INT UNSIGNED DEFAULT 0 COMMENT '原工作人员ID(换人前)' AFTER `change_id`,
ADD COLUMN `original_price` DECIMAL(10,2) DEFAULT 0.00 COMMENT '原价格(换人前)' AFTER `original_staff_id`;

INSERT INTO `la_dict_data`
(`type_id`, `name`, `value`, `type_value`, `sort`, `status`, `remark`, `create_time`, `update_time`)
VALUES
((SELECT id FROM `la_dict_type` WHERE `type` = 'system_config' LIMIT 1),
 '疫情', '1', '1', 1, 1, '订单暂停类型-疫情', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
((SELECT id FROM `la_dict_type` WHERE `type` = 'system_config' LIMIT 1),
 '突发事件', '2', '2', 2, 1, '订单暂停类型-突发事件', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
((SELECT id FROM `la_dict_type` WHERE `type` = 'system_config' LIMIT 1),
 '个人原因', '3', '3', 3, 1, '订单暂停类型-个人原因', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
((SELECT id FROM `la_dict_type` WHERE `type` = 'system_config' LIMIT 1),
 '其他', '4', '4', 4, 1, '订单暂停类型-其他', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


DROP TABLE IF EXISTS `la_review`;
CREATE TABLE `la_review` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '评价ID',
    `order_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单ID',
    `order_item_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单项ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID',
    `score` tinyint(1) UNSIGNED NOT NULL DEFAULT 5 COMMENT '综合评分 1-5星',
    `score_service` tinyint(1) UNSIGNED NOT NULL DEFAULT 5 COMMENT '服务态度评分 1-5星',
    `score_professional` tinyint(1) UNSIGNED NOT NULL DEFAULT 5 COMMENT '专业水平评分 1-5星',
    `score_punctual` tinyint(1) UNSIGNED NOT NULL DEFAULT 5 COMMENT '时间守约评分 1-5星',
    `score_effect` tinyint(1) UNSIGNED NOT NULL DEFAULT 5 COMMENT '整体效果评分 1-5星',
    `content` text COMMENT '评价内容',
    `images` text COMMENT '评价图片 JSON数组',
    `video` varchar(500) NOT NULL DEFAULT '' COMMENT '评价视频URL',
    `video_cover` varchar(500) NOT NULL DEFAULT '' COMMENT '视频封面图URL',
    `is_anonymous` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否匿名 0否 1是',
    `is_top` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否置顶 0否 1是',
    `top_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '置顶时间',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态 0待审核 1已通过 2已拒绝',
    `reject_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '拒绝原因',
    `review_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '评价类型 1文字 2图文 3视频',
    `reward_points` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '奖励积分',
    `is_rewarded` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否已发放奖励 0否 1是',
    `reward_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '奖励发放时间',
    `like_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '点赞数',
    `reply_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '回复数',
    `is_show` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否显示 0隐藏 1显示',
    `admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核人ID',
    `audit_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核时间',
    `service_date` date DEFAULT NULL COMMENT '服务日期（冗余字段便于查询）',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` int(11) UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_status` (`status`),
    KEY `idx_score` (`score`),
    KEY `idx_is_top` (`is_top`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='评价主表';

DROP TABLE IF EXISTS `la_review_tag`;
CREATE TABLE `la_review_tag` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '标签ID',
    `name` varchar(50) NOT NULL DEFAULT '' COMMENT '标签名称',
    `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '标签类型 1好评 2中评 3差评',
    `icon` varchar(255) NOT NULL DEFAULT '' COMMENT '标签图标',
    `color` varchar(20) NOT NULL DEFAULT '' COMMENT '标签颜色',
    `sort` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
    `use_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '使用次数',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态 0禁用 1启用',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` int(11) UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_type` (`type`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='评价标签表';

DROP TABLE IF EXISTS `la_review_tag_relation`;
CREATE TABLE `la_review_tag_relation` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `review_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '评价ID',
    `tag_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '标签ID',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_review_tag` (`review_id`, `tag_id`),
    KEY `idx_tag_id` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='评价标签关联表';

DROP TABLE IF EXISTS `la_review_reply`;
CREATE TABLE `la_review_reply` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '回复ID',
    `review_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '评价ID',
    `parent_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '父回复ID（用于楼中楼）',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID（追评时使用）',
    `admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '管理员ID（商家回复时使用）',
    `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID（人员回复时使用）',
    `reply_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '回复类型 1用户追评 2商家回复 3人员回复',
    `content` text COMMENT '回复内容',
    `images` text COMMENT '回复图片 JSON数组',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态 0待审核 1已通过 2已拒绝',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` int(11) UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_review_id` (`review_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_reply_type` (`reply_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='评价回复表';

DROP TABLE IF EXISTS `la_review_like`;
CREATE TABLE `la_review_like` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `review_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '评价ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_review_user` (`review_id`, `user_id`),
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='评价点赞表';

DROP TABLE IF EXISTS `la_review_appeal`;
CREATE TABLE `la_review_appeal` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '申诉ID',
    `review_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '评价ID',
    `appeal_user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '申诉人ID（用户）',
    `appeal_staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '申诉人ID（服务人员）',
    `appeal_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '申诉类型 1恶意差评 2虚假评价 3侵犯隐私 4其他',
    `appeal_reason` text COMMENT '申诉原因',
    `evidence_images` text COMMENT '证据图片 JSON数组',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态 0待处理 1已通过 2已驳回',
    `handle_admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理人ID',
    `handle_result` text COMMENT '处理结果',
    `handle_action` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理动作 0无 1删除评价 2隐藏评价 3警告用户',
    `handle_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理时间',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_review_id` (`review_id`),
    KEY `idx_status` (`status`),
    KEY `idx_appeal_user_id` (`appeal_user_id`),
    KEY `idx_appeal_staff_id` (`appeal_staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='评价申诉表';

DROP TABLE IF EXISTS `la_review_reward_config`;
CREATE TABLE `la_review_reward_config` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `reward_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '奖励类型 1文字评价 2图文评价 3视频评价',
    `reward_points` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '奖励积分',
    `min_content_length` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '最少字数要求',
    `min_images` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '最少图片数量',
    `min_video_duration` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '最短视频时长（秒）',
    `extra_points_for_good` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '好评额外奖励积分',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态 0禁用 1启用',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_reward_type` (`reward_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='评价奖励配置表';

DROP TABLE IF EXISTS `la_review_share_reward`;
CREATE TABLE `la_review_share_reward` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `review_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '评价ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `share_platform` varchar(50) NOT NULL DEFAULT '' COMMENT '分享平台 wechat/moments/weibo等',
    `share_url` varchar(500) NOT NULL DEFAULT '' COMMENT '分享链接',
    `reward_points` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '奖励积分',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态 0待审核 1已通过 2已拒绝',
    `verify_image` varchar(500) NOT NULL DEFAULT '' COMMENT '分享截图凭证',
    `admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核人ID',
    `audit_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核时间',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_review_id` (`review_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='晒单奖励记录表';

DROP TABLE IF EXISTS `la_staff_review_stats`;
CREATE TABLE `la_staff_review_stats` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID',
    `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '总评价数',
    `good_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '好评数（4-5星）',
    `medium_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '中评数（3星）',
    `bad_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '差评数（1-2星）',
    `image_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '有图评价数',
    `video_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '有视频评价数',
    `avg_score` decimal(3,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '平均综合评分',
    `avg_score_service` decimal(3,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '平均服务态度评分',
    `avg_score_professional` decimal(3,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '平均专业水平评分',
    `avg_score_punctual` decimal(3,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '平均时间守约评分',
    `avg_score_effect` decimal(3,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '平均整体效果评分',
    `good_rate` decimal(5,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '好评率',
    `reply_rate` decimal(5,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '回复率',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='服务人员评价统计表';

DROP TABLE IF EXISTS `la_sensitive_word`;
CREATE TABLE `la_sensitive_word` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `word` varchar(100) NOT NULL DEFAULT '' COMMENT '敏感词',
    `replace_word` varchar(100) NOT NULL DEFAULT '***' COMMENT '替换词',
    `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '类型 1广告 2违法 3政治 4色情 5其他',
    `level` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '级别 1警告 2禁止',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态 0禁用 1启用',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_word` (`word`),
    KEY `idx_type` (`type`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='敏感词表';

INSERT INTO `la_review_tag` (`name`, `type`, `icon`, `color`, `sort`, `status`, `create_time`, `update_time`) VALUES
('服务热情', 1, '', '#52c41a', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('专业细致', 1, '', '#52c41a', 2, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('准时守约', 1, '', '#52c41a', 3, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('效果满意', 1, '', '#52c41a', 4, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('沟通顺畅', 1, '', '#52c41a', 5, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('性价比高', 1, '', '#52c41a', 6, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('耐心负责', 1, '', '#52c41a', 7, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('创意独特', 1, '', '#52c41a', 8, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('一般般', 2, '', '#faad14', 10, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('有待改进', 2, '', '#faad14', 11, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('服务冷淡', 3, '', '#ff4d4f', 20, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('不够专业', 3, '', '#ff4d4f', 21, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('迟到早退', 3, '', '#ff4d4f', 22, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('效果不佳', 3, '', '#ff4d4f', 23, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

INSERT INTO `la_review_reward_config` (`reward_type`, `reward_points`, `min_content_length`, `min_images`, `min_video_duration`, `extra_points_for_good`, `status`, `create_time`, `update_time`) VALUES
(1, 10, 20, 0, 0, 5, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(2, 30, 20, 3, 0, 10, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(3, 50, 10, 0, 15, 20, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


DROP TABLE IF EXISTS `la_financial_flow`;
CREATE TABLE `la_financial_flow` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `flow_sn` VARCHAR(32) NOT NULL COMMENT '流水编号',
    `flow_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '流水类型：1=收入,2=支出,3=退款,4=分账,5=提现',
    `biz_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '业务类型：1=订单支付,2=订单退款,3=人员结算,4=平台抽成,5=其他',
    `biz_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '业务ID(订单/退款等)',
    `biz_sn` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '业务编号',
    `order_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联用户ID',
    `staff_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联服务人员ID',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '金额(正值)',
    `direction` TINYINT NOT NULL DEFAULT 1 COMMENT '方向：1=收入(+),-1=支出(-)',
    `balance_before` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '变动前余额',
    `balance_after` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '变动后余额',
    `pay_way` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付方式：0=系统,1=微信,2=支付宝,3=余额,4=线下',
    `transaction_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '第三方交易号',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `operator_type` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作者类型：0=系统,1=用户,2=管理员',
    `operator_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作者ID',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_flow_sn` (`flow_sn`),
    KEY `idx_flow_type` (`flow_type`),
    KEY `idx_biz_type` (`biz_type`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='资金流水表';

DROP TABLE IF EXISTS `la_cost_record`;
CREATE TABLE `la_cost_record` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `cost_sn` VARCHAR(32) NOT NULL COMMENT '成本编号',
    `order_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
    `order_item_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单项ID',
    `staff_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联服务人员ID',
    `cost_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '成本类型：1=人工成本,2=物料成本,3=交通成本,4=设备成本,5=其他成本',
    `cost_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '成本名称',
    `cost_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '成本金额',
    `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '单价',
    `quantity` DECIMAL(10,2) NOT NULL DEFAULT 1.00 COMMENT '数量',
    `service_date` DATE DEFAULT NULL COMMENT '服务日期',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=待确认,1=已确认,2=已取消',
    `confirm_admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '确认管理员ID',
    `confirm_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '确认时间',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_cost_sn` (`cost_sn`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_cost_type` (`cost_type`),
    KEY `idx_service_date` (`service_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='成本记录表';

DROP TABLE IF EXISTS `la_staff_settlement`;
CREATE TABLE `la_staff_settlement` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `settlement_sn` VARCHAR(32) NOT NULL COMMENT '结算编号',
    `batch_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '结算批次ID',
    `staff_id` INT UNSIGNED NOT NULL COMMENT '服务人员ID',
    `order_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单ID',
    `order_item_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单项ID',
    `service_date` DATE DEFAULT NULL COMMENT '服务日期',
    `order_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '订单金额',
    `settlement_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT '结算比例(%)',
    `settlement_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '结算金额',
    `platform_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '平台抽成',
    `cost_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '扣除成本',
    `actual_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '实际结算金额',
    `settlement_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '结算类型：1=自动结算,2=手动结算',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态：0=待结算,1=已结算,2=已取消,3=结算失败',
    `settle_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '结算时间',
    `settle_way` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '结算方式：1=余额,2=银行卡,3=微信,4=支付宝',
    `transaction_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '交易号',
    `fail_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '失败原因',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_settlement_sn` (`settlement_sn`),
    KEY `idx_batch_id` (`batch_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_status` (`status`),
    KEY `idx_service_date` (`service_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='服务人员结算表';

DROP TABLE IF EXISTS `la_settlement_batch`;
CREATE TABLE `la_settlement_batch` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `batch_sn` VARCHAR(32) NOT NULL COMMENT '批次编号',
    `batch_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '批次名称',
    `settle_start_date` DATE NOT NULL COMMENT '结算开始日期',
    `settle_end_date` DATE NOT NULL COMMENT '结算结束日期',
    `total_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '结算总笔数',
    `success_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '成功笔数',
    `fail_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '失败笔数',
    `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '结算总金额',
    `success_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '成功金额',
    `fail_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '失败金额',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态：0=待审核,1=审核通过,2=处理中,3=已完成,4=已取消',
    `audit_admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核管理员ID',
    `audit_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核时间',
    `audit_remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '审核备注',
    `execute_admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '执行管理员ID',
    `execute_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '执行时间',
    `complete_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '完成时间',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_batch_sn` (`batch_sn`),
    KEY `idx_status` (`status`),
    KEY `idx_settle_date` (`settle_start_date`, `settle_end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='结算批次表';

DROP TABLE IF EXISTS `la_financial_daily`;
CREATE TABLE `la_financial_daily` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `report_date` DATE NOT NULL COMMENT '报表日期',
    `order_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单数量',
    `paid_order_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付订单数',
    `refund_order_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '退款订单数',
    `total_income` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '总收入',
    `deposit_income` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '定金收入',
    `balance_income` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '尾款收入',
    `wechat_income` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '微信支付收入',
    `alipay_income` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '支付宝收入',
    `balance_pay_income` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '余额支付收入',
    `offline_income` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '线下收入',
    `total_refund` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '总退款',
    `total_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '总成本',
    `staff_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '人工成本',
    `material_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '物料成本',
    `other_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '其他成本',
    `total_settlement` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '人员结算总额',
    `platform_income` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '平台收入',
    `gross_profit` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '毛利润',
    `net_profit` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '净利润',
    `profit_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT '利润率(%)',
    `new_user_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '新用户数',
    `active_user_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '活跃用户数',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_report_date` (`report_date`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='财务日报表';

DROP TABLE IF EXISTS `la_financial_monthly`;
CREATE TABLE `la_financial_monthly` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `report_year` SMALLINT UNSIGNED NOT NULL COMMENT '报表年份',
    `report_month` TINYINT UNSIGNED NOT NULL COMMENT '报表月份',
    `order_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单数量',
    `paid_order_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付订单数',
    `complete_order_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '完成订单数',
    `refund_order_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '退款订单数',
    `total_income` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '总收入',
    `wechat_income` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '微信支付收入',
    `alipay_income` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '支付宝收入',
    `balance_pay_income` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '余额支付收入',
    `offline_income` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '线下收入',
    `total_refund` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '总退款',
    `total_cost` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '总成本',
    `staff_cost` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '人工成本',
    `material_cost` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '物料成本',
    `other_cost` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '其他成本',
    `total_settlement` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '人员结算总额',
    `platform_income` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '平台收入',
    `gross_profit` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '毛利润',
    `net_profit` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '净利润',
    `profit_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT '利润率(%)',
    `avg_order_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '平均订单金额',
    `conversion_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT '转化率(%)',
    `yoy_growth_rate` DECIMAL(6,2) NOT NULL DEFAULT 0.00 COMMENT '同比增长率(%)',
    `mom_growth_rate` DECIMAL(6,2) NOT NULL DEFAULT 0.00 COMMENT '环比增长率(%)',
    `new_user_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '新用户数',
    `active_user_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '活跃用户数',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_report_year_month` (`report_year`, `report_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='财务月报表';

DROP TABLE IF EXISTS `la_invoice`;
CREATE TABLE `la_invoice` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `invoice_sn` VARCHAR(32) NOT NULL COMMENT '发票编号',
    `invoice_no` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '发票号码',
    `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `invoice_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '发票类型：1=电子普票,2=电子专票,3=纸质普票,4=纸质专票',
    `title_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '抬头类型：1=个人,2=企业',
    `invoice_title` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '发票抬头',
    `tax_no` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '税号',
    `bank_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '开户行',
    `bank_account` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '银行账号',
    `company_address` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '企业地址',
    `company_phone` VARCHAR(30) NOT NULL DEFAULT '' COMMENT '企业电话',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '发票金额',
    `email` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '接收邮箱',
    `receiver_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '收件人(纸质)',
    `receiver_phone` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '收件电话(纸质)',
    `receiver_address` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '收件地址(纸质)',
    `invoice_url` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '电子发票下载地址',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态：0=待开票,1=开票中,2=已开票,3=开票失败,4=已作废',
    `issue_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '开票时间',
    `issue_admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '开票管理员ID',
    `fail_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '失败原因',
    `void_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '作废原因',
    `void_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '作废时间',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_invoice_sn` (`invoice_sn`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='发票记录表';

DROP TABLE IF EXISTS `la_staff_settlement_config`;
CREATE TABLE `la_staff_settlement_config` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `staff_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID(0=默认配置)',
    `category_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务分类ID(0=全部分类)',
    `settlement_rate` DECIMAL(5,2) NOT NULL DEFAULT 70.00 COMMENT '结算比例(%)',
    `min_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '最低结算金额',
    `settle_cycle` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '结算周期：1=月结,2=周结,3=单笔结',
    `settle_delay_days` INT UNSIGNED NOT NULL DEFAULT 7 COMMENT '结算延迟天数(服务完成后)',
    `is_default` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否默认配置：0=否,1=是',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=禁用,1=启用',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_category_id` (`category_id`),
    KEY `idx_is_default` (`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='服务人员结算配置表';

DROP TABLE IF EXISTS `la_financial_reconciliation`;
CREATE TABLE `la_financial_reconciliation` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `reconcile_sn` VARCHAR(32) NOT NULL COMMENT '对账编号',
    `reconcile_date` DATE NOT NULL COMMENT '对账日期',
    `pay_channel` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '支付渠道：1=微信,2=支付宝',
    `system_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '系统交易笔数',
    `system_amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '系统交易金额',
    `channel_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '渠道交易笔数',
    `channel_amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '渠道交易金额',
    `diff_count` INT NOT NULL DEFAULT 0 COMMENT '差异笔数',
    `diff_amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT '差异金额',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态：0=待对账,1=对账中,2=已平账,3=有差异,4=已处理',
    `bill_file` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '账单文件地址',
    `result_file` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '对账结果文件',
    `handle_admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理管理员ID',
    `handle_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理时间',
    `handle_remark` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '处理备注',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_reconcile_sn` (`reconcile_sn`),
    UNIQUE KEY `uk_date_channel` (`reconcile_date`, `pay_channel`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='财务对账记录表';

INSERT INTO `la_staff_settlement_config` (`staff_id`, `category_id`, `settlement_rate`, `min_amount`, `settle_cycle`, `settle_delay_days`, `is_default`, `status`, `remark`, `create_time`, `update_time`) VALUES
(0, 0, 70.00, 100.00, 1, 7, 1, 1, '默认结算配置：70%分成，月结，服务完成后7天可结算', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


DROP TABLE IF EXISTS `la_timeline_template`;
CREATE TABLE `la_timeline_template` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `template_name` VARCHAR(100) NOT NULL COMMENT '模板名称',
    `template_desc` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '模板描述',
    `service_type` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '适用服务类型：0=通用,1=摄影,2=化妆,3=全套服务',
    `tasks` TEXT NOT NULL COMMENT '任务配置JSON [{title, desc, days_before, type}]',
    `is_default` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否默认模板：0=否,1=是',
    `is_enabled` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否启用：0=禁用,1=启用',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序权重',
    `use_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '使用次数',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_service_type` (`service_type`),
    KEY `idx_is_default` (`is_default`),
    KEY `idx_is_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='时间轴模板表';

DROP TABLE IF EXISTS `la_order_timeline`;
CREATE TABLE `la_order_timeline` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `template_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '来源模板ID',
    `wedding_date` DATE NOT NULL COMMENT '婚期日期',
    `task_title` VARCHAR(100) NOT NULL COMMENT '任务标题',
    `task_desc` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '任务描述',
    `task_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '任务类型：1=准备物料,2=确认事项,3=沟通联系,4=现场安排,5=其他',
    `days_before` INT NOT NULL DEFAULT 0 COMMENT '婚期前N天(0=当天,负数=婚期后)',
    `trigger_date` DATE NOT NULL COMMENT '实际触发日期',
    `trigger_time` TIME DEFAULT NULL COMMENT '触发时间(可选)',
    `is_completed` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否完成：0=未完成,1=已完成',
    `complete_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '完成时间',
    `complete_user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '完成人ID',
    `complete_remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '完成备注',
    `is_reminded` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否已提醒：0=未提醒,1=已提醒',
    `remind_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '提醒时间',
    `is_system` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否系统任务：0=手动添加,1=系统生成',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序权重',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_wedding_date` (`wedding_date`),
    KEY `idx_trigger_date` (`trigger_date`),
    KEY `idx_is_completed` (`is_completed`),
    KEY `idx_task_type` (`task_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单时间轴任务表';

INSERT INTO `la_timeline_template` (`template_name`, `template_desc`, `service_type`, `tasks`, `is_default`, `is_enabled`, `sort`, `create_time`, `update_time`) VALUES
('通用婚礼筹备模板', '适用于所有类型婚礼服务的标准筹备流程', 0, '[{\"title\":\"确认服务细节\",\"desc\":\"与工作人员确认服务内容、时间、地点等细节\",\"days_before\":30,\"type\":2},{\"title\":\"试妆预约\",\"desc\":\"预约试妆时间，确认妆容造型\",\"days_before\":21,\"type\":2},{\"title\":\"最终方案确认\",\"desc\":\"确认最终服务方案，签署补充协议\",\"days_before\":14,\"type\":2},{\"title\":\"物料清单核对\",\"desc\":\"核对婚礼当天所需物料清单\",\"days_before\":7,\"type\":1},{\"title\":\"与工作人员确认集合时间\",\"desc\":\"确认婚礼当天集合时间、地点和联系方式\",\"days_before\":3,\"type\":3},{\"title\":\"婚礼前一天确认\",\"desc\":\"最终确认所有细节，确保万无一失\",\"days_before\":1,\"type\":2},{\"title\":\"婚礼当天\",\"desc\":\"婚礼当天服务开始\",\"days_before\":0,\"type\":4}]', 1, 1, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('摄影服务专属模板', '适用于婚礼摄影、婚纱照等摄影服务', 1, '[{\"title\":\"确认拍摄风格\",\"desc\":\"与摄影师沟通确认拍摄风格和场景\",\"days_before\":30,\"type\":2},{\"title\":\"场地踩点\",\"desc\":\"提前踩点拍摄场地，规划拍摄路线\",\"days_before\":14,\"type\":2},{\"title\":\"确认拍摄服装\",\"desc\":\"确认拍摄当天的服装和配饰\",\"days_before\":7,\"type\":1},{\"title\":\"设备检查\",\"desc\":\"摄影师检查设备，确保万无一失\",\"days_before\":3,\"type\":1},{\"title\":\"拍摄当天\",\"desc\":\"拍摄服务开始\",\"days_before\":0,\"type\":4},{\"title\":\"初片交付\",\"desc\":\"交付初选照片供客户挑选\",\"days_before\":-7,\"type\":5},{\"title\":\"精修交付\",\"desc\":\"交付精修照片\",\"days_before\":-21,\"type\":5}]', 0, 1, 90, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('化妆服务专属模板', '适用于新娘化妆、跟妆等化妆服务', 2, '[{\"title\":\"确认妆容风格\",\"desc\":\"与化妆师沟通确认妆容风格\",\"days_before\":30,\"type\":2},{\"title\":\"试妆\",\"desc\":\"进行试妆，调整妆容细节\",\"days_before\":14,\"type\":2},{\"title\":\"确认化妆品\",\"desc\":\"确认婚礼当天使用的化妆品\",\"days_before\":7,\"type\":1},{\"title\":\"皮肤护理提醒\",\"desc\":\"婚前皮肤护理，保持最佳状态\",\"days_before\":3,\"type\":2},{\"title\":\"化妆当天\",\"desc\":\"婚礼当天化妆服务开始\",\"days_before\":0,\"type\":4}]', 0, 1, 80, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


DROP TABLE IF EXISTS `la_sales_advisor`;
CREATE TABLE `la_sales_advisor` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联管理员ID',
    `advisor_name` VARCHAR(50) NOT NULL COMMENT '顾问姓名',
    `avatar` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '头像',
    `mobile` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '手机号',
    `wechat` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '企业微信号',
    `email` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '邮箱',
    `areas` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '负责区域(JSON数组)',
    `specialties` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '擅长服务类型(JSON数组)',
    `max_customer_count` INT UNSIGNED NOT NULL DEFAULT 100 COMMENT '最大客户数',
    `current_customer_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '当前客户数',
    `total_order_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '累计成交订单数',
    `total_order_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '累计成交金额',
    `conversion_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT '转化率(%)',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=离职,1=正常,2=休假',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序权重(自动分配时使用)',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_admin_id` (`admin_id`),
    KEY `idx_status` (`status`),
    KEY `idx_current_customer_count` (`current_customer_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='销售顾问表';

DROP TABLE IF EXISTS `la_customer`;
CREATE TABLE `la_customer` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联用户ID(0=潜在客户)',
    `customer_name` VARCHAR(50) NOT NULL COMMENT '客户姓名',
    `customer_mobile` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '手机号',
    `customer_wechat` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '微信号',
    `gender` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '性别：0=未知,1=男,2=女',
    `age` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '年龄',
    `city` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '所在城市',
    `district` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '所在区域',
    `intention_level` CHAR(1) NOT NULL DEFAULT 'D' COMMENT '意向等级：A=高意向,B=中意向,C=低意向,D=待跟进',
    `intention_score` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '意向评分(0-100)',
    `wedding_date` DATE DEFAULT NULL COMMENT '计划婚期',
    `wedding_venue` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '婚礼场地',
    `wedding_budget` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '预算金额',
    `budget_range` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '预算范围(如1-2万)',
    `service_needs` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '服务需求(JSON数组)',
    `source_channel` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '来源渠道：1=小程序,2=H5,3=线下,4=转介绍,5=广告,6=其他',
    `source_detail` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '来源详情',
    `tags` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '客户标签(JSON数组)',
    `customer_status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '客户状态：1=新客户,2=跟进中,3=已签单,4=已流失,5=已完成',
    `loss_reason` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '流失原因',
    `loss_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '流失时间',
    `advisor_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '分配销售顾问ID',
    `assign_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '分配时间',
    `first_contact_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '首次联系时间',
    `last_follow_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后跟进时间',
    `next_follow_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '下次跟进时间',
    `follow_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '跟进次数',
    `order_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '成交订单数',
    `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '累计消费金额',
    `remark` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '备注',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_customer_mobile` (`customer_mobile`),
    KEY `idx_intention_level` (`intention_level`),
    KEY `idx_customer_status` (`customer_status`),
    KEY `idx_advisor_id` (`advisor_id`),
    KEY `idx_wedding_date` (`wedding_date`),
    KEY `idx_last_follow_time` (`last_follow_time`),
    KEY `idx_next_follow_time` (`next_follow_time`),
    KEY `idx_source_channel` (`source_channel`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='客户信息表';

DROP TABLE IF EXISTS `la_follow_record`;
CREATE TABLE `la_follow_record` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `customer_id` INT UNSIGNED NOT NULL COMMENT '客户ID',
    `advisor_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '销售顾问ID',
    `admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作管理员ID',
    `follow_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '跟进方式：1=电话,2=微信,3=到店,4=试妆,5=看样片,6=上门,7=其他',
    `follow_content` TEXT NOT NULL COMMENT '跟进内容',
    `follow_result` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '跟进结果：1=继续跟进,2=意向提升,3=意向下降,4=已成交,5=已流失',
    `intention_before` CHAR(1) NOT NULL DEFAULT '' COMMENT '跟进前意向等级',
    `intention_after` CHAR(1) NOT NULL DEFAULT '' COMMENT '跟进后意向等级',
    `duration` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '沟通时长(分钟)',
    `next_follow_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '下次跟进时间',
    `next_follow_content` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '下次跟进计划',
    `attachments` TEXT COMMENT '附件(图片、文件JSON)',
    `is_important` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否重要：0=否,1=是',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_customer_id` (`customer_id`),
    KEY `idx_advisor_id` (`advisor_id`),
    KEY `idx_follow_type` (`follow_type`),
    KEY `idx_follow_result` (`follow_result`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='跟进记录表';

DROP TABLE IF EXISTS `la_customer_assign_log`;
CREATE TABLE `la_customer_assign_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `customer_id` INT UNSIGNED NOT NULL COMMENT '客户ID',
    `from_advisor_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '原顾问ID(0=首次分配)',
    `to_advisor_id` INT UNSIGNED NOT NULL COMMENT '新顾问ID',
    `assign_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '分配类型：1=自动分配,2=手动分配,3=转交,4=回收重分',
    `assign_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '分配原因',
    `admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作管理员ID(0=系统)',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_customer_id` (`customer_id`),
    KEY `idx_from_advisor_id` (`from_advisor_id`),
    KEY `idx_to_advisor_id` (`to_advisor_id`),
    KEY `idx_assign_type` (`assign_type`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='客户分配日志表';

DROP TABLE IF EXISTS `la_customer_loss_warning`;
CREATE TABLE `la_customer_loss_warning` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `customer_id` INT UNSIGNED NOT NULL COMMENT '客户ID',
    `advisor_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '销售顾问ID',
    `warning_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '预警类型：1=长期未跟进,2=意向下降,3=竞品流失,4=预算不足,5=其他',
    `warning_level` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '预警等级：1=低,2=中,3=高',
    `warning_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '预警原因',
    `days_no_follow` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '未跟进天数',
    `warning_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理状态：0=待处理,1=已处理,2=已忽略',
    `handle_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理时间',
    `handle_remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '处理备注',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_customer_id` (`customer_id`),
    KEY `idx_advisor_id` (`advisor_id`),
    KEY `idx_warning_type` (`warning_type`),
    KEY `idx_warning_status` (`warning_status`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='客户流失预警表';


DROP TABLE IF EXISTS `la_after_sale_ticket`;
CREATE TABLE `la_after_sale_ticket` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '工单ID',
    `ticket_sn` varchar(32) NOT NULL DEFAULT '' COMMENT '工单编号',
    `order_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '工单类型 1投诉 2咨询 3售后 4建议 5其他',
    `priority` tinyint(1) UNSIGNED NOT NULL DEFAULT 2 COMMENT '优先级 1低 2中 3高 4紧急',
    `title` varchar(200) NOT NULL DEFAULT '' COMMENT '工单标题',
    `content` text COMMENT '工单内容',
    `images` text COMMENT '图片凭证 JSON数组',
    `contact_name` varchar(50) NOT NULL DEFAULT '' COMMENT '联系人姓名',
    `contact_phone` varchar(20) NOT NULL DEFAULT '' COMMENT '联系电话',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态 0待分配 1处理中 2待确认 3已完成 4已关闭 5已取消',
    `assign_admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理人ID',
    `assign_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '分配时间',
    `handle_result` text COMMENT '处理结果',
    `handle_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理时间',
    `close_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '关闭原因',
    `close_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关闭时间',
    `satisfaction` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '满意度评分 0未评价 1-5星',
    `satisfaction_remark` varchar(500) NOT NULL DEFAULT '' COMMENT '满意度评价内容',
    `expect_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '期望处理时间',
    `deadline` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理截止时间',
    `is_overtime` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否超时 0否 1是',
    `escalate_level` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '升级次数',
    `escalate_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后升级时间',
    `source` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '来源 1小程序 2后台 3电话',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` int(11) UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_ticket_sn` (`ticket_sn`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_type` (`type`),
    KEY `idx_status` (`status`),
    KEY `idx_priority` (`priority`),
    KEY `idx_assign_admin_id` (`assign_admin_id`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='售后工单表';

DROP TABLE IF EXISTS `la_after_sale_ticket_log`;
CREATE TABLE `la_after_sale_ticket_log` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `ticket_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '工单ID',
    `operator_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '操作人类型 1用户 2管理员 3系统',
    `operator_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作人ID',
    `action` varchar(50) NOT NULL DEFAULT '' COMMENT '操作动作',
    `old_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作前状态',
    `new_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作后状态',
    `content` text COMMENT '操作内容/备注',
    `images` text COMMENT '附件图片 JSON数组',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_ticket_id` (`ticket_id`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='工单处理记录表';

DROP TABLE IF EXISTS `la_complaint`;
CREATE TABLE `la_complaint` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '投诉ID',
    `complaint_sn` varchar(32) NOT NULL DEFAULT '' COMMENT '投诉编号',
    `order_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '被投诉服务人员ID',
    `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '投诉类型 1服务态度 2专业能力 3迟到早退 4违规行为 5其他',
    `level` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '投诉等级 1一般 2严重 3紧急',
    `title` varchar(200) NOT NULL DEFAULT '' COMMENT '投诉标题',
    `content` text COMMENT '投诉内容',
    `images` text COMMENT '图片凭证 JSON数组',
    `videos` text COMMENT '视频凭证 JSON数组',
    `expect_result` varchar(500) NOT NULL DEFAULT '' COMMENT '期望处理结果',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态 0待处理 1处理中 2已处理 3已申诉 4已关闭',
    `handle_admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理人ID',
    `handle_result` text COMMENT '处理结果',
    `handle_action` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理动作 0无 1警告 2扣款 3禁用 4其他',
    `handle_amount` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '处理涉及金额（扣款/赔偿）',
    `handle_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理时间',
    `deadline` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理截止时间',
    `is_overtime` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否超时 0否 1是',
    `appeal_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '申诉状态 0未申诉 1申诉中 2申诉成功 3申诉失败',
    `appeal_reason` text COMMENT '申诉原因',
    `appeal_images` text COMMENT '申诉图片 JSON数组',
    `appeal_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '申诉时间',
    `close_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '关闭原因',
    `close_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关闭时间',
    `source` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '来源 1小程序 2后台 3电话',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` int(11) UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_complaint_sn` (`complaint_sn`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_type` (`type`),
    KEY `idx_status` (`status`),
    KEY `idx_level` (`level`),
    KEY `idx_handle_admin_id` (`handle_admin_id`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='投诉表';

DROP TABLE IF EXISTS `la_reshoot`;
CREATE TABLE `la_reshoot` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '补拍ID',
    `reshoot_sn` varchar(32) NOT NULL DEFAULT '' COMMENT '补拍编号',
    `order_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
    `order_item_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单项ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID',
    `reason` text COMMENT '补拍原因',
    `images` text COMMENT '图片凭证 JSON数组',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态 0待审核 1已通过 2已拒绝 3已完成 4已取消',
    `audit_admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核人ID',
    `audit_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核时间',
    `audit_remark` varchar(255) NOT NULL DEFAULT '' COMMENT '审核备注',
    `reject_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '拒绝原因',
    `schedule_date` date DEFAULT NULL COMMENT '补拍日期',
    `time_slot` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '补拍时间段 0全天 1早礼 2午宴 3晚宴',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` int(11) UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_reshoot_sn` (`reshoot_sn`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='补拍申请表';

DROP TABLE IF EXISTS `la_service_callback`;
CREATE TABLE `la_service_callback` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '回访ID',
    `order_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
    `order_item_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单项ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID',
    `callback_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '回访类型 1服务前 2服务中 3服务后',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态 0待回访 1已回访 2已关闭',
    `callback_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '回访时间',
    `callback_admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '回访人ID',
    `callback_result` text COMMENT '回访结果',
    `satisfaction` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '满意度评分 0未评价 1-5星',
    `satisfaction_remark` varchar(500) NOT NULL DEFAULT '' COMMENT '满意度评价内容',
    `next_callback_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '下次回访时间',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` int(11) UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_status` (`status`),
    KEY `idx_callback_time` (`callback_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='服务回访表';

DROP TABLE IF EXISTS `la_callback_questionnaire`;
CREATE TABLE `la_callback_questionnaire` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `title` varchar(200) NOT NULL DEFAULT '' COMMENT '问卷标题',
    `description` varchar(500) NOT NULL DEFAULT '' COMMENT '问卷描述',
    `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '回访类型 1服务前 2服务中 3服务后',
    `questions` text COMMENT '问题列表 JSON数组',
    `sort` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态 0禁用 1启用',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` int(11) UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_type` (`type`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='回访问卷配置表';

DROP TABLE IF EXISTS `la_callback_answer`;
CREATE TABLE `la_callback_answer` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `callback_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '回访ID',
    `questionnaire_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '问卷ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `answers` text COMMENT '答案 JSON数组',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_callback_id` (`callback_id`),
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='回访问卷答案表';

DROP TABLE IF EXISTS `la_escalate_rule`;
CREATE TABLE `la_escalate_rule` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `name` varchar(100) NOT NULL DEFAULT '' COMMENT '规则名称',
    `ticket_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '工单类型 0全部',
    `priority` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '优先级 0全部',
    `timeout_hours` int(11) UNSIGNED NOT NULL DEFAULT 24 COMMENT '超时时间（小时）',
    `escalate_to` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '升级到人员ID',
    `notify_method` varchar(50) NOT NULL DEFAULT 'system' COMMENT '通知方式 system/sms/wechat',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态 0禁用 1启用',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='问题升级规则配置表';

DROP TABLE IF EXISTS `la_after_sale_daily_stats`;
CREATE TABLE `la_after_sale_daily_stats` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `stat_date` date NOT NULL COMMENT '统计日期',
    `ticket_total` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '工单总数',
    `ticket_new` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '新增工单数',
    `ticket_completed` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '完成工单数',
    `ticket_overtime` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '超时工单数',
    `complaint_total` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '投诉总数',
    `complaint_handled` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '已处理投诉数',
    `reshoot_total` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '补拍申请总数',
    `reshoot_approved` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '已通过补拍数',
    `callback_total` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '回访总数',
    `callback_completed` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '已完成回访数',
    `avg_handle_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '平均处理时长（秒）',
    `satisfaction_avg` decimal(3,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '平均满意度',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_stat_date` (`stat_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='售后工单每日统计表';

INSERT INTO `la_escalate_rule` (`name`, `ticket_type`, `priority`, `timeout_hours`, `notify_method`, `status`, `create_time`, `update_time`) VALUES
('紧急工单2小时升级', 0, 4, 2, 'system,sms', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('高优先级工单4小时升级', 0, 3, 4, 'system', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('普通工单24小时升级', 0, 2, 24, 'system', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('投诉48小时升级', 1, 0, 48, 'system,sms', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

INSERT INTO `la_callback_questionnaire` (`title`, `description`, `type`, `questions`, `sort`, `status`, `create_time`, `update_time`) VALUES
('服务后满意度调查', '感谢您使用我们的服务，请花1分钟完成以下问卷', 3, '[{\"id\":1,\"type\":\"rating\",\"title\":\"整体服务满意度\",\"required\":true},{\"id\":2,\"type\":\"rating\",\"title\":\"服务人员态度\"},{\"id\":3,\"type\":\"rating\",\"title\":\"专业水平\"},{\"id\":4,\"type\":\"rating\",\"title\":\"时间守约\"},{\"id\":5,\"type\":\"text\",\"title\":\"您的建议\",\"placeholder\":\"请输入您的宝贵意见...\"}]', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


CREATE TABLE IF NOT EXISTS `la_subscribe_message_template` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `template_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '微信模板ID',
    `name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '模板名称',
    `title` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '模板标题',
    `scene` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '使用场景: order_create/order_paid/order_confirm/order_complete/schedule_remind/refund_result/callback_remind/ticket_update/change_result/schedule_change/waitlist_release',
    `content` TEXT COMMENT '模板内容(JSON格式，存储字段配置)',
    `example` TEXT COMMENT '示例内容',
    `keywords` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '关键词列表(逗号分隔)',
    `category_id` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '模板类目ID',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态: 0-禁用 1-启用',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序(越大越靠前)',
    `remark` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '备注说明',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_template_id` (`template_id`),
    KEY `idx_scene` (`scene`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订阅消息模板表';

CREATE TABLE IF NOT EXISTS `la_user_subscribe` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `template_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '微信模板ID',
    `scene` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '使用场景',
    `accept_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '累计授权次数(一次性订阅消息每次授权+1)',
    `reject_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '累计拒绝次数',
    `last_accept_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后授权时间',
    `last_reject_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后拒绝时间',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '订阅状态: 0-已拒绝 1-已订阅 2-永久订阅',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_template` (`user_id`, `template_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_template_id` (`template_id`),
    KEY `idx_scene` (`scene`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户订阅记录表';

CREATE TABLE IF NOT EXISTS `la_subscribe_message_log` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `openid` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '用户OpenID',
    `template_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '微信模板ID',
    `scene` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '使用场景',
    `business_type` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '业务类型: order/schedule/refund/callback/ticket等',
    `business_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '业务ID',
    `content` TEXT COMMENT '发送内容(JSON格式)',
    `page` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '跳转页面路径',
    `miniprogram_state` VARCHAR(20) NOT NULL DEFAULT 'formal' COMMENT '小程序状态: developer/trial/formal',
    `send_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '发送状态: 0-待发送 1-发送成功 2-发送失败',
    `error_code` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '错误码',
    `error_msg` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '错误信息',
    `request_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '请求ID',
    `send_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '发送时间',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_template_id` (`template_id`),
    KEY `idx_scene` (`scene`),
    KEY `idx_business` (`business_type`, `business_id`),
    KEY `idx_send_status` (`send_status`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订阅消息发送日志表';

CREATE TABLE IF NOT EXISTS `la_subscribe_message_scene` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `scene` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '场景标识',
    `name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '场景名称',
    `description` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '场景描述',
    `template_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '关联模板ID',
    `trigger_event` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '触发事件标识',
    `data_mapping` TEXT COMMENT '数据字段映射(JSON)',
    `page_path` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '跳转页面路径',
    `is_auto` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否自动触发：0=否,1=是',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态: 0-禁用 1-启用',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_scene` (`scene`),
    KEY `idx_template_id` (`template_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订阅消息场景配置表';

INSERT INTO `la_subscribe_message_scene` (`scene`, `name`, `description`, `trigger_event`, `page_path`, `is_auto`, `status`, `create_time`, `update_time`) VALUES
('order_create', '订单创建通知', '用户提交订单后发送确认通知', 'OrderCreated', 'pages/order_detail/order_detail', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('order_paid', '支付成功通知', '用户完成支付后发送确认通知', 'OrderPaid', 'pages/order_detail/order_detail', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('order_confirm', '订单确认通知', '商家确认订单后通知用户', 'OrderConfirmed', 'pages/order_detail/order_detail', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('order_complete', '服务完成通知', '服务完成后通知用户进行评价', 'OrderCompleted', 'pages/review/publish', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('schedule_remind', '档期提醒通知', '服务日期前提醒用户', 'ScheduleRemind', 'pages/order_detail/order_detail', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('refund_result', '退款结果通知', '退款审核结果通知', 'RefundProcessed', 'pages/order_detail/order_detail', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('callback_remind', '回访提醒通知', '服务完成后的回访提醒', 'CallbackRemind', 'pages/aftersale/callback', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('ticket_update', '工单进度通知', '售后工单状态更新通知', 'TicketUpdated', 'pages/aftersale/ticket_detail', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('change_result', '变更审核通知', '订单变更申请审核结果通知', 'ChangeProcessed', 'pages/order_change/change_detail', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('schedule_change', '档期变更通知', '人员档期发生变更时通知', 'ScheduleChanged', 'pages/order_detail/order_detail', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

INSERT INTO `la_subscribe_message_scene` (`scene`, `name`, `description`, `trigger_event`, `data_mapping`, `page_path`, `is_auto`, `status`, `create_time`, `update_time`) VALUES
('waitlist_release', '候补释放通知', '档期释放后通知候补用户', 'WaitlistReleased', '{"thing1":"staff_name","time2":"schedule_date","thing3":"time_slot_desc","thing4":"package_name","thing5":"remark"}', 'packages/pages/waitlist/waitlist', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

INSERT INTO `la_subscribe_message_template` (`template_id`, `name`, `title`, `scene`, `content`, `keywords`, `status`, `sort`, `remark`, `create_time`, `update_time`) VALUES
('TEMPLATE_ID_ORDER_CREATE', '订单提交成功通知', '订单提交成功', 'order_create', '{"thing1":{"key":"订单内容","value":""},"character_string2":{"key":"订单编号","value":""},"amount3":{"key":"订单金额","value":""},"time4":{"key":"下单时间","value":""}}', '订单内容,订单编号,订单金额,下单时间', 1, 100, '订单创建后发送，需在微信后台申请模板后更新template_id', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('TEMPLATE_ID_ORDER_PAID', '支付成功通知', '支付成功', 'order_paid', '{"character_string1":{"key":"订单编号","value":""},"amount2":{"key":"支付金额","value":""},"time3":{"key":"支付时间","value":""},"thing4":{"key":"商品名称","value":""}}', '订单编号,支付金额,支付时间,商品名称', 1, 99, '支付完成后发送，需在微信后台申请模板后更新template_id', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('TEMPLATE_ID_SERVICE_REMIND', '服务提醒通知', '服务提醒', 'schedule_remind', '{"thing1":{"key":"服务内容","value":""},"time2":{"key":"服务时间","value":""},"thing3":{"key":"服务地点","value":""},"thing4":{"key":"服务人员","value":""}}', '服务内容,服务时间,服务地点,服务人员', 1, 98, '服务前1天/3天提醒，需在微信后台申请模板后更新template_id', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('TEMPLATE_ID_REFUND_RESULT', '退款结果通知', '退款通知', 'refund_result', '{"character_string1":{"key":"订单编号","value":""},"amount2":{"key":"退款金额","value":""},"phrase3":{"key":"退款状态","value":""},"thing4":{"key":"退款原因","value":""}}', '订单编号,退款金额,退款状态,退款原因', 1, 97, '退款审核后发送，需在微信后台申请模板后更新template_id', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('TEMPLATE_ID_TICKET_UPDATE', '工单进度通知', '工单状态更新', 'ticket_update', '{"character_string1":{"key":"工单编号","value":""},"phrase2":{"key":"工单状态","value":""},"thing3":{"key":"处理说明","value":""},"time4":{"key":"更新时间","value":""}}', '工单编号,工单状态,处理说明,更新时间', 1, 96, '工单状态变更时发送，需在微信后台申请模板后更新template_id', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('TEMPLATE_ID_WAITLIST_RELEASE', '候补释放通知', '候补释放', 'waitlist_release', '{"thing1":{"key":"服务人员","value":""},"time2":{"key":"档期日期","value":""},"thing3":{"key":"时间段","value":""},"thing4":{"key":"套餐名称","value":""},"thing5":{"key":"备注","value":""}}', '服务人员,档期日期,时间段,套餐名称,备注', 1, 95, '候补释放后发送，需在微信后台申请模板后更新template_id', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;


INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (0, 'M', '服务管理', 'el-icon-User', 900, '', 'service', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @service_menu_id = LAST_INSERT_ID();

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@service_menu_id, 'C', '服务人员', '', 100, 'staff.staff/lists', 'staff', 'staff/lists/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@service_menu_id, 'C', '服务分类', '', 90, 'service.service_category/lists', 'category', 'service/category/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@service_menu_id, 'C', '服务套餐', '', 80, 'service.service_package/lists', 'package', 'service/package/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (0, 'M', '档期管理', 'el-icon-Calendar', 850, '', 'schedule', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @schedule_menu_id = LAST_INSERT_ID();

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@schedule_menu_id, 'C', '档期日历', '', 100, 'schedule.schedule/lists', 'calendar', 'schedule/calendar/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@schedule_menu_id, 'C', '预约列表', '', 90, 'schedule.booking/lists', 'booking', 'schedule/booking/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@schedule_menu_id, 'C', '候补列表', '', 80, 'schedule.waitlist/lists', 'waitlist', 'schedule/waitlist/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (0, 'M', '订单管理', 'el-icon-Document', 800, '', 'order', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @order_menu_id = LAST_INSERT_ID();

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@order_menu_id, 'C', '订单列表', '', 100, 'order.order/lists', 'lists', 'order/lists/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@order_menu_id, 'C', '退款管理', '', 90, 'order.refund/lists', 'refund', 'order/refund/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@order_menu_id, 'C', '订单变更', '', 80, 'order.order_change/lists', 'change', 'order/change/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@order_menu_id, 'C', '订单转让', '', 70, 'order.order_transfer/lists', 'transfer', 'order/transfer/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@order_menu_id, 'C', '订单暂停', '', 60, 'order.order_pause/lists', 'pause', 'order/pause/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (0, 'M', '动态社区', 'el-icon-ChatDotRound', 750, '', 'dynamic', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @dynamic_menu_id = LAST_INSERT_ID();

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@dynamic_menu_id, 'C', '动态列表', '', 100, 'dynamic.dynamic/lists', 'lists', 'dynamic/lists/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (0, 'M', '评价管理', 'el-icon-Star', 700, '', 'review', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @review_menu_id = LAST_INSERT_ID();

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@review_menu_id, 'C', '评价列表', '', 100, 'review.review/lists', 'lists', 'review/lists/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (0, 'M', '财务管理', 'el-icon-Coin', 650, '', 'financial', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @financial_menu_id = LAST_INSERT_ID();

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@financial_menu_id, 'C', '资金流水', '', 100, 'financial.flow/lists', 'flow', 'financial/flow/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@financial_menu_id, 'C', '结算管理', '', 80, 'financial.settlement/lists', 'settlement', 'financial/settlement/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@financial_menu_id, 'C', '成本管理', '', 70, 'financial.cost/lists', 'cost', 'financial/cost/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@financial_menu_id, 'C', '发票管理', '', 60, 'financial.invoice/lists', 'invoice', 'financial/invoice/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (0, 'M', 'CRM管理', 'el-icon-Phone', 600, '', 'crm', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @crm_menu_id = LAST_INSERT_ID();

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@crm_menu_id, 'C', '客户管理', '', 100, 'crm.customer/lists', 'customer', 'crm/customer/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@crm_menu_id, 'C', '顾问管理', '', 90, 'crm.sales_advisor/lists', 'advisor', 'crm/advisor/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@crm_menu_id, 'C', '流失预警', '', 80, 'crm.customer_loss_warning/lists', 'warning', 'crm/warning/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (0, 'M', '售后服务', 'el-icon-Service', 550, '', 'aftersale', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @aftersale_menu_id = LAST_INSERT_ID();

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@aftersale_menu_id, 'C', '售后工单', '', 100, 'aftersale.aftersale/ticketLists', 'ticket', 'aftersale/ticket/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (0, 'M', '营销管理', 'el-icon-Present', 500, '', 'marketing', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @marketing_menu_id = LAST_INSERT_ID();

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@marketing_menu_id, 'C', '优惠券管理', '', 100, 'coupon.coupon/lists', 'coupon', 'coupon/lists/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (0, 'M', '消息中心', 'el-icon-Bell', 450, '', 'message', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @message_menu_id = LAST_INSERT_ID();

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@message_menu_id, 'C', '消息通知', '', 100, 'notification.notification/lists', 'notification', 'notification/lists/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@message_menu_id, 'C', '订阅消息', '', 90, 'subscribe.subscribe/templateList', 'subscribe', 'subscribe/template/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (0, 'M', '时间线管理', 'el-icon-Timer', 400, '', 'timeline', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @timeline_menu_id = LAST_INSERT_ID();

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@timeline_menu_id, 'C', '时间线模板', '', 100, 'timeline.timeline/templateList', 'lists', 'timeline/lists/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


ALTER TABLE `la_service_package` 
ADD COLUMN `staff_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属工作人员ID（0=全局套餐）' AFTER `category_id`,
ADD COLUMN `package_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '套餐类型：1=全局套餐,2=人员专属套餐' AFTER `staff_id`,
ADD COLUMN `slot_prices` TEXT COMMENT '时段价格配置JSON：[{"start_time":"08:00","end_time":"12:00","price":1000}]' AFTER `price`,
ADD INDEX `idx_staff_id` (`staff_id`),
ADD INDEX `idx_package_type` (`package_type`);

ALTER TABLE `la_staff_package` 
ADD COLUMN `custom_price` DECIMAL(10,2) DEFAULT NULL COMMENT '个人定制价格（覆盖套餐默认价格）' AFTER `package_id`,
ADD COLUMN `custom_slot_prices` TEXT COMMENT '个人时段价格配置JSON' AFTER `custom_price`,
ADD COLUMN `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=禁用,1=启用' AFTER `custom_slot_prices`;

CREATE TABLE IF NOT EXISTS `la_package_booking` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT '主键ID',
  `package_id` INT UNSIGNED NOT NULL COMMENT '套餐ID',
  `staff_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID（0=不限人员）',
  `booking_date` DATE NOT NULL COMMENT '预订日期',
  `start_time` VARCHAR(10) DEFAULT NULL COMMENT '开始时间 HH:mm',
  `end_time` VARCHAR(10) DEFAULT NULL COMMENT '结束时间 HH:mm',
  `order_id` INT UNSIGNED DEFAULT NULL COMMENT '关联订单ID',
  `order_item_id` INT UNSIGNED DEFAULT NULL COMMENT '关联订单项ID',
  `user_id` INT UNSIGNED DEFAULT NULL COMMENT '预订用户ID',
  `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=已释放,1=临时锁定,2=已确认',
  `lock_expire_time` INT UNSIGNED DEFAULT NULL COMMENT '临时锁定过期时间戳',
  `version` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '乐观锁版本号',
  `create_time` INT UNSIGNED NOT NULL COMMENT '创建时间',
  `update_time` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
  UNIQUE KEY `uk_package_date_active` (`package_id`, `booking_date`, `status`),
  INDEX `idx_staff_id` (`staff_id`),
  INDEX `idx_order_id` (`order_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_lock_expire` (`lock_expire_time`),
  INDEX `idx_booking_date` (`booking_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='套餐预订记录表（用于单日唯一限制）';

UPDATE `la_service_package` SET `package_type` = 1, `staff_id` = 0 WHERE `package_type` IS NULL OR `package_type` = 0;




SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'C', '服务人员添加/编辑', '', 99, 'staff.staff/add:edit', 'staff/edit', 'staff/lists/edit', '/service/staff', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `paths` = 'service' AND `type` = 'M'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'staff.staff/add:edit')
LIMIT 1;

SET FOREIGN_KEY_CHECKS = 1;
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'la_dynamic' 
    AND COLUMN_NAME = 'allow_comment');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `la_dynamic` ADD COLUMN `allow_comment` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT ''是否允许评论：0=禁止，1=允许'' AFTER `tags`', 
    'SELECT "Column allow_comment already exists, skipping..."');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `la_dynamic` SET `allow_comment` = 1 WHERE `allow_comment` = 0 OR `allow_comment` IS NULL;


ALTER TABLE `la_dynamic_comment` 
ADD COLUMN `review_status` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 
COMMENT '审核状态：0=待审核，1=已通过，2=已拒绝' 
AFTER `status`;

ALTER TABLE `la_dynamic_comment` 
ADD COLUMN `review_admin_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 
COMMENT '审核管理员ID' 
AFTER `review_status`;

ALTER TABLE `la_dynamic_comment` 
ADD COLUMN `review_time` INT(11) UNSIGNED NOT NULL DEFAULT 0 
COMMENT '审核时间' 
AFTER `review_admin_id`;

ALTER TABLE `la_dynamic_comment` 
ADD COLUMN `review_remark` VARCHAR(255) NOT NULL DEFAULT '' 
COMMENT '审核备注（拒绝原因）' 
AFTER `review_time`;

UPDATE `la_dynamic_comment` SET `review_status` = 1 WHERE `review_status` = 0;


SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

SET @dynamic_menu_id = (SELECT id FROM `la_system_menu` WHERE `name` = '动态社区' AND `pid` = 0 LIMIT 1);

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@dynamic_menu_id, 'C', '评论审核', '', 90, 'dynamic.dynamicComment/reviewList', 'comment/review', 'dynamic/comment/review', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@dynamic_menu_id, 'C', '动态配置', '', 80, 'dynamic.dynamicComment/getReviewConfig', 'config', 'dynamic/config/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

SET FOREIGN_KEY_CHECKS = 1;


ALTER TABLE `la_service_package`
ADD COLUMN `booking_type` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '预约类型：0=全天套餐,1=分场次套餐' AFTER `slot_prices`,
ADD COLUMN `allowed_time_slots` TEXT NULL COMMENT '允许场次(JSON数组)' AFTER `booking_type`;

ALTER TABLE `la_staff_package`
ADD COLUMN `booking_type` TINYINT UNSIGNED NULL DEFAULT NULL COMMENT '预约类型覆盖：0=全天套餐,1=分场次套餐' AFTER `custom_slot_prices`,
ADD COLUMN `allowed_time_slots` TEXT NULL COMMENT '允许场次覆盖(JSON数组)' AFTER `booking_type`;

ALTER TABLE `la_package_booking`
ADD COLUMN `time_slot` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '时间段：0=全天,1=早礼,2=午宴,3=晚宴' AFTER `booking_date`;

ALTER TABLE `la_package_booking`
DROP INDEX `uk_package_date_active`,
ADD UNIQUE KEY `uk_staff_package_date_slot` (`staff_id`, `package_id`, `booking_date`, `time_slot`);

ALTER TABLE `la_waitlist`
ADD COLUMN `batch_no` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '候补批次号' AFTER `package_id`;

UPDATE `la_service_package` SET `booking_type` = 0 WHERE `booking_type` IS NULL;
SET FOREIGN_KEY_CHECKS = 1;


SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `la_service_category` 
ADD COLUMN `level` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '分类层级:1-一级,2-二级,3-三级' AFTER `pid`;

UPDATE `la_service_category` SET `level` = 1 WHERE `pid` = 0;

ALTER TABLE `la_service_category` 
ADD KEY `idx_level` (`level`) USING BTREE;

SET FOREIGN_KEY_CHECKS = 1;


SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'C', '服务人员标签', '', 70, 'service.styleTag/lists', 'tag', 'service/tag/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `paths` = 'service' AND `type` = 'M'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'service.styleTag/lists')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '新增', '', 0, 'service.styleTag/add', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'service.styleTag/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'service.styleTag/add')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '编辑', '', 0, 'service.styleTag/edit', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'service.styleTag/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'service.styleTag/edit')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '删除', '', 0, 'service.styleTag/delete', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'service.styleTag/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'service.styleTag/delete')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '状态切换', '', 0, 'service.styleTag/changeStatus', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'service.styleTag/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'service.styleTag/changeStatus')
LIMIT 1;

SET FOREIGN_KEY_CHECKS = 1;

ALTER TABLE `la_staff`
ADD COLUMN `banner_mode` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '轮播图展示模式：1=小图模式，2=大图模式' AFTER `service_desc`,
ADD COLUMN `banner_small_height` INT(11) UNSIGNED NOT NULL DEFAULT 400 COMMENT '小图模式初始高度（rpx）' AFTER `banner_mode`,
ADD COLUMN `banner_large_height` INT(11) UNSIGNED NOT NULL DEFAULT 600 COMMENT '大图模式/展开后高度（rpx）' AFTER `banner_small_height`,
ADD COLUMN `banner_indicator_style` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '指示器样式：1=圆点，2=数字，3=进度条，0=无' AFTER `banner_large_height`,
ADD COLUMN `banner_autoplay` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否自动轮播：0=否，1=是' AFTER `banner_indicator_style`,
ADD COLUMN `banner_interval` INT(11) UNSIGNED NOT NULL DEFAULT 3000 COMMENT '轮播间隔时间（毫秒）' AFTER `banner_autoplay`;

CREATE TABLE IF NOT EXISTS `la_staff_banner` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `staff_id` INT(11) UNSIGNED NOT NULL COMMENT '人员ID',
  `type` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '类型：1=图片，2=视频',
  `file_url` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '文件地址',
  `cover_url` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '封面图地址（视频必填）',
  `is_autoplay` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '视频是否自动播放：0=否，1=是',
  `sort` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序（数字越小越靠前）',
  `create_time` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_staff_id` (`staff_id`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='人员轮播图表';

ALTER TABLE `la_staff_package` 
ADD COLUMN `original_price` DECIMAL(10,2) DEFAULT NULL COMMENT '原价（用于显示划线价）' AFTER `price`,
ADD COLUMN `update_time` INT(10) UNSIGNED DEFAULT NULL COMMENT '更新时间' AFTER `create_time`;

SET FOREIGN_KEY_CHECKS=1;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `la_staff`
    ADD COLUMN `admin_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联后台管理员ID' AFTER `user_id`,
    ADD KEY `idx_admin_id` (`admin_id`);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'feature_switch', 'staff_center', '1', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config` WHERE `type` = 'feature_switch' AND `name` = 'staff_center'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'feature_switch', 'staff_admin', '1', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config` WHERE `type` = 'feature_switch' AND `name` = 'staff_admin'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'feature_switch', 'admin_dashboard', '1', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config` WHERE `type` = 'feature_switch' AND `name` = 'admin_dashboard'
);

INSERT INTO `la_system_role` (`name`, `desc`, `sort`, `create_time`, `update_time`)
SELECT '服务人员', '服务人员', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_role` WHERE `name` = '服务人员' AND `delete_time` IS NULL
);

SET @staff_role_id = (
    SELECT `id` FROM `la_system_role`
    WHERE `name` = '服务人员' AND `delete_time` IS NULL
    ORDER BY `id` DESC
    LIMIT 1
);

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT 0, 'M', '服务人员中心', 'el-icon-User', 720, '', 'staff_center', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `paths` = 'staff_center' AND `type` = 'M'
);

SET @staff_center_menu_id = (
    SELECT `id` FROM `la_system_menu`
    WHERE `paths` = 'staff_center' AND `type` = 'M'
    ORDER BY `id` DESC
    LIMIT 1
);

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_center_menu_id, 'C', '我的资料', '', 100, 'staff.staff/lists', 'profile', 'staff/lists/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'profile'
);

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_center_menu_id, 'C', '档期日历', '', 90, 'schedule.schedule/lists', 'calendar', 'schedule/calendar/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'calendar'
);

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_center_menu_id, 'C', '档期规则', '', 80, 'schedule.scheduleRule/lists', 'rule', 'schedule/rule/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'rule'
);

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_center_menu_id, 'C', '预约列表', '', 70, 'schedule.booking/lists', 'booking', 'schedule/booking/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'booking'
);

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_center_menu_id, 'C', '候补列表', '', 60, 'schedule.waitlist/lists', 'waitlist', 'schedule/waitlist/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'waitlist'
);

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_center_menu_id, 'C', '订单列表', '', 50, 'order.order/lists', 'order', 'order/lists/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'order'
);

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_center_menu_id, 'C', '动态管理', '', 40, 'dynamic.dynamic/lists', 'dynamic', 'dynamic/lists/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'dynamic'
);

INSERT INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT @staff_role_id, m.id
FROM `la_system_menu` m
WHERE m.pid = @staff_center_menu_id
  AND NOT EXISTS (
      SELECT 1 FROM `la_system_role_menu` rm
      WHERE rm.role_id = @staff_role_id AND rm.menu_id = m.id
  );

INSERT INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT @staff_role_id, m.id
FROM `la_system_menu` m
WHERE m.perms = 'staff.staff/add:edit'
  AND NOT EXISTS (
      SELECT 1 FROM `la_system_role_menu` rm
      WHERE rm.role_id = @staff_role_id AND rm.menu_id = m.id
  )
LIMIT 1;

INSERT INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT @staff_role_id, m.id
FROM `la_system_menu` m
WHERE m.perms IN (
    'staff.staff/detail',
    'staff.staff/edit',
    'schedule.schedule/monthCalendar',
    'schedule.schedule/detail',
    'schedule.schedule/setStatus',
    'schedule.schedule/batchSet',
    'schedule.schedule/unlock',
    'schedule.schedule/statistics',
    'schedule.schedule/lockRecords',
    'schedule.scheduleRule/detail',
    'schedule.scheduleRule/add',
    'schedule.scheduleRule/edit',
    'schedule.scheduleRule/delete',
    'schedule.scheduleRule/changeStatus',
    'schedule.scheduleRule/staffRule',
    'schedule.waitlist/detail',
    'schedule.waitlist/notify',
    'schedule.waitlist/batchNotify',
    'schedule.waitlist/convert',
    'schedule.waitlist/invalidate',
    'schedule.waitlist/statistics',
    'order.order/detail',
    'order.order/startService',
    'order.order/complete',
    'order.order/cancel',
    'order.order/auditVoucher',
    'order.order/logs',
    'order.order/addRemark',
    'order.order/statistics'
)
  AND NOT EXISTS (
      SELECT 1 FROM `la_system_role_menu` rm
      WHERE rm.role_id = @staff_role_id AND rm.menu_id = m.id
  );

SET @setting_menu_id = (
    SELECT `id` FROM `la_system_menu`
    WHERE `paths` = 'setting' AND `type` = 'M'
    ORDER BY `id` DESC
    LIMIT 1
);

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @setting_menu_id, 'C', '功能开关', 'el-icon-Switch', 55, 'setting.feature_switch/getConfig', 'feature_switch', 'setting/feature_switch/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @setting_menu_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM `la_system_menu` WHERE `perms` = 'setting.feature_switch/getConfig'
  );

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT m.id, 'A', '保存', '', 0, 'setting.feature_switch/setConfig', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu` m
WHERE m.perms = 'setting.feature_switch/getConfig'
  AND NOT EXISTS (
      SELECT 1 FROM `la_system_menu` WHERE `perms` = 'setting.feature_switch/setConfig'
  )
LIMIT 1;

SET FOREIGN_KEY_CHECKS = 1;
