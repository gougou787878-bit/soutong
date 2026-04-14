layui.use(['HTTPUtil', 'element', 'jquery', 'table', 'laytpl', 'laydate', 'form', 'upload','layedit'], function () {
    var config = Util.config(),
        table = layui.table,
        Constant = layui.Constant,
        $ = layui.jquery,
        laytpl = layui.laytpl,
        form = layui.form,
        HTTPUtil = layui.HTTPUtil,
        laydate = layui.laydate,
        upload = layui.upload,
        layedit = layui.layedit,
        layeditIndexArray = [],
        /**
         * 绑定表格加载事件
         */
        tableIns = table.render({
            headers: {},
            elem: '#gridTab',
            url: config.url,
            "cellMinWidth":xx.config('cellMinWidth' , 100),
            toolbar: xx.config('toolbar' , '#toolbar'),
            limit: xx.config('limit' , 20),
            title: '数据表',
            cols: config.cols,
            request: {
                pageName: 'page', //页码的参数名称，默认：page
                limitName: 'limit' //每页数据量的参数名，默认：limit
            },
            page: true,
            done: function (res, curr, count, filterName) {
                filterName = $('#' + this.id).attr('lay-filter');
                /**
                 * 表格头部左侧工具栏 事件绑定
                 */
                table.on('toolbar(' + filterName + ')', function (obj) {
                    let ele = $(this)
                        , layerId = ele.attr('lay-form')
                        , fn = xx.config('event.toolbar.' + obj.event, function () {
                        layout(obj, layerId);
                    })
                        , layerEle = $("#" + layerId)
                        , nextFn = function (html, cb) {
                        layout(obj, layerId, html, cb)
                    };
                    console.log("尝试触发:event.toolbar->" + 'toolbar.' + obj.event);
                    return fn.call(this, obj, table, HTTPUtil, laytpl, layerEle, form, $, nextFn);
                });

                /**
                 * 监听工具条
                 */
                table.on('tool(' + filterName + ')', function (obj) {
                    let ele = $(this)
                        , layerId = ele.attr('lay-form')
                        , fn = xx.config('event.operate.' + obj.event, function () {
                        layout(obj, layerId);
                    })
                        , layerEle = $("#" + layerId)
                        , nextFn = function (html, cb) {
                        layout(obj, layerId, html, cb)
                    };
                    console.warn("尝试触发:event.operate->" + obj.event);
                    return fn.call(this, obj, table, HTTPUtil, laytpl, layerEle, form, $, nextFn);
                });
                /**
                 * 绑定单击数据表格某一行的事件
                 */
                table.on('row(' + filterName + ')', function (obj) {
                    //console.warn("点击事件");
                    xx.config('event.row:click', xx.emptyFn()).call(this, obj, HTTPUtil, laytpl, layout);
                });
                /**
                 * 绑定双击数据表格某一行的事件
                 */
                table.on('rowDouble(' + filterName + ')', function (obj) {
                    //console.warn("双击事件");
                    xx.config('event.row:dbClick', xx.emptyFn()).call(this, obj, HTTPUtil, laytpl, layout);
                });
                /**
                 * 监听单元格编辑
                 */
                table.on('edit(' + filterName + ')', function (obj) {
                    //console.warn("编辑事件");
                    if (obj.field === "mobile" || obj.field === 'contactPhone') {
                        if (!/^1(\d){10}$/.test(obj.value)) {
                            layer.msg("数据格式不正确", {icon: 5}, function () {
                                location.reload();
                            });
                            return false;
                        }
                    }
                    if (obj.field === 'age') {
                        if (obj.value < 10 || obj.value > 110) {
                            layer.msg("数据格式不正确", {icon: 5}, function () {
                                location.reload();
                            });
                            return false;
                        }
                    }
                    saveDataFieldValue(obj.data[xx.config('pk', 'id')], obj.field, obj.value);
                });


                table.on('sort(' + filterName + ')', function(obj){
                    let orderBy = {};
                    orderBy[obj.field] = obj.type;
                    tableIns.reload({
                        orderBy: orderBy
                    });
                    return false;
                });


                Util.config('done', xx.emptyFn()).call(null, table, table.cache[this.id], form, HTTPUtil, laytpl, $ ,res)
                setTimeout(function () {
                    $("#user-edit-dialog,#user-data-dialog,.data-dialog").each(function () {
                        var h = $(this).attr('data-h');
                        if (typeof (h) === "undefined") {
                            $(this).attr('data-h', document.body.clientHeight);
                        }
                    });
                }, 100)
            }
        });



    $('.x-date-time').each(function (key , item) {
        laydate.render({elem:item,'type':'datetime'});
    });
    $('.x-date').each(function (key , item) {
        laydate.render({elem:item});
    });
    xx.config('dateEle').split(',').forEach(function (value, index, array) {
        if ($(value).length) {
            laydate.render({elem: value});
        }
    });
    form.verify(xx.config('verify', {}));

    layedit.set({uploadImage: {url: Util.config("editUpload" , '')}});

    var layout = function (obj, layerId, html, cb) {
        obj.data = xx.config('formatRow')(obj.data, obj.event, HTTPUtil, laytpl);

        if (typeof (pageEvent) !== "undefined" && typeof (pageEvent[obj.event]) === "function") {
            let ret = pageEvent[obj.event].call(null, obj, HTTPUtil, Constant, laytpl);
            if (ret === false) {
                return
            }
        }

        if (layerId === undefined) {
            return;
        }


        let formEle = $('#' + layerId),
            width = formEle.data('w') || 800,//layer宽度
            height = formEle.data('h') || 500,//layer高度
            iframeUrl = formEle.data('iframe'),//iframeUrl
            type = iframeUrl === undefined ? 1 : 2, //layer是iframe还是html
            options = formEle.data('option') || "{}", //获取配置
            formHtmlContent = html || formEle.html(),  //html的数据
            dialog = formEle.attr('layer-dialog'),
            _config = {};


        if (dialog !== undefined) {
            _config['btn'] = dialog.split(',');
            _config['yes'] = function (index, layer) {
                if (layeditIndexArray.length){
                    //同步富文本
                    let length = layeditIndexArray.length;
                    for (let i = 0; i < length; i++) {
                        layedit.sync(layeditIndexArray.pop());
                    }
                }
                $('button.submit[lay-filter]').trigger('click');
            };
            //_config['cancel'] = function () {}
        }

        if (iframeUrl !== undefined) {
            iframeUrl = laytpl(iframeUrl).render(obj.data);
        }

        laytpl(formHtmlContent).render(obj.data, function (html) {
            let layoutIndex = layer.open($.extend(_config, {
                type: type
                , area: [width + 'px', height + 'px']
                , content: iframeUrl || html //这里content是一个DOM，注意：最好该元素要存放在body最外层，否则可能被其它的相对元素所影响
                , success: function () {
                    form.render(null, 'form-save');
                    Util.event('render', form, obj, $);
                    setTimeout(renderLayEdit , 200);
                    Util.formSubmit(config.saveUrl, form, HTTPUtil,null,function (json) {
                        layer.close(layoutIndex);
                        if (obj.event === "addUser") {
                            //添加
                            Util.msgOk(json.msg);
                            tableIns.reload()
                        } else {
                            //修改
                            obj.update(json.data);
                            let index = $(obj.tr).data('index')
                            table.cache['gridTab'][index] = json.data;
                            Util.msgOk(json.msg);
                        }
                    });
                    Util.uploader('button.but-upload-img', config.uploadUrl, layui.upload, layui.jquery);
                }, end: function () {
                    Util.event('end', form, obj);
                }
            }, options = xx.jsonParse(options)));
            if (options['full'] === true) {
                layer.full(layoutIndex);
            }
            if (typeof(cb) === "function"){
                cb(layoutIndex)
            }
        });

    };


    function renderLayEdit() {
        let edit = $('.layedit');
        if (edit.length) {
            edit.each(function (key, item) {
                layeditIndexArray.push(layedit.build($(item).attr('id')));
            })
        }
    }

    xx.use({
        'getEleData': function (that) {
            var othis = $(that);
            if (othis.parents('tr').length >= 1) {
                var  field = othis.parents('td').eq(0).data('field')
                    , index = othis.parents('tr').eq(0).data('index')
                    , objectData = table.cache[tableIns.config.id][index];
            } else {
                objectData = {};
            }
            return objectData;
        }
    });


    //监听行工具事件
    $('.layui-card-body').on('click', '.layout-open', function () {
        var layerId = undefined,objectData = xx.config('getEleData')(this);
        let object = {
            "data": objectData,
            'event': $(this).attr('lay-event'),
            'obj': xx.emptyFn(),
            'update': xx.emptyFn()
        };
        if (this.hasAttribute('lay-form')) {
            layerId = $(this).attr('lay-form');
        }

        layout(object, layerId)
    });
    /**
     * 绑定搜索事件
     */
    form.on('submit(search)', function (data) {
        var where = {}, ary = data.field, k;
        for (k in ary) {
            if (ary.hasOwnProperty(k) && ary[k].length > 0) {
                if (k.substring(k.length - 4) === 'Time' && /^\d{4}-\d{2}-\d{2}$/.test(ary[k])) {
                    ary[k] += " 00:00:00";
                }
                where[k] = ary[k];
            }else{
                where[k] = "__undefined__"
            }
        }
        xx.config('event.search', xx.emptyFn()).call(this, HTTPUtil, laytpl, where);
        tableIns.reload({
            where: where,
            page: {curr: 1}
        });
        return false;
    });


    function saveDataFieldValue(id, field, value) {
        if (config.url) {
            var data = {};
            data[field] = value;
            data['_pk'] = id;
            HTTPUtil.post(config.saveUrl, data).then(function (json) {
                layer.msg(json.msg);
            });
        }
    }


    $('body').on('change', 'select.lay-select', function () {
        let
            index = $(this).parents('tr').eq(0).data('index')
            , field = $(this).parents('td').eq(0).data('field')
            , data = table.cache.gridTab[index]
            , value = $(this).val();
        saveDataFieldValue(data[xx.config('pk', 'id')], field, value);
    });

});


