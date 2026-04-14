<?php

class SeoController extends PcBaseController
{
    public function robotsAction()
    {
        @header('Content-Type: text/plain; charset=utf-8');
        $txt = <<<EOE
Sitemap: https://%s/sitemap.xml

User-Agent: *
Disallow: /admin*
EOE;
        $host = $_GET['d'] ?? $_SERVER['HTTP_HOST'];
        exit($this->getResponse()->setBody(sprintf($txt, $host)));
    }

    public function sitemapAction()
    {
        @header('Content-Type: text/xml; charset=utf-8');
        $host = $_GET['d'] ?? $_SERVER['HTTP_HOST'];
        $p = (int)($_GET['p'] ?? 0);
        if ($p == 0) {
            $tpl = '<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">%s</sitemapindex>';
            $ct = PcPostModel::select(['id', 'created_at'])
                ->where('status', PcPostModel::STATUS_PASS)
                ->where('is_finished', PcPostModel::FINISH_OK)
                ->count();
            $max = ceil($ct / 1000);//50000
            $node = '';
            for ($k = 1; $k <= $max; $k++) {
                $node .= sprintf('<sitemap><loc>https://%s/sitemap%s.xml</loc></sitemap>', $host, $k);
            }
            exit(sprintf($tpl, $node));
        }

        $xmlHref = "https://" . $host . '/sitemap.xsl';
        $tpl = $this->makeHeader($xmlHref);
        // 第一页放权重较高的首页
        if ($p == 1) {
            $tpl .= $this->makeMiddle("https://" . $host, date('Y-m-d'), '0.8');
        }
        PcPostModel::select(['id', 'created_at'])
            ->where('status', PcPostModel::STATUS_PASS)
            ->where('is_finished', PcPostModel::FINISH_OK)
            ->forPage($p, 1000)
            ->orderByDesc('id')
            ->get()
            ->map(function ($item) use ($p, $host, &$tpl) {
                $url = "https://" . $host . "/cdetails?ptype=2&amp;pid={$item->id}&amp;nav_active=1";
                $date = date('Y-m-d', strtotime($item->created_at));
                $tpl .= $this->makeMiddle($url, $date, '0.5');
            });
        $tpl .= '</urlset>';
        exit($tpl);
    }

    protected function makeHeader($xmlHref): string
    {
        return <<<STR
<?xml version="1.0" encoding="utf-8"?>
<?xml-stylesheet type='text/xsl' href='$xmlHref'?>
<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd"
xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
STR;
    }

    protected function makeMiddle($url, $at, $priority): string
    {
        return sprintf("<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%s</priority></url>", $url, $at, 'always', $priority);
    }
}