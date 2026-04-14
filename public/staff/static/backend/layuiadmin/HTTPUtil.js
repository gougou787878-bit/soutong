layui.define(function (exports) {


    let obj2params = (obj) => {
        let result = '';
        for (let item in obj) {
            result += `&${item}=${encodeURIComponent(obj[item])}`
        }
        return result ? result.slice(1) : result
    }
        , _fetch = function (url, method, paramsObj) {


        let options = {
            method: method,
            /* 携带cookie */
            credentials: 'include',
            headers: {
                'Accept': 'appliaction/json,text/plain,*/*',
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
                //"TOKEN": localStorage.getItem('token')
            }
        };

        if (method.toLowerCase() === "post" || method.toLowerCase() === "put") {
            options["body"] = obj2params(paramsObj)
        }


        return fetch(url, options)
            .then(res => {
                if (res.status === 200) {
                    //判断请求
                    return res.json();
                }
                res.text().then(text => {
                    return Promise.reject(new Error(`${url}-->${text}-->${res.status}`))
                })
            }).then(json => {
                if (json.code === 401) {
                    localStorage.removeItem('token');
                    top.location.reload();
                    return {"code": 1, "msg": ""};
                }
                return json;
            })
    }
        , post = (url, paramsObj) => {
        return _fetch(url, 'POST', paramsObj)
    }
        , get = (url, paramsObj) => {
        return _fetch(url, 'GET', paramsObj)
    }
        , del = (url, paramsObj) => {
        return _fetch(url, 'DELETE', paramsObj)
    }
        , put = (url, paramsObj) => {
        return _fetch(url, 'PUT', paramsObj)
    };


    window.ajax = {
        "get": get,
        "post": post,
        "del": del,
        "delete": del,
        "put": put
    };

    exports('HTTPUtil', window.ajax);
});

