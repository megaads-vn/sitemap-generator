<?php

namespace Megaads\Generatesitemap\Services;

class SitemapConfigurator
{
    protected $xmlString = "";
    protected $defaultUrlSet = '<url><loc>#url</loc><priority>#piority</priority>#lastMode#changefreq#imageString</url>';
    protected $defaultLastMode = '<lastmod>#lastMode</lastmod>';
    protected $defaultChangefreq = '<changefreq>#changefreq</changefreq>';
    protected $imageString = '<image:image><image:loc>#imagePath</image:loc><image:title><![CDATA[#imageTitte]]></image:title><image:caption><![CDATA[#imageCaption]]></image:caption></image:image>';
    protected $arrayUrlSet = [];
    protected $publicPath = null;

    /***
     * XmlFileUtil constructor.
     */
    public function __construct()
    {
        $this->publicPath = base_path() . '/public/';
        $this->addXmlHead();
    }

    /***
     * @param $url
     * @param $piority
     * @param $lastMode
     * @param $changefreq
     */
    public function add($url, $piority, $lastMode='', $changefreq='', $imageGallerys = [])
    {
        $stringUrlSet = $this->defaultUrlSet;
        $stringUrlSet = str_replace('#url', $url, $stringUrlSet);
        $stringUrlSet = str_replace('#piority', $piority, $stringUrlSet);
        if ($lastMode != '') {
            $strLastMode = str_replace('#lastMode', $lastMode, $this->defaultLastMode);
            $stringUrlSet = str_replace('#lastMode', $strLastMode, $stringUrlSet);
        } else {
            $stringUrlSet = str_replace('#lastMode', '', $stringUrlSet);
        }

        if ($changefreq != '') {
            $strChangefreq = str_replace('#changefreq', $changefreq, $this->defaultChangefreq);
            $stringUrlSet = str_replace('#changefreq', $strChangefreq, $stringUrlSet);
        } else {
            $stringUrlSet = str_replace('#changefreq', '', $stringUrlSet);
        }

        $stringUrlSet = str_replace('#changefreq', $changefreq, $stringUrlSet);

        if (!empty($imageGallerys)) {
            $str = '';
            foreach($imageGallerys as $image) {
                $imageString = $this->imageString;
                foreach(array_keys($image) as $value) {
                    $imageString = str_replace($value, $image[$value], $imageString);
                }
                $str .= $imageString;
            }
            $stringUrlSet = str_replace('#imageString', $str, $stringUrlSet);
        } else {
            $stringUrlSet = str_replace('#imageString', '', $stringUrlSet);
        }

        array_push($this->arrayUrlSet, $stringUrlSet);
    }


    /***
     * @param $type
     * @param $name
     */
    public function store($type, $name, $isMultiple = false, $locale = '')
    {
        $xmlFile = $this->publicPath . $name . '.' . $type;
        if ($isMultiple) {
            $localeFolder = $this->publicPath . 'sitemap/' . $locale;
            if (!file_exists($localeFolder)) {
                mkdir($localeFolder, 0777, true);
            }

            $xmlFile = $localeFolder . '/' . $name . '.' . $type;
        }

        $openFile = fopen($xmlFile, "w");
        $stringContents = '';

        foreach ($this->arrayUrlSet as $item) {
            $stringContents .= $item;
        }

        $this->xmlString = str_replace('#contents', $stringContents, $this->xmlString);

        fwrite($openFile, $this->xmlString);
        fclose($openFile);
    }

    public function mergeSitemap($locales, $mergeFile = 'sitemap')
    {
        $baseUrl = url('/');
        $publicPath = $this->publicPath;
        $this->addMergeXmlHead();
        $this->arrayUrlSet = [];
        $mergeSitemapString = '<sitemap><loc>#loc_content</loc><lastmod>#lastmod</lastmod></sitemap>';
        $lastMode = date('Y-m-d');
        foreach($locales as $filePath) {
            if (file_exists($publicPath . 'sitemap/' . $filePath)) {
                $mergeXml = $mergeSitemapString;
                $mergeXml = str_replace('#loc_content', $baseUrl . '/sitemap/' . $filePath, $mergeXml);
                $mergeXml = str_replace('#lastmod', $lastMode, $mergeXml);
                array_push($this->arrayUrlSet, $mergeXml);
            }
        }
        $this->store('xml', $mergeFile);
    }


    public function resetUrlSet() {
        $this->arrayUrlSet = [];
    }

    public function resetXmlString() {
        $this->addXmlHead();
    }

    /***
     * @return string
     */
    public function hello() {
        return "HELLO WORLD";
    }


    /***
     *
     */
    protected function addXmlHead() {
        $this->xmlString = '<?xml version="1.0" encoding="UTF-8"?>';
        $this->xmlString .= '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd http://www.google.com/schemas/sitemap-image/1.1 http://www.google.com/schemas/sitemap-image/1.1/sitemap-image.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $this->xmlString .= '#contents';
        $this->xmlString .= '</urlset>';
    }

    /***
     *
     */
    private function addMergeXmlHead() {
        $this->xmlString = '<?xml version="1.0" encoding="utf-8" ?>';
        $this->xmlString .= '<sitemapindex xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd">';
        $this->xmlString .= '#contents';
        $this->xmlString .= '</sitemapindex>';
    }
}