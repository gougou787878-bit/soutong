var sDialog = (function () {

    function getAnimationEndName(dom) {
        let cssAnimation = ["animation", "webkitAnimation"];
        let animationEnd = {
            "animation": "animationend",
            "webkitAnimation": "webkitAnimationEnd"
        };
        for (var i = 0; i < cssAnimation.length; i++) {
            if (dom.style[cssAnimation[i]] !== undefined) {
                return animationEnd[cssAnimation[i]];
            }
        }
        return undefined;
    }

    function addClass(e, c) {
        let newclass = e.className.split(" ");
        if (e.className === "") newclass = [];
        newclass.push(c);
        e.className = newclass.join(" ");
    }

    function extend(source, target) {
        for (let key in target) {
            source[key] = target[key];
        }
        return source;
    }

    function getFontSize() {
        let clientWidth = document.documentElement.clientWidth;
        if (clientWidth < 640) {
            return 16 * (clientWidth / 375) + "px";
        } else {
            return 16;
        }
    }

    let layer = {
        initOpen: function initOpen(dom, options) {
            dom.style.fontSize = getFontSize();

            let body = document.querySelector("body");
            let bg = document.createElement("div");
            addClass(bg, "dialog-mobile-bg");
            if (options.showBottom === true) {
                addClass(bg, "animation-bg-fadeIn");
            }

            if (options.bottom) {
                bg.addEventListener("click", function () {
                    handleClose();
                });
            }

            body.appendChild(bg);
            body.appendChild(dom);

            const animationEndName = getAnimationEndName(dom);

            function handleClose() {
                if (animationEndName) {
                    layer.close([bg]);
                    addClass(dom, options.closeAnimation);
                    dom.addEventListener(animationEndName, function () {
                        layer.close([dom]);
                    });
                } else {
                    layer.close([bg, dom]);
                }
            }

            // set button click event
            options.btns.forEach(function (btn, i) {
                if (i !== 0 && i <= options.btns.length - 1) {
                    if (!options.bottom) {
                        btn.addEventListener("click", function () {
                            handleClose();
                            options.sureBtnClick();
                        });
                    } else {
                        btn.addEventListener("click", function () {
                            handleClose();
                            options.btnClick(this.getAttribute("i"));
                        });
                    }
                } else {
                    btn.addEventListener("click", handleClose);
                }
            });

            if (!options.bottom) {
                // set position
                dom.style.top = (document.documentElement.clientHeight - dom.offsetHeight) / 2 + "px";
                dom.style.left = (document.documentElement.clientWidth - dom.offsetWidth) / 2 + "px";
            }
        },
        close: function close(doms) {
            const body = document.querySelector("body");
            for (let i = 0; i < doms.length; i++) {
                body.removeChild(doms[i]);
            }
        }
    };

    const sDialog = {
        alert: function alert(content, options) {
            let opts = {
                titleText: "",
                sureBtnText: "确定"
            };
            opts = extend(opts, options);
            console.log(opts)
            let btn = document.createElement("div");
            btn.innerText = opts.sureBtnText;
            addClass(btn, "dialog-button");

            opts.btns = [btn];

            this.open(content, opts);
        },
        confirm: function confirm(content, options) {
            let opts = {
                titleText: "",
                cancelBtnText: "取消",
                sureBtnText: "确定",
                sureBtnClick: function sureBtnClick() {
                }
            };
            opts = extend(opts, options);

            const cancelBtn = document.createElement("div");
            cancelBtn.innerText = opts.cancelBtnText;
            addClass(cancelBtn, "dialog-cancel-button");

            const sureBtn = document.createElement("div");
            sureBtn.innerText = opts.sureBtnText;
            addClass(sureBtn, "dialog-sure-button");

            opts.btns = [cancelBtn, sureBtn];
            this.open(content, opts);
        },
        open: function open(content, options) {
            const dialog = document.createElement("div");
            const dialogContent = document.createElement("div");

            addClass(dialog, "dialog-mobile");
            addClass(dialog, "animation-zoom-in");
            addClass(dialogContent, "dialog-content");

            dialogContent.innerText = content;

            if (options.titleText) {
                const dialogTitle = document.createElement("div");
                addClass(dialogTitle, "dialog-title");
                dialogTitle.innerText = options.titleText;
                dialog.appendChild(dialogTitle);
            }

            dialog.appendChild(dialogContent);

            options.btns.forEach(function (btn, i) {
                dialog.appendChild(btn);
            });
            options.closeAnimation = "animation-zoom-out";

            layer.initOpen(dialog, options);
        },
        // 兑换会员和兑换金币
        showBottom: function showBottom(options) {
            console.log(options)
            let opts = {
                title: "",
                cancelText: "取消",
                btn: ["删除"],
                btnColor: [],
                btnClick: function btnClick(index) {
                }
            };
            opts = extend(opts, options);
            console.log(opts)
            opts.bottom = true;
            if (opts.btn.length === 1 && opts.btn[0] === "删除") {
                opts.btnColor = ["#EE2C2C"];
            }

            let bottomDialog = document.createElement("div");
            let title = document.createElement("div");
            let dialogItem = document.createElement("div");
            let cancelBtn = document.createElement("div");
            title.innerText = opts.title;
            cancelBtn.innerText = opts.cancelText;
            addClass(bottomDialog, "dialog-mobile-bottom");
            addClass(bottomDialog, "animation-bottom-in");
            addClass(title, "bottom-btn-title");
            if (opts.row) {
                addClass(dialogItem, "bottom-btn-item flex-row");
            } else {
                addClass(dialogItem, "bottom-btn-item");
            }
            addClass(cancelBtn, "dialog-cancel-btn");
            if (opts.title) {
                bottomDialog.appendChild(title);
            }
            bottomDialog.appendChild(dialogItem);
            bottomDialog.appendChild(cancelBtn);

            opts.btns = [];
            opts.btns.push(cancelBtn);
            opts.btn.forEach(function (b, i) {
                let btn = document.createElement("div");
                btn.setAttribute("id", b.id);
                btn.setAttribute("i", i);
                addClass(btn, "dialog-item-btn");
                let imps = document.createElement("img");
                imps.setAttribute("src", b.url)
                addClass(imps, "dialog-item-img");
                btn.appendChild(imps)
                if (opts.btnColor[i]) btn.style.color = opts.btnColor[i];
                dialogItem.appendChild(btn);
                opts.btns.push(btn);
            });
            opts.closeAnimation = "animation-bottom-out";
            opts.showBottom = true;

            layer.initOpen(bottomDialog, opts);
        },
        toast: function toast(content, time) {
            time = time || 3;
            let toast = document.createElement("div");
            let toastContent = document.createElement("div");

            addClass(toast, "dialog-mobile-toast");
            addClass(toast, "animation-fade-in");
            addClass(toastContent, "toast-content");

            toastContent.innerText = content;

            toast.appendChild(toastContent);

            let body = document.querySelector("body");
            body.appendChild(toast);

            toast.style.fontSize = getFontSize();
            toast.style.left = (document.documentElement.clientWidth - toast.offsetWidth) / 2 + "px";

            setTimeout(function () {
                body.removeChild(toast);
            }, time * 1000);
        },
        // 我的奖品
        showRecord: function(options) {
            let opts = {
                list: [{ url: './assets/lottery/bonus_05.png',text: '0元红包',time: '2020-01-01 00:00' }]
            }
            opts = extend(opts, options);
            let recordBg = document.createElement("div");
            let recordContainer = document.createElement("div");

            addClass(recordBg, "mobile-record-bg");
            addClass(recordContainer, "mobile-record");
            addClass(recordContainer, "animation-zoom-in");

            // 头部返回按钮
            let recordNav = document.createElement("div");
            let recordBackButton = document.createElement("img");
            recordBackButton.src = './assets/back_button.png'
            addClass(recordNav, "mobile-record-header")
            addClass(recordBackButton, "mobile-record-header-back")
            recordNav.appendChild(recordBackButton)
            recordContainer.appendChild(recordNav)
            // 记录列表
            let recordContent = document.createElement("div");
            addClass(recordContent, "mobile-record-content")
            opts.list.forEach(function (b, i) {
                let recordItem = document.createElement("div");
                let bonusImg = document.createElement("img");
                let bonusText = document.createElement("div");
                let bonusTime = document.createElement("div");
                addClass(recordItem, "mobile-record-content-item");
                addClass(bonusImg, "mobile-record-item-img");
                addClass(bonusText, "mobile-record-content-text");
                addClass(bonusTime, "mobile-record-content-time");
                bonusText.innerText = b.text;
                bonusTime.innerText = b.time;
                bonusImg.setAttribute("src", b.url)
                recordItem.appendChild(bonusImg);
                recordItem.appendChild(bonusText);
                recordItem.appendChild(bonusTime);
                recordContent.appendChild(recordItem)
            });

            recordContainer.appendChild(recordContent)

            // 底部图片
            let recordFoot = document.createElement("div");
            let recordFootImg = document.createElement("img");
            recordFootImg.src = './assets/foot_bg.png'
            addClass(recordFoot, "mobile-record-foot")
            addClass(recordFootImg, "mobile-record-foot-img")
            recordFoot.appendChild(recordFootImg)
            recordContainer.appendChild(recordFoot)

            let body = document.querySelector("body");
            body.style.height = '100vh'
            body.style.overflow = "hidden"
            body.appendChild(recordBg);
            body.appendChild(recordContainer);

            recordContainer.style.fontSize = getFontSize();
            recordContainer.style.width = document.documentElement.clientWidth + "px";
            recordContainer.style.height = document.documentElement.clientHeight + "px";
            opts.closeAnimation = "animation-zoom-out";
            let animationEndNameBonus = getAnimationEndName(recordContainer);

            function handleClose() {
                addClass(recordContainer, opts.closeAnimation);
                recordContainer.addEventListener(animationEndNameBonus, function () {
                    layer.close([recordBg]);
                    layer.close([recordContainer]);
                });
            }

            recordBackButton.addEventListener("click", function () {
                body.style.height = 'unset'
                body.style.overflow = "auto"
                handleClose();
                if (options.backBtnClick){
                    options.backBtnClick.call();
                }
            });
        },
        // 抽奖结果
        bonus: function bonus(options) {
            let opts = {
                button: "./assets/get/now_get.png",
                bg: "./assets/get/get_bg.png",
                hint: "加载中"
            };
            opts = extend(opts, options);
            let bonusBg = document.createElement("div");
            let bonusContainer = document.createElement("div");
            let bonusImg = document.createElement("img");

            addClass(bonusBg, "mobile-bonus-bg");
            addClass(bonusContainer, "mobile-bonus");
            addClass(bonusContainer, "animation-zoom-in");
            addClass(bonusImg, "mobile-bonus-bg-img");
            // 背景图
            bonusImg.src = opts.bg;
            bonusContainer.appendChild(bonusImg);

            // 奖品图片
            let bonusPictureContainer = document.createElement("div");
            addClass(bonusPictureContainer, "mobile-bonus-picture-container");
            let bonusPic = document.createElement("img");
            addClass(bonusPic, "mobile-bonus-picture");
            bonusPic.src = opts.bonusImg;
            bonusPictureContainer.appendChild(bonusPic)

            // 奖品文字
            let bonusTextContainer = document.createElement("div");
            addClass(bonusTextContainer, "mobile-bonus-text-container");
            bonusTextContainer.innerText = opts.text;
            bonusPictureContainer.appendChild(bonusTextContainer)

            // 领取按钮
            let bonusButton = document.createElement("img");
            addClass(bonusButton, "mobile-bonus-button");
            bonusButton.src = opts.button;

            bonusContainer.appendChild(bonusPictureContainer)
            bonusContainer.appendChild(bonusButton);

            let body = document.querySelector("body");
            body.appendChild(bonusBg);
            body.appendChild(bonusContainer);

            bonusContainer.style.fontSize = getFontSize();
            bonusContainer.style.left = (document.documentElement.clientWidth - bonusContainer.offsetWidth) / 2 + "px";
            bonusContainer.style.top = (document.documentElement.clientHeight - bonusContainer.offsetHeight) / 2 + "px";
            opts.closeAnimation = "animation-zoom-out";
            let animationEndNameBonus = getAnimationEndName(bonusContainer);

            function handleClose() {
                addClass(bonusContainer, opts.closeAnimation);
                bonusContainer.addEventListener(animationEndNameBonus, function () {
                    layer.close([bonusBg]);
                    layer.close([bonusContainer]);
                });
            }

            bonusButton.addEventListener("click", function () {
                handleClose();
                options.sureBtnClick();
            });
        },
        loadElement: [],
        // loading加载动画
        loading: function loading(options) {
            let opts = {
                src: "../assets/loading.gif",
                hint: "加载中"
            };
            opts = extend(opts, options);

            let loadingBg = document.createElement("div");
            let loading = document.createElement("div");
            let img = document.createElement("img");

            addClass(loadingBg, "mobile-loading-bg");
            addClass(loading, "mobile-loading");
            addClass(loading, "animation-zoom-in");
            img.src = opts.src;
            loading.appendChild(img);

            if (opts.hint) {
                var loadingContent = document.createElement("div");
                addClass(loadingContent, "loading-content");
                loadingContent.innerText = opts.hint;
                loading.appendChild(loadingContent);
            }

            let body = document.querySelector("body");
            body.appendChild(loadingBg);
            body.appendChild(loading);

            loading.style.fontSize = getFontSize();
            loading.style.left = (document.documentElement.clientWidth - loading.offsetWidth) / 2 + "px";
            loading.style.top = (document.documentElement.clientHeight - loading.offsetHeight) / 2 + "px";

            this.loadElement.push(loadingBg);
            this.loadElement.push(loading);
        },
        closeLoading: function closeLoading() {
            layer.close(this.loadElement);
            this.loadElement = [];
        }
    };

    // providing better operations in Vue
    sDialog.install = function (Vue, options) {
        Vue.prototype.sDialog = sDialog;
    };

    return sDialog;

})(window);
