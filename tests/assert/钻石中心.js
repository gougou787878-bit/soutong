//定义要断言的数据结构
assertData = {
    "user": {
        "coins": 295771,
        "isVV": true,
        "expiredStr": "2020/01/08",
        "thumb": "http://imgpublic.ycomesc.com/img.head/91_default_user_header_logo_nologin.jpg",
        "nickname": "游客-16",
        "username": ""
    },
    "ads": [
        {
            "id": 0,
            "title": "",
            "img_url": "",
            "url": "",
            "type": 1,
            "ios_url": "",
            "android_url": "",
            "value": 0
        }
    ],
    "diamond": {
        "online":[{
            "img": "",
            "op": 12.34,
            "p": 12.34,
            "coins": 300,
            "free_coins": 10,
            "id": 5,
            "pname": "300钻石",
            "pt": "online",
            "description": "",
            "pw": [
                //可以悬着的支付类型pa
                "pa",//支付宝
                "pb", //银联
                "pv", //visa
                "pw" //微信
            ]
        }],
        "agent":[{
            "img": "",
            "op": 12.34,
            "p": 12.34,
            "coins": 300,
            "free_coins": 10,
            "id": 5,
            "pname": "300钻石",
            "pt": "online",
            "description": "",
            "pw": [ ]
        }]
    },
    "Liang": {
        "name": "靓号购买",
        "moreTitle": "查看更多",
        "moreUrl": "",
        "list": [
            {
                "id": 6,
                "name": "321321",
                "length": 6,
                "needcoin": 321321,
                "addtime": 1576848182,
                "uid": 0,
                "buytime": 0,
                "orderno": 0,
                "status": 0,
                "state": 1,
                "coin_date": "0钻石"
            }
        ]
    },
    "car": {
        "name": "砖石产品",
        "moreTitle": "查看更多",
        "moreUrl": "",
        "list": [
            {
                "id": 7,
                "name": "超跑",
                "thumb": "/data/upload/20190925/5d8b4885db9d4.png",
                "swf": "/data/upload/20190924/5d8a1dbf53a05.svga",
                "swftime": "10.00",
                "needcoin": 1000,
                "orderno": 0,
                "addtime": 1569332671,
                "words": "开着超跑进入房间",
                "uptime": 0,
                "status": 1,
                "expired_at": 1,
                "coin_date": "1,000钻石"
            }
        ]
    }
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