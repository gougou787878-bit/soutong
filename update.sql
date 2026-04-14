drop table if exists ks_game_guess;
drop table if exists ks_game_guess_item;
drop table if exists ks_game_guess_member;
drop table if exists ks_game_guess_result;



drop table if exists ks_tab;
create table if not exists ks_tab
(
    tab_id     int auto_increment
        primary key,
    tab_name   varchar(20)       not null comment '导航蓝标签组',
    tags_str   varchar(2048)     null comment '标签',
    sort_num   smallint unsigned not null comment '排序',
    status     tinyint default 1 null comment '1 启用',
    created_at bigint unsigned   not null comment '创建时间',
    updated_at bigint unsigned   not null comment '修改时间'
) comment 'tab栏';



alter table ks_members add column live_count int unsigned not null comment '直播次数' after likes_count;
alter table ks_app_nav modify pos varchar(20) default 'null' not null comment '位置';
alter table ks_mv_pay add column mv_uid int  not null comment '视频作者的id';
alter table ks_mv_pay add column date_at int not null comment '日期';

alter table ks_mv add column is_top tinyint not null default 0 comment '是否置顶';

alter table ks_members add column consumption int not null default 0 comment '总消费金额';





-- auto-generated definition
create table ks_mv_submit
(
    id               int auto_increment
        primary key,
    uid              int(255)         default 0      not null comment '用户UUID',
    music_id         int              default 0      not null comment '音乐id',
    coins            int(11) unsigned default 0      not null comment '定价',
    vip_coins        int              default -1     not null comment '会员购买价格，-1表示没有设置会员价格',
    title            varchar(500)     default ''     not null comment '影片标题',
    m3u8             varchar(255)     default ''     not null comment '影片资源1',
    full_m3u8        varchar(255)     default ''     not null comment '完整视频的m3u8地址',
    v_ext            varchar(20)      default 'm3u8' not null comment '视频格式类型',
    duration         int              default 0      not null comment '时长，秒',
    cover_thumb      varchar(128)     default ''     not null comment '封面小图',
    thumb_width      int(10)          default 0      not null comment '封面宽',
    thumb_height     int(10)          default 0      not null comment '封面高',
    gif_thumb        varchar(128)     default ''     not null comment '视频动图',
    gif_width        int              default 0      not null comment '视频动图宽',
    gif_height       int              default 0      not null comment '视频动图高',
    directors        varchar(50)      default ''     not null comment '导演',
    actors           varchar(255)     default ''     not null comment '演员',
    category         varchar(255)     default ''     not null comment '分类',
    tags             varchar(255)     default ''     not null comment '影片标签',
    via              varchar(255)     default ''     not null comment '来源',
    onshelf_tm       int              default 0      not null comment '影片上映时间',
    rating           int(10)          default 0      not null comment '总历史点击数',
    refresh_at       int              default 0      not null comment '刷新时间',
    is_free          tinyint(1)       default 0      not null comment '是否限免 0 收费 1 限免',
    `like`           int(10)          default 0      not null comment '喜欢点击数',
    comment          int              default 0      not null comment '评论数',
    status           tinyint          default 0      not null comment '0未审核1审核通过',
    thumb_start_time int              default 0      not null comment '精彩片段开始时间',
    thumb_duration   int              default 30     not null comment '精彩时长：秒',
    is_hide          tinyint(3)       default 0      not null comment '0显示1隐藏',
    created_at       int              default 0      not null comment '创建时间',
    is_recommend     tinyint          default 0      not null comment '是否推荐',
    is_feature       tinyint          default 0      not null comment '是否是精选视频',
    y_cover          varchar(255)                    not null comment '竖版封面',
    is_top           tinyint          default 0      not null comment '是否置顶'
)comment 'AV数据表 未发布的数据' collate = utf8_unicode_ci;

create index coins on ks_mv_submit (coins);
create index ks_mv_is_recommend_index on ks_mv_submit (is_recommend);
create fulltext index ks_mv_tags_index on ks_mv_submit (tags);
create index `like` on ks_mv_submit (`like`);
create index rating on ks_mv_submit (rating);
create index refresh_at  on ks_mv_submit (refresh_at);
create index status on ks_mv_submit (status);
create index title on ks_mv_submit (title);
create index user_uuid on ks_mv_submit (uid);



create table if not exists ks_notify_log
(
    id        bigint unsigned not null primary key auto_increment,
    type      varchar(10)     not null comment '通知类型',
    log       text            not null comment '内容',
    create_at bigint unsigned not null comment '通知时间'
)