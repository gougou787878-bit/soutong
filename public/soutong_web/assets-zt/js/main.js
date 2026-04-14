

const getDeviceInfo = () => {
    const userAgent = navigator.userAgent.toLowerCase()
    let isPc = Boolean(userAgent.match(/mobile|android|iphone/i)) === false
    let isMobile = Boolean(userAgent.match(/mobile|android|iphone/i))
    let isIos = Boolean(userAgent.match(/iphone|ipad/i))
    let isAndroid = Boolean(userAgent.match(/android|mobile|pad/i) && Boolean(userAgent.match(/ipad/i)) === false && Boolean(userAgent.match(/mac/i)) === false)

    if (screen.availWidth >= 1024 && isAndroid) {
        isPc = true
        isMobile = false
        isAndroid = false
    }

    return { isPc, isMobile, isIos, isAndroid }
}
const { isAndroid } = getDeviceInfo();


const copyText = (function () {
    return function (text) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();
        try {
            const success = document.execCommand('copy');
            return Promise.resolve(success);
        } catch (err) {
            return Promise.reject(err);
        } finally {
            document.body.removeChild(textarea);
        }
    };
})();

function toast(message, options = {}) {
    const container = document.createElement("div");
    container.className = "toast-container";
    document.body.appendChild(container);
    const {
        type = "success", // 类型：info, success, error, warn
        duration = 3000, // 显示时间（毫秒）
    } = options;

    const el = document.createElement("div");
    el.className = `toast ${type}`;
    el.textContent = message;

    container.appendChild(el);

    // 自动移除
    setTimeout(() => {
        el.style.animation = "toast-out 0.3s forwards";
        el.addEventListener("animationend", () => el.remove());
    }, duration);
};
$(document).ready(() => {

    const params = new URLSearchParams(window.location.search)
    const type = params.get('type');
    const token = params.get('token');
    let config = {}
    const validType = ['1', '2', '3', '4'].includes(type) ? type : '1';

    $(".small-images .nav").on("click", function () {
        const $this = $(this);
        const index = $this.index();

        $(".small-images .nav").removeClass("active")
        $this.addClass("active")

        $('#big-image .tips-container').eq(index).fadeIn().siblings().hide();


    });

    $(`.def${validType}`).click();


    const $mask = $('.van-mask');
    const $pay_dialog = $('#pay-dialog');
    const $payed_dialog = $('#payed-dialog')
    function showDialog($dialog, maskClickable = 1) {
        $('body').addClass('van-overflow-hidden')
        $mask.fadeIn(200);
        $dialog.addClass('show');
        $mask.data('clickable', maskClickable)
    }

    function closeDialog($dialog) {
        $mask.fadeOut(200);
        $('body').removeClass('van-overflow-hidden')
        $dialog.removeClass('show');
    }

    function renderConcact(data) {
        const { tg, pt } = data;
        let html = '<div>您已解锁正太资源权限，请联系官方人员领取已为您打包好的稀缺正太资源！</div>'

        if (tg) {
            const [href, name] = tg.split('|').map(item => item.trim())
            html += `<div  class="flex-item">
                    <img class="icon" src="/assets-zt/images/tg.png" alt="">
                    <div class="truncate">
                        telegram： <a class="copy-button" data-clipboard="${href}">${name}</a>
                    </div>
                   
                    <button data-clipboard="${href}" class="van-button confirm copy-button">复制</button>
                </div>`
        }
        if (pt) {
            const [href, name] = pt.split('|').map(item => item.trim())
            html += `<div  class="flex-item">
                    <img class="icon" src="/assets-zt/images/pt.png" alt="">
                    <div class="truncate">
                        potato： <a class="copy-button" data-clipboard="${href}">${name}</a>
                    </div>
                    <button data-clipboard="${href}" class="van-button confirm copy-button">复制</button>
                </div>`
        }

        return $(`<div class="gird-container">
                ${html}
            </div>`)
    }

    $('.open-vip-btn,#big-image .tips-container').on('click', function () {
        const { has_auth } = config
        const dialog = has_auth ? $payed_dialog : $pay_dialog
        const content = has_auth ? renderConcact(config) : config.tips
        dialog.find('.van-dialog__message').html(content)
        showDialog(dialog)
    });

    function navigateToVip(isCloseWebview) {
        if (isCloseWebview) {
            if (isAndroid) {
                window.location.href = 'xlpmncode://pvv'
            } else {
                window.parent.postMessage('pwa::view::vip', '*');
            }
        }
    }

    $('.van-button.cancel,.van-button.confirm').on('click', function () {
        const isCloseWebview = $(this).data('close')
        navigateToVip(isCloseWebview)
        const { has_auth } = config
        const dialog = has_auth ? $payed_dialog : $pay_dialog;
        closeDialog(dialog);
    });
    $('.van-mask').on('click', function () {
        if ($(this).data('clickable')) {
            closeDialog($loading_dialog);
        }
        $('#pay-dialog').removeClass('show')
        $('#payed-dialog').removeClass('show')
    });
    $(document).on('click', '.copy-button', function () {

        copyText($(this).data('clipboard')).then(() => {
            toast('复制成功')
        }).catch((e => {
            console.log('e: ', e);
        }))


    })
    const $loading_dialog = $('.loading-dialog')
    const base_url = `${location.origin}/index.php?m=index&a=uu_msg&token=${token}`

    // const base_url = `https://ksav.fun/index.php?m=index&a=uu_msg&token=${token}`

    function init() {

        showDialog($loading_dialog, 0)
        fetch(base_url)
            .then(res => res.json())
            .then(res => {
                console.log('data: ', res);
                config = res.data
                closeDialog($loading_dialog)
            }).catch(e => {
                toast(e, { type: 'error' })
                closeDialog($loading_dialog)
            })
    }

    init()

});
