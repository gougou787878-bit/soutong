<?php


namespace App\console;


use DB;
use QL\QueryList;
use service\GameService;

class GrabRituConsole extends AbstractConsole
{

    public $name = 'grab-ritu';

    public $description = '抓取日土网：https://www.ritub.me/';


    public function process($argc, $argv)
    {
        if (false) {
            $url_index = 'https://www.ritub.me/17135.html';

            $data = QueryList::getInstance()->get($url_index, null, [
                'headers' => [
                    'Referer'    => 'https://www.ritub.me/zylist',
                    'User-Agent' => 'testing/1.0',
                    'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',

                    // 携带cookie
                    'Cookie'     => 'wordpress_logged_in_b85ef2017524ee2b9106e4373b5be3de=yunfeiyang%7C1658227781%7CSP1Wge3RraPgR97vvKFDdEAHyJQwaRq3kJtuxF0J17U%7C93628056c00716ace6d1484b17ef75967caa58dc7cc881f411fd830822247389; fk_num=NGAW; PHPSESSID=sn5pm6cat3r0c1o341c8527b4h; __51cke__=; notice=0; dx_current_page=https%3A//www.ritub.me/17151.html; __tins__20101741=%7B%22sid%22%3A%201657081303014%2C%20%22vd%22%3A%203%2C%20%22expires%22%3A%201657083150327%7D; __51laig__=15'
                ]
            ])->find('.entry-categories')
                ->find('a')
                ->map(function ($item) {
                    return $item->text();
                })
                ->all();
            print_r($data);
            die;
        }
        //$data = self::getIndexData();
        //print_r($data);die;
        //$all = self::getDataDetail($data);
        $string = file_get_contents('a.json');
        $all = json_decode($string,true);
        print_r($all);die;

        $res = collect($all)->map(function ($item){
            $url_index = $item['link'];
            $item['tag'] = QueryList::getInstance()->get($url_index, null, [
                'headers' => [
                    'Referer'    => 'https://www.ritub.me/zylist',
                    'User-Agent' => 'testing/1.0',
                    'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',

                    // 携带cookie
                    'Cookie'     => 'wordpress_logged_in_b85ef2017524ee2b9106e4373b5be3de=yunfeiyang%7C1658227781%7CSP1Wge3RraPgR97vvKFDdEAHyJQwaRq3kJtuxF0J17U%7C93628056c00716ace6d1484b17ef75967caa58dc7cc881f411fd830822247389; fk_num=NGAW; PHPSESSID=sn5pm6cat3r0c1o341c8527b4h; __51cke__=; notice=0; dx_current_page=https%3A//www.ritub.me/17151.html; __tins__20101741=%7B%22sid%22%3A%201657081303014%2C%20%22vd%22%3A%203%2C%20%22expires%22%3A%201657083150327%7D; __51laig__=15'
                ]
            ])->find('.entry-categories')
                ->find('a')
                ->map(function ($item) {
                    return $item->text();
                })
                ->all();

            print_r($item);
            return $item;

        })->values();

        file_put_contents('re.json',json_encode($res));
        print_r($res);

    }

    static function getIndexData()
    {
        $data = cached('index-data')->fetchJson(function () {
            $url_index = 'https://www.ritub.me/zylist';
            $data = QueryList::getInstance()->get($url_index)->find('ul.zylist')->find('li')->map(function ($item) {

                $href = $item->find('a')->href;
                return [
                    'id'    => pathinfo($href, PATHINFO_FILENAME),
                    'link'  => $href,
                    'title' => $item->find('a')->text(),

                ];
            })->toArray();
            return $data;
        }, 9640000);


        print_r($data);
        return $data;
    }

    static function getDataDetail($data)
    {
        return collect($data)->map(function ($item) {

            //$url_index = 'https://www.ritub.me/17151.html';
            $url_index = $item['link'];
            $item['detail'] = QueryList::getInstance()->get($url_index, null, [
                'headers' => [
                    'Referer'    => 'https://www.ritub.me/zylist',
                    'User-Agent' => 'testing/1.0',
                    'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',

                    // 携带cookie
                    'Cookie'     => 'wordpress_logged_in_b85ef2017524ee2b9106e4373b5be3de=yunfeiyang%7C1658227781%7CSP1Wge3RraPgR97vvKFDdEAHyJQwaRq3kJtuxF0J17U%7C93628056c00716ace6d1484b17ef75967caa58dc7cc881f411fd830822247389; fk_num=NGAW; PHPSESSID=sn5pm6cat3r0c1o341c8527b4h; __51cke__=; notice=0; dx_current_page=https%3A//www.ritub.me/17151.html; __tins__20101741=%7B%22sid%22%3A%201657081303014%2C%20%22vd%22%3A%203%2C%20%22expires%22%3A%201657083150327%7D; __51laig__=15'
                ]
            ])->find('.baidupan')
                ->find('table')
                ->find('tr:eq(1)')
                ->find('td')
                ->map(function ($item) {
                    if ($href = $item->find('a')->href) {
                        return $href;
                    }
                    return $item->text();
                })
                ->all();
            print_r($item);
            //die;
            return $item;

        })->values();
    }


}