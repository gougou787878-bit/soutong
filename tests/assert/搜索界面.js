//定义要断言的数据结构
assertData = {
    "hotSearch": [
        "热搜", "热搜1", "热搜2"
    ],
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
    "data": [
        {
            "name": "今日热播视频",
            "type": "hotPlay",
            "list": [
                {
                    "id": 48739,
                    "uid": 4980633,
                    "title": "偷干    人妻",
                    "coins": 0,
                    "hasBuy": true,
                    "duration": 25,
                    "durationStr": "00:25",
                    "like": 10,
                    "comment": 0,
                    "tags": [
                        "人妻"
                    ],
                    "thumbImg": "http:\/\/image.bt0577.com\/img.xiao\/91_1566599881183_4333.jpeg",
                    "thumbWidth": 360,
                    "thumbHeight": 640,
                    "playURL": "http:\/\/play.cnkamax.com\/useruploadfiles\/9ba27ddd2547f6d8a81800074ff2cf77\/9ba27ddd2547f6d8a81800074ff2cf77.m3u8?auth_key=1584354387-0-0-dea536b2098d571e57a6a92c32094e05&start=0&duration=30&via=kekaoyun",
                    "shareUrl": "http:\/\/a.kslive.tv\/?code=9bq7",
                    "hasLongVideo": false,
                    "status": 1,
                    "isLiked": 0,
                    "isFollowed": 0,
                    "isLive": false,
                    "musicID": 0,
                    "user": {
                        "isVV": false,
                        "vvLevel": 0,
                        "uuid": "9be30b3ac1ef56575b73bf599afd0fda",
                        "sexType": 2,
                        "uid": 4980633,
                        "thumb": "http:\/\/image.bt0577.com\/img.head\/91_ads_20200111FeTEqY.png",
                        "nickname": "游客账号_4980633"
                    }
                }
            ]
        },
        {
            "name": "今日热播视频",
            "type": "hotBuy",
            "list": [
                {
                    "id": 48739,
                    "uid": 4980633,
                    "title": "偷干    人妻",
                    "coins": 0,
                    "hasBuy": true,
                    "duration": 25,
                    "durationStr": "00:25",
                    "like": 10,
                    "comment": 0,
                    "tags": [
                        "人妻"
                    ],
                    "thumbImg": "http:\/\/image.bt0577.com\/img.xiao\/91_1566599881183_4333.jpeg",
                    "thumbWidth": 360,
                    "thumbHeight": 640,
                    "playURL": "http:\/\/play.cnkamax.com\/useruploadfiles\/9ba27ddd2547f6d8a81800074ff2cf77\/9ba27ddd2547f6d8a81800074ff2cf77.m3u8?auth_key=1584354387-0-0-dea536b2098d571e57a6a92c32094e05&start=0&duration=30&via=kekaoyun",
                    "shareUrl": "http:\/\/a.kslive.tv\/?code=9bq7",
                    "hasLongVideo": false,
                    "status": 1,
                    "isLiked": 0,
                    "isFollowed": 0,
                    "isLive": false,
                    "musicID": 0,
                    "user": {
                        "isVV": false,
                        "vvLevel": 0,
                        "uuid": "9be30b3ac1ef56575b73bf599afd0fda",
                        "sexType": 2,
                        "uid": 4980633,
                        "thumb": "http:\/\/image.bt0577.com\/img.head\/91_ads_20200111FeTEqY.png",
                        "nickname": "游客账号_4980633"
                    }
                }
            ]

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