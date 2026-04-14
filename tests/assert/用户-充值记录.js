//定义要断言的数据结构
assertData = {
    "lastIndex": 26,
    "list": [
        {
            "id": 26,
            "product_id": 21,
            "order_type": 0,
            "order_id": "2020012411540605977",
            "descp": "15000钻石",
            "amount": 200000,
            "pay_amount": 0,
            "payway": "visa",
            "status": 0,
            "msg": "",
            "updated_at": 1579838021,
            "created_at": 1579838021,
            "expired_at": 0,
            "pay_type": "online",
            "desc_img": "",
            "oauth_type": "android",
            "build_id": "0",
            "updated_str": "2020-01-24 11:53:41",
            "created_str": "2020-01-24 11:53:41",
            "amount_rmb": "2000.00",
            "pay_amount_rmb": "0.00"
        }
    ],
    "total": 25
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