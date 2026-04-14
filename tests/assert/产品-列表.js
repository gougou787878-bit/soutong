//定义要断言的数据结构
assertData = {
    "ads": [],
    "list": {
        "online": [
            {
                "img": "http:\/\/image.bt0577.com\/img.ads\/91_ads_20200219115923595.png",
                "op": 55,
                "p": 50,
                "coins": 0,
                "free_coins": 0,
                "id": 2,
                "pname": "月度會員36天",
                "pt": "online",
                "valid_date": "有效期:30天",
                "description": "每日領取5鑽石",
                "pw": [
                    "pa",
                ]
            }
        ],
        "agent": [
            {
                "img": "http:\/\/image.bt0577.com\/img.ads\/91_ads_20200218162814378.png",
                "op": 55,
                "p": 50,
                "coins": 0,
                "free_coins": 0,
                "id": 15,
                "pname": "月度會員36天",
                "pt": "agent",
                "valid_date": "有效期:30天",
                "description": "每日領取5鑽石",
                "pw": [
                    "pa",
                ]
            }
        ]
    },
    "desc": "1.如遇多次充值失败，长时间未到账且消费金额未返还情况，请在【个人中心】-【意见反馈】中联系客服，发送支付截图凭证为您处理。## 2.请尽量在生成订单的两分钟内支付，若不能支付可以尝试重新发起订单请求。",
    "user": {
        "coins": 24,
        "isVV": true,
        "expiredStr": "2022\/02\/02",
        "thumb": "http:\/\/image.bt0577.com\/img.head\/91_ads_20200111FeTEqY.png",
        "nickname": "游客账号_5624098"
    },
    "privilege": [
        {
            "name": "无限观看",
            "coins_url": "http:\/\/image.bt0577.com\/img.ads\/91_ads_20200113ADnxYh1578898195594.png"
        },
        {
            "name": "钻石福利",
            "coins_url": "http:\/\/image.bt0577.com\/img.ads\/91_ads_20200113UXJkXN1578898257550.png"
        },
        {
            "name": "专属铭牌",
            "coins_url": "http:\/\/image.bt0577.com\/img.ads\/91_ads_20200113uIsUpt1578898337529.png"
        },
        {
            "name": "昵称变色",
            "coins_url": "http:\/\/image.bt0577.com\/img.ads\/91_ads_20200113tOU70x1578898350499.png"
        }
    ]
};


//之后的代码直接复制可用
//模板数据断言
assertTest = function (data, assertData, keyStr) {
    var tmp = keyStr || "", tmpStr = tmp, key;
    client.assert(typeof (data) === typeof (assertData), "预期类型不对");
    for (key in assertData) {
        tmpStr = tmp + "[" + key + "]";
        if (!data.hasOwnProperty(key)) {
            client.assert(false, tmpStr + " 字段不存在");
        } else if (typeof (assertData[key]) !== typeof (data[key])) {
            client.assert(false, tmpStr + " 预期类型不对");
        } else if (typeof (assertData[key]) === "object") {
            assertTest(data[key], assertData[key], tmpStr)
        }
    }
};
//全局断言
assertGlobal = function (response) {
    client.assert(response.status === 200, "Response status is not 200");
    client.assert(typeof (response.body) === "object", "返回的数据结构错误");
    var body = response.body;
    client.assert(body.errcode === 0, "解密失败");
    client.assert(typeof (body.data) === "object", "解密后，内部数据结构错误");
    return body.data.data;
};

//开始执行
client.test("Request executed successfully", function () {
    assertGlobal(response);
    assertTest(response.body.data.data, assertData);
});