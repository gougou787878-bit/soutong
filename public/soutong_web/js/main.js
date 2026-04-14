$(document).ready(function () {
    const fullUrl = window.location.href;

    function getDeviceType() {
        const userAgent = navigator.userAgent.toLowerCase() || navigator.vendor || window.opera;
        if (Boolean(userAgent.match(/android|mobile|pad/i) && Boolean(userAgent.match(/ipad/i)) === false && Boolean(userAgent.match(/mac/i)) === false)) {
            return 'Android';
        }
        if (Boolean(userAgent.match(/iphone/i))) {
            return 'iOS';
        }
        if (Boolean(userAgent.match(/ipad|pad/i))) {
            return 'pad';
        }
        return 'Unknown';
    }

    const deviceType = getDeviceType();

    fetch(`/index.php/?m=index&a=api_index&url=${fullUrl}`, { method: 'GET' })
        .then(response => response.json())
        .then(data => {
            const traceId = Tracker.getTraceId();
            $('#iphone').attr({ href: '/page/ios.html?aff_code=' + data.aff_code + '&trace_id=' + traceId });
            $('#iphone-web').attr({ href: '/page/ios.html?aff_code=' + data.aff_code + '&trace_id=' + traceId });
            $('#business').attr({ href: data.shangwu1 });
            $('#contact').attr({ href: data.group });
            $('#business-mb').attr({ href: data.shangwu1 });
            $('#contact-mb').attr({ href: data.group });
            $('#android-web').attr({ href: data.version_and, 'data-clipboard-text': data.share });
            $('#android').attr({ href: data.version_and, 'data-clipboard-text': data.share });
            $('#android2').attr({ href: data.special_and, 'data-clipboard-text': data.share });
            if (data.is_download == 1) {
                if (deviceType == "Android") {
                    window.location.href = data.version_and
                }
                if (deviceType == "iOS") {
                    window.location.href = '/page/ios.html?aff_code=' + data.aff_code + '&trace_id=' + traceId
                }
            }
        }).catch(() => {
            // alert('加载失败，请刷新重试')
        }).finally(() => {
            $('.spinner-container').remove();
            new ClipboardJS(".clipboard-btn");
        });

    

    function creatQr(id, url, size) {
        new QRCode(document.querySelector(id), {
            text: url,
            width: size,
            height: size,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.Q
        });
    }

    if (deviceType == "Unknown" || deviceType == "pad") {
        creatQr("#qr", location.href, 120);
    }
    

    $('#iphone').on('click', function () {
        $('#ios-detail').fadeIn().css('display', 'flex');
    })

    $('#android, #android2').on('click', function () {
        $('#platform-list').fadeIn().css('display', 'flex');
    })

    $('.set-up-tip').on('click', function () {
        if (deviceType == "iOS") {
            $('#ios-detail').fadeIn().css('display', 'flex');
        }
        if (deviceType == "Android") {
            $('#platform-list').fadeIn().css('display', 'flex');
        }
    })

    $('.platform-item').on('click', function () {
        const imgSrc = $(this).data('src');
        const imgSrc2 = $(this).data('src2');
        const platformDetail = $('#platform-detail')
        platformDetail.find('.content').append($('<img>').addClass('modal-common-img').attr('src', imgSrc))
        if (imgSrc2 !== "" || imgSrc2 !== undefined) {
            platformDetail.find('.content').append($('<img>').addClass('modal-common-img-2').attr('src', imgSrc2))
        }
        platformDetail.fadeIn().css('display', 'flex');
    });

    $('.android-modal-arrow').on('click', function () {
        const type = $(this).data('type')
        if (type === 1) {
            $('#platform-list').fadeOut()
        }
        if (type === 2) {
            $('#platform-detail').fadeOut();
            const platformDetail = $('#platform-detail');
            platformDetail.find('.modal-common-img').remove();
            platformDetail.find('.modal-common-img-2').remove();
        }
        if (type === 3) {
            $('#ios-detail').fadeOut();
        }
    });

    $('.clipboard-btn').on('click', function () {
        fetch(`index.php/?m=index&a=stat`)
    })
});