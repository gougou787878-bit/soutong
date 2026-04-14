function render_countdown(countdown) {
    //"2021 01 01 0:0:0"
    let x = setInterval(function () {
        let now = new Date().getTime();
        let distance = countdown - now;
        let days = Math.floor(distance / (1000 * 60 * 60 * 24));
        let hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        let minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        let lotteryDay = $('#lotteryDay')
        let lotteryHours = $('#lotteryHours')
        let lotteryMinutes = $('#lotteryMinutes')
        lotteryDay.text(days)
        lotteryHours.text(hours)
        lotteryMinutes.text(minutes)
        if (distance < 0) {
            clearInterval(x);
            lotteryDay.text(0)
            lotteryHours.text(0)
            lotteryMinutes.text(0)
        }
    }, 1000);

}

let lottery = {
    index: -1,
    count: 0,
    timer: 0,
    speed: 20,
    times: 2,
    cycle: 50,
    prize: -1,
    bonusImg: './assets/lottery/lottery_button.png',
    text: '加载中',
    init: function (id) {
        let $lottery;
        let $units;
        if ($("#" + id).find(".lottery-td").length > 0) {
            $lottery = $("#" + id);
            $units = $lottery.find(".lottery-td");
            this.obj = $lottery;
            this.count = $units.length;
            $lottery.find(".lottery-unit-" + this.index).addClass("active");
        }

    },
    roll: function () {
        let index = this.index;
        let count = this.count;
        let lottery = this.obj;
        $(lottery).find(".lottery-unit-" + index).removeClass("active");
        index += 1;
        if (index > count - 1) {
            index = 0;
        }

        $(lottery).find(".lottery-unit-" + index).addClass("active");
        this.index = index;
        return false;
    },
    stop: function (index) {
        this.prize = index;
        return false;
    }
};

function roll() {
    lottery.times += 1;
    lottery.roll();
    if (lottery.times > lottery.cycle + 10 && lottery.prize === lottery.index) {
        clearTimeout(lottery.timer);
        lottery.prize = -1;
        lottery.times = 0;
        click = false;
        // bonusImg 中奖图片
        // text 中奖文字
        setTimeout(function () {
            sDialog.bonus({
                bonusImg:lottery.bonusImg,
                text: lottery.text,
                sureBtnClick: function () {
                    console.log('点击立即领取回调')
                }
            })
        },800)
    } else {
        if (lottery.times < lottery.cycle) {
            lottery.speed -= 10;
        } else {
            if (lottery.times > lottery.cycle + 10 && ((lottery.prize === 0 && lottery.index === 7) || lottery.prize === lottery.index + 1)) {
                lottery.speed += 110;
            } else {
                lottery.speed += 20;
            }
        }
        if (lottery.speed < 40) {
            lottery.speed = 40;
        }
        lottery.timer = setTimeout(roll, lottery.speed);
    }
    return false;
}