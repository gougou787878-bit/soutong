//定义要断言的数据结构
assertData = {
    "ads": [],
    "tab": [
        {
            "default": true,
            "name": "关注",
            "api": "\/api.php\/v2\/ChargeVideo\/follow",
            "params": [],
            "list": []
        },
        {
            "default": true,
            "name": "推荐",
            "api": "\/api.php\/v2\/ChargeVideo\/recommend",
            "params": [],
            "list": []
        },
        {
            "default": false,
            "name": "自慰",
            "api": "\/api.php\/v2\/ChargeVideo\/tag",
            "params": {
                "tag": "自慰",
                "lastIndex": 0
            },
            "list": []
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