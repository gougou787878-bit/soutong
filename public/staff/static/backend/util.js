Util = {
    _config: {
        //加载表格数据的url
        "url": undefined,
        "saveUrl": undefined,
        "delUrl": undefined,
        "cols": [],
        "title": "数据表",
        "dateEle": "#date1",
        //事件
        "event": {
            "search": function (ajax, laytpl, where) {

            },
            /**
             * 表格左侧事件
             */
            "toolbar": {
                /**
                 * 事件原型
                 * @param event  事件
                 * @param table
                 * @param ajax  数据请求累
                 * @param laytpl
                 * @param layerEle  对象绑定的layer层的元素
                 * @param form
                 * @param $
                 * @param nextFn  callback(html , function(layerIndex){}) 下一步执行
                 * @private
                 */
                "_original": function (event, table, ajax, laytpl, layerEle, form, $, nextFn) {
                },
                "delSelect": function (event, table, ajax, laytpl, layerEle, form, $, nextFn) {
                    var checkStatus = table.checkStatus(event.config.id), data = checkStatus.data,
                        pkValAry = [],
                        pkName = $(this).data('pk'),
                        url = Util.config('delAllUrl', false);

                    for (var i = 0; i < data.length; i++) {
                        if (typeof (data[i][pkName]) !== "undefined") {
                            pkValAry.push(data[i][pkName])
                        }
                    }
                    if (pkValAry.length === 0) {
                        return Util.msgErr('请先选择行');
                    }

                    if (url) {
                        layer.confirm('真的删除吗?', function (index) {
                            layer.close(index);
                            ajax.post(url, {"value": pkValAry.join(',')})
                                .then(function (json) {
                                    if (json.code) {
                                        Util.msgErr(json.msg);
                                    } else {
                                        Util.msgOk(json.msg, Util.reload);
                                    }
                                })
                        });
                    } else {
                        Util.msgErr('删除链接功能未启用');
                    }
                }
            },
            "operate": {
                "_original": function (event, table, ajax, laytpl, layerEle, form, $, nextFn) {
                },
                "edit": function (event, table, ajax, laytpl, layerEle, form, $, nextFn) {
                    nextFn();
                    Util.renderSelect(event.data, $, form);
                },
                //删除方法
                "del": function (event, table, ajax, laytpl, layerEle, form, $, nextFn) {
                    var url = Util.config('delUrl', false), $this = $(this)
                    if (url) {
                        layer.confirm('真的删除吗?', function (index) {
                            layer.close(index);
                            ajax.post(url, {"_pk": $this.data('pk')})
                                .then(function (json) {
                                    if (json.code) {
                                        Util.msgErr(json.msg);
                                    } else {
                                        //Util.msgOk(json.msg, Util.reload);
                                        Util.msgOk(json.msg);
                                        event.del();
                                    }
                                })
                        });
                    } else {
                        Util.msgErr('删除链接功能未启用');
                    }
                }
            },
            /**
             * 单击数据表格一行的事件，
             * @param obj
             * @param ajax
             * @param laytpl
             * @param layout
             */
            'row:click': function (obj, ajax, laytpl, layout) {
                //console.log(obj.tr); //得到当前行元素对象
                //console.log(obj.data) //得到当前行数据
                //obj.del(); //删除当前行
                //obj.update(fields) //修改当前行数据
            },
            /**
             * 双击击数据表格一行的事件，
             * @param obj
             * @param ajax
             * @param laytpl
             * @param layout
             */
            'row:dbClick': function (obj, ajax, laytpl, layout) {
                //console.log(obj.tr); //得到当前行元素对象
                //console.log(obj.data) //得到当前行数据
                //obj.del(); //删除当前行
                //obj.update(fields) //修改当前行数据
            },
            'on': {}
        },
        /**
         * 在打开层的时候，对数据进行格式化
         * @returns {*}
         * @param data  格式化数据
         * @param eventName  数据来之什么事件
         * @param ajax
         * @param laytpl
         */
        formatRow: function (data, eventName, ajax, laytpl) {
            if (data === undefined) {
                data = {};
            }
            return data;
        },
        /**
         * 提交表单时候需要的验证
         */
        'verify': {
            __username: function (value, item) { //value：表单的值、item：表单的DOM对象
                if (/^\d+\d+\d$/.test(value)) {
                    return '用户名不能全为数字';
                }
            }
            //我们既支持上述函数式的方式，也支持下述数组的形式
            //数组的两个值分别代表：[正则匹配、匹配不符时的提示文字]
            , __pass: [
                /^[\S]{6,12}$/
                , '密码必须6到12位，且不能出现空格'
            ]
        },
        "done": function (table, data, from, ajax, tpl, $, res) {
            if (res.hasOwnProperty('desp')) {
                let desp = res['desp'];
                let el = $('span#total-id');
                if (desp.length > 0) {
                    el.text(desp)
                }
            }
        }
    },
    eq: function () {
        for (var args = arguments, v = args[0], i = 1
            ; i < args.length
            ; i++) {
            if (v !== args[i]) return false;
            v = args[i];
        }
        return true;
    },
    extend: function () {
        var length = arguments.length,
            target = arguments[0] || {},
            source, i, key;
        if (typeof target != "object") {
            target = {};
        }
        for (i = 1; i < length; i++) {
            source = arguments[i];
            for (key in source) {
                if (target.hasOwnProperty(key)
                    && this.eq(typeof (target[key]), typeof (source[key]), "object")) {
                    target[key] = this.extend(target[key], source[key]);
                    continue;
                }
                target[key] = source[key];
            }
        }
        return target;
    },
    /**
     * 设置配置
     * @param config
     */
    use: function (config) {
        Util._config = Util.extend(Util._config, config);
    },
    isUndefined: function (val) {
        return typeof (val) == "undefined";
    },
    emptyFn: function () {
        return function () {
        }
    },
    jsonParse: function (text) {
        var _json = {};
        eval("_json=" + text + ';');
        return _json;
    },
    /**
     * 获取配置
     * @returns {Util._config|{}}
     */
    config: function (name, _default) {
        if (Util.isUndefined(name)) {
            return Util._config;
        }
        _default = arguments.length > 1 ? _default : null;
        name = name + "";
        let keys = name.split('.');
        let config = Util._config;

        for (let i = 0; i <= keys.length; i++) {
            if (i === keys.length) {
                return config;
            }
            if (config.hasOwnProperty(keys[i])) {
                config = config[keys[i]];
            } else {
                config = undefined;
                break;
            }
        }
        if (typeof (config) === "undefined") {
            return _default;
        }
    },
    CR: {
        img: function (src, w, h, style) {
            let option = {};
            if (typeof (w) === "object") {
                w = option['w'];
                h = option['h'];
                style = option['css'];
            } else {
                w = w || 80;
                h = h || 100;
                style = style || '';
            }
            return "<img src='" + src + "' width='" + w + "' height='" + h + "' style='" + style + "' />"
        }
    },
    /**
     * 重新家在页面
     */
    reload: function () {
        location.reload()
    },
    uploader: function (selector, url, upload, $) {
        let index = undefined;
        let p = $(selector).attr('data-json')|| '{}';
        eval('p='+p+';');
        upload.render({
            elem: selector //'button.but-upload-img'
            , url: url
            , auto: true
            , headers: {
                //'token': localStorage.getItem('token')
            }
            ,data:p
            , field: 'file'
            , accept: 'file'
            , before: function (obj) {
                let eUrl = this.item.data('url') || false;
                this.url = eUrl ? eUrl : url;
                obj.preview((index, file, result) => {
                    let post = this.item.data('json') || '{}';
                    if (typeof(post) ==='string'){
                        eval('post='+post+';');
                    }
                    this.data = post;
                    $(this.item.data('img')).attr('src', result); //图片链接（base64）
                });
                index = layer.load();
            }
            , done: function (res) {
                layer.close(index);
                if (res.code === 200) {
                    $(this.item.data('input')).val(res.data.url).change();
                    Util.msgOk('上传成功');
                }else{
                    Util.msgErr(res.msg);
                }
            }
            , error: function () {
                layer.close(index);
            }
        });
    },
    /**
     * 上传多图片（一张一张的传）
     * @param selector
     * @param url
     * @param upload
     * @param $
     * @param inputname
     */
    uploaderMany: function (selector, url, upload, $, inputname) {
        let index = undefined;
        upload.render({
            elem: selector //'button.but-upload-img'
            , url: url
            , auto: true
            , headers: {
                //'token': localStorage.getItem('token')
            }
            , field: 'file'
            , before: function (obj) {
                obj.preview((index, file, result) => {
                    $(this.item.data('img')).attr('src', result); //图片链接（base64）
                });
                index = layer.load();
            }
            , done: function (res) {
                layer.close(index);
                if (res.code === 200) {
                    var str = '';
                    str += '<div style="float: left;margin-right: 10px;margin-bottom: 10px;cursor: pointer;">';
                    str += '<img class="layui-upload-img" width="120" height="120" src="' + res.data.url + '">';
                    str += '<input type="hidden" name="' + inputname + '" value="' + res.data.url + '">';
                    str += '</div>';

                    $(this.item.data('div')).append(str);
                    $(function () {
                        $("#upload-img-many div").click(function () {
                            $(this).remove();
                        });
                    })
                }
            }
            , error: function () {
                layer.close(index);
            }
        });
    },

    /**
     * 保存数据
     * @param url
     * @param form
     * @param ajax
     * @param filterName
     * @param successFn
     * @param errorFn
     */
    formSubmit: function (url, form, ajax, filterName, successFn, errorFn) {
        filterName = filterName || "save";
        form.on('submit('+filterName+')', function (data) {
            if (url) {
                Util.event('save', form, data, ajax);
                let i = top.layer.load(0, {time: 6 * 1000, shade: [0.5, '#000']});
                ajax.post(url, data.field).then(function (json) {
                    top.layer.close(i);
                    if (json.code) {
                        if (typeof(errorFn) !== "undefined"){
                            errorFn(json);
                        }else{
                            Util.msgErr(json.msg);
                        }
                    } else {
                        if (typeof(successFn) !== "undefined"){
                            successFn(json);
                        }else{
                            Util.msgOk(json.msg, Util.reload);
                        }
                    }
                });
            }
            return false;
        });
    },
    on: function (event, callback) {
        if (typeof (Util._config.event.on[event]) === "undefined") {
            Util._config.event.on[event] = [];
        }
        Util._config.event.on[event].push(callback);
    },

    event: function (event, form) {
        var fn = Util.config('event.on.' + event, undefined);
        if (typeof (fn) !== "undefined") {
            var args = [];
            for (let i = 1; i < arguments.length; i++) {
                args.push(arguments[i])
            }
            for (let i = 0; i < Util._config.event.on[event].length; i++) {
                Util._config.event.on[event][i].apply(null, args);
            }
        }
    },
    jsonOk: function (json) {
        return new Promise(function (resolve, reject) {
            if (json.code === 200) {
                resolve(json.data);
            } else {
                reject(json.msg);
            }
        });
    },

    /**
     * 弹出成功消息
     * @param msg
     * @param fn
     */
    msgOk: function (msg, fn) {
        layer.msg(msg, {time: 1200}, fn)
    },
    /**
     * 弹出失败消息
     * @param msg
     * @param fn
     */
    msgErr: function (msg, fn) {
        layer.msg(msg, {time: 1600,icon: 5,anim: 6}, fn)
    },
    /**
     * 显示图片
     * @param obj
     */
    showImg: function (obj) {
        layer.open({
            type: 1,
            title: false,
            closeBtn: 0,
            //    area: ['656px', '656px'],
            skin: 'layui-layer-nobg', //没有背景色
            shadeClose: true,
            content: "<img src='" + obj.src + "'>"
        });

    },
    islogin: function () {
        return !!localStorage.getItem('token');
    },
    renderSelect: function (data,$, form) {
        //<select data-value="{{d.oauth_type}}"></select>
        let eles = $("select[data-value]");
        for (let i = 0; i < eles.length; i++) {
            let that = eles.eq(i),
                val = that.data('value'),
                options = that.find('option');
            for (let j = 0; j < options.length; j++) {
                let $that = options.eq(j);
                if ($that.val() == val) {
                    $that.attr('selected', 'true');
                    break;
                }
            }
        }
        let array = {};
        xx.each($('input[type=radio]'), function (item, key) {
            if (!array.hasOwnProperty(item.name)){
                array[item.name] = [];
            }
            array[item.name].push(item);
        });
        for (let key in array) {
            let val = data[key];
            xx.each(array[key],function (item) {
                console.log(item,val);
                item = $(item);
                if (val == $(item).val()){
                    item.attr('checked' , 'true');
                }
            });
        }


       setTimeout(function () {
           form.render('select');
           form.render('radio');
       },500)
    },
    each: function (array, cb) {
        for (let i = 0; i <array.length; i++) {
            cb(array[i] , i , array);
        }
    },
    queryEncode: function (json) {
        return Object.keys(json).map(function (key) {
            // body...
            return encodeURIComponent(key) + "=" + encodeURIComponent(json[key]);
        }).join("&");
    }
};
xx = Util;
Log = {
    "error": function () {
        console.error.apply(console, arguments);
    },
    "debug": function () {
        console.debug.apply(console, arguments);
    },
    "warn": function () {
        console.warn.apply(console, arguments);
    },
    "info": function () {
        console.info.apply(console, arguments);
    },
};


function in_array(search, array) {
    for (var i in array) {
        if (array[i] == search) {
            return true;
        }
    }
    return false;
}

