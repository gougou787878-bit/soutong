
## soutong 全新启航
#编码约束
>老的接口。我们核实复核我们的需求就不用处理哈。  
>我们自己加的新接口的严格遵守命名规范。  
>非必要 不要使用 trait , 统一风格走service分层  
>controller层，负责验证参数，service成负责逻辑  
>controller层方法使用使用小驼峰，更符合uri  
>service层的所有错误，使用异常方式抛出来，由controller层级进行try catch处理。  
>需要格式话的数据，统一在model层使用ORM的appends进行格式 ，非必要不要在 controller或者service层重新格式数据  
>service 层的方法使用大驼峰命名方式，不要使用 static 类型的方法  
>一切状态，都采用常量方式，  
>所有参数，尽量明确类型，非必要，不要array类型的参数，返回值也一样，列表类型的数据除外。  
>字段必写注释？  
>表里面的状态。用const 常量，判断的时候


#助手函数
>`url_image`补全图片的路径，`url_video`补全视频的路径


>`cached`函数
```php
//缓存, 减少 if else
$data = cached("redis-key")
    ->expired("过期时间，秒")
    ->serializerPHP()
    ->fetch(function (){
         return MvModel::where('id',111)->first();
    })
```

>`redis`函数
```php
//利用ide提示快熟补全
$v = redis()->set('aaa' ,11);
$v = redis()->getx("防止key失效造成大量的db查询" , function (){
    return MvModel::where('id',111)->first();
} , 7200);
```

>`test_assert`函数，快速抛出异常
```php
//判断第一个参数是不是 为false，为false，之后的代码不再执行
test_assert(false , '一个参数为假，表示验证失败，这里会抛出异常，这句话就是异常的msg')
test_assert(true , '一个参数为真，表示验证成功，这里不会抛出异常')
```

>`transaction`函数，自动处理mysql事物，需要回滚的话，抛出异常
```php
//自动处理mysql事物
transaction(function (){
    $isOk = MvModel::create([]);
    test_assert($isOk ,'添加视频失败');
    $isOk = MemberModel::where('uid',11)->increment('mv_count');
    test_assert($isOk ,'影响用户视频计数失败');
})
```

#smarty模板
> {%html_upload name='input的name' src='图片链接' value='input值'%}
> <select name="finished" data-value="{{=d.finished}}">{%html_options options=$packageArr%}</select>
> {%html_textarea name='remark' value='{{=d.remark }}'%}


#api索引    `└─`,`├──`,`│`
```text
└── controllers
    ├── Account.php   // 账号相关
    │   ├──registerByPasswordAction   账号密码注册
    │   ├──loginByPasswordAction      密码登陆
    │   ├──validateUsernameAction     验证账号是否注册
    │   ├──validatePhoneAction        验证手机是否注册
    │   ├──loginByPhoneAction         使用短信验证   
    │   ├──registerByPhoneAction      使用短信注册   
    │   ├──bindPhoneAction            绑定手机号，同上   
    │   ├──changePhoneAction          重新绑定手机号   
    │   ├──forgetPasswordAction       使用短信找回账号   
    │   ├──updatePasswordAction       修改密码   
    │   └──setPasswordAction          设置密码   
    ├── Book.php   // 漫画相关
    │   ├──getDetailAction                  详情
    │   ├──evaluationAction                 4种点赞   
    │   ├──readAction                       
    │   ├──list_episodeAction               章节列表
    │   ├──getDetailRecommendListAction     评论列表
    │   ├──searchAction                     搜索
    │   ├──favoritesAction                  点赞列表
    │   ├──buyAction                        购买
    │   ├──filter_navAction                 分类的塞选项
    │   ├──list_filterAction                筛选的内容列表
    │   ├──filter_cartoonAction             动漫更多页面筛选
    │   ├──list_buyAction                   漫画的购买
    │   ├──delHistoryAction                 删除观看历史
    │   ├──getRankAction   
    │   ├──myBookListAction   
    │   ├──getFavoriteRankAction   
    │   └──likeAction                       点赞
    ├── Broker.php   // 商户相关
    │   ├──getListAction   
    │   ├──infoAction   
    │   ├──editAction   
    │   ├──chatOrderManageAction   
    │   ├──myGirlmeetAction   
    │   ├──topAction   
    │   ├──updownAction   
    │   └──moreBrokerAction   
    ├── Callback.php   // 回调
    │   ├──pay_callbackAction           支付回调
    │   ├──notify_withdrawAction        提现回调
    │   └──checkLineAction              线路检查
    ├── Chat.php   // 裸聊相关
    │   ├──getListAction   
    │   ├──getListWithFilterAction   
    │   ├──getFilterOptionAction   
    │   ├──getDetailAction   
    │   ├──unlockAction   
    │   ├──buyAction   
    │   ├──contactAction   
    │   ├──getMyOrdersAction   
    │   ├──confirm_chatAction   
    │   ├──commentAction   
    │   ├──list_buyAction   
    │   ├──getCommentAction   
    │   └─searchAction   
    ├── Comment.php   // 这里是备注
    │   ├──indexAction   
    │   └──commentAction   
    ├── Element.php   // 元素相关 结构数据
    │   ├──getElementByIdAction   
    │   ├──getElementByIdSecondPageAction   
    │   └──getConstructByIdAction   获取结构的所有元素
    ├── Girl.php   // 约会，嫖娼相关
    │   ├──getListAction   
    │   ├──getListWithFilterAction   
    │   ├──getFilterOptionAction   
    │   ├──getDetailAction   
    │   ├──unlockAction   
    │   ├──buyAction   
    │   ├──searchAction   
    │   ├──commentAction   
    │   ├──getCommentAction   
    │   ├──getAvgCommentAction   
    │   ├──contactAction   
    │   ├──getCommentListAction   
    │   ├──getBrokerDetailAction   
    │   ├──getMyOrdersAction   
    │   ├──list_buyAction   
    │   ├──uploadAction   
    │   └──uploadOptionAction   
    ├── Home.php   // 首页配置相关
    │   ├──sendAction   
    │   ├──configAction   
    │   ├──domainCheckReportAction   
    │   ├──getContactListAction   
    │   ├──checkLineAction   
    │   ├──appCenterAction   
    │   ├──exchangeAction   
    │   ├──appclickAction   
    │   ├──testAction   
    │   ├──navAction   
    │   ├──getSearchWordAction   
    │   └──getUpdateNumAction   
    ├── Message.php   // 消息相关
    │   ├──feedbackAction           
    │   ├──feedingAction                反馈
    │   ├──getMessageListAction         消息列表
    │   ├──getSystemNoticeListAction    系统消息列表
    │   └──getUnreadCountAction         未读消息
    ├── Mv.php   // 视频相关
    │   ├──detail_promotionalAction   
    │   ├──list_promotionalAction   
    │   ├──wantbuy_promotionalAction   
    │   ├──getListAction   
    │   ├──getListFromElementAction   
    │   ├──getDetailAction   
    │   ├──playAction   
    │   ├──buyAction   
    │   ├──favoritesAction   
    │   ├──navAction   
    │   ├──myCartoonListAction   
    │   ├──delHistoryAction   
    │   ├──getDetailRecommendListAction   
    │   ├──searchAction   
    │   ├──getRankAction   
    │   ├──getFavoriteRankAction   
    │   ├──batFavoritesDelAction   
    │   ├──evaluationAction   
    │   └──list_buyAction   
    ├── Order.php   // 订单相关
    │   ├──goodsListAction      产品列表
    │   ├──orderListAction      订单列表
    │   ├──createPayingAction   创建支付订单
    │   ├──withdrawAction       申请提现 WithdrawController::create_withdraw 的别名
    │   ├──listWithdrawAction   提现列表 WithdrawController::list_withdraw 的别名
    │   └──exchangeAction       使用金币兑换
    ├── Package.php   // 打包数据
    │   ├──listAction   
    │   ├──list_resourceAction   
    │   ├──detailAction   
    │   ├──buyAction   
    │   └──list_buy_mvAction   
    ├── Page.php   // 这里是备注
    │   ├──detailAction   
    │   └──listAction   
    ├── Pay.php   // 未使用
    │   ├──orderListAction             
    │   ├──listDonationMonthlyAction    
    │   ├──getListAction   
    │   ├──donationAction   
    │   └──cancelDonationAction   
    ├── Pic.php   // 美图相关
    │   ├──getDetailAction   
    │   ├──favoritesAction   
    │   ├──buyAction   
    │   ├──searchAction   
    │   ├──getListAction   
    │   ├──readAction   
    │   ├──likeAction   
    │   ├──batFavoritesDelAction   
    │   ├──myBookListAction   
    │   ├──delHistoryAction   
    │   ├──getDetailRecommendListAction   
    │   ├──getRankAction   
    │   ├──getFavoriteRankAction   
    │   └──list_buyAction   
    ├── Privilege.php   // 用户的权限相关
    │   ├──getUserPrivilegeAction   
    │   ├──downloadAction           获取视频下载的链接，并且扣除次数
    ├── Proxy.php   // 代理相关
    │   ├──detailAction   
    │   ├──list_logAction   
    │   ├──listAction   
    │   └──applyAction   
    ├── Pua.php   // 把妹相关
    │   ├──homeAction   
    │   ├──list_teacherAction   
    │   ├──list_courseAction   
    │   ├──list_seriesAction   
    │   └──buy_courseAction   
    ├── Search.php   // 搜索配置
    │   └──indexAction   
    ├── Story.php   // 小说相关
    │   ├──getListAction   
    │   ├──getDetailAction   
    │   ├──readAction   
    │   ├──getSeriesAction   
    │   ├──favoritesAction   
    │   ├──evaluationAction   
    │   ├──batFavoritesDelAction   
    │   ├──searchAction   
    │   ├──getDetailRecommendListAction   
    │   ├──buyAction   
    │   ├──list_buyAction   
    │   └──read2Action   
    ├── User.php   // 用户相关
    │   ├──getCoinCardStatusAction   
    │   ├──getCoinFromCoinCardAction   
    │   ├──listMoneyDetailAction   
    │   ├──updateUserInfoAction   
    │   ├──invitationAction   
    │   ├──listInvitationAction   
    │   ├──userInfoAction   
    │   ├──getImAction   
    │   ├──getUserInfoAction   
    │   ├──getUserFavorAction   
    │   ├──favoritesAction   
    │   ├──getUserBuyAction   
    │   ├──myInvitationAction   
    │   ├──getMyRewardAction   
    │   ├──getUserProductListAction   
    │   ├──add_bankcardAction   
    │   ├──del_bankcardAction   
    │   ├──list_bankcardAction   
    │   ├──clear_cachedAction   
    │   └──im_linesAction   
    ├── Vlog.php   // 短视频相关
    │   ├──vlog_tabAction   
    │   ├──list_vlogAction          //短视频列表，含推荐
    │   ├──list_vlogtagsAction   
    │   ├──detail_vlogAction   
    │   ├──favoritesAction   
    │   └──list_buyAction           //购买短视频
    └── Withdraw.php   // 提现相关
        ├──indexAction   
        ├──create_withdrawAction    申请提现
        └─list_withdrawAction       提现列表


```