<?php

/**
 * 国家电话模型
 * Class SMSCountryModel
 */
class SMSCountryModel
{

    private $countryCodes = [];

    public function __construct()
    {
        $this->countryCodes = [
            ['code' => 1, 'name' => '特立尼达和多巴哥', 'eName' => 'Republic of Trinidad and Tobag', 'code' => '1868', 'status' => 0],
            ['code' => 2, 'name' => '托克劳群岛', 'eName' => 'Tokelau', 'code' => '690', 'status' => 0],
            ['code' => 3, 'name' => '萨摩亚群岛', 'eName' => 'Samoa', 'code' => '684', 'status' => 0],
            ['code' => 4, 'name' => '纽埃岛', 'eName' => 'Island of Niue', 'code' => '683', 'status' => 0],
            ['code' => 5, 'name' => '瓦利斯和富图纳群岛', 'eName' => 'Wallis et Futuna', 'code' => '681', 'status' => 0],
            ['code' => 6, 'name' => '瑙鲁共和国', 'eName' => 'The Republic of Nauru', 'code' => '674', 'status' => 0],
            ['code' => 7, 'name' => '马绍尔群岛', 'eName' => 'Marshall Islands', 'code' => '692', 'status' => 0],
            ['code' => 8, 'name' => '密克罗尼西亚', 'eName' => 'Micronesia', 'code' => '691', 'status' => 0],
            ['code' => 9, 'name' => '图瓦卢', 'eName' => 'Tuvalu', 'code' => '688', 'status' => 0],
            ['code' => 10, 'name' => '基里巴斯', 'eName' => 'Kiribati', 'code' => '686', 'status' => 0],
            ['code' => 11, 'name' => '梵蒂冈', 'eName' => 'Vatican City State (Holy See)', 'code' => '379', 'status' => 0],
            ['code' => 12, 'name' => '马约特', 'eName' => 'Mayotte', 'code' => '262', 'status' => 0],
            ['code' => 13, 'name' => '圣多美和普林西比', 'eName' => 'Sao Tome and Princip', 'code' => '239', 'status' => 0],
            ['code' => 14, 'name' => '中国', 'eName' => 'China', 'code' => '86', 'status' => 1],
            ['code' => 15, 'name' => '塞尔维亚', 'eName' => 'Serbia', 'code' => '381', 'status' => 0],
            ['code' => 16, 'name' => '斯洛文尼亚', 'eName' => 'Slovenia', 'code' => '386', 'status' => 0],
            ['code' => 17, 'name' => '塞内加尔', 'eName' => 'Senegal', 'code' => '221', 'status' => 0],
            ['code' => 18, 'name' => '斯洛伐克', 'eName' => 'Slovakia', 'code' => '421', 'status' => 0],
            ['code' => 19, 'name' => '塞舌尔', 'eName' => 'Seychelles', 'code' => '248', 'status' => 0],
            ['code' => 20, 'name' => '新加坡', 'eName' => 'Singapore', 'code' => '65', 'status' => 1],
            ['code' => 21, 'name' => '索马里', 'eName' => 'Somalia', 'code' => '252', 'status' => 0],
            ['code' => 22, 'name' => '南苏丹', 'eName' => 'South Sudan', 'code' => '211', 'status' => 0],
            ['code' => 23, 'name' => '南非', 'eName' => 'South Africa', 'code' => '27', 'status' => 0],
            ['code' => 24, 'name' => '萨摩亚独立国', 'eName' => 'Samoa', 'code' => '685', 'status' => 0],
            ['code' => 25, 'name' => '圣马力诺', 'eName' => 'San Marino', 'code' => '378', 'status' => 0],
            ['code' => 26, 'name' => '瑞典', 'eName' => 'Sweden', 'code' => '46', 'status' => 0],
            ['code' => 27, 'name' => '中国台湾', 'eName' => 'Taiwan', 'code' => '886', 'status' => 1],
            ['code' => 28, 'name' => '苏里南', 'eName' => 'Suriname', 'code' => '597', 'status' => 0],
            ['code' => 29, 'name' => '特立尼达和多巴哥', 'eName' => 'Trinidad and Tobago', 'code' => '1809', 'status' => 0],
            ['code' => 30, 'name' => '坦桑尼亚', 'eName' => 'Tanzania United Republic', 'code' => '255', 'status' => 0],
            ['code' => 31, 'name' => '乌干达', 'eName' => 'Uganda', 'code' => '256', 'status' => 0],
            ['code' => 32, 'name' => '英国', 'eName' => 'United Kingdom', 'code' => '44', 'status' => 1],
            ['code' => 33, 'name' => '阿拉伯联合酋长国', 'eName' => 'United Arab Emirates', 'code' => '971', 'status' => 0],
            ['code' => 34, 'name' => '土库曼斯坦', 'eName' => 'Turkmenistan', 'code' => '993', 'status' => 0],
            ['code' => 35, 'name' => '突尼斯', 'eName' => 'Tunisia', 'code' => '216', 'status' => 0],
            ['code' => 36, 'name' => '阿拉伯叙利亚共和国', 'eName' => 'Syrian Arab Republic', 'code' => '963', 'status' => 0],
            ['code' => 37, 'name' => '圣卢西亚岛', 'eName' => 'Saint Lucia', 'code' => '1758', 'status' => 0],
            ['code' => 38, 'name' => '赞比亚', 'eName' => 'Zambia', 'code' => '260', 'status' => 0],
            ['code' => 39, 'name' => '也门', 'eName' => 'Yemen', 'code' => '967', 'status' => 0],
            ['code' => 40, 'name' => '塔吉克斯坦', 'eName' => 'Tajikistan', 'code' => '992', 'status' => 0],
            ['code' => 41, 'name' => '特克斯和凯科斯群岛', 'eName' => 'Turks and Caicos Islands', 'code' => '1649', 'status' => 0],
            ['code' => 42, 'name' => '圣文森特和格林纳丁斯', 'eName' => 'Saint Vincent and the Grenadin', 'code' => '1784', 'status' => 0],
            ['code' => 43, 'name' => '土耳其', 'eName' => 'Turkey', 'code' => '90', 'status' => 0],
            ['code' => 44, 'name' => '瓦努阿图', 'eName' => 'Vanuatu', 'code' => '678', 'status' => 0],
            ['code' => 45, 'name' => '美国', 'eName' => 'United States', 'code' => '1', 'status' => 1],
            ['code' => 46, 'name' => '苏丹', 'eName' => 'Sudan', 'code' => '249', 'status' => 0],
            ['code' => 47, 'name' => '泰国', 'eName' => 'Thailand', 'code' => '66', 'status' => 0],
            ['code' => 48, 'name' => '津巴布韦', 'eName' => 'Zimbabwe', 'code' => '263', 'status' => 0],
            ['code' => 49, 'name' => '法属玻利尼西亚', 'eName' => 'French Polynesia', 'code' => '689', 'status' => 0],
            ['code' => 50, 'name' => '加蓬', 'eName' => 'Gabon', 'code' => '241', 'status' => 0],
            ['code' => 51, 'name' => '冈比亚', 'eName' => 'Gambia', 'code' => '220', 'status' => 0],
            ['code' => 52, 'name' => '格鲁吉亚', 'eName' => 'Georgia', 'code' => '995', 'status' => 0],
            ['code' => 53, 'name' => '德国', 'eName' => 'Germany', 'code' => '49', 'status' => 0],
            ['code' => 54, 'name' => '加纳', 'eName' => 'Ghana', 'code' => '233', 'status' => 0],
            ['code' => 55, 'name' => '直布罗陀', 'eName' => 'Gibraltar', 'code' => '350', 'status' => 0],
            ['code' => 56, 'name' => '希腊', 'eName' => 'Greece', 'code' => '30', 'status' => 0],
            ['code' => 57, 'name' => '格陵兰岛', 'eName' => 'Greenland', 'code' => '45', 'status' => 0],
            ['code' => 58, 'name' => '格林纳达', 'eName' => 'Grenada', 'code' => '1809', 'status' => 0],
            ['code' => 59, 'name' => '瓜德罗普', 'eName' => 'Guadeloupe', 'code' => '590', 'status' => 0],
            ['code' => 60, 'name' => '关岛', 'eName' => 'Guam', 'code' => '1671', 'status' => 0],
            ['code' => 61, 'name' => '危地马拉', 'eName' => 'Guatemala', 'code' => '502', 'status' => 0],
            ['code' => 62, 'name' => '根西(英国)', 'eName' => 'Guernsey', 'code' => '44', 'status' => 1],
            ['code' => 63, 'name' => '几内亚', 'eName' => 'Guinea', 'code' => '675', 'status' => 0],
            ['code' => 64, 'name' => '几内亚比绍共和国', 'eName' => 'Guinea-Bissau', 'code' => '245', 'status' => 0],
            ['code' => 65, 'name' => '圭亚那', 'eName' => 'Guyana', 'code' => '592', 'status' => 0],
            ['code' => 66, 'name' => '海地', 'eName' => 'Haiti', 'code' => '509', 'status' => 0],
            ['code' => 67, 'name' => '洪都拉斯', 'eName' => 'Honduras', 'code' => '504', 'status' => 0],
            ['code' => 68, 'name' => '中国香港', 'eName' => 'Hong Kong', 'code' => '852', 'status' => 1],
            ['code' => 69, 'name' => '匈牙利', 'eName' => 'Hungary', 'code' => '36', 'status' => 0],
            ['code' => 70, 'name' => '冰岛', 'eName' => 'Iceland', 'code' => '354', 'status' => 0],
            ['code' => 71, 'name' => '印度', 'eName' => 'India', 'code' => '91', 'status' => 1],
            ['code' => 72, 'name' => '印度尼西亚', 'eName' => 'Indonesia', 'code' => '62', 'status' => 1],
            ['code' => 73, 'name' => '伊朗', 'eName' => 'Iran Islamic Republic', 'code' => '98', 'status' => 0],
            ['code' => 74, 'name' => '伊拉克', 'eName' => 'Iraq', 'code' => '964', 'status' => 0],
            ['code' => 75, 'name' => '爱尔兰', 'eName' => 'Ireland', 'code' => '353', 'status' => 0],
            ['code' => 76, 'name' => '马恩(英国)', 'eName' => 'Isle of Man', 'code' => '44', 'status' => 1],
            ['code' => 77, 'name' => '以色列', 'eName' => 'Israel', 'code' => '972', 'status' => 0],
            ['code' => 78, 'name' => '意大利', 'eName' => 'Italy', 'code' => '39', 'status' => 0],
            ['code' => 79, 'name' => '牙买加', 'eName' => 'Jamaica', 'code' => '1876', 'status' => 0],
            ['code' => 80, 'name' => '日本', 'eName' => 'Japan', 'code' => '81', 'status' => 1],
            ['code' => 81, 'name' => '泽西(英国)', 'eName' => 'Jersey', 'code' => '44', 'status' => 1],
            ['code' => 82, 'name' => '约旦', 'eName' => 'Jordan', 'code' => '962', 'status' => 0],
            ['code' => 83, 'name' => '哈萨克斯坦', 'eName' => 'Kazakhstan', 'code' => '7', 'status' => 0],
            ['code' => 84, 'name' => '肯尼亚', 'eName' => 'Kenya', 'code' => '254', 'status' => 0],
            ['code' => 85, 'name' => '朝鲜', 'eName' => 'Korea Democratic People‘s Repu', 'code' => '850', 'status' => 1],
            ['code' => 86, 'name' => '韩国', 'eName' => 'Korea Republic', 'code' => '82', 'status' => 1],
            ['code' => 87, 'name' => '科索沃', 'eName' => 'Kosovo', 'code' => '381', 'status' => 0],
            ['code' => 88, 'name' => '科威特', 'eName' => 'Kuwait', 'code' => '965', 'status' => 0],
            ['code' => 89, 'name' => '吉尔吉斯斯坦', 'eName' => 'Kyrgyzstan', 'code' => '996', 'status' => 0],
            ['code' => 90, 'name' => '老挝人民民主共和国', 'eName' => 'Lao People‘s Democratic Republ', 'code' => '856', 'status' => 0],
            ['code' => 91, 'name' => '拉脱维亚', 'eName' => 'Latvia', 'code' => '371', 'status' => 0],
            ['code' => 92, 'name' => '黎巴嫩', 'eName' => 'Lebanon', 'code' => '961', 'status' => 0],
            ['code' => 93, 'name' => '莱索托', 'eName' => 'Lesotho', 'code' => '266', 'status' => 0],
            ['code' => 94, 'name' => '利比里亚', 'eName' => 'Liberia', 'code' => '231', 'status' => 0],
            ['code' => 95, 'name' => '利比亚', 'eName' => 'Libyan Arab Jamahiriya', 'code' => '218', 'status' => 0],
            ['code' => 96, 'name' => '列支敦斯登', 'eName' => 'Liechtenstein', 'code' => '423', 'status' => 0],
            ['code' => 97, 'name' => '立陶宛', 'eName' => 'Lithuania', 'code' => '370', 'status' => 0],
            ['code' => 98, 'name' => '卢森堡', 'eName' => 'Luxembourg', 'code' => '352', 'status' => 0],
            ['code' => 99, 'name' => '中国澳门', 'eName' => 'Macao', 'code' => '853', 'status' => 1],
            ['code' => 100, 'name' => '前南斯拉夫马其顿共和国', 'eName' => 'Macedonia', 'code' => '389', 'status' => 0],
            ['code' => 101, 'name' => '马达加斯加', 'eName' => 'Madagascar', 'code' => '261', 'status' => 0],
            ['code' => 102, 'name' => '马拉维', 'eName' => 'Malawi', 'code' => '265', 'status' => 0],
            ['code' => 103, 'name' => '马来西亚', 'eName' => 'Malaysia', 'code' => '60', 'status' => 1],
            ['code' => 104, 'name' => '马尔代夫', 'eName' => 'Maldives', 'code' => '960', 'status' => 0],
            ['code' => 105, 'name' => '马里', 'eName' => 'Mali', 'code' => '223', 'status' => 0],
            ['code' => 106, 'name' => '马耳他', 'eName' => 'Malta', 'code' => '356', 'status' => 0],
            ['code' => 107, 'name' => '马提尼克', 'eName' => 'Martinique', 'code' => '596', 'status' => 0],
            ['code' => 108, 'name' => '毛里塔尼亚', 'eName' => 'Mauritania', 'code' => '222', 'status' => 0],
            ['code' => 109, 'name' => '毛里求斯', 'eName' => 'Mauritius', 'code' => '230', 'status' => 0],
            ['code' => 110, 'name' => '墨西哥', 'eName' => 'Mexico', 'code' => '52', 'status' => 0],
            ['code' => 111, 'name' => '摩尔多瓦', 'eName' => 'Moldova', 'code' => '373', 'status' => 0],
            ['code' => 112, 'name' => '摩纳哥', 'eName' => 'Monaco', 'code' => '377', 'status' => 0],
            ['code' => 113, 'name' => '蒙古', 'eName' => 'Mongolia', 'code' => '976', 'status' => 1],
            ['code' => 114, 'name' => '黑山共和国', 'eName' => 'Montenegro', 'code' => '382', 'status' => 0],
            ['code' => 115, 'name' => '蒙特塞拉特岛', 'eName' => 'Montserrat', 'code' => '1664', 'status' => 0],
            ['code' => 116, 'name' => '摩洛哥', 'eName' => 'Morocco', 'code' => '212', 'status' => 0],
            ['code' => 117, 'name' => '莫桑比克', 'eName' => 'Mozambique', 'code' => '258', 'status' => 0],
            ['code' => 118, 'name' => '缅甸', 'eName' => 'Myanmar', 'code' => '95', 'status' => 0],
            ['code' => 119, 'name' => '纳米比亚', 'eName' => 'Namibia', 'code' => '264', 'status' => 0],
            ['code' => 120, 'name' => '尼泊尔', 'eName' => 'Nepal', 'code' => '977', 'status' => 0],
            ['code' => 121, 'name' => '荷兰', 'eName' => 'Netherlands', 'code' => '31', 'status' => 0],
            ['code' => 122, 'name' => '荷属安的列斯群岛', 'eName' => 'Netherlands Antilles', 'code' => '599', 'status' => 0],
            ['code' => 123, 'name' => '新喀里多尼亚', 'eName' => 'New Caledonia', 'code' => '687', 'status' => 0],
            ['code' => 124, 'name' => '新西兰', 'eName' => 'New Zealand', 'code' => '64', 'status' => 0],
            ['code' => 125, 'name' => '尼加拉瓜', 'eName' => 'Nicaragua', 'code' => '505', 'status' => 0],
            ['code' => 126, 'name' => '尼日尔', 'eName' => 'Niger', 'code' => '227', 'status' => 0],
            ['code' => 127, 'name' => '尼日利亚', 'eName' => 'Nigeria', 'code' => '234', 'status' => 0],
            ['code' => 128, 'name' => '挪威', 'eName' => 'Norway', 'code' => '47', 'status' => 0],
            ['code' => 129, 'name' => '阿曼', 'eName' => 'Oman', 'code' => '968', 'status' => 0],
            ['code' => 130, 'name' => '巴基斯坦', 'eName' => 'Pakistan', 'code' => '92', 'status' => 0],
            ['code' => 131, 'name' => '帕劳', 'eName' => 'Palau', 'code' => '680', 'status' => 0],
            ['code' => 132, 'name' => '巴拿马', 'eName' => 'Panama', 'code' => '507', 'status' => 0],
            ['code' => 133, 'name' => '巴布亚新几内亚', 'eName' => 'Papua New Guinea', 'code' => '675', 'status' => 0],
            ['code' => 134, 'name' => '巴拉圭', 'eName' => 'Paraguay', 'code' => '595', 'status' => 0],
            ['code' => 135, 'name' => '秘鲁', 'eName' => 'Peru', 'code' => '51', 'status' => 0],
            ['code' => 136, 'name' => '菲律宾', 'eName' => 'Philippines', 'code' => '63', 'status' => 1],
            ['code' => 137, 'name' => '波兰', 'eName' => 'Poland', 'code' => '48', 'status' => 0],
            ['code' => 138, 'name' => '葡萄牙', 'eName' => 'Portugal', 'code' => '351', 'status' => 0],
            ['code' => 139, 'name' => '阿富汗', 'eName' => 'Afghanistan', 'code' => '93', 'status' => 0],
            ['code' => 140, 'name' => '阿尔巴尼亚', 'eName' => 'Albania', 'code' => '355', 'status' => 0],
            ['code' => 141, 'name' => '阿尔及利亚', 'eName' => 'Algeria', 'code' => '213', 'status' => 0],
            ['code' => 142, 'name' => '美属萨摩亚', 'eName' => 'American Samoa', 'code' => '1684', 'status' => 0],
            ['code' => 143, 'name' => '安道尔', 'eName' => 'Andorra', 'code' => '376', 'status' => 0],
            ['code' => 144, 'name' => '安哥拉', 'eName' => 'Angola', 'code' => '244', 'status' => 0],
            ['code' => 145, 'name' => '安圭拉岛', 'eName' => 'Anguilla', 'code' => '1264', 'status' => 0],
            ['code' => 146, 'name' => '安提瓜和巴布达', 'eName' => 'Antigua and Barbuda', 'code' => '1268', 'status' => 0],
            ['code' => 147, 'name' => '阿根廷', 'eName' => 'Argentina', 'code' => '54', 'status' => 0],
            ['code' => 148, 'name' => '亚美尼亚', 'eName' => 'Armenia', 'code' => '374', 'status' => 0],
            ['code' => 149, 'name' => '阿鲁巴', 'eName' => 'Aruba', 'code' => '297', 'status' => 0],
            ['code' => 150, 'name' => '澳大利亚', 'eName' => 'Australia', 'code' => '61', 'status' => 0],
            ['code' => 151, 'name' => '奥地利', 'eName' => 'Austria', 'code' => '43', 'status' => 0],
            ['code' => 152, 'name' => '阿塞拜疆', 'eName' => 'Azerbaijan', 'code' => '994', 'status' => 0],
            ['code' => 153, 'name' => '巴哈马群岛', 'eName' => 'Bahamas', 'code' => '1242', 'status' => 0],
            ['code' => 154, 'name' => '巴林', 'eName' => 'Bahrain', 'code' => '973', 'status' => 0],
            ['code' => 155, 'name' => '孟加拉共和国', 'eName' => 'Bangladesh', 'code' => '880', 'status' => 0],
            ['code' => 156, 'name' => '巴巴多斯', 'eName' => 'Barbados', 'code' => '1246', 'status' => 0],
            ['code' => 157, 'name' => '白俄罗斯', 'eName' => 'Belarus', 'code' => '375', 'status' => 0],
            ['code' => 158, 'name' => '比利时', 'eName' => 'Belgium', 'code' => '32', 'status' => 0],
            ['code' => 159, 'name' => '伯利兹', 'eName' => 'Belize', 'code' => '501', 'status' => 0],
            ['code' => 160, 'name' => '贝宁', 'eName' => 'Benin', 'code' => '229', 'status' => 0],
            ['code' => 161, 'name' => '百慕大群岛', 'eName' => 'Bermuda', 'code' => '1441', 'status' => 0],
            ['code' => 162, 'name' => '不丹', 'eName' => 'Bhutan', 'code' => '975', 'status' => 0],
            ['code' => 163, 'name' => '玻利维亚', 'eName' => 'Bolivia', 'code' => '591', 'status' => 0],
            ['code' => 164, 'name' => '波黑(波斯尼亚和黑塞哥维那)', 'eName' => 'Bosnia and Herzegovina', 'code' => '387', 'status' => 0],
            ['code' => 165, 'name' => '博茨瓦纳', 'eName' => 'Botswana', 'code' => '267', 'status' => 0],
            ['code' => 166, 'name' => '巴西', 'eName' => 'Brazil', 'code' => '55', 'status' => 0],
            ['code' => 167, 'name' => '文莱达鲁萨兰国', 'eName' => 'Brunei Darussalam', 'code' => '673', 'status' => 0],
            ['code' => 168, 'name' => '保加利亚', 'eName' => 'Bulgaria', 'code' => '359', 'status' => 0],
            ['code' => 169, 'name' => '布基纳法索', 'eName' => 'Burkina Faso', 'code' => '226', 'status' => 0],
            ['code' => 170, 'name' => '布隆迪', 'eName' => 'Burundi', 'code' => '257', 'status' => 0],
            ['code' => 171, 'name' => '柬埔寨', 'eName' => 'Cambodia', 'code' => '855', 'status' => 0],
            ['code' => 172, 'name' => '喀麦隆', 'eName' => 'Cameroon', 'code' => '237', 'status' => 0],
            ['code' => 173, 'name' => '加拿大', 'eName' => 'Canada', 'code' => '1', 'status' => 0],
            ['code' => 174, 'name' => '佛得角', 'eName' => 'Cape Verde', 'code' => '238', 'status' => 0],
            ['code' => 175, 'name' => '开曼群岛', 'eName' => 'Cayman Islands', 'code' => '1345', 'status' => 0],
            ['code' => 176, 'name' => '中非共和国', 'eName' => 'Central African Republic', 'code' => '236', 'status' => 0],
            ['code' => 177, 'name' => '乍得', 'eName' => 'Chad', 'code' => '235', 'status' => 0],
            ['code' => 178, 'name' => '智利', 'eName' => 'Chile', 'code' => '56', 'status' => 0],
            ['code' => 179, 'name' => '哥伦比亚', 'eName' => 'Colombia', 'code' => '57', 'status' => 0],
            ['code' => 180, 'name' => '科摩罗', 'eName' => 'Comoros', 'code' => '269', 'status' => 0],
            ['code' => 181, 'name' => '刚果', 'eName' => 'Congo', 'code' => '242', 'status' => 0],
            ['code' => 182, 'name' => '刚果民主共和国', 'eName' => 'Congo The Democratic Republic', 'code' => '243', 'status' => 0],
            ['code' => 183, 'name' => '库克群岛', 'eName' => 'Cook Islands', 'code' => '682', 'status' => 0],
            ['code' => 184, 'name' => '哥斯达黎加', 'eName' => 'Costa Rica', 'code' => '506', 'status' => 0],
            ['code' => 185, 'name' => '科特迪瓦', 'eName' => 'Cote D‘ivoire', 'code' => '225', 'status' => 0],
            ['code' => 186, 'name' => '克罗地亚', 'eName' => 'Croatia', 'code' => '385', 'status' => 0],
            ['code' => 187, 'name' => '古巴', 'eName' => 'Cuba', 'code' => '53', 'status' => 0],
            ['code' => 188, 'name' => '塞浦路斯', 'eName' => 'Cyprus', 'code' => '357', 'status' => 0],
            ['code' => 189, 'name' => '捷克共和国', 'eName' => 'Czech Republic', 'code' => '420', 'status' => 0],
            ['code' => 190, 'name' => '丹麦', 'eName' => 'Denmark', 'code' => '45', 'status' => 0],
            ['code' => 191, 'name' => '吉布提', 'eName' => 'Djibouti', 'code' => '253', 'status' => 0],
            ['code' => 192, 'name' => '多米尼克', 'eName' => 'Dominica', 'code' => '1767', 'status' => 0],
            ['code' => 193, 'name' => '多米尼加共和国', 'eName' => 'Dominican Republic', 'code' => '1849', 'status' => 0],
            ['code' => 194, 'name' => '厄瓜多尔', 'eName' => 'Ecuador', 'code' => '593', 'status' => 0],
            ['code' => 195, 'name' => '埃及', 'eName' => 'Egypt', 'code' => '20', 'status' => 0],
            ['code' => 196, 'name' => '萨尔瓦多', 'eName' => 'El Salvador', 'code' => '503', 'status' => 0],
            ['code' => 197, 'name' => '赤道几内亚', 'eName' => 'Equatorial Guinea', 'code' => '240', 'status' => 0],
            ['code' => 198, 'name' => '厄立特里亚', 'eName' => 'Eritrea', 'code' => '291', 'status' => 0],
            ['code' => 199, 'name' => '爱沙尼亚', 'eName' => 'Estonia', 'code' => '372', 'status' => 0],
            ['code' => 200, 'name' => '埃塞俄比亚', 'eName' => 'Ethiopia', 'code' => '251', 'status' => 0],
            ['code' => 201, 'name' => '福克兰群岛', 'eName' => 'Falkland Islands (Malvinas)', 'code' => '500', 'status' => 0],
            ['code' => 202, 'name' => '法罗群岛', 'eName' => 'Faroe Islands', 'code' => '298', 'status' => 0],
            ['code' => 203, 'name' => '斐济', 'eName' => 'Fiji', 'code' => '679', 'status' => 0],
            ['code' => 204, 'name' => '芬兰', 'eName' => 'Finland', 'code' => '358', 'status' => 0],
            ['code' => 205, 'name' => '法国', 'eName' => 'France', 'code' => '33', 'status' => 1],
            ['code' => 206, 'name' => '法属圭亚那', 'eName' => 'French Guiana', 'code' => '594', 'status' => 0]
        ];
    }

    /**
     * 检查是否可发送
     * @param $area
     * @return bool
     */
    public function isStatus($area)
    {
        foreach ($this->countryCodes as $v) {
            if (
                ($v['code'] == $area)
                && $v['status'] == 1
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * 得到国家列表
     * @return array
     */
    public function getList()
    {
        $match = [];
        foreach ($this->countryCodes as $v) {
            if ($v['status'] == 1) {
                $match[] = [
                    'label' => $v['name'],
                    'name' => $v['eName'],
                    'value' => $v['code'],
                ];
            }

        }
        return $match;
    }
}
