//定义要断言的数据结构
assertData =  [
    {
        "id": 27740,
        "uid": 41,
        "title": "质量",
        "coins": 0,
        "hasBuy": true,
        "duration": 20,
        "durationStr": "00:20",
        "like": 0,
        "comment": 0,
        "tags": [],
        "thumbImg": "http:\/\/image.bt0577.com\/img.xiao\/xj_feed20190917191732.jpeg",
        "thumbWidth": 0,
        "thumbHeight": 0,
        "gifImg": "",
        "gifWidth": 0,
        "gifHeight": 0,
        "playURL": "http:\/\/play.cnkamax.com\/useruploadfiles\/e3b06eb25361a651dd5a5c26d6383a57\/e3b06eb25361a651dd5a5c26d6383a57.m3u8?auth_key=1585732320-0-0-70ecc4a86c3c5a82f78d51427ab8549f&start=0&duration=30&via=kekaoyun",
        "shareUrl": "http:\/\/b.kslive.tv\/af\/DGFY",
        "hasLongVideo": false,
        "status": 1,
        "isLiked": 0,
        "isFollowed": 0,
        "isLive": false,
        "musicID": 236,
        "user": {
            "isVV": false,
            "vvLevel": 0,
            "uuid": "74e998001feb7f4ad1552c0232724d0d",
            "sexType": 2,
            "uid": 41,
            "thumb": "http:\/\/image.bt0577.com\/img.head\/91_ads_20200111FeTEqY.png",
            "nickname": ""
        }
    }
];

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