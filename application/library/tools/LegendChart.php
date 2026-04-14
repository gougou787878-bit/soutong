<?php


namespace tools;
class LegendChart implements \JsonSerializable
{

    protected $seriesData = [];
    protected $title;

    public function __construct($title)
    {
        $this->title = $title;
    }

    public function addLine($legend, $category, $series)
    {
        $category = (string)$category;
        if (!isset($this->seriesData[$legend])) {
            $this->seriesData[$legend] = [
                'data' => [],
                'name' => $legend,
                'smooth' => true,
                'type' => 'line',
            ];
        }
        $this->seriesData[$legend]['data'][$category] = $series;
    }


    public function jsonSerialize(): array
    {
        $legendData = [];
        $category = [];
        $seriesData = [];
        foreach ($this->seriesData as $key => $series) {
            $legendData[] = $key;
            $data = $series;
            $data['data'] = [];
            foreach ($series['data'] as $k => $v) {
                $category[$k] = $k;
                $data['data'][] = $v;
            }
            $seriesData[] = $data;
        }
        return [
            'legendData' => $legendData,
            'category' => array_values($category),
            'seriesData' => $seriesData,
            'title' => $this->title
        ];
    }
}