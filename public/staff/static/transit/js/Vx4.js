Object.defineProperty(Array.prototype, 'chunk', {
    value: function (chunkSize) {
        var ary = [], i = 0;
        for (; i < this.length; i += chunkSize) {
            ary.push(this.slice(i, i + chunkSize));
        }
        return ary;
    }
});
Object.defineProperty(Array.prototype, 'random', {
    value: function () {
        var index = parseInt((Math.random() * 1000) % this.length),  result = undefined, ary = [] , v , i ;
        for (i = this.length - 1; i >= 0; i--) {
            v = this.pop();
            i !== index ? ary.push(v) : (result = v)
        }
        for (var j = 0 , len = ary.length; j < len; j++) {
            this.push(ary.pop())
        }
        return result;
    }
});
Vx = {
    range: function (start , end){
        var arr = [];
        for (; start <= end; start++) {
            arr.push(start);
        }
        return arr;
    },
    query: function (name){
        var regpx = new RegExp(name + "=([^&]+)");
        var matches = location.href.match(regpx);
        return matches ? decodeURIComponent(matches[1]) : '';
    },
    map : function (arr , cb){
        var result = [];
        for (var i = 0; i < arr.length; i++) {
            result.push(cb(arr[i] , i , arr) || arr[i])
        }
        return result;
    },
    template: function (id, data) {
        data = data || {};
        var htmlMap = window.__htmlMap || {}, matchMap = window.__matchMap || {}, j = 0, tmp
        if (!htmlMap.hasOwnProperty(id)) {
            tmp = document.getElementById(id);
            htmlMap[id] = tmp ? tmp.innerHTML : "";
            matchMap[id] = htmlMap[id].match(/\$\{([^}]+)\}/g) || [];
            window.__htmlMap = htmlMap;
            window.__matchMap = matchMap;
        }
        var result = htmlMap[id], matches = matchMap[id];
        for (; j < matches.length; j++) {
            tmp = matches[j].substring(2, matches[j].length - 1);
            result = result.replace(matches[j], data[tmp] || "undefined");
        }
        return result;
    },
    ping: function (url ,i) {
        let src = url + "/ping.gif";
        return new Promise(function (resolve , reject){

            try {
                var ele = (new Image);
                ele.onload = function (){
                    resolve({"url":url , "i":i})
                }
                ele.onerror = function (){
                    reject({"url":url , "i":i})
                }
                ele.src = src
            }catch (e){
                throw Error("aaaa");
            }finally {
                return 11;
            }

        })
    },
    append : function (ele , code){
        var tmpWrap = document.createElement('div')
        tmpWrap.innerHTML = code;
        ele.append(tmpWrap.firstElementChild)
    },
    expand : function (ob , object){
        for (let key in object) {
            ob.prototype[key] = object[key]
        }
    },
    task : function (){
        this.tasks = [];
        this.runNum = 0;
    }
};
Vx.task.prototype = {
    "push": function (task) {
        this.tasks.push(task);
    },
    'run': function () {
        for (var i = 0; i < this.tasks.length; i++) {
            setTimeout(function (cb, that) {
                cb(),that.runNum++;
            }, 10, this.tasks[i], this);
        }
    },
    "done": function () {
        return this.runNum === this.tasks.length;
    }
};
function Process(){
    this.events = {};
}
Process.prototype = {
    task: function (tasks, chunkSize) {
        this.tasks = tasks.chunk(chunkSize).reverse();
        this.index = 0;
        this.stop = false;
        this.timerId = undefined;
    },
    on: function (event, cb) {
        this.events[event] = cb;
    },
    emit: function (eventName, args) {
        var event = this.events[eventName] || new Function();
        event.apply(this, args);
    },
    _finish: function () {
        this.stop = false;
        if (this.timerId !== undefined) {
            clearInterval(this.timerId);
            this.timerId = undefined;
        }
        this.emit('_finish');
    },
    _listener: function ($task){
        if (this.timerId === undefined){
            this.timerId = setInterval(function ($task , that) {
                if ($task.done()) {
                    that.emit('tick')
                    that.start();
                }
            }, 500 , $task, this);
        }
    },
    start: function (){
        if (this.stop){
            this.emit('stop');
            this._finish();
            return ;
        }
        if (this.tasks.length) {
            var $task = new Vx.task(), tasks = this.tasks.pop() , i
            for (i = 0; i < tasks.length; i++) {
                $task.push(tasks[i])
            }
            $task.run();
            this._listener($task);
        } else {
            this._finish();
            this.emit('done')
        }
    }
}