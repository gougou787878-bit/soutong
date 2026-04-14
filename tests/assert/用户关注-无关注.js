//定义要断言的数据结构
assertData = {
    "followRecommend": [
        {
            "thumb": "http:\/\/image.bt0577.com\/img.head\/91_ads_20200111FeTEqY.png",
            "uid": 41,
            "uuid": "74e998001feb7f4ad1552c0232724d0d",
            "level": 1,
            "nickname": "",
            "coins": 0,
            "coins_total": 0,
            "votes": "0.00",
            "votes_total": "0.00",
            "consumption": 0,
            "level_anchor": 1,
            "follows": 0,
            "live_count": 0,
            "fans_count": 0,
            "likes_count": 0,
            "videos_count": 10,
            "sexType": 2,
            "birthday": "",
            "person_signnatrue": "这家伙很懒，什么都没留下",
            "car": {
                "id": 0,
                "swf": "",
                "swftime": "0",
                "words": ""
            },
            "is_reg": 0,
            "beauty_no": "0",
            "aff": "a",
            "url": "https:\/\/a.kslive.tv\/af\/a",
            "share_url": "什么！快手悄悄出了成人版？已有百万老铁在上传国语性爱视频，还有美女主播答应你的“无理”要求，白嫖也能赚钞票，一切都在成人快手！https:\/\/a.kslive.tv\/af\/a （因包含色情内容被微信、QQ屏蔽，请复制链接在浏览器中打开）",
            "isVV": false,
            "vvLevel": 0,
            "expire_date": "已过期",
            "token": "a7b966f81a345b2ab8914dbdd09e218b",
            "auth_status": 1,
            "is_follow_live": false,
            "background_pic": "http:\/\/image.bt0577.com\/img.live\/icon\/live\/2020_bannaer.jpg",
            "left_count": 10,
            "video": [
                {
                    "id": 604,
                    "uid": 41,
                    "title": "那期待的小眼神就等着你过来",
                    "coins": 0,
                    "hasBuy": true,
                    "duration": 4,
                    "durationStr": "00:04",
                    "like": 2,
                    "comment": 0,
                    "tags": [
                        "原创视频",
                        "高清",
                        "自拍作品"
                    ],
                    "thumbImg": "http:\/\/image.bt0577.com\/img.xiao\/5616b5184e74149c618e163a95389cff.jpg",
                    "thumbWidth": 544,
                    "thumbHeight": 928,
                    "gifImg": "",
                    "gifWidth": 0,
                    "gifHeight": 0,
                    "playURL": "http:\/\/play.cnkamax.com\/useruploadfiles\/twitter\/5616b5184e74149c618e163a95389cff\/5616b5184e74149c618e163a95389cff.m3u8?auth_key=1584953027-0-0-c03b71622d788dbed47026e5501169e1&start=0&duration=30&via=kekaoyun",
                    "shareUrl": "https:\/\/b.kslive.tv\/af\/DGFY",
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
            ]
        }
    ],
    "followLive": [],
    "followMv": []
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