<?php


namespace App\console;


use DB;
use QL\QueryList;
use service\GameService;

class GrabSexConsole extends AbstractConsole
{

    public $name = 'grab-sex';

    public $description = '抓取 https://sexinsex.net/bbs/forum-369-1.html';


    public function process($argc, $argv)
    {
        if (1) {
            $url_index = 'https://sexinsex.net/bbs/forum-369-1.html';

            $data = QueryList::getInstance()->get($url_index, null, [
                'headers' => [
                    'Referer'    => 'https://sexinsex.net/bbs/forum-369-1.html',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.0.0 Safari/537.36',
                    'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',

                    // 携带cookie
                    'Cookie'     => 'cdb3_sid=rbV4cJ; cdb3_cookietime=2592000; cdb3_auth=usqF5oBnUsAsR2%2BDBFUZPeJ9cTBhmgBuWRA95E%2BMtTLXk2jgPQDeumgVeIlUMoprLa4; __utmz=1.1658550998.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none); cdb3_smile=1D1; __utmc=1; __utma=1.1834320808.1658550998.1658894460.1658904455.3; cdb3_oldtopics=D9062953D8187356D; cdb3_fid369=1658899372; __utmb=1.5.10.1658904455'
                ]
            ])->find('table:eq(6)')
                ->find('tbody')->map(function ($item){
                    $th =  $item->find('th');
                    if($th){
                        return [
                            'id'=>$th->find('span:first')->id,
                            'title'=>$th->find('span:first')->find('a')->text(),
                            'url'=>"https://sexinsex.net/bbs/".$th->find('span:first')->find('a')->href,
                            //https://sexinsex.net/bbs/thread-8766723-1-1.html
                        ];
                    }
                    return null;
                })->filter()->all();
            errLog(var_export($data,1));
            print_r($data);
            die;
        }
        return ;
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