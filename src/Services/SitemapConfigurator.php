<?php

namespace Megaads\Generatesitemap\Services;

class SitemapConfigurator
{
    protected $xmlString;
    protected $defaultUrlSet = '<url><loc>#url</loc><priority>#piority</priority>#lastMode#changefreq</url>';
    protected $defaultLastMode = '<lastmod>#lastMode</lastmod>';
    protected $defaultChangefreq = '<changefreq>#changefreq</changefreq>';
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
    public function add($url, $piority, $lastMode='', $changefreq='')
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
        array_push($this->arrayUrlSet, $stringUrlSet);
    }


    /***
     * @param $type
     * @param $name
     */
    public function store($type, $name) {
        $xmlFile = $this->publicPath . $name . '.' . $type;
        // if (file_exists($xmlFile)) unlink($xmlFile);

        $openFile = fopen($xmlFile, "w");

        $stringContents = '';
        foreach ($this->arrayUrlSet as $item) {
            $stringContents .= $item;
        }

        $this->xmlString = str_replace('#contents', $stringContents, $this->xmlString);

        fwrite($openFile, $this->xmlString);
        fclose($openFile);
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
        $this->xmlString .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';
        $this->xmlString .= '#contents';
        $this->xmlString .= '</urlset>';
    }
}